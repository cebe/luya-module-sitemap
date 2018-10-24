<?php

namespace cebe\luya\sitemap\tests;

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
                ]
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
        if ($withHidden) {
            // $module->withHidden = true; = 2 Pages in index
            $this->assertContainsTrimmed('<loc>https://luya.io/foo-hidden</loc>', $content);
        } else {
            // $module->withHidden = false; = 1 Page in index
            $this->assertNotContains('<loc>https://luya.io/foo-hidden</loc>', $content);
        }

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
                ]
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
                ]
            ]
        ]));
    }
}
