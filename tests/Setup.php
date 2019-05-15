<?php

namespace cebe\luya\sitemap\tests;

use luya\testsuite\cases\WebApplicationTestCase;
use yii\helpers\FileHelper;

abstract class Setup extends WebApplicationTestCase
{
    public function beforeSetup()
    {
        parent::beforeSetup();
        // clean up application runtime directory, do not use cached version of sitemap.xml
        $runtimePath = dirname(__DIR__) . '/tests/runtime';
        FileHelper::removeDirectory($runtimePath);
        FileHelper::createDirectory($runtimePath);
    }
}
