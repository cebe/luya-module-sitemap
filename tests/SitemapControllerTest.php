<?php

namespace cebe\luya\sitemap\tests;

use luya\cms\models\Config;
use luya\testsuite\cases\WebApplicationTestCase;
use cebe\luya\sitemap\Module;
use cebe\luya\sitemap\controllers\SitemapController;
use luya\testsuite\fixtures\ActiveRecordFixture;
use luya\cms\models\NavItem;
use luya\cms\models\Nav;
use luya\admin\models\Lang;
use yii\helpers\FileHelper;

class SitemapControllerTest extends WebApplicationTestCase
{
    public function getConfigArray()
    {
        return [
           'id' => 'mytestapp',
           'basePath' => dirname(__DIR__),
           'aliases' => [
               'runtime' => dirname(__DIR__) . '/tests/runtime',
           ],
           'modules' => [
               'cms' => 'luya\cms\frontend\Module',
           ],
           'components' => [
                'db' => [
                    'class' => 'yii\db\Connection',
                    'dsn' => 'sqlite::memory:',
                ],
                'request' => [
                    'hostInfo' => 'https://luya.io',
                ],
                'adminLanguage' => [
                    'class' => \luya\admin\components\AdminLanguage::class,
                ],
            ]
        ];
    }

    public function boolProvider()
    {
        return [
            [true],
            [false],
        ];
    }

    public function beforeSetup()
    {
        parent::beforeSetup();
        // clean up application runtime directory, do not use cached version of sitemap.xml
        $runtimePath = dirname(__DIR__) . '/tests/runtime';
        FileHelper::removeDirectory($runtimePath);
        FileHelper::createDirectory($runtimePath);
    }

    /**
     * @dataProvider boolProvider
     */
    public function testIgnoreHiddenModuleProperty($withHidden)
    {
        $module = new Module('sitemap');
        $module->module = $this->app;
        $module->withHidden = $withHidden;

        $this->prepareBasicTableStructureAndData();

        $ctrl = new SitemapController('sitemap', $module);
        $response = $ctrl->actionIndex();
        list($handle, $begin, $end) = $response->stream;

        fseek($handle, $begin);
        $content = stream_get_contents($handle);

        $this->assertContainsTrimmed('<loc>https://luya.io</loc>', $content);
        $this->assertContainsTrimmed('<loc>https://luya.io/foo</loc>', $content);

        $this->assertContainsTrimmed('<loc>https://luya.io/foo-3</loc>', $content);
        $this->assertContainsTrimmed('<loc>https://luya.io/foo-3/foo-4-child</loc>', $content);
        $this->assertContainsTrimmed('<loc>https://luya.io/foo-3/foo-4-child/foo-5-child-child</loc>', $content);

        // check correct language on nested pages
        $this->assertContainsTrimmed('<loc>https://luya.io/foo-3-de</loc>', $content);
        $this->assertContainsTrimmed('<loc>https://luya.io/foo-3-de/foo-4-child-de</loc>', $content);
        $this->assertNotContains('<loc>https://luya.io/foo-3-de/foo-4-child</loc>', $content);
        $this->assertNotContains('<loc>https://luya.io/foo-3/foo-4-child-de</loc>', $content);

        if ($withHidden) {
            // $module->withHidden = true; = 2 Pages in index
            $this->assertContainsTrimmed('<loc>https://luya.io/foo-hidden</loc>', $content);
        } else {
            // $module->withHidden = false; = 1 Page in index
            $this->assertNotContains('<loc>https://luya.io/foo-hidden</loc>', $content);
        }

        $this->assertNotContains('<loc>https://luya.io/not-to-show-404</loc>', $content);
    }

    public function testEncodedUrls()
    {
        $ctrl = new SitemapController('sitemap', $this->app);
        $this->assertSame('https://luya.io/nothing/to/encode', $this->invokeMethod($ctrl, 'encodeUrl', ['https://luya.io/nothing/to/encode']));
        $this->assertSame('https://luya.io/crazy%20space', $this->invokeMethod($ctrl, 'encodeUrl', ['https://luya.io/crazy space']));
        $this->assertSame('https://luya.io/?fancy=1&params=2&amp;indeed=3', $this->invokeMethod($ctrl, 'encodeUrl', ['https://luya.io/?fancy=1&params=2&amp;indeed=3']));
        $this->assertSame('https://luya.io/%C3%A4%C3%B6%C3%BC', $this->invokeMethod($ctrl, 'encodeUrl', ['https://luya.io/äöü']));
        $this->assertSame('https://japan.com/jp/%E6%96%B0', $this->invokeMethod($ctrl, 'encodeUrl', ['https://japan.com/jp/新']));
    }

