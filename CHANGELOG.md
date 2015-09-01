## 2.1.1 - 2015-08-31
### Changed
- `DOMNodeListToArray()` doesn't return node attributes by default anymore

### Fixed
- A `DOMText` should only be skipped when it contains whitespace

## 2.1.0 - 2015-08-31
### Added
- Implemented the `DOMNodeListToArray()` helper method in `Impensavel\XMLEssence`
- Added documentation for the `DOMNodeListToArray()` helper method

## 2.0.1 - 2015-08-22
### Changed
- Change the visibility of properties/methods from private to protected

## 2.0.0 - 2015-08-22
### Changed
- The third argument of the `extract()` method is now passed by reference
- The data handler (previously referred as callback) `Closure` signature now accepts three arguments instead of one
- Updated documentation to reflect major changes to the classes

## 1.2.0 - 2015-08-07
### Changed
- XML namespaces are now registered in the `Impensavel\SOAPEssence` and `Impensavel\XMLEssence` constructors 
- The documentation has been updated to reflect the above changes

## 1.1.1 - 2015-04-29
### Added
- `.gitattributes` file added

### Changed
- `Impensavel\SOAPEssence` implementation example in the documentation

## 1.1.0 - 2015-04-25
### Added
- `Impensavel\SOAPEssence` class for extracting data from WebServices/SOAP sources
- PHP 7 added to the tests
- Needed PHP extensions are now in the `require` section of `composer.json`

### Changed
- Empty element maps are now allowed

## 1.0.3 - 2015-03-09
### Added
- Increased CSV/XML test coverage (including invalid XML)

### Changed
- The way errors are handled in the `provision()` method from `Impensavel\XMLEssence` 

## 1.0.2 - 2015-03-09
### Added
- Increased XML test coverage
- Additional documentation

### Fixed
- Suppress warning so that exceptions can be thrown

## 1.0.1 - 2015-03-08
### Added
- Increased CSV test coverage

### Fixed
- Check the that the `resource` type is a `stream` before trying to do anything with it

## 1.0.0 - 2015-03-07
### Added
- Initial stable version
