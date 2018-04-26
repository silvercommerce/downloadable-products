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

By default, this module taps into SilverStripe 4's file permissions system.
This means any file attached to a `DownloadableProduct` will be made
unavailable to view unless the current user can edit the file or they have
purchased it.

If you are not using a secured assets folder in SilverStripe 4 (possibly for
performance reasons), then this module does come with a simple controller to
manage downloads. You can start using this by adding something like below to
your config.yml:

    SilverStripe\Control\Director:
      rules:
        'assets/downloadableproducts': 'DownloadableFileController'

This will ensure the folder "downloadableproducts" in assets is mapped to the
controller.

You will also need to tell your webserver that these URL's are now handled by
SilverStripe (otherwise users could share the download links). You can do this
in your .htaccess by adding the following:

    RewriteEngine On
    RewriteCond %{REQUEST_URI} ^(.*)$
    RewriteRule assets/downloadable/* index.php?url=%1 [QSA]

Or alternativley, if you use web.config, add the following:

    <rewrite>
        <rules>
            <rule name="Silverstripe downloadable products" stopProcessing="true">
                <match url="^assets/downloadable/(.*)$" />
                <action type="Rewrite" url="index.php?url={R:1}" appendQueryString="true" />
            </rule>
        </rules>
    </rewrite>

**NOTE:** The IIS script above **should** work, but has not been tested,
some tweaking may be required.

## Add download link to orders pannel and emails

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

