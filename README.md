# MV framework
Content management framework with admin panel for developing internet sites and applications.

Installation via composer
---
```
composer create-project makscraft/mv-framework project_name
```

- If your project is located not at the domain root, you will need to specify the project subdirectory like /my/project/ during the composer installation.

Manual installation
---
- Download archive from (official site) [GitHub Pages](https://mv-framework.com)
- Unzip the contents into your project folder.
- Fill .env file at the root directory.
- If your project is located not at the domain root, you need to fill APP_FOLDER setting in .env file and RewriteBase value in .hthacces file with the same value like /my/project/.
