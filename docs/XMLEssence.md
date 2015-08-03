# XML Essence
Extract data from an [XML](http://en.wikipedia.org/wiki/XML) source.

### Example XML data
The following data will be used for all the basic and advanced examples.

```xml
<?xml version="1.0" encoding="UTF-8"?>
<Persons>
    <Person>
        <Name>Anna</Name>
        <Surname>Adams</Surname>
        <Email>anna.adams@example.com</Email>
        <Addresses>
            <Address Type="Home">
                <Name>Rocky Row</Name>
                <Postcode>6181</Postcode>
            </Address>
            <Address Type="Work">
                <Name>Round Valley</Name>
                <Postcode>6781</Postcode>
            </Address>
        </Addresses>
    </Person>
    <Person>
        <Name>Bob</Name>
        <Surname>Brown</Surname>
        <Email>bob.brown@example.com</Email>
        <Addresses>
            <Address Type="Home">
                <Name>Stony Boulevard</Name>
                <Postcode>8276</Postcode>
            </Address>
        </Addresses>
    </Person>
    <Person>
        <Name>Charles</Name>
        <Surname>Cooper</Surname>
        <Email>N/A</Email>
        <Addresses>
            <Address Type="Home">
                <Name>Lazy Fawn Mount</Name>
                <Postcode>9828</Postcode>
            </Address>
            <Address Type="Work">
                <Name>High Zephyr Impasse</Name>
                <Postcode>8918</Postcode>
            </Address>
        </Addresses>
    </Person>
</Persons>
```

## Basic usage
Examples of how to use the class are provided in this document, along with an explanation of available options.

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

use Impensavel\Essence\EssenceException;
use Impensavel\Essence\XMLEssence;

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

} catch (EssenceException $e) {
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
        <!-- data -->
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

### Node/element skipping
Sometimes we need to skip to a specific element if a pre-condition fails.
A reason for this would be that there's no point in storing data from a child node if we didn't save the parent's data.

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
            'type'     => 'string(@Type)',
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

### Storing and retrieving data
In order to keep track of node/element relations, we can store data from one callback and retrieve it from another.

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
            'type'      => 'string(@Type)',
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
Previous values will be overwritten each time the callback returns.

On the map properties, by passing `#<element XPath>` instead of an XPath expression, the stored value registered to that element XPath will be used instead.

An `EssenceException` will be thrown if the XPath is not registered.

## XPath
In order to extract data from an XML, we use XPaths to map the document structure by element and by properties.

### Version
Only XPath 1.0 is supported.

### Element configuration
Configuration keys should always have the *absolute* XPath to the element we want to extract data from.
To extract data from `Person` elements, the configuration should be:
```php
$config = array(
    '/Persons/Person' => array(),
);
```

### Map values
Map values should always be XPath expressions *relative* to the current element, unless when we want to retrieve stored element data.
To get the `Name` property of a `/Persons/Person` element, the configuration should be:
```php
$config = array(
    '/Persons/Person' => array(
        'name' => 'string(Name)',
    ),
);
```

We should always cast the values when mapping element properties, unless there's a special reason to work with a `DOMNodeList` object, instead.

### Documentation
- [Edankert](http://www.edankert.com/xpathfunctions.html)
- [W3Schools](http://www.w3schools.com/xpath/)
