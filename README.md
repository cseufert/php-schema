php-schema
==========

SQL Schema Updating Tool (using PHP Doc)

Ability to Document MySQL Schema using PHP Doc Directives

Syntax
`@dbTable [table name]`
`@dbColumn [column name]|[column type]|[Allow Null YES/NO]|[Default Value]|[Extra Info eg auto_increment]`
`@dbIndex [index name]|[index cols [,index col2]]|[Not Unique 0/1]|[Index Size]`

Example
```php
<?php
/**
 * Class to Represent a Movie
 * 
 * @dbTable Movie
 * @dbColumn movieid|int(64)|NO|NULL|auto_increment
 * @dbIndex PRIMARY|id
 * @dbColumn moviename|varchar(250)|NO||
 * @dbIndex moviename|moviename|0|10
 * @dbColumn moviecatid|int(64)|NO|0|
 */
 class movie { ... }
```
