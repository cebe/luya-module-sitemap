<?php

/**
 * @copyright Copyright (c) 2019 Carsten Brandt <mail@cebe.cc> and contributors
 * @license https://github.com/cebe/luya-module-sitemap/blob/master/LICENSE.md
 */

namespace cebe\luya\sitemap\controllers;

use luya\admin\models\Lang;
use luya\cms\models\Config;
use Yii;
use luya\cms\helpers\Url;
use luya\cms\models\Nav;
use luya\cms\models\NavItem;
use luya\web\Controller;
use samdark\sitemap\Sitemap;
use cebe\luya\sitemap\SitemapLinkInterface;
use yii\base\InvalidConfigException;
use luya\helpers\ArrayHelper;

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

        // update sitemap file as soon as CMS structure changes or external items changed
        $lastCmsChange = max(ArrayHelper::merge($this->getExternalItemsChangeTimestamp(), NavItem::find()->select(['MAX(timestamp_create) as tc', 'MAX(timestamp_update) as tu'])->asArray()->one()));

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

        $isSingleLanguageSite = Lang::find()->where(['is_deleted' => false])->count() == 1;

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
                    if (!$navItem->lang) {
                        continue;
                    }
                    
                    $fullUriPath = $this->getRelativeUriByNavItem($navItem, [$errorPageId]);

                    $domain = $this->getDomainForLangIfExists($navItem->lang->short_code);
                    $host = $domain ?? $host;

                    $url = $host
                        . Yii::$app->menu->buildItemLink($fullUriPath, $navItem->lang->short_code);

                    $urls[$navItem->lang->short_code] = $this->module->encodeUrls ? $this->encodeUrl($url) : $url;
                }
                $lastModified = $navItem->timestamp_update == 0 ? $navItem->timestamp_create : $navItem->timestamp_update;

                // add single item with out language alternatives on single language site
                if ($isSingleLanguageSite && count($urls) === 1) {
                    $urls = reset($urls);
                }

                $sitemap->addItem($urls, $lastModified);
            }
        }

        $this->lookupExternalItems($sitemap);

        // write sitemap files
        $sitemap->write();
    }

    /**
     * Loop over defined external interfaces and return last change timestamp
     * Used to decide whether to rebuild sitemap or not
     * @return array List of external interfaces change timestamps
     */
    private function getExternalItemsChangeTimestamp()
    {
        $lastChange = [];

        if (empty($this->module->linkInterfaceLookup)) {
            return $lastChange;
        }
        foreach ($this->module->linkInterfaceLookup as $externalLinkInterface) {
            foreach ($this->getExternalLinkInterfaceObject($externalLinkInterface)->linksIterator() as $externalLink) {
                $lastChange[] = $externalLink->getLastModificationTimestamp();
            }
        }
        return $lastChange;
    }

    /**
     * Lookup defined link interfaces for extra items and add them to the sitemap
     * @param samdark\sitemap\Sitemap $sitemap The sitemap object
     * @since 1.2.1?
     */
    private function lookupExternalItems($sitemap)
    {
        if (empty($this->module->linkInterfaceLookup)) {
            return;
        }
        foreach ($this->module->linkInterfaceLookup as $externalLinkInterface) {
            foreach ($this->getExternalLinkInterfaceObject($externalLinkInterface)->linksIterator() as $externalLink) {
                /** @var SitemapLinkInterface $externalLink */
                $url = $externalLink->linkUrl();
                $lastModified = $externalLink->linkUpdatedTimestamp();
                $sitemap->addItem($url, $lastModified);
            }
        }
    }

    /**
     * Checks whether a given external link configuration is valid or not and returns
     * and instance of the external link object if ok
     * @param string $externalLinkInterface
     * @return SitemapLinkInterface
     * @throws InvalidConfigException
     */
    private function getExternalLinkInterfaceObject($externalLinkInterface)
    {
        if (!class_exists($externalLinkInterface)) {
            throw new InvalidConfigException("Wrong sitemap module configuration: $externalLinkInterface is not found");
        }

        $externalLinkInterfaceObject = new $externalLinkInterface;
        if (!($externalLinkInterfaceObject instanceof SitemapLinkInterface)) {
            throw new InvalidConfigException("Wrong sitemap module configuration: $externalLinkInterface is not implementing SitemapLinkInterface");
        }

        $this->assertLinksItoratorReturnedObject($externalLinkInterfaceObject);

        return $externalLinkInterfaceObject;
    }

    /**
     * Asserts that the external interface object `linksIterator()` method is returning
     * an array of SitemapLinkInterface
     * @param SitemapLinkInterface $externalLinkInterfaceObject
     * @throws InvalidConfigException
     */
    private function assertLinksItoratorReturnedObject($externalLinkInterfaceObject)
    {
        $links = $externalLinkInterfaceObject->linksIterator();
        if (!empty($links)) {
            $entry = array_pop($links);
            if (!($entry instanceof SitemapLinkInterface)) {
                throw new InvalidConfigException(sprintf("External interface %s::linksIterator() should return an array of SitemapLinkInterface objects", get_class($externalLinkInterfaceObject)));
            }
        }
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
     * Limitation:
     * For domains with language AND country  specific content;  de-de, de-at, de-ch; en-gb, en-us etc the domain of the first matching `langShortCode`will be considered

     *  For eg
     *  `Composition::$hostInfoMapping`

     *     ```php
     *     'hostInfoMapping' => [
     *         'http://example.us' => ['langShortCode' => 'en', 'countryShortCode' => 'us'],
     *         'http://example.co.uk' => ['langShortCode' => 'en', 'countryShortCode' => 'uk'],
     *         'http://example.de' => ['langShortCode' => 'de', 'countryShortCode' => 'de'],
     *     ],
     *    ```

     *     sitemap.xml will have link of  `http://example.us` first matching domain for `en` langugae i.e. `langShortCode` and NOT of `http://example.co.uk`

     *  see   PR: https://github.com/cebe/luya-module-sitemap/pull/14
     *  and test code
     *
     * @param  string $lang shortLangCode
     * @return string|null
     */
    private function getDomainForLangIfExists($lang)
    {
        // method available since luya version 1.0.18
        // https://github.com/luyadev/luya/issues/1921
        if (method_exists(Yii::$app->composition, 'resolveHostInfo')) {
            return Yii::$app->composition->resolveHostInfo($lang) ?: null;
        }

        // Fallback implementation
        foreach (Yii::$app->composition->hostInfoMapping as $domain => $value) {
            if ($value['langShortCode'] === $lang) {
                return $domain;
            }
        }
        return null;
    }
}
