<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprigcoretests\unit\services;

use Codeception\Test\Unit;
use Craft;
use craft\elements\Entry;
use putyourlightson\sprig\services\ComponentsService;
use putyourlightson\sprig\Sprig;
use Twig\Markup;
use UnitTester;
use yii\base\Application;
use yii\base\ExitException;
use yii\base\Model;
use yii\web\BadRequestHttpException;
use yii\web\Request;

/**
 * @author    PutYourLightsOn
 * @package   Sprig
 * @since     1.0.0
 */

class ComponentsTest extends Unit
{
    protected UnitTester $tester;

    protected function _before(): void
    {
        parent::_before();

        // Disable cookie validation
        Craft::$app->getRequest()->enableCookieValidation = false;

        // Bootstrap the module
        Sprig::bootstrap();

        Craft::$app->getView()->setTemplatesPath(Craft::getAlias('@templates'));
    }


    public function testHtmxScriptExistsForDev()
    {
        Craft::$app->getConfig()->env = 'dev';

        $this->_testScriptExistsLocally();
    }

    public function testHtmxScriptExistsForProduction()
    {
        Craft::$app->getConfig()->env = 'production';

        $this->_testScriptExistsLocally();
    }

    public function testCreate()
    {
        $markup = Sprig::$core->components->create(
            '_component',
            ['number' => 15],
            ['id' => 'abc', 's-trigger' => 'load', 's-vars' => 'limit:1', 's-push-url' => 'new-url']
        );
        $html = (string)$markup;

        $this->assertStringContainsString('id="abc"', $html);
        $this->assertStringContainsString('data-hx-include="this"', $html);
        $this->assertStringContainsString('data-hx-trigger="load"', $html);
        $this->assertStringContainsString('limit:1', $html);
        $this->assertStringContainsString('data-hx-push-url="new-url"', $html);
        $this->assertStringContainsString('xyz 15', $html);
    }

    public function testCreateEmptyComponent()
    {
        $markup = Sprig::$core->components->create('_empty');
        $html = (string)$markup;

        $this->assertStringContainsString('data-hx-get=', $html);
    }

    public function testCreateNoComponent()
    {
        $this->expectException(BadRequestHttpException::class);

        Sprig::$core->components->create('_no-component');
    }

    public function testCreateRefreshOnLoadComponent()
    {
        $selector = ComponentsService::SPRIG_CSS_CLASS;
        $object = Sprig::$core->components->createObject('RefreshOnLoad');

        Craft::$app->getRequest()->getHeaders()->set('HX-Request', 'true');
        $html = $object->render();
        $this->assertStringContainsString("htmx.findAll('.$selector'))", $html);
    }

    public function testCreateRefreshOnLoadComponentWithSelector()
    {
        $selector = '.test-class';
        $object = Sprig::$core->components->createObject(
            'RefreshOnLoad',
            ['selector' => $selector]
        );

        Craft::$app->getRequest()->getHeaders()->set('HX-Request', 'true');
        $html = $object->render();
        $this->assertStringContainsString("htmx.findAll('$selector'))", $html);
    }

    public function testCreateObjectFromComponent()
    {
        // Require the class since it is not autoloaded.
        require CRAFT_TESTS_PATH . '/_craft/sprig/components/TestComponent.php';

        $object = Sprig::$core->components->createObject(
            'TestComponent',
            ['number' => 15]
        );

        $html = $object->render();

        $this->assertStringContainsString('xyz 15', $html);
    }

    public function testCreateObjectFromNoComponent()
    {
        $object = Sprig::$core->components->createObject('NoComponent');

        $this->assertNull($object);
    }

    public function testCreateVariableEntry()
    {
        $this->_testCreateVariable(['number' => '', 'entry' => new Entry()]);
    }

    public function testCreateInvalidVariableModel()
    {
        $this->_testCreateInvalidVariable(['number' => '', 'model' => new Model()]);
    }

    public function testCreateInvalidVariableObject()
    {
        $this->_testCreateInvalidVariable(['number' => '', 'model' => (object)[]]);
    }

    public function testCreateInvalidVariableArray()
    {
        $this->_testCreateInvalidVariable(['number' => '', 'array' => [new Entry()]]);
    }

    public function testGetParsedTagAttributes()
    {
        $html = '<div sprig s-method="post" s-action="a/b/c" s-vals=\'{"limit":1}\'></div>';
        $html = Sprig::$core->components->parse($html);

        $this->assertStringContainsString('data-hx-post=', $html);
        $this->assertStringContainsString('&amp;sprig:action=', $html);
        $this->assertStringContainsString('data-hx-headers="{&quot;' . Request::CSRF_HEADER . '&quot;', $html);
        $this->assertStringContainsString('data-hx-vals="{&quot;limit&quot;:1}', $html);
        $this->assertStringContainsString('data-sprig-parsed', $html);
    }

    public function testGetParsedTagAttributesWithData()
    {
        $html = '<div data-sprig></div>';
        $html = Sprig::$core->components->parse($html);

        $this->assertStringContainsString('data-hx-get=', $html);
    }

    public function testGetParsedTagAttributesWithSpaces()
    {
        $html = '<div s-target = "#id" ></div>';
        $html = Sprig::$core->components->parse($html);
        $this->assertStringContainsString('data-hx-target="#id"', $html);

        $html = '<div s-target = \'#id\' ></div>';
        $html = Sprig::$core->components->parse($html);
        $this->assertStringContainsString('data-hx-target="#id"', $html);

        $html = '<div s-target = #id ></div>';
        $html = Sprig::$core->components->parse($html);
        $this->assertStringContainsString('data-hx-target="#id"', $html);

        $html = '<div s-target = #id' . PHP_EOL . '></div>';
        $html = Sprig::$core->components->parse($html);
        $this->assertStringContainsString('data-hx-target="#id"', $html);
    }

