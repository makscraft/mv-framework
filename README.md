# MV framework
Content management framework with admin panel for developing internet sites and applications.

<img src="https://github.com/user-attachments/assets/85543c74-5fae-49e3-bb74-2e4ba160a457" width="400">
<img src="https://github.com/user-attachments/assets/1ee419b3-9ac6-4573-90d7-61a6a5529987" width="400">

Installation via composer
---
```
composer create-project makscraft/mv-framework project_name
```

- If your project is located at the domain root, get into the domain root directory and run the composer command with the **.** (dot) value instead of **project_name**.
- If your project is located not at the domain root, you will need to specify the project subdirectory like **/my/project/** during the composer installation.
- All the other critical settings will be generated automatically by composer.
- After the intallation process run your http server and check your app.

Manual installation
---
- Download archive from (official site) https://mv-framework.com
- Unzip the contents into your project folder.
- Fill the database section in the .env file at the root directory (sqlite is set by default, in this case you don't need to fill anything for start).
- For mysql database initial dump is located at the **/userfiles/database** directory. Upload it into your database.
- If your project is located not at the domain root, you need to fill **APP_FOLDER** setting in .env file and **RewriteBase** value in .htaccess file with the same value like **/my/project/**.
- You may not fill **APP_TOKEN** value in .env file, because later MV will generate random value for you and ask you to put it into .env file.
- After the intallation process run your http server and check your app.
