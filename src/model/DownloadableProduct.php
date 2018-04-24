<?php

class DownloadableProduct extends Product {

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
    public function getDownloadLink() {
        $link = "";

        if($this->FileID)
            $link = $this->File()->Link();

        return $link;
    }


    public function getCMSFields() {
        $fields = parent::getCMSFields();

        $fields->removeByName("Weight");
        $fields->removeByName("PackSize");

        $fields->addFieldsToTab(
            "Root.Settings",
            array(
                TextField::create('LinkLife','Life of download link (in days)'),
                UploadField::create("File")
                    ->setFolderName("downloadable")
            )
        );

        return $fields;
    }

    public function requireDefaultRecords() {
        parent::requireDefaultRecords();

        // See if we need to create downloadable postage
        $records = PostageArea::get()
            ->filter(
                "Title",
                _t(
                    "DownloadableProduct.DownloadableGoods",
                    "Downloadable Goods"
                )
            );

        if(!$records->exists()) {
            $config = SiteConfig::current_site_config();

            $postage = PostageArea::create();
            $postage->Title = _t(
                "DownloadableProduct.DownloadableGoods",
                "Downloadable Goods"
            );
            $postage->Country = "*";
            $postage->ZipCode = "*";
            $postage->Calculation = "Weight";
            $postage->Unit = 0.0;
            $postage->Cost = 0.0;
            $postage->Tax = 0.0;
            $postage->SiteID = $config->ID;
            $postage->write();

            DB::alteration_message(_t("DownloadableProduct.AddedPostage", "Added downloadable postage"), 'created');
        }
    }

    public function onBeforeWrite() {
        parent::onBeforeWrite();

        // Downloadable products have 0 weight and Pack Size
        $this->Weight = 0;
        $this->PackSize = 0;
    }

    public function canDownload($member = null) {
        if(!$member || !$member instanceof Member)
            $member = Member::currentUser();

        if($member) {
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
