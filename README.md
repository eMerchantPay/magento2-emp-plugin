Genesis client for Magento 2 CE
=============================

This is a Payment Module for Magento 2 Community Edition, that gives you the ability to process payments through eMerchantPay's Payment Gateway - Genesis.

Requirements
------------

* Magento 2 Community Edition* 2.x (Tested upto 2.0.2)
* [GenesisPHP v1.4.2](https://github.com/GenesisGateway/genesis_php) - (Integrated in Module)

*Note: this module has been tested only with Magento 2 __Community Edition__, it may not work
as intended with Magento 2 __Enterprise Edition__

Installation (composer)
---------------------
* Install __Composer__ - [Composer Download Instructions](https://getcomposer.org/doc/00-intro.md)

* Install __eMerchantPay Payment Gateway__
    
    * Add eMerchantPay Repository

        ```sh
        $ composer config repositories.emerchantpay git https://github.com/eMerchantPay/magento2-emp-plugin.git
        ``` 

    * Install Payment Module

        ```sh
        $ composer require eMerchantPay/magento2-emp-plugin:1.0.1@stable
        ```

    * Enable Payment Module 
        
        ```sh
        $ php bin/magento module:enable EMerchantPay_Genesis --clear-static-content
        ```

        ```sh
        $ php bin/magento setup:upgrade
        ```

* Login inside the __Admin Panel__ and go to ```Stores``` -> ```Configuration``` -> ```Sales``` -> ```Payment Methods```
* If the Payment Module Panel ```eMerchantPay``` is not visible in the list of available Payment Methods, 
  go to  ```System``` -> ```Cache Management``` and clear Magento Cache by clicking on ```Flush Magento Cache```
* Go back to ```Payment Methods``` and click the button ```Configure``` under the payment method ```eMerchantPay Checkout``` to expand the available settings
* Set ```Enabled``` to ```Yes```, set the correct credentials, select your prefered transaction types and additional settings and click ```Save config```

Installation (manual)
---------------------

* Upload the contents of the folder (excluding ```README.md```) to a new folder ```<root>/app/code/EMerchantPay/Genesis/``` of your Magento 2 installation
* Enable Payment Module 

    ```sh
    $ php bin/magento module:enable EMerchantPay_Genesis --clear-static-content
    ```

    ```sh
    $ php bin/magento setup:upgrade
    ```

* Login inside the __Admin Panel__ and go to ```Stores``` -> ```Configuration``` -> ```Sales``` -> ```Payment Methods```
* If the Payment Module Panel ```eMerchantPay``` is not visible in the list of available Payment Methods, 
  go to  ```System``` -> ```Cache Management``` and clear Magento Cache by clicking on ```Flush Magento Cache```
* Go back to ```Payment Methods``` and click the button ```Configure``` under the payment method ```eMerchantPay Checkout``` to expand the available settings
* Set ```Enabled``` to ```Yes```, set the correct credentials, select your prefered transaction types and additional settings and click ```Save config```

Install GenesisGateway Library
------------

You should follow these steps to install the __GenesisGateway__ Library manual if it has not been installed automatically by Module

* Install __Composer__ 
    [Composer Download Instructions](https://getcomposer.org/doc/00-intro.md)

* Install __GenesisGateway__
    * Add GenesisGateway Repository

        ```sh
        $ composer config repositories.genesisgateway git https://github.com/GenesisGateway/genesis_php.git
        ```

    * Install GenesisGateway Client Library
    
        ```sh
        $ composer require GenesisGateway/genesis_php:1.4.2@stable
        ```

GenesisPHP Requirements
------------

* PHP version 5.3.2 or newer
* PHP Extensions:
    * [BCMath](https://php.net/bcmath)
    * [CURL](https://php.net/curl) (required, only if you use the curl network interface)
    * [Filter](https://php.net/filter)
    * [Hash](https://php.net/hash)
    * [XMLReader](https://php.net/xmlreader)
    * [XMLWriter](https://php.net/xmlwriter)

_Note_: If you have trouble with your credentials or terminal configuration, get in touch with our [support] team

You're now ready to process payments through our gateway.

[support]: mailto:tech-support@emerchantpay.net
