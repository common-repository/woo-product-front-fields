WooCommerce Product Front Fields
================================

The plugin allows to add custom fields to your products.

Description
-----------

The plugin allows to add extra fields to the product that may affect product price. This allows to create products that customers can customize according to their preferences before adding to the cart. For example, specify pizza parameters, such as Size, Toppings (the charge of every topping item can depend on the chosen Size option) or a customized T-Shirt with the Number, Favorite player's name, T-shirt player list, or Own name.

Features
--------
* Supports 8 widgets (field types):
    <ol>
    <li>Text</li>
    <li>Textarea</li>
    <li>Checkbox</li>
    <li>Select</li>
    <li>Radio (Normal or Images)</li>
    <li>Checkboxes (Normal or Images)</li>
    <li>Datepicker</li>
    <li>Slider</li>
    </ol>
* Field profiles (product types) that allows to create products with different fields with one click.
* Product duplication with fields.
* Setting and managing fields for a specific product, including overridding the field default charge and value.
* Products Import by field profiles.

The number of video series that allow to understand how to work with this plugin:

<ol>
    <li>[How to create the fields](https://youtu.be/hiQJDRO92Eg))</li>
    <li>[The ways how to attach fields to the products](https://youtu.be/yH5XNSanKO8)</li>
    <li>[Product form and `Front fields` section](https://youtu.be/yaabj_qr5AU)</li>
    <li>[Settings form](https://youtu.be/kO0J9ZcNEGs)</li>
    <li>[Products Import](https://youtu.be/4fRHKBJ-JoI)</li>
</ol>

For developers
--------------

The plugin has very flexible and extensible set of hooks that allow to create more complex price calculations, customize the fields and create new widgets. Check hooks.api.php file for more information and examples.


Installation
============
Minimum Requirements
--------------------
* WooCommerce 3.0 or greater
* WordPress 4.0 or greater
* PHP version 5.2.4 or greater (PHP 7.2 or greater is recommended)
* MySQL version 5.0 or greater (MySQL 5.6 or greater is recommended)

Manual installation
-------------------
1. Make sure you have the latest version of WooCommerce plugin installed ( 2.2 or above )
2. Unzip and upload contents of the plugin to your /wp-content/plugins/ directory
3. Go to the `Plugins` menu and activate the plugin

Uninstallation
--------------
1. Open your wp-config.php file and add:
define( 'WPF_REMOVE_ALL_DATA', true );
below 
/* That's all, stop editing! Happy blogging. */ 
and save a file.
2. Go to the `Plugins` menu and deactivate the plugin