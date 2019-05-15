<?php

namespace cebe\luya\sitemap\tests;

use cebe\luya\sitemap\tests\Setup;
use luya\cms\models\Config;
use cebe\luya\sitemap\Module;
use cebe\luya\sitemap\controllers\SitemapController;
use luya\testsuite\fixtures\ActiveRecordFixture;
use luya\cms\models\NavItem;
use luya\cms\models\Nav;
use luya\admin\models\Lang;
use yii\helpers\FileHelper;

class MultiLangSitemapTest extends Setup
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

    /**
     * @dataProvider boolProvider
     */
    public function testIgnoreHiddenModuleProperty($withHidden)
    {
        $module = new Module('sitemap');
        $module->module = $this->app;
        $module->withHidden = $withHidden;

        SimpleSitemapTest::prepareBasicTableStructureAndData();

        $ctrl = new SitemapController('sitemap', $module);
        $response = $ctrl->actionIndex();
        list($handle, $begin, $end) = $response->stream;

        fseek($handle, $begin);
        $content = stream_get_contents($handle);

        $this->assertContainsTrimmed('<loc>https://luya.io</loc>', $content);
        $this->assertContainsTrimmed('<loc>https://luya.io/en/foo</loc>', $content);

        $this->assertContainsTrimmed('<loc>https://luya.io/en/foo-3</loc>', $content);
        $this->assertContainsTrimmed('<loc>https://luya.io/en/foo-3/foo-4-child</loc>', $content);
        $this->assertContainsTrimmed('<loc>https://luya.io/en/foo-3/foo-4-child/foo-5-child-child</loc>', $content);

        // check correct language on nested pages
        $this->assertContainsTrimmed('<loc>https://luya.io/de/foo-3-de</loc>', $content);
        $this->assertContainsTrimmed('<loc>https://luya.io/de/foo-3-de/foo-4-child-de</loc>', $content);
        $this->assertNotContains('<loc>https://luya.io/de/foo-3-de/foo-4-child</loc>', $content);
        $this->assertNotContains('<loc>https://luya.io/de/foo-3/foo-4-child-de</loc>', $content);

        if ($withHidden) {
            // $module->withHidden = true; = 2 Pages in index
            $this->assertContainsTrimmed('<loc>https://luya.io/en/foo-hidden</loc>', $content);
        } else {
            // $module->withHidden = false; = 1 Page in index
            $this->assertNotContains('<loc>https://luya.io/en/foo-hidden</loc>', $content);
        }

        $this->assertNotContains('<loc>https://luya.io/not-to-show-404</loc>', $content);
        $this->assertNotContains('<loc>https://luya.io/en/not-to-show-404</loc>', $content);
        $this->assertNotContains('<loc>https://luya.io/de/not-to-show-404</loc>', $content);

        $this->assertNotContains('<loc>https://luya.io/publish-check-past</loc>', $content);
        $this->assertNotContains('<loc>https://luya.io/en/publish-check-past</loc>', $content);
        $this->assertNotContains('<loc>https://luya.io/de/publish-check-past</loc>', $content);
        $this->assertNotContains('<loc>https://luya.io/publish-check-future</loc>', $content);
        $this->assertNotContains('<loc>https://luya.io/en/publish-check-future</loc>', $content);
        $this->assertNotContains('<loc>https://luya.io/de/publish-check-future</loc>', $content);
        $this->assertContainsTrimmed('<loc>https://luya.io/en/publish-check-present</loc>', $content);
    }
}
