# XML Essence
Extract data from [XML](http://en.wikipedia.org/wiki/XML) sources.

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
In order to extract data and given the more complex nature of the XML format, **at least** one map/data handler pair is required.

Each element configuration must be an associative `array`, with the *absolute* XPath of the element as key and an `array` containing the map/data handler pair as value.
```php
'/xpath' => array(
    'map'     => array(
        // ...
    ),
    'handler' => function ($element, array $properties, &$data) {
        // ...
    },
),
```

### Map
The map must be an associative `array` with each property name as key and the respective *relative* XPath as value.

### Data handler
The data handler should be of the type `Closure` and have the following signature:

```php
/**
 * @param string $element    Absolute XPath of the current XML element
 * @param array  $properties Associative array with extracted properties
 * @param mixed  $data       User data
 */
$handler = function ($element, array $properties, &$data) {
    // implementation
);
```
>**TIP:** User data will be passed by reference

### Namespaces
For some XML documents, a namespace needs to be registered in order to parse the data properly.
```php
$namespaces = array(
    'atom' => 'http://www.w3.org/2005/Atom',
);
```

### Implementation
```php
<?php

require 'vendor/autoload.php';

use Impensavel\Essence\EssenceException;
use Impensavel\Essence\XML;

$config = array(
    '/Persons/Person' => array(
        'map'     => array(
            'name'    => 'string(Name)',
            'surname' => 'string(Surname)',
            'email'   => 'string(Email)',
        ),
        'handler' => function ($element, array $properties, &$data) {
            var_dump($properties);
        },
    ),
);

$namespaces = array();

try
{
    $essence = new XML($config, $namespaces);

    $essence->extract(new SplFileInfo('input.xml'));

} catch (EssenceException $e) {
    // handle exceptions
}
```

## Input
The `extract()` method allows consuming XML data from a few input types.
Currently supported are `string`, `resource` (normally the result of a `fopen()`) and `SplFileInfo`.

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
$input = new SplFileInfo('input.xml');

$essence->extract($input);
```

## Options
The `extract()` method has a few options that can be used to handle different situations.

### encoding
The `encoding` option is set to `UTF-8` by default and it should remain so in normal circumstances. 
In order to use the encoding defined in the document, set the value to `null` or to another encoding when appropriate.

```php
$essence->extract($input, array(
    'encoding' => 'ISO-8859-1',
));
```

### options
By default, the `options` value is set to `LIBXML_PARSEHUGE`.

For extra parsing configurations, like loading an external subset, use a bitmask.
```php
$essence->extract($input, array(
    'options' => LIBXML_PARSEHUGE|LIBXML_DTDLOAD,
));
```

Refer to the [documentation](http://php.net/manual/en/libxml.constants.php) for the complete list of supported `LIBXML_*` constants.

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

## Advanced usage
In this section we will cover two advanced use cases.

### Node/element skipping
Sometimes, it might be necessary to skip to a specific element if a pre-condition fails.
A reason for this would be that there's no point in storing data from a child node if the parent data wasn't saved.

On the XML above, the third `Person` element has some missing data and only valid/complete data from the set should be extracted.

The following configuration takes care of that:
```php
$config = array(
    '/Persons/Person' => array(
        'map'     => array(
            'name'    => 'string(Name)',
            'surname' => 'string(Surname)',
            'email'   => 'string(Email)',
        ),
        'handler' => function ($element, array $properties, &$data) {
            // skip to the next /Persons/Person element if the email is invalid
            if (filter_var($properties['email'], FILTER_VALIDATE_EMAIL) === false) {
                return '/Persons/Person';
            }
            
            // do something with the data, otherwise
        },
    ),
    '/Persons/Person/Addresses/Address' => array(
        'map'     => array(
            'type'     => 'string(@Type)',
            'address'  => 'string(Name)',
            'postcode' => 'string(Postcode)',
        ),
        'handler' => function ($element, array $properties, &$data) {
            // do something with the data
        },
    ),
);

```
In other words, the *absolute* XPath of the element we want to skip to, must be returned from the handler we're in.

### Storing and retrieving data
In order to keep track of node/element relations, we can store data from one handler and retrieve it from another.

```php
$config = array(
    '/Persons/Person' => array(
        'map'     => array(
            'name'    => 'string(Name)',
            'surname' => 'string(Surname)',
            'email'   => 'string(Email)',
        ),
        'handler' => function ($element, array $properties, &$data) {
            // store data using a Laravel Person model
            $person = Person::create($properties);
            
            // return the last inserted id
            return $person->id;
        },
    ),
    '/Persons/Person/Addresses/Address' => array(
        'map'     => array(
            // use the last inserted Person id set from 
            // the other handler to make the relation
            'person_id' => '#/Persons/Person',
            'type'      => 'string(@Type)',
            'address'   => 'string(Name)',
            'postcode'  => 'string(Postcode)',
        ),
        'handler' => function ($element, array $properties, &$data) {
            // store data using a Laravel Address model
            Address::create($properties);
        },
    ),
);

```

When a handler returns, any value than cannot be mapped to an *absolute* XPath (otherwise it would skip), will be stored.
Previous values will be overwritten each time the handler returns.

By prefixing a `#` to the _absolute_ XPath of a mapped element (e.g. `#/Persons/Person`) on a map property value, the stored value registered to that element XPath will be used instead.

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
    '/Persons/Person' => array(
        // ...
    ),
);
```

### Map values
Map values should always be XPath expressions *relative* to the current element, unless when we want to retrieve stored element data.
To get the `Name` property of a `/Persons/Person` element, the configuration should be:
```php
$config = array(
    '/Persons/Person' => array(
        'map' => array(
            'name' => 'string(Name)',
        ),

        // data handler
    ),
);
```

Values should be cast to a type when mapping element properties, unless there's a reason to work with a `DOMNodeList`, instead.

### DOMNodeLists
Sometimes it may be easier to have a `DOMNodeList` and work with it, instead of having to set a new element map and data handler.
Since version `2.1.0`, a helper method has been added to convert `DOMNodeList` objects into `array` types.

#### DOMNodeListToArray
This `static` method converts a `DOMNodeList` object into an indexed `array` (by default), or to an associative one when the **second** argument is `true`.

By default, node attributes are not included in the `array`. To include them, set the value of the **third** argument to `true`.

```php
<?php

