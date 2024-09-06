# WooCommerce NanoPay Gateway

**NOTICE: This plugin is currently under active development and testing. Use in production environments is not recommended at this time.**

## Description

The WooCommerce NanoPay Gateway plugin adds NanoPay as a payment method for WooCommerce. This allows customers to pay for their orders using Nano cryptocurrency through the NanoPay service.

## Features

- Easy integration with WooCommerce
- Support for WooCommerce Blocks (for compatible themes)
- Configurable payment gateway title and description
- Custom Nano address or Nano.to username for receiving payments
- Email notifications for successful payments
- Automatic order status updates

## Requirements

- WordPress 5.0 or higher
- WooCommerce 3.0 or higher
- PHP 7.0 or higher

## Installation

1. Download the plugin zip file.
2. Log in to your WordPress admin panel and navigate to Plugins > Add New.
3. Click on the "Upload Plugin" button at the top of the page.
4. Choose the plugin zip file you downloaded and click "Install Now".
5. After the installation is complete, click "Activate Plugin".

Alternatively, you can manually upload the plugin files to your server:

1. Unzip the plugin zip file.
2. Upload the `woocommerce-nanopay-gateway` folder to the `/wp-content/plugins/` directory of your WordPress installation.
3. Activate the plugin through the 'Plugins' menu in WordPress.

## Configuration

1. Go to WooCommerce > Settings > Payments.
2. Click on "NanoPay" to configure the payment method.
3. Enable the payment method by checking the "Enable NanoPay Payment" box.
4. Enter your Nano address or Nano.to username in the "Nano Address or Username" field.
5. (Optional) Enter an email address in the "Notification Email" field to receive payment notifications.
6. Customize the title and description if desired.
7. Click "Save changes" to apply your settings.

## Usage

Once configured, NanoPay will appear as a payment option during the WooCommerce checkout process. Customers can select NanoPay to pay for their orders using Nano cryptocurrency. They will be redirected to a NanoPay interface to complete their payment.

## Support

If you encounter any issues or have questions about the plugin, please create an issue on the plugin's GitHub repository or contact the plugin author.

## Changelog

### 1.6
- Automatic order status updates
  
### 1.5
- Updated to use NanoPay JavaScript library directly
- Removed API key requirement
- Added notification email option
- Improved payment flow

### 1.4
- Initial release of the improved WooCommerce NanoPay Gateway
- Integrated NanoPay API for secure payment processing
- Included both Nano address and API key configuration options

### 1.3

- Added email notifications for successful payments.

### 1.2

- Improved integration with NanoPay API.

### 1.1

- Bug fixes and performance improvements.

### 1.0

- Initial release.


## License

This plugin is released under the GPL v2 or later license.

## Credits

This plugin was developed by mnpezz and is powered by the NanoPay API.

## Disclaimer

This plugin is in active development. While efforts have been made to ensure its functionality, it may contain bugs or incomplete features. Use in production environments is at your own risk. Always backup your site before installing or updating plugins.
