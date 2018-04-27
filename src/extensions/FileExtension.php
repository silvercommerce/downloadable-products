<?php

namespace SilverCommerce\DownloadableProducts\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injector;
use SilverCommerce\OrdersAdmin\Model\Invoice;
use SilverCommerce\DownloadableProducts\Model\DownloadableProduct;

/**
 * Custom extension to file that checks if the file is "downloadable",
 * if ensure the user has bought it (of the correct access key is available)
 */
class FileExtension extends DataExtension
{

    /**
     * Files can only be associated with one DownloadableProduct
     * 
     * @var array
     */
    private static $belongs_to = [
        "DownloadableProduct" => DownloadableProduct::class . ".File"
    ];
}
