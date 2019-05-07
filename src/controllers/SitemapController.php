<?php

/**
 * @copyright Copyright (c) 2019 Carsten Brandt <mail@cebe.cc> and contributors
 * @license https://github.com/cebe/luya-module-sitemap/blob/master/LICENSE.md
 */

namespace cebe\luya\sitemap\controllers;

use luya\cms\models\Config;
use Yii;
use luya\cms\helpers\Url;
use luya\cms\models\Nav;
use luya\cms\models\NavItem;
use luya\web\Controller;
use samdark\sitemap\Sitemap;

/**
 * Controller provides sitemap.xml
 */
class SitemapController extends Controller
{
    /**
     * Return the sitemap xml content.
     *
     * @return \yii\web\Response
     */
    public function actionIndex()
    {
        $sitemapFile = Yii::getAlias('@runtime/sitemap.xml');

        // update sitemap file as soon as CMS structure changes
        $lastCmsChange = max(NavItem::find()->select(['MAX(timestamp_create) as tc', 'MAX(timestamp_update) as tu'])->asArray()->one());

        if (!file_exists($sitemapFile) || filemtime($sitemapFile) < $lastCmsChange) {
            $this->buildSitemapfile($sitemapFile);
        }

        return Yii::$app->response->sendFile($sitemapFile, null, [
            'mimeType' => 'text/xml',
            'inline' => true,
        ]);
    }

    private function buildSitemapfile($sitemapFile)
    {
        $domain = $this->getDomainForLangIfExists(Yii::$app->composition->langShortCode);

        $host = $domain ?? Yii::$app->request->hostInfo;

        $baseUrl = $host . Yii::$app->request->baseUrl;

        // create sitemap
        $sitemap = new Sitemap($sitemapFile, true);

        // ensure sitemap is only one file
        // TODO make this configurable and allow more than one sitemap file
        $sitemap->setMaxUrls(PHP_INT_MAX);

        // add entry page
        $sitemap->addItem($baseUrl);

        // add luya CMS pages
        if ($this->module->module->hasModule('cms')) {
            $query = $this->getBaseQuery()->with(['navItems', 'navItems.lang']);

            if (!$this->module->withHidden) {
                $query->andWhere(['is_hidden' => false]);
            }

            $errorPageConfig = Config::findOne(['name' => Config::HTTP_EXCEPTION_NAV_ID]);
            $errorPageId = $errorPageConfig ? $errorPageConfig->value : null;

            foreach ($query->each() as $nav) {
                /** @var Nav $nav */

                // do not include 404 error page
                if ($errorPageId !== null && $errorPageId == $nav->id) {
                    continue;
                }

                $urls = [];
                foreach ($nav->navItems as $navItem) {
                    /** @var NavItem $navItem */

                    $fullUriPath = $this->getRelativeUriByNavItem($navItem, [$errorPageId]);

                    $domain = $this->getDomainForLangIfExists($navItem->lang->short_code);
                    $host = $domain ?? $host;

                    $url = $host
                        . Yii::$app->menu->buildItemLink($fullUriPath, $navItem->lang->short_code);

                    $urls[$navItem->lang->short_code] = $this->module->encodeUrls ? $this->encodeUrl($url) : $url;
                }
                $lastModified = $navItem->timestamp_update == 0 ? $navItem->timestamp_create : $navItem->timestamp_update;

                $sitemap->addItem($urls, $lastModified);
            }
        }

        // write sitemap files
        $sitemap->write();
    }


    /**
     * Encode an URL by using rawurlencode().
     *
     * @param string $url This can be either a full url with protocol or just a path.
     * @return string
     * @see https://stackoverflow.com/a/7974253/4611030
     */
    protected function encodeUrl($url)
    {
        return preg_replace_callback('#://([^/]+)/([^?]+)#', function ($match) {
            return '://' . $match[1] . '/' . join('/', array_map('rawurlencode', explode('/', $match[2])));
        }, $url);
    }

    /**
     * Get full relative URI by NavItem
     *
     * @param NavItem $navItem object
     * @param int[] $ignoreNavIds nav ids to ignore
     *
     * @return return string
     */
    private function getRelativeUriByNavItem($navItem, $ignoreNavIds)
    {
        $fullUriPath = $navItem->alias;
        $language = $navItem->lang->short_code;
        $parentNavId = $navItem->nav->attributes['parent_nav_id'];
        while ($parentNavId) {
            $parentNav = $this->getBaseQuery()->andWhere(['id' => $parentNavId])->one();

            if (!$parentNav) {
                break;
            }

            $parentNavItem = $parentNav->getNavItems()->andWhere(['lang_id' => $navItem->lang_id])->one();

            if ($parentNavItem) {
                $alias = $parentNavItem->attributes['alias'];
                if (!in_array($parentNav->id, $ignoreNavIds)) {
                    $fullUriPath = $alias . '/' . $fullUriPath;
                }
            }

            $parentNavId = $parentNav->attributes['parent_nav_id'];
        }

        return $fullUriPath;
    }

    /**
     * Common query building part
     * @return \yii\db\ActiveQuery
     */
    private function getBaseQuery()
    {
        return Nav::find()->where([
                'is_deleted' => false,
                'is_offline' => false,
                'is_draft' => false
            ])
            ->andWhere(['or', ['publish_from' => null], ['<=', 'publish_from', time()]])
            ->andWhere(['or', ['publish_till' => null], ['>=', 'publish_till', time()]]);
    }

    /**
     * Get Domain For Language If it Exists in composition config
     * @param  string $lang shortLangCode
     * @return string|null
     */
    private function getDomainForLangIfExists($lang)
    {
        // TODO replace by nadar provided function - https://github.com/luyadev/luya/issues/1921
        foreach (Yii::$app->composition->hostInfoMapping as $domain => $value) {
            if ($value['langShortCode'] === $lang) {
                return $domain;
            }
        }
        return null;
    }
}
