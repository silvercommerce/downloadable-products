<?php

namespace SilverCommerce\DownloadableProducts;

use Product;
use SilverStripe\Assets\File;
use SilverStripe\Forms\TextField;
use SilverStripe\Security\Security;
use SilverStripe\AssetAdmin\Forms\UploadField;

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
    private static $allowed_order_statuses = [
        "paid",
        "processing",
        "dispatched"
    ];

    /**
     * The location to place the uploaded files
     * 
     * @var string
     */
    private static $folder_name = "downloadableproducts";

    private static $description = "A product that can be downloaded";

    private static $db = [
        'LinkLife' => 'Int'
    ];

    private static $has_one = [
        "File" => File::class
    ];

    private static $casting = [
        "DownloadLink" => "Varchar",
        "Deliverable" => "Boolean"
    ];

    private static $owns = [
        "File"
    ];

    private static $defaults = [
        'LinkLife' => 7
    ];

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
     * @return string
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

        $fields->addFieldsToTab(
            "Root.Settings",
            [
                TextField::create('LinkLife', 'Life of download link (in days)'),
                UploadField::create("File")
                    ->setFolderName($this->config()->folder_name)
            ]
        );

        return $fields;
    }

    /**
     * Ensure weight is removed on save
     *
     * @return void
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        $this->Weight = 0;
    }

    /**
     * Special permission to see if this product can be downloaded by the current member
     *
     * @param Member $member The current member object
     *
     * @return boolean
     */
    public function canDownload(Member $member = null)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }

        $contact = null;

        if (isset($member)) {
            $contact = $member->Contact();
        }

        if (isset($contact)) {
            $items = $contact
                ->Invoices()
                ->filter(
                    [
                        "Status" => $this->config()->allowed_order_statuses,
                        "Items.StockID" => $this->StockID
                    ]
                );

            return $items->exists();
        }

        return false;
    }
}
