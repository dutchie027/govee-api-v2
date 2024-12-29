[![Latest Stable Version](https://poser.pugx.org/dutchie027/govee-api-v2/v)](https://packagist.org/packages/dutchie027/govee-api-v2) [![Total Downloads](https://poser.pugx.org/dutchie027/govee-api-v2/downloads)](https://packagist.org/packages/dutchie027/govee-api-v2) [![Latest Unstable Version](https://poser.pugx.org/dutchie027/govee-api-v2/v/unstable)](https://packagist.org/packages/dutchie027/govee-api-v2) [![License](https://poser.pugx.org/dutchie027/govee-api-v2/license)](https://packagist.org/packages/dutchie027/govee-api-v2) [![PHP Version Require](https://poser.pugx.org/dutchie027/govee-api-v2/require/php)](https://packagist.org/packages/dutchie027/govee-api-v2)

# Govee PHP API v2
This is a rewrite of the Govee API I wrote a while back. Govee changed the API to be more robust, so I am rewriting the code to accommodate.

## WORK IN PROGRESS
NOTE: This is still a work in progress. As I commit more to it, it will become more stable, but for now it's not fully baked.

## Requirements

* PHP >8

## Installation

You can install the package using the [Composer](https://getcomposer.org/) package manager. You can install it by running this command in your project root:

```sh
composer require dutchie027/govee-api-v2
```

## Basic Usage

### Set up the Environment Variables
Rename `.env.sample` to `.env` and set the variables accordingly.

### Instantiate the client

To use any of the Govee API functions, you first need a connection reference. The connection refrence can then be fed to either the Lights library or the Plugs library, or even both if you have both Govee Lights and Plugs.

Using the full name:
```php
// Ensure we have the composer libraries
require_once ('vendor/autoload.php');

// Instantiate & Connect
$govee = new dutchie027\GoveeApiV2\Connect();
```

Using a namespace:
```php
// Ensure we have the composer libraries
require_once ('vendor/autoload.php');

// Namespace
use dutchie027\GoveeApiV2\Connect;

// Instantiate with defaults
$govee = new Connect();
```

### Get Device Count

```php
print $govee->getDeviceCount();
```

### Get All Devices

```php
$array = $govee->getDeviceList();
```

NOTE: This function takes a boolean parameter. If the parameter is set to true it will return an array based on the Govee API. If it's set to false, it returns raw JSON.


### Get An Array of All Callable MAC Addresses

```php
$macArray = $govee->getDeviceMACArray();
```

#### MAC Return Array

```php
Array
(
    [0] => A9:E9:0A:04:AD:CD:12:34
    [1] => FA:8F:50:B2:AD:A7:00:12
    [2] => E0:94:41:AC:62:13:56:78
)
```

### Get An Array of All Device Names

```php
$nameArray = $govee->getDeviceNameArray();
```

#### Device Name Return Array

```php
Array
(
    [0] => My-Living-Room
    [1] => Hallway
    [2] => Fire-House
)
```

## Contributing

If you're having problems, spot a bug, or have a feature suggestion, [file an issue](https://github.com/dutchie027/govee-api-v2/issues). If you want, feel free to fork the package and make a pull request. This is a work in progresss as I get more info and the Govee API grows.
