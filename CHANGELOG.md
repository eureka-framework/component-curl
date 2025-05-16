# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

```
## [tag] - YYYY-MM-DD
[tag]: https://github.com/eureka-framework/component-curl/compare/2.1.0...master
### Changed
 - Change 1
### Added
 - Added 1
### Removed
 - Remove 1
```

----
## [3.1.0] - 2025-05-16
[3.1.0]: https://github.com/eureka-framework/component-curl/compare/3.0.0...3.1.0
### Added
- Support PHP 8.4
### Changed
- Update tests
- Update makefile
- Update CI workflow

## [3.0.0] - 2024-08-22
[3.0.0]: https://github.com/eureka-framework/component-curl/compare/2.2.0...3.0.0
### Changed
- Support PHP 8.3
- Now use `\CurlHandle` as resource connection
- Fix some code style
- Replace PHPCS by php-cs-fixer
- Move unit test to subdirectory `unit/`
- Dev dependencies update
- Update CI workflow
- Update Makefile & Readme
### Remove
- Drop support of PHP 7.4 & PHP 8.0

----

## [2.2.0] - 2023-03-10
### Changed
- Support PHP 8.2

## [2.1.0] - 2022-11-13
### Added
 - Add phpstan (dev dependency)
### Removed
 - PHP Compatibility (dev dependency)
### Changed
 - Update makefile with the latest improvements
 - Now specify clearly supported php versions
 - composer.json update
 - Some minor improvements in comment & code organisation
 - Improve CI GitHub actions

## [2.0.1] - 2020-11-01
### Changed
 - Fix property type in class curl

## [2.0.0] - 2020-10-29
### Changed
 - Require php 7.4+
 - Tests
 - Upgrade phpcodesniffer to v0.7 for composer 2.0

----

## [1.0.0] - 2020-06-01
### Added
 - Class wrapper for php native curl function
 - PSR-18 Http Client implementation
