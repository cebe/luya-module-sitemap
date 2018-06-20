<?php

namespace cebe\luya\sitemap\frontend\controllers;

use luya\cms\models\Nav;
use luya\cms\models\NavItem;
use luya\web\Controller;
use samdark\sitemap\Sitemap;
use luya\cms\helpers\Url;

/**
 * Controller provides sitemap.xml
 *
 * @author Carsten Brandt <mail@cebe.cc>
 */
class SitemapController extends Controller
{
    public function actionIndex()
    {
        $baseUrl = Yii::$app->request->hostInfo . Yii::$app->request->baseUrl;

        // create sitemap
        $sitemap = new Sitemap(Yii::getAlias('@runtime/sitemap.xml'), true);

        $sitemap->setMaxUrls(50000); // TODO make this configurable

        // add entry page
        $sitemap->addItem($baseUrl);

        // add luya CMS pages
        if (class_exists(NavItemPage::class)) {

            $query = Nav::find()->andWhere([
                'is_deleted' => false,
                'is_offline' => false,
                'is_draft' => false,
            ])->with(['navItems', 'navItems.lang']);
            foreach($query->each() as $nav) {
                /** @var Nav $nav */

                $urls = [];
                foreach($nav->navItems as $navItem) {
                    /** @var NavItem $navItem */
                    $urls[$navItem->lang->short_code] = Url::toMenuNavItem($navItem->id, 'cms/default/index');
                }
                $lastModified = $navItem->timestamp_update == 0 ? $navItem->timestamp_create : $navItem->timestamp_update;
                $sitemap->addItem($urls, $lastModified);
            }

        }

        // write it
        $sitemap->write();
    }
}