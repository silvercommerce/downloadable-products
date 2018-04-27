<?php

namespace SilverCommerce\DownloadableProducts\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverCommerce\OrdersAdmin\Model\Invoice;

class LineItemExtension extends DataExtension
{

    private static $casting = [
        "DownloadLink" => "Varchar"
    ];

    public function getDownloadLink()
    {
        $invoice = Invoice::get()->byID($this->owner->ParentID);
        $match = $this->owner->match();

        if ($match && method_exists($match, "getDownloadLink")) {
            return $match->getDownloadLink().
                '?o='.$invoice->ID.
                '&k='.$invoice->AccessKey;
        }

        return false;
    }
}
