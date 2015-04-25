# SOAPEssence
This class extends the [XMLEssence](XMLEssence.md) in order to extract data from [WebService](http://en.wikipedia.org/wiki/Web_service)/[SOAP](http://en.wikipedia.org/wiki/SOAP) sources.


## Usage
Examples of how to use the class are provided in this document, along with an explanation of available options.

### Implementation
```php
<?php

require 'vendor/autoload.php';

use Impensavel\Essence\EssenceException;
use Impensavel\Essence\SOAPEssence;

$elements = array(
    '/soap:Envelope/soap:Body/ConversionRateResponse' => array(
        'map'      => array(
            'rate' => 'string(ns:ConversionRateResult)',
        ),
        'callback' => function ($data) {
            var_dump($data);
        },
    ),
);

$options = array(
    // SOAP client options
);

try
{
    $essence = new SOAPEssence($elements, 'http://www.webservicex.net/CurrencyConvertor.asmx?WSDL', $options);

    $input = array(
        'function'  => 'ConversionRate',
        'arguments' => array(
            'FromCurrency' => 'GBP',
            'ToCurrency'   => 'EUR',
        ),
    );

    $essence->extract($input, array(
        'namespaces' => array(
            'ns' => 'http://www.webserviceX.NET/',
        ),
    ));

} catch (EssenceException $e) {
    // handle exceptions
}
```

To see what options the SOAP client accepts, refer to the [documentation](http://php.net/manual/en/soapclient.soapclient.php).

The [webservicex.net](http://www.webservicex.net) website provides access to dozens of web services. The above code is an implementation to one of them.

## Input
The `extract()` method expects the input to be an array with the SOAP function call to execute, along with the arguments (if any).

```php
$input = array(
    'function'  => 'Foo',
    'arguments' => array(
        'Bar' => 'baz',
    ),
);

$essence->extract($input);
```

## Options
The options supported by the `extract()` method are the same as the ones in the [XML Essence](XMLEssence.md) class. To know more about it, refer to the [documentation](XMLEssence.md#options).

## Extra
Normally, the only data the callback has access to, is the one being extracted. But sometimes, we might need to have access to other data from within the callback. 
To do that, we can pass it in as the 3rd parameter of the method:

```php
$extra = Foo::bar();

$essence->extract($input, array(), $extra);
```

## Debugging
Sometimes using WebService/SOAP can be a bit tricky, so having a bit more information of what's going on is always helpful.

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

This method is pacticularly useful when the element mapping hasn't been done yet and we need to see the document structure we're extracting data from.

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
