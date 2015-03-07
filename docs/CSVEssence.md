# CSVEssence
This class allows us to extract tabular data in the [CSV](http://en.wikipedia.org/wiki/Comma-separated_values) format.

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

### Callback
Besides the map, a callback must also be set. It should be an anonymous function (`Closure`) which accepts an `array` argument with the following structure:
```php
array(
    'properties' => array(), // associative array with extracted properties
    'extra'      => null,    // extra data passed to the extract() method
    'line'       => 0,       // line number of the current CSV element
);
```

### Implementation
```php
<?php

require 'vendor/autoload.php';

use Impensavel\Essence\CSVEssence;
use Impensavel\Essence\EssenceException;

$config = array(
    'map'      => array(
        'name'    => 1, // 2nd column
        'surname' => 2, // 3rd column
        'email'   => 0, // 1st column
    ),
    'callback' => function ($data) {
        var_dump($data);
    },
);

try {
    $essence = new CSVEssence($config);
    
    $essence->extract(new SplFileInfo('input.csv'));

} catch(EssenceException $e) {
    // handle exceptions
}
```


## Input types
The `extract()` method allows us to consume CSV data from a few input types.
Currently supported are `string`, `resource` (normally a result of a `fopen()`) and `SplFileInfo`.

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

Line and column count always starts at `0` (zero).

### delimiter, enclosure & escape
Sometimes, CSV data can have a slightly different format, depending on the vendor or person who created the data.
By default, the `delimiter` is set to `,` (comma), the `enclosure` is set to `"` (double quote) and the `escape` is set to `\` (backslash).

So, to extract the following data
```
email|name|surname
john@doe.com|john|doe
jane@doe.com|jane|doe
```

we would have to do

```php
$essence->extract($input, array(
    'delimiter' => '|',
));
```

### exceptions
An `EssenceException` is thrown by **default** when we try to extract data from an invalid column index.
This might happen because of an invalid map (a wrong column index was set) or bad data (some lines in the CSV have less columns).

If it's the second case, we probably want to continue extracting the data, skipping invalid lines.
```php
$essence->extract($input, array(
    'exceptions' => false,
));
```

### auto_eol
Depending on the [OS](http://en.wikipedia.org/wiki/Operating_system) in which the CSV data was created, line endings might not be properly recognised.
To (try to) solve the issue, set the `auto_eol` option to `true`.
```php
$essence->extract($input, array(
    'auto_eol' => true,
));
```

## Extra
Normally, the only data the callback has access to, is the one being extracted. But sometimes, we might need to have access to other data from within the callback. 
To do that, we can pass it as the 3rd parameter of the `extract()` method:

```php
$extra = Foo::bar();

$essence->extract($input, array(), $extra);
```

## Troubleshooting

### CSV files exported from Micro$oft Excel fail to extract
This is a [known issue](http://superuser.com/questions/349882/how-to-avoid-double-quotes-when-saving-excel-file-as-unicode) and happens when the CSV file is exported with the Unicode format.

Use the following **sed** one liner to fix the file before trying to extract from it:
```bash
sed 's/.$//; s/^.//; s/""/"/g' input.csv > fixed_input.csv
```

This will remove the **first** and **last** characters of each line (usually double quotes) and will replace **all** double **double quotes**, with a single **double quote**.
