<?php

namespace SilverCommerce\DownloadableProducts;

use SilverStripe\ORM\DataExtension;
use SilverCommerce\OrdersAdmin\Model\Invoice;

class LineItemExtension extends DataExtension
{

    private static $casting = [
        "DownloadLink" => "Varchar"
    ];

    /**
     * Generate a link which can be used to download this product.
     * 
     * @return string
     */
    public function getDownloadLink()
    {
        $invoice = $this->getOwner()->Parent();
        $match = $this->getOwner()->match();

        if ($match && method_exists($match, "getDownloadLink") && $invoice->isPaid()) {
            return $match->getDownloadLink(
                $invoice->ID,
                $invoice->AccessKey
            );
        }

        return "";
    }
}
