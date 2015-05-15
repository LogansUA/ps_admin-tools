# Admin tools
Module for PrestaShop CMS which provides tools such as [CLI](https://github.com/Myrkotyn/ps_CLI) and [Migrations](http://en.wikipedia.org/wiki/Data_migration)

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/LogansUA/ps_admin-tools/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/LogansUA/ps_admin-tools/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/LogansUA/ps_admin-tools/badges/build.png?b=master)](https://scrutinizer-ci.com/g/LogansUA/ps_admin-tools/build-status/master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/1f65466f-45ad-44ee-9a83-fb5c47abb819/mini.png)](https://insight.sensiolabs.com/projects/1f65466f-45ad-44ee-9a83-fb5c47abb819)

## Installation
* Download latest version from git
```
git clone https://github.com/LogansUA/ps_admin-tools.git
```
* Move module dir (`ps_admin-tools`) to modules folder of your shop
```
mv ps_admin-tools/ YourShop/modules/admintools
```
* Install module from your back-office, in the "Modules" tab
> [How to install/Uninstall modules in PrestaShop](http://prestaddon.com/tutorials/23-how-to-installuninstall-modules-in-prestashop.html)
* Dont forget to delete `class_index.php` file in `You-Project/cache`

## Rollbar
Plugin provides errors and exceptions detecting in your application and reports them to Rollbar for alerts, reporting, and analysis.

To use that you should:
* Register project on [Rollbar](https://rollbar.com/)
* Get access token for your project
* Go to module settings from your back-office
* Enable Rollbar service
* Fill access token field
* Press submit to connect log reports with Rollbar

## Migrations
* To generate migration go to module settings from your back-office and press "Generate migration"
* To execute migration go to module settings from your back-office and press "Migrate"

## CLI
|  Command  |       Arguments      |         Description         |                Usage               |
|:---------:|:--------------------:|:---------------------------:|:----------------------------------:|
|   cache   |                      |         remove cache        |           ./console cache          |
|   domain  |      domainname      |      change site domain     |     ./console domain someDomain    |
|  addhook  |       hookname       |       add hook to site      |     ./console addhook myNewHook    |
|  linkhook | modulename, hookname |    link module with hook    | ./console linkhook someModule hook |
| migration |        action        | generate/migrate migrations |    ./console migration generate    |

## License
Code released under [the MIT license](https://github.com/LogansUA/ps_admin-tools/blob/master/LICENSE).
