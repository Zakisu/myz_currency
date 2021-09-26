

# myz_currency
Opencart 3.0.x.x Module to synchronize with the latest currency prices
# Installation
You need to setup this setting.
## Database
1. Create Database
2. Import **[myz_currency.sql](https://github.com/Zakisu/myz_currency/blob/main/api/myz_currency.sql "myz_currency.sql")**
3. Setting database config on **[db.php](https://github.com/Zakisu/myz_currency/blob/main/api/db.php "db.php")**
4. Subscribe **Basic Plan** Currency API on https://exchangeratesapi.io/
5. Set  API key on **[cron_currency.php](https://github.com/Zakisu/myz_currency/blob/main/api/cron_currency.php "cron_currency.php")**
## Cron Job
Set up the cronjob for run every day something like:
```  
curl --request GET 'https://[your_domain]/path/cron_currency.php'  
```  
## Module
1. Open [myz_currency.php](https://github.com/Zakisu/myz_currency/blob/main/upload/admin/controller/extension/module/myz_currency.php "myz_currency.php")
2. Search ```// WEB API```, replace with your WEB API
## Installation for Opencart
Just open the installation.txt for more further.
# Demo
URL: [https://lab.myuzee.com/oc37/](https://lab.myuzee.com/oc37/)