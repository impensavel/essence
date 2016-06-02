# CSV Essence
Extract tabular data from [CSV](http://en.wikipedia.org/wiki/Comma-separated_values) sources.

## Usage
An example of how to use the class is provided in this document, along with an explanation of available options.

### Example CSV data
The following data will be used for all the examples below.
```
email,name,surname
john@doe.com,john,doe
jane@doe.com,jane,doe
```

### Map
In order to extract data, a property map must be defined.
Given the simple nature of the CSV format, only **one** map is required.
The map must be an associative `array` with each property name as key and the respective column index as value.

### Data handler
Besides the map, a data handler must be set. It should be of the type `Closure`, with the following signature:

```php
/**
 * @param int   $element    Line number of the current CSV element
 * @param array $properties Associative array with extracted properties
 * @param mixed $data       User data
 */
$handler = function ($element, array $properties, &$data) {
    // implementation
);
```
>**TIP:** User data will be passed by reference

### Implementation
```php
<?php

require 'vendor/autoload.php';

use Impensavel\Essence\CSV;
use Impensavel\Essence\EssenceException;

$config = array(
    'map'     => array(
        'name'    => 1, // 2nd column
        'surname' => 2, // 3rd column
        'email'   => 0, // 1st column
    ),
    'handler' => function ($element, array $properties, &$data) {
        var_dump($properties);
    },
);

try {
    $essence = new CSV($config);
    
    $essence->extract(new SplFileInfo('input.csv'));

} catch (EssenceException $e) {
    // handle exceptions
}
```

## Input
The `extract()` method allows consuming CSV data from a few input types.
Currently supported are `string`, `resource` (normally the result of a `fopen()`) and `SplFileInfo`.

### String
```php
$input = "email,name,surname\njohn@doe.com,john,doe\njane@doe.com,jane,doe\n";

$essence->extract($input);
```

### Resource
```php
$input = fopen('input.csv', 'r');

$essence->extract($input);
```

### SplFileInfo
```php
$input = new SplFileInfo('input.csv');

$essence->extract($input);
```

## Options
The `extract()` method has a few options that can be used to handle different situations.

### start_line
The first line of the CSV data example is just metadata with column names.
Since it's not actual data, we can skip it by setting the `start_line` option to `1`.

```php
$essence->extract($input, array(
    'start_line' => 1,
));
```

>**TIP:** Line and column count always start at `0` (zero).

### delimiter, enclosure & escape
Sometimes, CSV data can have a slightly different format, depending on the vendor or person who created it.
By default, the `delimiter` is set to `,` (comma), the `enclosure` is set to `"` (double quote) and the `escape` is set to `\` (backslash).

To extract the following pipe separated values
```
email|name|surname
john@doe.com|john|doe
jane@doe.com|jane|doe
```

this would be the configuration

```php
$essence->extract($input, array(
    'delimiter' => '|',
));
```

### exceptions
Trying to extract data from an invalid column index, will throw an `EssenceException` by default.
This might happen because of an invalid map (a wrong column index was set) or bad data (some lines in the CSV have less columns).

If the second case happens, the `extract()` method can continue extracting data in a best effort manner, skipping invalid lines.
```php
$essence->extract($input, array(
    'exceptions' => false,
));
```

### auto_eol
Depending on the [OS](http://en.wikipedia.org/wiki/Operating_system) in which the CSV data was created, line endings might not be properly recognised when **reading from a file**.
To (try to) solve the issue, the `auto_eol` option should be set to `true`.
```php
$essence->extract($input, array(
    'auto_eol' => true,
));
```

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

## Troubleshooting

### CSV files exported from Micro$oft Excel fail to extract
This is a [known issue](http://superuser.com/questions/349882/how-to-avoid-double-quotes-when-saving-excel-file-as-unicode) and happens when the CSV file is exported with the Unicode format.

On a UNIX environment, use the following [**sed**](http://en.wikipedia.org/wiki/Sed) one liner to fix the file before trying to extract from it:
```bash
sed 's/.$//; s/^.//; s/""/"/g' input.csv > fixed_input.csv
```

This will remove the **first** and **last** characters of each line (usually double quotes) and will replace **all** double **double quotes**, with a single **double quote**.
