# MV framework
Content management framework with admin panel for developing internet sites and applications.

Installation via composer
---
```
composer create-project makscraft/mv-framework project_name
```

- If your project is located at the domain root, get into the domain root directory and run the composer command with the **.** (dot) value instead of **project_name**.
- If your project is located not at the domain root, you will need to specify the project subdirectory like /my/project/ during the composer installation.
- All the other critical settings will be generated automatically by composer.
- After the intallation process run your http server and check your app.

Manual installation
---
- Download archive from (official site) https://mv-framework.com
- Unzip the contents into your project folder.
- Fill the database section in the .env file at the root directory (sqlite is set by default, in this case you don't need to fill anything for start).
- If your project is located not at the domain root, you need to fill **APP_FOLDER** setting in .env file and **RewriteBase** value in .htaccess file with the same value like **/my/project/**.
- You may not fill **APP_TOKEN** value in .env file, because later MV will generate random value for you and ask you to put it into .env file.
- After the intallation process run your http server and check your app.
