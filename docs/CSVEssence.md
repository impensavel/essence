# CSVEssence
This class handles data extraction from sources with the [Character Separated Values](http://en.wikipedia.org/wiki/Comma-separated_values) format.

## Usage
The following example shows how to extract data from a CSV.

### Data
```
email,name,surname
john@doe.com,john,doe
jane@doe.com,jane,doe
```

### Implementation
```php
<?php

require 'vendor/autoload.php';

use Impensavel\Essence\CSVEssence;
use Impensavel\Essence\EssenceException;

$config = array(
    'map' => array(
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
The `extract()` method allows us to extract CSV data from a few input types.
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
$input = new SplFileInfo('input.csv')

$essence->extract($input);
```

## Options
The `extract()` method has a few options that can be used to handle different situations.

### start_line
On the example above, the first line of the CSV data is just a header with the column names.
Since it's not actual data, we can skip it by setting the `start_line` option to `1` (line count starts at `0`).

```php
$essence->extract($input, array(
    'start_line' => 1,
));
```

### delimiter, enclosure & escape
Sometimes, CSV data can have a slightly different format, depending on the vendor or person who created the data.
By default, the `delimiter` is set to `,` (comma), `enclosure` is set to `"` (double quote) and `escape` is set to `\` (backslash).

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
Exceptions are thrown by **default** when we try to extract data from an invalid column index.
This might happen because of an error in the map (wrong column index) or bad data (one of the lines in the CSV has less columns).

If it's the second case, we probably want to continue parsing the data, ignoring invalid lines.
```php
$essence->extract($input, array(
    'exceptions' => false,
));
```

### auto_eol
Depending on the [OS](http://en.wikipedia.org/wiki/Operating_system) in which the CSV data was created, line endings might not be properly recognised.
To (try to) solve the issue, the `auto_eol` option should be set to `true`.
```php
$essence->extract($input, array(
    'auto_eol' => true,
));
```

## Extra
Normally, the only data the callback as access to, is the one being extracted. But sometimes, we might need to have access to other data from within the callback. 
To do that, we can pass it in as the 3rd parameter of the method:

```php
$extra = Foo::bar();

$essence->extract($input, array(), $extra);
```