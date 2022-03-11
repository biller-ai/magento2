<p align="center">
  <img src="https://biller.ai/apple-touch-icon.png" width="128" height="128"/>
</p>
<h1 align="center">Biller for Magento 2.3.3 and higher</h1>
Paying on invoice causes major cash flow disruption. Suppliers expect payment within 30 days. But buyers often need more time than that. Get ready for the next step: Biller. The payment solution that advances both sides. 
We pay out every invoice on time. And buyers get to choose Buy Now, Pay Later.  

---
To make the integration process as easy as possible for you, Biller offer a easy to intergrate Magento® 2 Plugin.
Before you start up the installation process, we recommend that you make a backup of your webshop files, as well as the database.

  
### Installation using Composer ###
Magento® 2 use the Composer to manage the module package and the library. Composer is a dependency manager for PHP. Composer declare the libraries your project depends on and it will manage (install/update) them for you.

Check if your server has composer installed by running the following command:
```
composer –v
``` 
If your server doesn’t have composer installed, you can easily install it by using this manual: https://getcomposer.org/doc/00-intro.md

Step-by-step to install the Magento® 2 extension through Composer:

1. Connect to your server running Magento® 2 using SSH or other method (make sure you have access to the command line).
2. Locate your Magento® 2 project root.
3. Install the Magento® 2 extension through composer and wait till it's completed:
```
composer require biller-ai/magento2

``` 
4. Once completed run the Magento® module enable command:
```
bin/magento module:enable Biller_Connect
``` 
5. After that run the Magento® upgrade and clean the caches:
```
php bin/magento setup:upgrade
php bin/magento cache:flush
```
6.  If Magento® is running in production mode you also need to redeploy the static content:
```
php bin/magento setup:static-content:deploy
```
7.  After the installation: Go to your Magento® admin portal and open ‘Stores’ > ‘Configuration’ > ‘Biller’ > .
  
## Development by Magmodules

We are a Dutch Magento® Only Agency dedicated to the development of extensions for Magento and Shopware. All our extensions are coded by our own team and our support team is always there to help you out.

[Visit Magmodules.eu](https://www.magmodules.eu/)


## Links

[Knowledgebase](https://www.magmodules.eu/help/magento2-biller.html)

[Terms and Conditions](https://www.magmodules.eu/terms.html)

[Contact Us](https://www.magmodules.eu/contact-us.html)
