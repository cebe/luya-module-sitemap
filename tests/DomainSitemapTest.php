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

class DomainSitemapTest extends WebApplicationTestCase
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
                'composition' => [
                    'hidden' => false,
                    'default' => ['langShortCode' => 'en'],
                    'hostInfoMapping' => [
                        'http://luya.co.uk' => ['langShortCode' => 'en', 'countryShortCode' => 'uk'],
                        'http://luya.de' => ['langShortCode' => 'de', 'countryShortCode' => 'de'],
                        'http://luya.ru' => ['langShortCode' => 'ru', 'countryShortCode' => 'ru'],
                    ],
                ],
            ]
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

    public function testMultipleDomainInSitemap()
    {
        $module = new Module('sitemap');
        $module->module = $this->app;

        $this->prepareBasicTableStructureAndData();

        $ctrl = new SitemapController('sitemap', $module);
        $response = $ctrl->actionIndex();
        list($handle, $begin, $end) = $response->stream;

        fseek($handle, $begin);
        $content = stream_get_contents($handle);

        $this->assertContainsTrimmed('<loc>http://luya.de/de/foo-de</loc>', $content);
        $this->assertContainsTrimmed('<loc>http://luya.co.uk/en/foo-en</loc>', $content);
        $this->assertContainsTrimmed('<loc>http://luya.ru/ru/foo-ru</loc>', $content);
    }

    private function prepareBasicTableStructureAndData()
    {
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
            ]
        ]));

        $navItemFixture = (new ActiveRecordFixture([
            'modelClass' => NavItem::class,
            'fixtureData' => [
                'model1' => [
                    'id' => 1,
                    'nav_id' => 1,
                    'lang_id' => 1,
                    'timestamp_create' => time(),
                    'timestamp_update' => time(),
                    'alias' => 'foo-en',
                    'title' => 'Bar Eng',
                ],
                'model2' => [
                    'id' => 2,
                    'nav_id' => 1,
                    'lang_id' => 2,
                    'timestamp_create' => time(),
                    'timestamp_update' => time(),
                    'alias' => 'foo-de',
                    'title' => 'Bar De',
                ],
                'model3' => [
                    'id' => 3,
                    'nav_id' => 1,
                    'lang_id' => 3,
                    'timestamp_create' => time(),
                    'timestamp_update' => time(),
                    'alias' => 'foo-ru',
                    'title' => 'Bar Ru',
                ],
                'model4' => [
                    'id' => 4,
                    'nav_id' => 3,
                    'lang_id' => 1,
                    'timestamp_create' => time(),
                    'timestamp_update' => time(),
                    'alias' => 'not-to-show-404',
                    'title' => '404 Not Found Page',
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
                ],
                'model3' => [
                    'id' => 3,
                    'name' => 'Russian',
                    'short_code' => 'ru',
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
                    'value' => 3,
                ]
            ]
        ]));
    }
}
