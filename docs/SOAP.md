# SOAP Essence
This class inherits from the [XMLEssence](XMLEssence.md) class, making it easier to extract data from [WebService](http://en.wikipedia.org/wiki/Web_service)/[SOAP](http://en.wikipedia.org/wiki/SOAP) sources.

## Usage
Examples of how to use the class are provided in this document, along with an explanation of available options.

### Implementation
```php
<?php

require 'vendor/autoload.php';

use Impensavel\Essence\EssenceException;
use Impensavel\Essence\SOAP;

$elements = array(
    '/soap:Envelope/soap:Body/ConversionRateResponse' => array(
        'map'     => array(
            'rate' => 'string(ns:ConversionRateResult)',
        ),
        'handler' => function ($element, array $properties, &$data) {
            var_dump($properties);
        },
    ),
);

// Web Service Definition Language
$wsdl = 'http://www.webservicex.net/CurrencyConvertor.asmx?WSDL';

// XML namespace
$namespaces = array(
    'ns' => 'http://www.webserviceX.NET/',
);

// SOAP client options
$options = array(
    'soap_version' => SOAP_1_2,
);

try
{
    $essence = new SOAP($elements, $wsdl, $namespaces, $options);

    $input = array(
        'function'  => 'ConversionRate',
        'arguments' => array(
            'FromCurrency' => 'GBP',
            'ToCurrency'   => 'EUR',
        ),
    );

    $essence->extract($input);

} catch (EssenceException $e) {
    // Handle exceptions
}
```

To see what arguments the SOAP client accepts, refer to the [documentation](http://php.net/manual/en/soapclient.soapclient.php).

The [webservicex.net](http://www.webservicex.net) website provides access to dozens of web services. The above code is an implementation to one of them.

## Input
The `extract()` method expects the input to be an `array` containing the SOAP function call to execute, along with the arguments (if any).

```php
$input = array(
    'function'  => 'Foo',
    'arguments' => array(
        'Bar' => 'Baz',
    ),
);

$essence->extract($input);
```

## Options
The options supported by the `extract()` method are the same as the ones in the [XML Essence](XMLEssence.md) class. To know more about them, refer to the [documentation](XMLEssence.md#options).

## User data
By default, the handler only has access to the data being extracted, but sometimes access to other data might be necessary.

To solve this, user data can be passed as a **third** argument to the `extract()` method.

```php
$config = array(
    // ...
);

$data = array(
    // ...
);

$essence->extract($input, $config, $data);
```

>**TIP:** The user data is passed by reference, which means that it can be modified by the handler, if needed.

## Debugging
Using SOAP/WebServices can be tricky, so it's always helpful to have a bit more of information on what's happening.

### Last request
Each time the `extract()` method is executed, a SOAP request is made. To retrieve the SOAP envelope used for the request, use the `lastRequest()` method.

```php
echo $essence->lastRequest();
```

Example output:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://www.webserviceX.NET/">
  <SOAP-ENV:Header/>
  <SOAP-ENV:Body>
    <ns1:ConversionRate>
      <ns1:FromCurrency>GBP</ns1:FromCurrency>
      <ns1:ToCurrency>EUR</ns1:ToCurrency>
    </ns1:ConversionRate>
  </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
```

### Last response
After a request is made, a response follows. In order to get the raw SOAP envelope response, the `lastResponse()` method should be used.

```php
echo $essence->lastResponse();
```

Example output:
```xml
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <soap:Body>
    <ConversionRateResponse xmlns="http://www.webserviceX.NET/">
      <ConversionRateResult>1.3965</ConversionRateResult>
    </ConversionRateResponse>
  </soap:Body>
</soap:Envelope>
```

This method is particularly useful when the element mapping hasn't been done yet and we need to see the document structure we're extracting data from.

### Last response headers
Sometimes we may also need to check the response headers. The `lastResponseHeaders()` method will return an array with all the headers included in the response.

```php
var_dump($essence->lastResponseHeaders());
```

Example output:
```php
array(0) {
}
```

### XPath dump
This method returns an array with all the XPaths and occurrence count of a SOAP response.

```php
<?php

require 'vendor/autoload.php';

use Impensavel\Essence\EssenceException;
use Impensavel\Essence\SOAP;

// Web Service Definition Language
$wsdl = 'http://www.webservicex.net/CurrencyConvertor.asmx?WSDL';

try
{
    $essence = new SOAP(array(), $wsdl);

    $input = array(
        'function'  => 'ConversionRate',
        'arguments' => array(
            'FromCurrency' => 'GBP',
            'ToCurrency'   => 'EUR',
        ),
    );

    $paths = $essence->dump($input);
    
    var_dump($paths);

} catch (EssenceException $e) {
    // Handle exceptions
}
```

The code above will provide the following output:
```php
array(4) {
  ["soap:Envelope"]=>
  int(1)
  ["soap:Envelope/soap:Body"]=>
  int(1)
  ["soap:Envelope/soap:Body/ConversionRateResponse"]=>
  int(1)
  ["soap:Envelope/soap:Body/ConversionRateResponse/ConversionRateResult"]=>
  int(1)
}
```
