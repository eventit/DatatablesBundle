# Version 2.0
* [BC] changed namespace of datatable and querybuilder classes which were only used in combination with doctrine
* Support Elasticsearch - This is not totally done yet. It is just a kind of way to use the elasticabundle with this datatablesbundle. Primarly to use this to visualize tables and simple filtering
* Extensions are changed to be quicker adding a new extension to the bundle
* Added fixed Header / Footer Extension for Datatable
* Refactored stuff to be more clean code
* Dropped support for PHP 7.2

# Version 1.3

* Dropped support for PHP 7.1
* Dropped support for Symfony 3.4 and <=4.3
* Support for PHP 8
* Support for Symfony 6

# Version 1.1.1

* Bugfix: DateRangeFilter overwrites other filter (#803)

# Version 1.1.0

* Dropped support for PHP 5 and PHP 7.0. (#850)
* Feature: Unique name to allow a datatable multiple times on the same page
* Feature: Added the RowGroup extension (#769)
* Bugfix: fixed Basque, Polish, Hebrew, Latvian and Russian translations (#760, #844, #846)
* Other bugfixes