    private function prepareBasicTableStructureAndData()
    {
        $navItemFixture = (new ActiveRecordFixture([
            'modelClass' => NavItem::class,
            'fixtureData' => [
                'model1' => [
                    'id' => 1,
                    'nav_id' => 1,
                    'lang_id' => 1,
                    'timestamp_create' => time(),
                    'timestamp_update' => time(),
                    'alias' => 'foo',
                    'title' => 'Bar',
                ],
                'model2' => [
                    'id' => 2,
                    'nav_id' => 2,
                    'lang_id' => 1,
                    'timestamp_create' => time(),
                    'timestamp_update' => time(),
                    'alias' => 'foo-hidden',
                    'title' => 'Bar Hidden',
                ],
                'model3' => [
                    'id' => 3,
                    'nav_id' => 3,
                    'lang_id' => 1,
                    'timestamp_create' => time(),
                    'timestamp_update' => time(),
                    'alias' => 'foo-3',
                    'title' => 'Bar 3 title',
                ],
                'model4' => [
                    'id' => 4,
                    'nav_id' => 4,
                    'lang_id' => 1,
                    'timestamp_create' => time(),
                    'timestamp_update' => time(),
                    'alias' => 'foo-4-child',
                    'title' => 'Bar 4 child-title',
                ],
                'model5' => [
                    'id' => 5,
                    'nav_id' => 5,
                    'lang_id' => 1,
                    'timestamp_create' => time(),
                    'timestamp_update' => time(),
                    'alias' => 'foo-5-child-child',
                    'title' => 'Bar 5 child child title',
                ],
                'model6' => [
                    'id' => 6,
                    'nav_id' => 6,
                    'lang_id' => 1,
                    'timestamp_create' => time(),
                    'timestamp_update' => time(),
                    'alias' => 'not-to-show-404',
                    'title' => 'Not To Show - 404 - in sitemap',
                ],
                'model3de' => [
                    'id' => 7,
                    'nav_id' => 3,
                    'lang_id' => 2,
                    'timestamp_create' => time(),
                    'timestamp_update' => time(),
                    'alias' => 'foo-3-de',
                    'title' => 'Bar 3 title de',
                ],
                'model4de' => [
                    'id' => 8,
                    'nav_id' => 4,
                    'lang_id' => 2,
                    'timestamp_create' => time(),
                    'timestamp_update' => time(),
                    'alias' => 'foo-4-child-de',
                    'title' => 'Bar 4 child-title de',
                ],
            ]
        ]));

        $navFixture = (new ActiveRecordFixture([
            'modelClass' => Nav::class,
            'fixtureData' => [
                'model1' => [
                    'id' => 1,
                    'nav_container_id' => 1,
                    'parent_nav_id' => 0,
                    'is_deleted' => 0,
                    'is_hidden' => 0,
                    'is_offline' => 0,
                    'is_draft' => 0,
                ],
                'model2' => [
                    'id' => 2,
                    'nav_container_id' => 1,
                    'parent_nav_id' => 0,
                    'is_deleted' => 0,
                    'is_hidden' => 1,
                    'is_offline' => 0,
                    'is_draft' => 0,
                ],
                'model3' => [
                    'id' => 3,
                    'nav_container_id' => 1,
                    'parent_nav_id' => 0,
                    'is_deleted' => 0,
                    'is_hidden' => 0,
                    'is_offline' => 0,
                    'is_draft' => 0,
                ],
                'model4' => [
                    'id' => 4,
                    'nav_container_id' => 1,
                    'parent_nav_id' => 3,
                    'is_deleted' => 0,
                    'is_hidden' => 0,
                    'is_offline' => 0,
                    'is_draft' => 0,
                ],
                'model5' => [
                    'id' => 5,
                    'nav_container_id' => 1,
                    'parent_nav_id' => 4,
                    'is_deleted' => 0,
                    'is_hidden' => 0,
                    'is_offline' => 0,
                    'is_draft' => 0,
                ],
                'model6' => [
                    'id' => 6,
                    'nav_container_id' => 1,
                    'parent_nav_id' => 0,
                    'is_deleted' => 0,
                    'is_hidden' => 0,
                    'is_offline' => 0,
                    'is_draft' => 0,
                ],
            ]
        ]));

        $langFixture = (new ActiveRecordFixture([
            'modelClass' => Lang::class,
            'fixtureData' => [
                'model1' => [
                    'id' => 1,
                    'name' => 'English',
                    'short_code' => 'en',
                    'is_default' => 1,
                    'is_deleted' => 0,
                ],
                'model2' => [
                    'id' => 2,
                    'name' => 'Deutsch',
                    'short_code' => 'de',
                    'is_default' => 0,
                    'is_deleted' => 0,
                ]
            ]
        ]));

        $configFixture = (new ActiveRecordFixture([
            'modelClass' => Config::class,
            'fixtureData' => [
                'model1' => [
                    'name' => 'httpExceptionNavId',
                    'value' => 6,
                ]
            ]
        ]));
    }
}
