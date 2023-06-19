<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprig\controllers;

use Craft;
use craft\base\Element;
use craft\elements\User;
use craft\events\ModelEvent;
use craft\helpers\ArrayHelper;
use craft\web\Controller;
use craft\web\UrlManager;
use craft\web\UrlRule;
use putyourlightson\sprig\Sprig;
use yii\base\Event;
use yii\web\Response;

class ComponentsController extends Controller
{
    /**
     * @inheritdoc
     */
    protected int|bool|array $allowAnonymous = true;

    /**
     * Renders a component.
     */
    public function actionRender(): Response
    {
        $config = Sprig::$core->requests->getValidatedConfig();

        Craft::$app->getSites()->setCurrentSite($config->siteId);
        $variables = ArrayHelper::merge($config->variables, Sprig::$core->requests->getVariables());
        $content = '';

        if ($config->component) {
            $componentObject = Sprig::$core->components->createObject($config->component, $variables);

            if ($componentObject) {
                if ($config->action && method_exists($componentObject, $config->action)) {
                    call_user_func([$componentObject, $config->action]);
                }

                $content = $componentObject->render();
            }
        } else {
            if ($config->action) {
                $actionVariables = $this->_runActionInternal($config->action);
                $variables = ArrayHelper::merge($variables, $actionVariables);
            }

            $content = Craft::$app->getView()->renderTemplate($config->template, $variables);
        }

        $response = Craft::$app->getResponse();
        $response->statusCode = 200;
        $response->format = Response::FORMAT_HTML;
        $response->data = Sprig::$core->components->parse($content);

        return $response;
    }

    /**
     * Runs an action and returns the variables from the response
     */
    private function _runActionInternal(string $action): array
    {
        if ($action == 'users/set-password') {
            return $this->_runActionWithJsonRequest($action);
        }

        if ($action == 'users/save-user') {
            $this->_registerSaveCurrentUserEvent();
        }

        // Add a redirect to the body params, so we can extract the ID on success
        $redirectPrefix = 'https://';
        Craft::$app->getRequest()->setBodyParams(ArrayHelper::merge(
            Craft::$app->getRequest()->getBodyParams(),
            ['redirect' => Craft::$app->getSecurity()->hashData($redirectPrefix . '{id}')]
        ));

        $actionResponse = Craft::$app->runAction($action);

        // Extract the variables from the route params which are generally set when there are errors
        /** @var UrlManager $urlManager */
        $urlManager = Craft::$app->getUrlManager();
        $variables = $urlManager->getRouteParams() ?: [];

        /**
         * Merge and unset any variable called `variables`
         * https://github.com/putyourlightson/craft-sprig/issues/94#issuecomment-771489394
         * @see UrlRule::parseRequest()
         */
        if (isset($variables['variables'])) {
            $variables = ArrayHelper::merge($variables, $variables['variables']);
            unset($variables['variables']);
        }

        // Override the `currentUser` global variable with a fresh version, in case it was just updated
        // https://github.com/putyourlightson/craft-sprig/issues/81#issuecomment-758619306
        $variables['currentUser'] = Craft::$app->getUser()->getIdentity();

        $success = $actionResponse !== null;
        $variables['success'] = $success;

        if ($success) {
            $response = Craft::$app->getResponse();

            $variables['id'] = str_replace($redirectPrefix, '', $response->getHeaders()->get('location'));

            // Remove the redirect header
            $response->getHeaders()->remove('location');
        }

        // Set flash messages variable and delete them
        $variables['flashes'] = Craft::$app->getSession()->getAllFlashes(true);

        return $variables;
    }

    /**
     * Runs the action with a JSON request for special case handling.
     * https://github.com/putyourlightson/craft-sprig/issues/300
     */
    private function _runActionWithJsonRequest(string $action): array
    {
        Craft::$app->getRequest()->getHeaders()->set('Accept', 'application/json');

        // The set password action requires

        $actionResponse = Craft::$app->runAction($action);

        $variables = [
            'success' => $actionResponse->getIsOk(),
            'message' => $actionResponse->data['message'],
        ];

        if (!$actionResponse->getIsOk()) {
            unset($actionResponse->data['message']);
            $variables['errors'] = $actionResponse->data;
        }

        return $variables;
    }

    /**
     * Registers an event when saving the current user
     */
    private function _registerSaveCurrentUserEvent(): void
    {
        $currentUserId = Craft::$app->getUser()->getId();
        $userId = Craft::$app->getRequest()->getBodyParam('userId');

        if (!$currentUserId || $currentUserId != $userId) {
            return;
        }

        Event::on(User::class, Element::EVENT_AFTER_SAVE, function(ModelEvent $event) {
            /** @var User $user */
            $user = $event->sender;

            // Update the user identity and regenerate the CSRF token in case the password was changed
            // https://github.com/putyourlightson/craft-sprig/issues/136
            Craft::$app->getUser()->setIdentity($user);
            Craft::$app->getRequest()->regenCsrfToken();
        });
    }
}
