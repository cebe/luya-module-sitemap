<?php

/**
 * @copyright Copyright (c) 2019 Carsten Brandt <mail@cebe.cc> and contributors
 * @license https://github.com/cebe/luya-module-sitemap/blob/master/LICENSE.md
 */

namespace cebe\luya\sitemap;

use luya\base\CoreModuleInterface;

/**
 * Sitemap Module.
 *
 */
final class Module extends \luya\base\Module implements CoreModuleInterface
{
    /**
     * @var boolean Whether hidden page should be included or not, defaults to false in order to make sure only
     * visible pages are included by default. In order to include hidden pages into the sitemap enable $withHidden.
     */
    public $withHidden = false;

    /**
     * @var boolean Whether the sitemap URLs should be encoded or not. Its highly recommend to enable this property as otherwise
     * the Sitemap file could have errors and therfore won't work.
     * @since 1.0.0
     * @see https://www.sitemaps.org/protocol.html#escaping
     */
    public $encodeUrls = true;

    /**
    * @var array  The list of link classes implementing the SitemapLinkInterface
    * for extra sitemap links
    * @since 1.2.1?
    */
    public $linkInterfaceLookup = [];
}
