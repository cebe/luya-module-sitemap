<?php

/**
 * @copyright Copyright (c) 2019 Carsten Brandt <mail@cebe.cc> and contributors
 * @license https://github.com/cebe/luya-module-sitemap/blob/master/LICENSE.md
 */

namespace cebe\luya\sitemap;

/**
 * Description of SitemapLinkInterface
 *
 * @author Rochdi Bazine
 * @since 1.3.0
 */
interface SitemapLinkInterface
{
    /**
     * Return an iteration of links. If used in ngRestModel, could be useful
     * to run the find() method
     * A simple implentation to get all model item would be :
     * ```php
     * public function linksIterator (){
     *    return self::find()->all();
     * }
     * ```
     *
     * @return SitemapLinkInterface[]
     */
    public function linksIterator() : array;

    /**
     * An example of link to the model view page would be :
     * ```php
     * public function linkGetUrl(){
     *   $absoluteBaseUrl = Url::base(true);
     *   return [
     *       $absoluteBaseUrl. Url::toModuleRoute('mymodulefrontend', ['/mumodulefrontend/sample/view', 'id' => $this->id]),
     *       $this->updated_at
     *   ];
     * }
     * ```
     *
     * @return string  Full URL of the link as first entry and the last
     * modification timestamp as second entry
     */
    public function linkUrl() : string;

    /**
     * An example of link to the model view page would be :
     * ```php
     * public function linkUrl(){
     *   $absoluteBaseUrl = Url::base(true);
     *   return $absoluteBaseUrl. Url::toModuleRoute('mymodulefrontend', ['/mumodulefrontend/sample/view', 'id' => $this->id]);
     * }
     * ```
     *
     * @return int the link last modification timestamp
     */
    public function linkUpdatedTimestamp() : int;

    /**
     * Used to know whether to forece sitemap re-build  or not
     *
     * Example of returning a model last updated_at timestamp
     *
     * ```php
     *public function getLastModificationTimestamp() {
     *   return self::find()->max('updated_at');
     *}
     * ```
     *or the max between last update and created timestamps
     * ```php
     *public function getLastModificationTimestamp() {
     *   return max(self::find()->select(['MAX(created_at) as tc', 'MAX(updated_at) as tu'])->asArray()->one());
     *}
     * ```
     *
     * To force generation of interface items return `time()`
     * ```php
     *public function getLastModificationTimestamp() {
     *   return time();
     *}
     * ```
     * @return int the last modification timestamp
     */
    public function getLastModificationTimestamp() : int;
}
