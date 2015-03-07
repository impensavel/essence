# XMLEssence
This class allows us to extract data in the [XML](http://en.wikipedia.org/wiki/XML) format.

## Basic usage
An example of how to use the class is provided in this document, along with an explanation of available options.

### Example XML data
The following data will be used for all the basic examples below.

```xml
<?xml version="1.0" encoding="UTF-8"?>
<Persons>
    <Person>
        <Name>John</Name>
        <Surname>Doe</Surname>
        <Email>john@doe.com</Email>
    </Person>
    <Person>
        <Name>Jane</Name>
        <Surname>Doe</Surname>
        <Email>jane@doe.com</Email>
    </Person>
</Persons>
```

### Elements
In order to extract data and given the more complex nature of the XML format, **at least** one map/callback pair is required.

Each element configuration should be an associative `array`, with the *absolute* XPath of the element as key and an `array` containing the map/callback pair as value.
```php
'/xpath' => array(
    'map'      => array(),
    'callback' => function ($data) {},
),
```

### Map
The map must be an associative `array` with each property name as key and the respective *relative* XPath as value.

### Callback
The callback should be an anonymous function (`Closure`) which accepts an `array` argument with the following structure:
```php
array(
    'properties' => array(),  // associative array with extracted properties
    'extra'      => null,     // extra data passed to the extract() method
    'element'    => '/xpath', // absolute XPath of the current XML element
);
```

### Implementation
```php
<?php

require 'vendor/autoload.php';

use Impensavel\Essence\XMLEssence;
use Impensavel\Essence\EssenceException;

$config = array(
    '/Persons/Person' => array(
        'map'      => array(
            'name'    => 'string(Name)',
            'surname' => 'string(Surname)',
            'email'   => 'string(Email)',
        ),
        'callback' => function ($data) {
            var_dump($data);
        },
    ),
);

try
{
    $essence = new XMLEssence($config);
    
    $essence->extract(new SplFileInfo('input.xml'));

} catch(EssenceException $e) {
    // handle exceptions
}
```

## Input types
The `extract()` method allows us to consume XML data from a few input types.
Currently supported are `string`, `resource` (normally a result of a `fopen()`) and `SplFileInfo`.

### String
```php
$input = <<< EOT
<?xml version="1.0" encoding="UTF-8"?>
<Persons>
    <Person>
        <Name>John</Name>
        <Surname>Doe</Surname>
        <Email>john@doe.com</Email>
    </Person>
    <Person>
        <Name>Jane</Name>
        <Surname>Doe</Surname>
        <Email>jane@doe.com</Email>
    </Person>
</Persons>
EOT;

$essence->extract($input);
```

### Resource
```php
$input = fopen('input.xml', 'r');

$essence->extract($input);
```

### SplFileInfo
```php
$input = new SplFileInfo('input.xml')

$essence->extract($input);
```

## Options
The `extract()` method has a few options that can be used to handle different situations.

### encoding
The `encoding` option is set to `UTF-8` by default and it should remain so in normal circumstances. 
In order to use the encoding defined in the document, set the value to `null`.

```php
$essence->extract($input, array(
    'encoding' => null,
));
```

### options
By default, the `options` value is set to `LIBXML_PARSEHUGE`. If the document being parsed requires extra configurations, do it so by passing the required bitmask.

If we needed to load an external subset provided by the document, we would do:
```php
$essence->extract($input, array(
    'options' => LIBXML_PARSEHUGE|LIBXML_DTDLOAD,
));
```

Refer to the [documentation](http://php.net/manual/en/libxml.constants.php) for the complete list of supported `LIBXML_*` constants.

### namespaces
For some XML documents, we might need to register a namespace in order to parse the data properly.

```php
$essence->extract($input, array(
    'namespaces' => array(
        'atom' => 'http://www.w3.org/2005/Atom',
    ),
));
```

## Extra
Normally, the only data the callback has access to, is the one being extracted. But sometimes, we might need to have access to other data from within the callback. 
To do that, we can pass it in as the 3rd parameter of the method:

```php
$extra = Foo::bar();

$essence->extract($input, array(), $extra);
```

## Advanced usage
In this section we will cover two advanced use cases.

### Example XML data
The following data will be used for all the advanced examples below.

```xml
<?xml version="1.0" encoding="UTF-8"?>
<Persons>
    <Person>
        <Name>John</Name>
        <Surname>Doe</Surname>
        <Email>john@doe.com</Email>
        <Addresses>
            <Address>
                <Name>Foo Street</Name>
                <Postcode>A12-3BC</Postcode>
            </Address>
        </Addresses>
    </Person>
    <Person>
        <Name>Jane</Name>
        <Surname>Doe</Surname>
        <Email>jane@doe.com</Email>
        <Addresses>
            <Address>
                <Name>Bar Street</Name>
                <Postcode>X89-0YZ</Postcode>
            </Address>
        </Addresses>
    </Person>
    <Person>
        <Name>Bob</Name>
        <Surname></Surname>
        <Email></Email>
        <Addresses>
            <Address>
                <Name>Baz Street</Name>
                <Postcode></Postcode>
            </Address>
        </Addresses>
    </Person>
</Persons>
```

### Node/element skipping
Sometimes we need to skip to a specific element if a pre-condition fails.
A reason for this would be that there's no point in storing data from a child node is we didn't save the parent's data.

On the XML above, the third `Person` element has some missing data and we only want to extract valid/complete data from the set.

The following configuration takes care of that:
```php
$config = array(
    '/Persons/Person' => array(
        'map'      => array(
            'name'    => 'string(Name)',
            'surname' => 'string(Surname)',
            'email'   => 'string(Email)',
        ),
        'callback' => function ($data) {
            // skip to the next /Persons/Person element if the email is invalid
            if (filter_var($data['properties']['email'], FILTER_VALIDATE_EMAIL) === false) {
                return '/Persons/Person';
            }
            
            // store data
        },
    ),
    '/Persons/Person/Addresses/Address' => array(
        'map'      => array(
            'address'  => 'string(Name)',
            'postcode' => 'string(Postcode)',
        ),
        'callback' => function ($data) {
            // store data
        },
    ),
);

```
In other words, the *absolute* XPath of the element we want to skip to, must be returned from the callback we're in.

### Storing and retrieving element data
In order to keep track of relations between callbacks, we can store data from one and retrieve it from another.

```php
$config = array(
    '/Persons/Person' => array(
        'map'      => array(
            'name'    => 'string(Name)',
            'surname' => 'string(Surname)',
            'email'   => 'string(Email)',
        ),
        'callback' => function ($data) {
            // store data using a Lavavel Person model
            $person = Person::create($data['properties']);
            
            // return the last inserted id
            return $person->id;
        },
    ),
    '/Persons/Person/Addresses/Address' => array(
        'map'      => array(
            // use the last inserted Person id set from 
            // the other callback to make the relation
            'person_id' => '#/Persons/Person',
            'address'   => 'string(Name)',
            'postcode'  => 'string(Postcode)',
        ),
        'callback' => function ($data) {
            // store data using a Laravel Address model
            Address::create($data['properties']);
        },
    ),
);

```

When a callback returns, any value than cannot be mapped to an *absolute* XPath (otherwise we would do a skip), will be stored.
Previous values will be overwritten each time the callback is executed.

On map properties, by passing `#<element XPath>` instead of an XPath expression, the stored value registered to that element XPath will be used instead.

An `EssenceException` will be thrown if the XPath is not registered.