    public function testGetParsedTagAttributesWithTabs()
    {
        $html = '<div	s-target="#id"></div>';
        $html = Sprig::$core->components->parse($html);
        $this->assertStringContainsString('div s-target="#id" data-hx-target="#id"', $html);
    }

    public function testGetParsedTagAttributesWithLineBreaks()
    {
        $html = '<div sprig class="a' . PHP_EOL . 'b"></div>';
        $html = Sprig::$core->components->parse($html);
        $this->assertStringContainsString('data-hx-get=', $html);
    }

    public function testGetParsedTagAttributesVals()
    {
        $html = '<div s-val:x-y-z="a" s-vals=\'{"limit":1}\'></div>';
        $html = Sprig::$core->components->parse($html);
        $this->assertStringContainsString('data-hx-vals="{&quot;xYZ&quot;:&quot;a&quot;,&quot;limit&quot;:1}"', $html);
    }

    public function testGetParsedTagAttributesValsWithEmpty()
    {
        $html = '<div s-val:x-y-z="" s-vals=\'{"limit":1}\'></div>';
        $html = Sprig::$core->components->parse($html);
        $this->assertStringContainsString('data-hx-vals="{&quot;xYZ&quot;:&quot;&quot;,&quot;limit&quot;:1}"', $html);
    }

    public function testGetParsedTagAttributesValsWithEncoded()
    {
        $html = '<div s-val:x-y-z="a" s-vals=\'{&quot;limit&quot;:1}\'></div>';
        $html = Sprig::$core->components->parse($html);
        $this->assertStringContainsString('data-hx-vals="{&quot;xYZ&quot;:&quot;a&quot;,&quot;limit&quot;:1}"', $html);
    }

    public function testGetParsedTagAttributesValsWithBrackets()
    {
        $html = '<div s-val:fields[x-y-z]="a"></div>';
        $html = Sprig::$core->components->parse($html);
        $this->assertStringContainsString('data-hx-vals="{&quot;fields[xYZ]&quot;:&quot;a&quot;}"', $html);
    }

    public function testGetParsedTagAttributesValsEncodedAndSanitized()
    {
        $html = '<div s-val:x="alert(\'xss\')" s-val:z=\'alert("xss")\' s-vals=\'{"limit":1}\'></div>';
        $html = Sprig::$core->components->parse($html);
        $this->assertStringContainsString('data-hx-vals="{&quot;x&quot;:&quot;alert(\u0027xss\u0027)&quot;,&quot;z&quot;:&quot;alert(\u0022xss\u0022)&quot;,&quot;limit&quot;:1}"', $html);
    }

    public function testGetParsedTagAttributesListen()
    {
        $html = '<div s-listen="#component1, #component2"></div>';
        $html = Sprig::$core->components->parse($html);
        $this->assertStringContainsString('data-hx-trigger="htmx:afterOnLoad from:#component1,htmx:afterOnLoad from:#component2"', $html);
    }

    public function testGetParsedTagAttributesEmpty()
    {
        $html = '';
        $result = Sprig::$core->components->parse($html);
        $this->assertEquals($html, $result);
    }

    public function testGetParsedTagAttributesHtml()
    {
        $html = '<div><p><span><template><h1>Hello</h1></template></span></p></div>';
        $result = Sprig::$core->components->parse($html);
        $this->assertEquals($html, $result);
    }

    public function testGetParsedTagAttributesDuplicateIds()
    {
        $html = '<div id="my-id"><p id="my-id"><span id="my-id"></span></p></div>';
        $result = Sprig::$core->components->parse($html);
        $this->assertEquals($html, $result);
    }

    public function testGetParsedTagAttributesComment()
    {
        $html = '<!-- Comment mentioning sprig -->';
        $result = Sprig::$core->components->parse($html);
        $this->assertEquals($html, $result);
    }

    public function testGetParsedTagAttributesScript()
    {
        $html = '<script>if(i < 1) let sprig=1</script>';
        $result = Sprig::$core->components->parse($html);
        $this->assertEquals($html, $result);
    }

    public function testGetParsedTagAttributesUtfEncoding()
    {
        $placeholder = 'ÆØÅäöü';
        $html = '<div sprig placeholder="' . $placeholder . '"></div>';
        $result = Sprig::$core->components->parse($html);
        $this->assertStringContainsString($placeholder, $result);
    }

    private function _testScriptExistsLocally(): void
    {
        $url = Sprig::$core->components->getScriptUrl();
        preg_match('/cpresources(.*?)\?v=/', $url, $matches);
        $path = Craft::getAlias(Craft::$app->getConfig()->getGeneral()->resourceBasePath) . $matches[1];

        $this->assertFileExists($path);
    }

    private function _testCreateVariable(array $variables): void
    {
        $this->tester->mockCraftMethods('view', ['doesTemplateExist' => true]);
        Craft::$app->getView()->setTemplatesPath(Craft::getAlias('@templates'));
        $markup = Sprig::$core->components->create('_component', $variables);

        $this->assertInstanceOf(Markup::class, $markup);
    }

    private function _testCreateInvalidVariable(array $variables): void
    {
        $this->tester->mockCraftMethods('view', ['doesTemplateExist' => true]);
        Craft::$app->getView()->setTemplatesPath(Craft::getAlias('@templates'));

        /**
         * Yii exits with an exception when `YII_ENV_TEST` is set.
         * @see Application::end()
         */
        $this->expectException(ExitException::class);

        Sprig::$core->components->create('_component', $variables);
    }
}
