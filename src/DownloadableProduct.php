<?php

namespace SilverCommerce\DownloadableProducts;

use Product;
use SilverStripe\Assets\File;
use SilverStripe\Forms\TextField;
use SilverStripe\Security\Security;
use SilverStripe\Core\Config\Config;
use SilverCommerce\OrdersAdmin\Model\Invoice;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverCommerce\DownloadableProducts\DownloadFolder;
use SilverCommerce\DownloadableProducts\FileDownloadController;
use SilverStripe\Core\Injector\Injector;

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
     * @param int    $invoice_id the ID of an associated invoice
     * @param string $access_key key of assocaiated invoice
     * 
     * @return string
     */
    public function getDownloadLink($invoice_id, $access_key)
    {
        $file = $this->File();

        if ($file->exists()) {
            $download = Injector::inst()
                ->get(FileDownloadController::class);

            return $download->DownloadLink(
                $file->ID,
                $invoice_id,
                $access_key,
                $file->Name
            );
        }

        return "";
    }

    /**
     * Get the folder to add downloads to
     * 
     * @return DownloadFolder
     */
    public function getDownloadFolder()
    {
        return DownloadFolder::find_or_make(
            Config::inst()->get(DownloadFolder::class, "folder_name")
        );
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
     * Ensure weight is removed on save and that attached files are moved to
     * the correct folder
     *
     * @return void
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        // Set weight
        $this->Weight = 0;

        // Deal with moving file
        $file = $this->File();
        $folder = $this->getDownloadFolder();
        $move = true;

        // If the file is in the download folder (or an ancestor), don't move
        if ($file->exists() && $file->ParentID == $folder->ID) {
            $move = false;
        } elseif ($file->exists()) {
            $id_list = $folder->getDescendantIDList();
            if (in_array($file->ParentID, $id_list)) {
                $move = false;
            }
        }

        // If needed, move the attached file to a new folder
        if ($move) {
            $file->ParentID = $folder->ID;
            $file->write();
        }
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
                        "Status" => Config::inst()
                            ->get(Invoice::class, "paid_statuses"),
                        "Items.StockID" => $this->StockID
                    ]
                );

            return $items->exists();
        }

        return false;
    }
}
