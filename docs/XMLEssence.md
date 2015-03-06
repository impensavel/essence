# XMLEssence
This class handles data extraction from sources with the [Extensible Markup Language](http://en.wikipedia.org/wiki/XML) format.

## Usage
The following example shows how to extract data from a simple XML.

### Data
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

### Implementation
```php
<?php

require 'vendor/autoload.php';

use Impensavel\Essence\XMLEssence;
use Impensavel\Essence\EssenceException;

$config = array(
    'Persons/Person' => array(
        'map' => array(
            'name'    => 'string(Name)',
            'surname' => 'string(Surname)',
            'email'   => 'string(Email)',
        ),
        'callback'   => function ($data) {
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
In order to use the encoding provided by the document being parsed, set the value to `null`.

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
For some XML documents, we need to register one or more namespaces in order to parse the data properly.

```php
$essence->extract($input, array(
    'namespaces' => array(
        'atom' => 'http://www.w3.org/2005/Atom',
    ),
));
```

## Extra
Normally, the only data the callback as access to, is the one being extracted. But sometimes, we might need to have access to other data from within the callback. 
To do that, we can pass it in as the 3rd parameter of the method:

```php
$extra = Foo::bar();

$essence->extract($input, array(), $extra);
```
