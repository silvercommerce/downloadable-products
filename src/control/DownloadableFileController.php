<?php

namespace SilverCommerce\DownloadableProducts;

use DateTime;
use SilverStripe\Assets\File;
use SilverStripe\Control\Director;
use SilverStripe\Security\Security;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\HTTPStreamResponse;
use SilverCommerce\OrdersAdmin\Model\Invoice;
use SilverStripe\Assets\Flysystem\FlysystemAssetStore;

/**
 * Controller responsible for downloading the resticted file (if the
 * user is allowed).
 *
 * This class will take the file URL, check if the current member (if
 * there is one) is allowed to download the file. If not, it will check
 * the URL and compare it to the link life of the file (if valid).
 */
class DownloadableFileController extends Controller
{

    /**
     * Calculate a timelimit based on the filesize. Set to 0 to give unlimited
     * timelimit. The calculation is: give enough time for the user with x kB/s
     * connection to donwload the entire file.
     *
     * E.G. The default 50kB/s equates to 348 minutes per 1GB file.
     *
     * @var int kilobytes per second
     */
    private static $min_download_bandwidth = 50;

    /**
     * The base URL segment this controller will be accessed via
     * 
     * @var string
    */
    private static $url_segment = "downloadproduct";

    /**
     * {@inheritdoc}
     */
    private static $url_handlers = [
        '$ID/$InvoiceID/$AccessKey/$FileName' => 'index'
    ];

    /**
     * Generate a link to this controller for downloading a file
     * 
     * @param int    $id         ID of the file.
     * @param int    $invoice_id ID of an associate invoice.
     * @param string $access_key Access key of invoice (for security).
     * @param string $filename   Actual name of file.
     * 
     * @return string
     */
    public function DownloadLink($id, $invoice_id, $access_key, $filename)
    {
        return Controller::join_links(
            $this->AbsoluteLink(),
            $id,
            $invoice_id,
            $access_key,
            $filename
        );
    }

    /**
     * Get a URL to download a file.
     *
     * @param string $action Action we would like to view.
     *
     * @return string
     */
    public function Link($action = NULL)
    {
        return Controller::join_links(
            $this->config()->url_segment,
            $action
        );
    }
    
    /**
     * Get absolute URL to download a file.
     *
     * @param string $action Action we would like to view.
     *
     * @return string
     */
    public function AbsoluteLink($action = NULL)
    {
        return Controller::join_links(
            Director::absoluteBaseURL(),
            $this->Link($action)
        );
    }

    /**
     * Main action for this controller, it handles the security checks and then
     * returns the file, or either an error or a login screen.
     * 
     * @return HTTPResponse
     */
    public function index()
    {
        $request = $this->getRequest();
        $member = Security::getCurrentUser();
        $file = File::get()->byID($request->param("ID"));

        if (empty($file)) {
            return $this->httpError(404);
        }

        // Does this file need to have permissions checked?
        $product = $file->DownloadableProduct();

        if (!$product->exists()) {
            return $this->redirect($file->AbsoluteLink());
        }

        // If the user is logged in, can they download this file?
        if (isset($member) && $product->canDownload($member)) {
            $this->extend('onBeforeSendFile', $file);
            return $this->sendFile($file);
        }
        
        // Finally  Attempt to get the invoice from the URL vars
        // and see if it matches this download
        $invoice = Invoice::get()->filter(
            [
                "ID" => $request->param('InvoiceID'),
                "AccessKey" => $request->param('AccessKey')
            ]
        )->first();
    
        if (isset($invoice)) {
            $origin = new DateTime($invoice->dbObject('StartDate')->Rfc822());
            $now = new DateTime();
            $diff = (int) $now->diff($origin)->format('%d');

            if ($diff < $product->LinkLife) {
                $this->extend('onBeforeSendFile', $file);
                return $this->sendFile($file);
            }
        }

        // Finally, return a login screen
        return Security::permissionFailure(
            $this,
            _t(
                'SilverCommerce\DownloadableProducts.NotAuthorised',
                'You are not authorised to access this resource. Please log in.'
            )
        );
    }

    /**
     * Output file to the browser as a stream.
     * 
     * @param File $file A file object
     * 
     * @return int|boolean
     */
    protected function sendFile(File $file)
    {
        $path = $file->getSourceURL(true);
        $size = $file->getAbsoluteSize();
        $mime = $file->getMimeType();
        $stream = $file->getStream();
        $min_bandwidth = $this->config()->min_download_bandwidth;
        $time = 0;

        // Create streamable response
        $response = HTTPStreamResponse::create($stream, $size)
            ->addHeader('Content-Type', $mime);

        // Add standard headers
        $headers = Config::inst()->get(FlysystemAssetStore::class, 'file_response_headers');
        foreach ($headers as $header => $value) {
            $response->addHeader($header, $value);
        }

        return $response;
    }
}
