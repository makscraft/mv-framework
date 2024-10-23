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

//Current work environment: 'production' - logs all errors into /log/ folder, 
//'development' - displays all possible errors on the screen.
//It's recommended to use APP_ENV setting in .env file instead.
'Mode' => '',

//Current build of the application, increase this value to drop cache in production environment.
'Build' => 1,

//Set true to display debug panel at the bottom of the screen.
'DebugPanel' => true,

//After increasing 'Buld' number, during this time MV will check files update times to refresh the cache.
'CacheFilesCheckTime' => 3600,

//Database parameters
//You can use DATABASE_ settings in .env file instead.
'DbEngine' => '', // mysql / sqlite
'DbMode' => 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION', //SQL mode for MySQL engine
'DbFile' => 'database.sqlite', //File of sqlite database if engine is 'sqlite' location 'userfiles/database/sqlite/'
'DbHost' => '', 
'DbUser' => '',
'DbPassword' => '',
'DbName' => '',

 //Project server time zone in format like 'Europe/Paris'
 //List of timezones http://php.net/manual/en/timezones.php
//It's recommended to use APP_TIMEZONE setting in .env file instead.
'TimeZone' => '',

//Region for localization see folder ~/adminpanel/i18n/
//Whole list of supported regions in config/settings.php file, 'SupportedRegions' array
//It's recommended to use APP_REGION setting in .env file instead.
'Region' => 'en',

//Domain name of the application must begin with 'http(s)://' (without trailing slash).
//It's recommended to use APP_DOMAIN setting in .env file instead.
'DomainName' => '',

//Subfolder of the application (usually '/' on production server).
//It's recommended to use APP_FOLDER setting in .env file instead.
'MainPath' => '/',

//Name of folder with admin panel. No '/' before or after.
//To change admin panel url, rename it's directory and change this setting.
'AdminFolder' => 'adminpanel',

//If true, MV will start the session at the front $mv object.
'SessionSupport' => true,

//Use HttpOnly mode for cookies.
'HttpOnlyCookie' => true,

//Enables cache and turns on cache cleaning in admin panel CRUD operartions.
'EnableCache' => false,

//Files storage directory. No '/' before or after.
'FilesPath' => 'userfiles',

//Special secret code of the application, to make security hashes.
//You can use APP_TOKEN setting in .env file instead.
'SecretCode' => '',

//Sender's email address, example: 'Name <email@domain.zone>'.
//It's recommended to use EMAIL_FROM setting in .env file instead.
'EmailFrom' => '',

//Email sender type, can be 'mail' or 'smtp'.
//It's recommended to use EMAIL_SENDER settings in .env file instead.
'EmailMode' => '',

//SMTP setting for email sender.
//You can use EMAIL_... settings in .env file instead.
'SMTPHost' => '',
'SMTPPort' => '',
'SMTPAuth' => true,
'SMTPEncryption' => '',
'SMTPUsername' => '',
'SMTPPassword' => '',

//Default email signature, you can change it as you like.
'EmailSignature' => '<p>Message from <a href="{domain}">{domain}</a></p>'
];