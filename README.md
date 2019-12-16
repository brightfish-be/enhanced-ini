# enhanced-ini

[![Build Status](https://travis-ci.com/brightfish-be/enhanced-ini.svg?branch=master&style=flat-square)](https://travis-ci.com/brightfish-be/enhanced-ini)

Read  .ini files with enhanced functionality:

## use of a 'default' chapter

* there is a default chapter that can be used to give default key/values for all the other chapters
* the default chapter name is `default` but this can be changed to e.g. `_default` or `def-values`


    [default]
    key1=1
    
    [chapter1]
    # key1 will exist and = 1
    key2=2
        
## {parameter} substitution

* key names can be used as variables in value definition and will be substituted when the value is consulted. 
* The variable syntax is `{key}` by default, but can be changed to e.g. `{$key}` or `[key]`


    [default]
    domain=www.example.com
    
    [chapter1]
    code=4567
    url=https://{domain}}/?code={code}
    # url will be = https://www.example.com/?code=4567