require 'vendor/autoload.php';

use Impensavel\Essence\EssenceException;
use Impensavel\Essence\XML;

$config = array(
    '/Persons/Person' => array(
        'map'     => array(
            'name'      => 'string(Name)',
            'surname'   => 'string(Surname)',
            'email'     => 'string(Email)',
            'addresses' => 'Addresses',
        ),
        'handler' => function ($element, array $properties, &$data) {
            // return an associative array
            $associative = false;
            
            // include node attributes
            $attributes = true;

            foreach ($properties as $name => $value) {
                if ($value instanceof DOMNodeList) {
                    $properties[$name] = XML::DOMNodeListToArray($value, $associative, $attributes);
                }
            }

            var_dump($properties);
        },
    ),
);

try
{
    $essence = new XML($config);

    $essence->extract(new SplFileInfo('input.xml'));

} catch (EssenceException $e) {
    // handle exceptions
}
```

Indexed `array` with node attributes (`@` key) for the `addresses` element:

```php
array(4) {
  ["name"]=>
  string(4) "Anna"
  ["surname"]=>
  string(5) "Adams"
  ["email"]=>
  string(22) "anna.adams@example.com"
  ["addresses"]=>
  array(1) {
    [0]=>
    array(2) {
      [0]=>
      array(3) {
        ["@"]=>
        array(1) {
          ["Type"]=>
          string(4) "Home"
        }
        [0]=>
        string(9) "Rocky Row"
        [1]=>
        string(4) "6181"
      }
      [1]=>
      array(3) {
        ["@"]=>
        array(1) {
          ["Type"]=>
          string(4) "Work"
        }
        [0]=>
        string(12) "Round Valley"
        [1]=>
        string(4) "6781"
      }
    }
  }
}
```

Associative `array` with node attributes (`@` key) for the `addresses` element:
```php
array(4) {
  ["name"]=>
  string(4) "Anna"
  ["surname"]=>
  string(5) "Adams"
  ["email"]=>
  string(22) "anna.adams@example.com"
  ["addresses"]=>
  array(1) {
    [0]=>
    array(1) {
      ["Address"]=>
      array(2) {
        [0]=>
        array(3) {
          ["@"]=>
          array(1) {
            ["Type"]=>
            string(4) "Home"
          }
          ["Name"]=>
          array(1) {
            [0]=>
            string(9) "Rocky Row"
          }
          ["Postcode"]=>
          array(1) {
            [0]=>
            string(4) "6181"
          }
        }
        [1]=>
        array(3) {
          ["@"]=>
          array(1) {
            ["Type"]=>
            string(4) "Work"
          }
          ["Name"]=>
          array(1) {
            [0]=>
            string(12) "Round Valley"
          }
          ["Postcode"]=>
          array(1) {
            [0]=>
            string(4) "6781"
          }
        }
      }
    }
  }
}
```

### Other useful documentation
- [Edankert](http://www.edankert.com/xpathfunctions.html)
- [W3Schools](http://www.w3schools.com/xpath/)
