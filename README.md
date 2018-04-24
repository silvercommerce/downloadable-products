# SilverCommerce Downloadable Products Module

Add downloadable product type to the SilverCommerce install that allows users
to attach a file to a product that can only be downloaded when the user is
logged in and has bought it.

## Dependancies

* SilverStripe Framework 4.0.x
* SilverCommerce 1.0.x

## Installation

Install using composer:

    composer require silvercommerce/downloadable-products

Then run: dev/build/?flush=all

## Usage

By default this module adds a "Downloadable Product" postage amount and
sets up the mechanisams needed to buy and download products.

You will also need to do some additional tasks to ensure that users get
the best experience and you keep your files secure.

**NOTE:** You will need to perform the following additional steps
manually in order to gain the most from this module.

### Restrict the downloads folder (using Apache or IIS 7)

The file downloads section of your Silverstripe install will need to be
restricted (otherwise users could share the download links). You can
do this in your .htaccess or web.config by adding the following:

    RewriteEngine On
    RewriteCond %{REQUEST_URI} ^(.*)$
    RewriteRule assets/downloadable/* $frameworkDir/main.php?url=%1 [QSA]

Or alternativley, if you use web.config, add the following:

    <rewrite>
        <rules>
            <rule name="Silverstripe downloadable products" stopProcessing="true">
                <match url="^assets/downloadable/(.*)$" />
                <action type="Rewrite" url="$frameworkDir/main.php?url={R:1}" appendQueryString="true" />
            </rule>
        </rules>
    </rewrite>

**NOTE:** The IIS script above **should** work, but has not been tested,
some tweaking may be required.

### Add download link to orders pannel and emails

When you have access to a product in either the orders panel or an email
then you can call $DownloadLink to render the download URL into the
template. For example, in the order paid email you can add something,ike
this:

    OrderNotificationEmail_Customer.ss

    <tbody><% loop $Items %>
        <tr>
            <td>
                {$Title}
                <% if $DownloadLink %> <small>(<a href="$DownloadLink">Download</a>)</small><% end_if %>
                <% if $StockID %>($StockID)<% end_if %><br/>
                <em>$CustomisationHTML</em>
            </td>
            <td style="text-align: right">{$Quantity}</td>
            <td style="text-align: right">{$Price.Nice}</td>
        </tr>
    <% end_loop %></tbody>

###

