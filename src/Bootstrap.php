<?php

namespace cebe\luya\sitemap;

use yii\base\BootstrapInterface;
use luya\web\Application;

/**
 * Sitemap Module Bootstrap.
 *
 * The Sitemap bootstrap class injects url rules for sitemap.xml
 *
 * @author Carsten Brandt <mail@cebe.cc>
 */
final class Bootstrap implements BootstrapInterface
{
    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        if ($app->hasModule('sitemap')) {
            $app->on(Application::EVENT_BEFORE_REQUEST, function ($event) {
                if (!$event->sender->request->isConsoleRequest && !$event->sender->request->isAdmin) {
                    $event->sender->urlManager->addRules([
                        ['pattern' => 'sitemap.xml', 'route' => 'sitemap/sitemap/index'],
                    ], false);
                }
            });
        }
    }
}
