<?php

namespace cebe\luya\sitemap;

use luya\base\CoreModuleInterface;

/**
 * Sitemap Module.
 *
 * @author Carsten Brandt <mail@cebe.cc>
 */
final class Module extends \luya\base\Module implements CoreModuleInterface
{
    /**
     * @var boolean Whether hidden page should be included or not, defaults to false in order to make sure only
     * visible pages are included by default. In order to include hidden pages into the sitemap enable $withHidden.
     */
    public $withHidden = false;
}
