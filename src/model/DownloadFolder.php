<?php

namespace SilverCommerce\DownloadableProducts;

use SilverStripe\ORM\DB;
use SilverStripe\Assets\Folder;
use SilverStripe\Security\InheritedPermissions;

/**
 * A custom type of folder used to 
 */
class DownloadFolder extends Folder
{
    /**
     * The location of this folder
     * 
     * @var string
     */
    private static $folder_name = "downloadableproducts";

    /**
     * Setup defaults when a dev build is run
     * 
     * @return void
     */
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        // Setup, or change, the top level download folder.
        $download_folder = static::find_or_make(static::config()->folder_name);
        $download_folder->ClassName = self::class;
        $download_folder->write();
        DB::alteration_message("Setup downloadableproduct folder");
    }

    /**
     * Ensure this folder has correct permissions
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        $allowed = [
            InheritedPermissions::LOGGED_IN_USERS,
            InheritedPermissions::ONLY_THESE_USERS
        ];

        if (!in_array($this->CanViewType, $allowed)) {
            $this->CanViewType = InheritedPermissions::LOGGED_IN_USERS;
        }
    }

}