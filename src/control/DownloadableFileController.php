<?php

namespace SilverCommerce\DownloadableProducts;

use DateTime;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\Control\Director;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverCommerce\OrdersAdmin\Model\Invoice;
use SilverCommerce\DownloadableProducts\DownloadableProduct;

/**
 * Core controller responsible for determining if the current user can
 * download the file selected.
 *
 * A lot of this code is taken and modified from the "secure asssts"
 * Silverstripe Module
 */
class DownloadableFileController extends Controller
{

    /**
     * We calculate the timelimit based on the filesize. Set to 0 to give unlimited
     * timelimit. The calculation is: give enough time for the user with x kB/s
     * connection to donwload the entire file.
     *
     * E.G. The default 50kB/s equates to 348 minutes per 1GB file.
     *
     * @var int kilobytes per second
     */
    private static $min_download_bandwidth = 50;

    /**
     * Process all incoming requests passed to this controller, checking
     * that the file exists and passing the file through if possible.
     *
     * {@inheritdoc}
     *
     * @return HTTPResponse
     */
    public function handleRequest(HTTPRequest $request)
    {
        if (!$request) {
            user_error("Controller::handleRequest() not passed a request!", E_USER_ERROR);
        }
        
        // Copied from Controller::handleRequest()
        $this->pushCurrent();
        $this->urlParams = $request->allParams();
        $this->request = $request;
        $this->response = new HTTPResponse();

        $url = array_key_exists('url', $_GET) ? $_GET['url'] : $_SERVER['REQUEST_URI'];

        // remove any relative base URL and prefixed slash that get appended to the file path
        // e.g. /mysite/assets/test.txt should become assets/test.txt to match the Filename field on File record
        $url = Director::makeRelative(ltrim(str_replace(BASE_URL, '', $url), '/'));
        $file = File::find($url);

        if ($this->canDownloadFile($file)) {
            // If we're trying to access a resampled image.
            if (preg_match('/_resampled\/[^-]+-/', $url)) {
                // File::find() will always return the original image, but we still want to serve the resampled version.
                $file = new Image();
                $file->Filename = $url;
            }

            $this->extend('onBeforeSendFile', $file);

            return $this->sendFile($file);
        } else {
            if ($file instanceof File) {
                // Permission failure
                Security::permissionFailure($this, 'You are not authorised to access this resource. Please log in.');
            } else {
                // File doesn't exist
                $this->response = new HTTPResponse('File Not Found', 404);
            }
        }

        return $this->response;
    }

    /**
     * Output file to the browser.
     * For performance reasons, we avoid SS_HTTPResponse and just output the contents instead.
     */
    public function sendFile($file)
    {
        $path = $file->getFullPath();

        if (SapphireTest::is_running_test()) {
            return file_get_contents($path);
        }

        header('Content-Description: File Transfer');
        // Quotes needed to retain spaces (http://kb.mozillazine.org/Filenames_with_spaces_are_truncated_upon_download)
        header('Content-Disposition: inline; filename="' . basename($path) . '"');
        header('Content-Length: ' . $file->getAbsoluteSize());
        header('Content-Type: ' . HTTP::get_mime_type($file->getRelativePath()));
        header('Content-Transfer-Encoding: binary');
        // Fixes IE6,7,8 file downloads over HTTPS bug (http://support.microsoft.com/kb/812935)
        header('Pragma: ');

        if ($this->config()->min_download_bandwidth) {
            // Allow the download to last long enough to allow full download with min_download_bandwidth connection.
            increase_time_limit_to((int)(filesize($path)/($this->config()->min_download_bandwidth*1024)));
        } else {
            // Remove the timelimit.
            increase_time_limit_to(0);
        }

        // Clear PHP buffer, otherwise the script will try to allocate memory for entire file.
        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        // Prevent blocking of the session file by PHP. Without this the user can't visit another page of the same
        // website during download (see http://konrness.com/php5/how-to-prevent-blocking-php-requests/)
        session_write_close();

        readfile($path);
        die();
    }

    /**
     * Determine if the file we found can be downloaded or not
     *
     * @return Boolean
     */
    public function canDownloadFile(File $file = null)
    {
        if ($file instanceof File) {
            $product = DownloadableProduct::get()
                ->filter("FileID", $file->ID)
                ->first();

            if ($product && ($product->canDownload() || $this->hasAccess())) {
                return true;
            }
        }

        return false;
    }

    public function hasAccess()
    {
        $request = $this->getRequest();
        $return = false;
        $vars = $request->getVars();
        $url = array_key_exists('url', $_GET) ? $_GET['url'] : $_SERVER['REQUEST_URI'];
        $url = Director::makeRelative(ltrim(str_replace(BASE_URL, '', $url), '/'));
        $file = File::find($url);

        $order = Invoice::get()->byID($vars['o']);
        if ($order) {
            $return = $order->AccessKey == $vars['k'] ? true : false;
            if ($return) {
                $product = DownloadableProduct::get()
                    ->filter("FileID", $file->ID)
                    ->first();
                if ($product) {
                    $life = $product->LinkLife;
                    $origin = new DateTime($order->dbObject('LastEdited')->Rfc822());
                    $now = new DateTime();
                    $diff = (int) $now->diff($origin)->format('%d');
                    if ($life < $diff) {
                        $return = false;
                    }
                } else {
                    $return = false;
                }
            }
        }

        return $return;
    }
}
