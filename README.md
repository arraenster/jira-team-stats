# jira-team-stats
Team Statistics via Jira API. Calculates stats in some specific way.

Based on https://github.com/chobie/jira-api-restclient
Uses PHP Phalcon Framework.

##Possible routes

* `workload/1/2017` - Workload in jira-points by developers by each project calculated per month
* `sprint/154` - Get sprint info by its id calculated by developers.
* `team/2017` - team report by year.

* `generate/license` - Generates random license file based on pem-keys on `config` folder. Simple action for testing licenses.

[Vladyslav Semerenko](mailto:vladyslav.semerenko@gmail.com)