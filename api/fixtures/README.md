# Fixtures

## Loading

The following command purges entity tables in the database before loading the 
up with fixtures from YAML files.

```shell script
docker-compose exec php bin/console hautelook:fixtures:load -n
```  

Note that memory limits can be exceeded when loading a large set of entities
such as Indicator Values. In that case, use the following command as a 
workaround:

```shell script
docker-compose exec php php -d memory_limit=-1 bin/console hautelook:fixtures:load -n
```

## Notes

### Target Entity
The first line of a fixture file should start with a label, indicating the
target entity, in the form of a fully-qualified class name. 
For example: `App\Entity\Indicator:`.

All the YAML files in this directory will be parsed for loading. Therefore, 
avoid creating files with duplicate content.

### Identifiers
The arbitrary strings used to identify entities in fixtures should be
*unique*. They can only contain alphanumeric characters and underscores and, 
 by convention, they should begin with the entity name in lower-case.

For example the term *Civilians - Injuries* would have `term_civilians_injuries` 
as an identifier. That would be referenced by other entities as a single-quoted 
string as follows: `'@term_civilians_injuries'`.

When instances of an entity are not going to be referenced by others, their
unique identifiers can simply be random strings; for example, GUIDs. Such is 
the case with Indicator Values.   

### Syntax
- Use 4 spaces instead of tabs for indentation and avoid trailing whitespace.
- Ensure that at least a single space is added after the hyphen `-` when  
declaring arrays.
- Strings should be properly escaped according to YAML syntax rules.
- Dates should follow the format specified in the evaluated PHP statement for
the formatter to parse them correctly:
    ```shell script
    date: <(\DateTime::createFromFormat('Y-m-d', '2019-09-30'))>
    ``` 
