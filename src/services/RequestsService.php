<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprig\services;

use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\helpers\Json;
use putyourlightson\sprig\models\ConfigModel;
use yii\web\BadRequestHttpException;

/**
 * @property-read ConfigModel $validatedConfig
 * @property-read array $variables
 */
class RequestsService extends Component
{
    /**
     * @const string[]
     */
    public const DISALLOWED_PREFIXES = ['_', 'sprig:'];

    /**
     * Returns allowed request variables.
     */
    public function getVariables(): array
    {
        $variables = [];
        $request = Craft::$app->getRequest();

        $requestParams = array_merge(
            $request->getQueryParams(),
            $request->getBodyParams(),
        );

        foreach ($requestParams as $name => $value) {
            if ($this->_getIsVariableAllowed($name)) {
                $variables[$name] = $value;
            }
        }

        return $variables;
    }

    /**
     * Returns a validated config request parameter.
     */
    public function getValidatedConfig(): ConfigModel
    {
        $value = Craft::$app->getRequest()->getParam('sprig:config');
        $value = Craft::$app->getSecurity()->validateData($value);

        if ($value === false) {
            throw new BadRequestHttpException('Invalid Sprig config.');
        }

        $values = Json::decode($value);
        $config = new ConfigModel();
        $config->setAttributes($values, false);

        foreach ($config->variables as $name => $value) {
            $config->variables[$name] = $this->_normalizeValue($value);
        }

        return $config;
    }

    /**
     * Returns whether a variable name is allowed.
     */
    private function _getIsVariableAllowed(string $name): bool
    {
        if ($name == Craft::$app->getConfig()->getGeneral()->getPageTrigger()) {
            return false;
        }

        foreach (self::DISALLOWED_PREFIXES as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return false;
            }
        }

        return true;
    }

    private function _normalizeValue(string $value): mixed
    {
        $value = Json::decodeIfJson($value);

        if (is_string($value)) {
            preg_match('/^element:(.*?):([0-9]*?):(.*)$/', $value, $matches);
            if (!empty($matches)) {
                $elementType = $matches[1];
                if (is_subclass_of($elementType, ElementInterface::class)) {
                    $elementId = $matches[2];
                    $with = explode(',', $matches[3]);
                    $value = $elementType::find()
                        ->id($elementId)
                        ->with($with)
                        ->one();
                }
            }
        }

        return $value;
    }
}
