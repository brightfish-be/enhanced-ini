# enhanced.ini 

## features

* [x] implement default section `[default]`
* [x] implement variable substitution `url=http://{domain}/test/`
* [ ] check ini syntax problems (like duplicate chapters)
* [ ] section nesting `[level1.level2.level3]`
* [ ] key nesting `person.name.first=Peter`
* [ ] predefined variables like `{DATE_Ymd}`
* [ ] interpret JSON values
* [ ] write INI files: simple (no default section)
* [ ] write INI files: compact (with default section)


## other similar libraries
* https://ini.unknwon.io/ (golang)
* https://github.com/revel/config (golang)
* https://github.com/chrisdone/ini (Haskell)
* https://github.com/austinhyde/IniParser (PHP)