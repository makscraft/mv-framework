<?php
/**
 * MV - content management framework for developing internet sites and applications.
 * 
 * Initial settings for setup of the application.
 * If the project uses .env file, these settings will be overriden by the values from .env file. 
 * These values go to Registry object to get the settings from any part of the application.
 * Any value can be taken through Registry :: get('name') method.
 */
 
$mvSetupSettings = [

//Current environment 'production' - logs all errors into /log/ folder, 
//'development' - displays all possible errors on the screen.
//You can use APP_ENV setting in .env file instead.
'Mode' => 'development',

//Current build of the application, increase this value to drop cache in production environment.
'Build' => 1,

//Set true to display debug panel at the botton of the screen.
'DebugPanel' => true,

//After increasing Buld number, during this time MV will check media files update times to refresh the cache.
'CacheFilesCheckTime' => 3600,

//Database parameters
//You can use DATABASE_ settings in .env file instead.
'DbEngine' => 'mysql', // mysql / sqlite
'DbMode' => 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION', //SQL mode for MySQL engine
'DbFile' => 'database.sqlite', //File of sqlite database if engine is 'sqlite' location 'userfiles/database/sqlite/'
'DbHost' => 'localhost', 
'DbUser' => 'root',
'DbPassword' => '',
'DbName' => '',

 //Project server time zone in format like 'Europe/Paris'
 //List of timezones http://php.net/manual/en/timezones.php
 //You can use APP_TIMEZONE setting in .env file instead.
'TimeZone' => 'Asia/Novosibirsk',

//Region for localization see folder ~/adminpanel/i18n/
'Region' => 'en',

//Domain name of the application must begin with 'http(s)://' (without trailing slash).
//You can use APP_DOMAIN setting in .env file instead.
'DomainName' => '',

//Folder of the application (usually '/' on production server).
//You can use APP_FOLDER setting in .env file instead.
'MainPath' => '/',

//Name of folder with admin panel. No '/' before or after.
'AdminFolder' => 'adminpanel',

//If true, MV will start the session at the frontend.
'SessionSupport' => true,

//Use HttpOnly mode for cookies.
'HttpOnlyCookie' => true,

//Enables cache and turns on cache cleaning in admin panel CRUD operartions.
'EnableCache' => false,

//Files storage path. No '/' before or after.
'FilesPath' => 'userfiles',

//Special secret code of the application, to make security hashes.
//You can use APP_TOKEN setting in .env file instead.
'SecretCode' => '',

//Sender's email address, example: 'Name <email@domain.zone>'.
//You can use EMAIL_FROM setting in .env file instead.
'EmailFrom' => '',

//Email sender type, can be 'mail' or 'smtp'.
//You can use EMAIL_SENDER settings in .env file instead.
'EmailMode' => 'mail',

//SMTP setting for email sender.
//You can use EMAIL_ settings in .env file instead.
'SMTPHost' => '',
'SMTPPort' => '',
'SMTPAuth' => true,
'SMTPEncryption' => '',
'SMTPUsername' => '',
'SMTPPassword' => '',

//Default email signature.
'EmailSignature' => '<p>Сообщение отправлено с сайта <a href="{domain}">{domain}</a></p>'
];