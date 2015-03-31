# Silverstripe Commerce

## Downloadable Product Module

Add downloadable product type to the Silverstripe Commerce module that
allows users to attach a file to a product that can only be downloaded
when the user is logged in and has bought it.

## Dependancies

* SilverStripe Framework 3.1.x
* Silverstripe Commerce

## Installation

Install this module either by downloading and adding to:

    [silverstripe-root]/commerce-downloadableproducts

Then run: dev/build/?flush=all

Or alternativly add use composer:

    i-lateral/silverstripe-commerce-downloadableproduct
    
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
    
Or alternaticly, if you use web.config, add the following:

    <rewrite>
        <rules>
            <rule name="Silverstripe downloadable products" stopProcessing="true">
                <match url="^assets/downloadable/(.*)$" />
                <action type="Rewrite" url="$frameworkDir/main.php?url={R:1}" appendQueryString="true" />
            </rule>
        </rules>
    </rewrite>

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
                <% if $Product.DownloadLink %>(<a href="$Product.DownloadLink">Download</a>)<% end_if %>
                <% if $StockID %>($StockID)<% end_if %><br/>
                <em>$CustomisationHTML</em>
            </td>
            <td style="text-align: right">{$Quantity}</td>
            <td style="text-align: right">{$Price.Nice}</td>
        </tr>
    <% end_loop %></tbody>

### 

