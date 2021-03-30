<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace cebe\luya\sitemap;

/**
 * Description of SitemapLinkInterface
 *
 * @author Rochdi Bazine
 * @since 1.2.1?
 */
interface SitemapLinkInterface
{
    /**
     * Return an iteration of links. If used in ngRestModel, could be useful
     * to run the find() method
     * A simple implentation to get all model item would be :
     * ```php
     * public function iteratorLinks(){
     *    return self::find()->all();
     * }
     * ```
     *
     * @return SitemapLinkInterface.
     */
    public function iteratorLinks();

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
     * @return array Full URL of the link as first entry and the last
     * modification timestamp as second entry
     */
    public function linkGetUrl();
    
    /**
     * Used to know whether to forece sitemap re-build  or not
     *
     * Example of returning a model max between last update and created timestamp
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
    public function getLastModificationTimestamp();
}
