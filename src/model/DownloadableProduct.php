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
     * Set the default DB table name
     * 
     * @var string
     */
    private static $table_name = "DownloadableProduct";

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

    /**
     * The location to place the uploaded files
     * 
     * @var string
     */
    private static $folder_name = "downloadableproducts";

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
     * Downloadable products are not deliverable. This will be
     * detected by the shopping cart to disable delivery options.
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
