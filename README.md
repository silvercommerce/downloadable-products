# SilverCommerce Downloadable Products Module

Adds a downloadable product type to a SilverCommerce install, that allows
users to attach a file that can only be downloaded when the user has bought
it.

## Dependancies

* SilverStripe Framework 4.0.x
* SilverCommerce 1.0.x

## Installation

Install using composer:

    composer require silvercommerce/downloadable-products

Then run: dev/build/?flush=1

## Usage

1. Visit /admin on your SilverStripe site.
2. Navigate to the "Catalogue".
3. Add a new "Downloadable Product" and setup.
4. Click "Settings" and attach a file.

## Setting a longer Link Life

By default, all products will be available via their download link for 7 days
(if the user did not create an account during the pruchase process).

If you would like to increase this length, you can change it on the product,
under "Settings". Alternativley you can change this glocally using config: 

    SilverCommerce\DownloadableProducts\DownloadableProduct:
        defaults:
            LinkLife: 14 # two weeks

**NOTE: You will ned to re-save any existing products for this to take effect**

## Restrict the downloads folder

By default, this module adds files into a restricted folder. Any file attached
to a `DownloadableProduct` will be moved to this folder and made unavailable to
view unless the current user can edit the file they use the associated download
link.

**NOTE: You need to ensure SilverStripe is managing your assets folder for access restriction to work.**

### Changing the default download location

If you wish to change the default location downloadable products are placed,
you can change the `folder_name` config variable, EG:

    SilverCommerce\DownloadableProducts\DownloadableProduct:
        folder_name: "mydownloadlocation"

## The `FileDownloadController`

By default, anyone purchasing a downloadable product can be provided a download
link (see below). This will send them to `FileDownloadController`, which will
attempt to see if the user is allowed to download the file.

If the link has expired, or the user user is not allowed to download, an error
will be displayed.

## Add a `DownloadLink` to orders pannel and emails

When you have access to a product in either the orders panel or an email
then you can call `$DownloadLink` to render the download URL into the
template.

For example, if an invoice has been produced and marked as paid (and you
have setup a relevent notification) you can update your email template to
use the following:

    OrderNotificationEmail_Customer.ss

    <tbody><% loop $Items %>
        <tr>
            <td>
                {$Title}
                <% if $DownloadLink %>(<a href="$DownloadLink">Download</a>)<% end_if %>
                <% if $StockID %>($StockID)<% end_if %><br/>
                <em>$CustomisationHTML</em>
            </td>
            <td style="text-align: right">{$Quantity}</td>
            <td style="text-align: right">{$Price.Nice}</td>
        </tr>
    <% end_loop %></tbody>