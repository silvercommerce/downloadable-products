<?php

namespace SilverCommerce\DownloadableProducts;

use Product;
use SilverStripe\Forms\TextField;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Security\Security;

/**
 * Product class that will allow adding of product to the CMS.
 */
class DownloadableProduct extends Product
{

    /**
     * A list of statuses that an order containing this product must
     * have in order to allow this product to be downloaded.
     *
     * @config
     */
    private static $allowed_order_statuses = array(
        "paid",
        "processing",
        "dispatched"
    );

    private static $folder_name = "downloadableproducts";

    /**
     * @config
     */
    private static $description = "A product that can be downloaded";

    private static $db = array(
        'LinkLife' => 'Int'
    );

    private static $has_one = array(
        "File" => "File"
    );

    private static $casting = array(
        "DownloadLink" => "Varchar",
        "Deliverable" => "Boolean"
    );

    private static $defaults = array(
        'LinkLife' => 7
    );

    /**
     * Is this product deliverable? Largely this is used
     * by the shopping cart when adding to cart.
     *
     * @return boolean
     */
    public function getDeliverable()
    {
        return false;
    }

    /**
     * Get the link to download the file associated with this product
     *
     */
    public function getDownloadLink()
    {
        $link = "";

        if ($this->FileID) {
            $link = $this->File()->Link();
        }

        return $link;
    }


    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName("Weight");
        $fields->removeByName("PackSize");

        $fields->addFieldsToTab(
            "Root.Settings",
            array(
                TextField::create('LinkLife', 'Life of download link (in days)'),
                UploadField::create("File")
                    ->setFolderName($this->config()->folder_name)
            )
        );

        return $fields;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        // Downloadable products have 0 weight and Pack Size
        $this->Weight = 0;
        $this->PackSize = 0;
    }

    public function canDownload($member = null)
    {
        if (!$member || !$member instanceof Member) {
            $member = Security::getCurrentUser();
        }

        if (isset($member)) {
            $items = $member
                ->Orders()
                ->filter(array(
                    "Status" => $this->config()->allowed_order_statuses,
                    "Items.StockID" => $this->StockID
                ));

            return $items->exists();
        }

        return false;
    }
}
