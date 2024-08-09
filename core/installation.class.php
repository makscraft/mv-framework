<?php
use Composer\Script\Event;

/**
 * Class for installation of the framework via composer CLI.
 */
class Installation
{
    //Heplers

    /**
     * Displays CLI prompt message and retutns the answer.
     * @return string answer from user
     */
    static public function typePrompt(string $message)
    {
        echo PHP_EOL.$message.': ';

        $stdin = fopen('php://stdin', 'r');
        $answer = trim(fgets($stdin));
        fclose($stdin);
        
        return $answer;
    }

    /**
     * Displays CLI error message with red background.
     */
    static public function displayErrorMessage(string $message)
    {
        echo "\033[41m\r\n\r\n ".$message." \r\n \033[0m".PHP_EOL.PHP_EOL;
    }

    /**
     * Displays CLI green colored success message (without background).
     */
    static public function displaySuccessMessage(string $message)
    {
        echo "\033[92m".$message." \033[0m".PHP_EOL;
    }

    /**
     * Displays CLI success message with green background.
     */
    static public function displayDoneMessage(string $message)
    {
        echo "\033[42m\r\n\r\n ".$message." \r\n \033[0m".PHP_EOL.PHP_EOL;
    }

    /**
     * Opens .env file and puts param into it.
     */
    static public function setEnvFileParameter(string $key, string $value)
    {
        $env_file = __DIR__.'/../.env';
        $env = file_get_contents($env_file);

        $env = preg_replace('/'.$key.'=[\/\w]*/ui', $key.'='.trim($value), $env);
        file_put_contents($env_file, $env);
    }

    //Installation process

    /**
     * Post "composer dump-autoload" event.
     */
    static public function postAutoloadDump(Event $event)
    {
        self :: finish();
    }

    /**
     * Final configuration at the end of "composer create-project" command.
     */
    static public function finish()
    {
        self :: configureFolder();
        self :: generateSecurityToken();

        $file = realpath(__DIR__.'/../index.php');
        $code = file_get_contents($file);
        $code = str_replace('config/autoload.php', 'vendor/autoload.php', $code);

        file_put_contents($file, $code);
        self :: displaySuccessMessage('index.php file has been configurated.');

        if(true === self :: configureDatabase())
            self :: displayDoneMessage('MV framework has been successfully installed.');
    }

    /**
     * Configures project subfolder (if the application is located not at the domain root).
     * Modifies .env and .htaccess files.
     */
    static public function configureFolder()
    {
        $directory = '';

        do{
            $folder = self :: typePrompt('Please type the name of project subdirectory or press Enter to skip [default is /]');
            $folder = trim($folder);
            $folder = $folder === '' ? '/' : $folder;
            $error = '';
            
            if(!preg_match('/^[\/\w]+$/', $folder))
                $error = 'Error! You need to enter the project subdirectory name like /my/application/ or myapp (or simply /).';
            else if(strpos($folder, '.') !== false)
                $error = 'Error! Project subdirectory name may not contain \'.\' character.';

            if(!$error && !preg_match('/^\//', $folder))
                $folder = '/'.$folder;
    
            if(!$error && !preg_match('/\/$/', $folder))
                $folder = $folder.'/';

            $root = realpath(__DIR__.'/../..');
            $directory_check = realpath(__DIR__.'/../../'.$folder);
        
            if(!$error && !is_dir($directory_check))
            {
                if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
                    $folder = str_replace('/', '\\', $folder);
                
                $error = 'Error! Project directory does not exist: "'.$root.$folder.'".';
            }

            if($error)
                self :: displayErrorMessage($error);
            else
            {
                $directory = $folder;
                break;
            }
        }
        while(true);

        if($directory !== '' && $directory !== '/')
        {
            $htaccess_file = __DIR__.'/../.htaccess';
            $htaccess = file_get_contents($htaccess_file);
            $htaccess = preg_replace('/RewriteBase\s+\/[\/\w]*/', 'RewriteBase '.$directory, $htaccess);
            file_put_contents($htaccess_file, $htaccess);

            self :: displaySuccessMessage('.htaccess file has been configurated.');
        }

        self :: setEnvFileParameter('APP_FOLDER', $directory);
        self :: displaySuccessMessage('.env file has been configurated.');   
    }

    /**
     * Generates secret token for application in .env file.
     */
    static public function generateSecurityToken()
    {
        $value = Service :: strongRandomString(40);
        self :: setEnvFileParameter('APP_TOKEN', $value);
        self :: displaySuccessMessage('Security token has been generated.');   
    }

    //Database confuguration

    /**
     * Runs PDO object according to db settings.
     */
    static public function runPdo(): ?PDO
    {
        $env_file = __DIR__.'/../.env';
        $env = parse_ini_file($env_file);

        if($env['DATABASE_ENGINE'] !== 'mysql' && $env['DATABASE_ENGINE'] !== 'sqlite')
        {
            self :: displayErrorMessage('Undefined database engine in parameter DATABASE_ENGINE in .env file.');
        }

        if($env['DATABASE_ENGINE'] == 'mysql')
        {
            $pdo = new PDO("mysql:host=".$env['DATABASE_HOST'].";dbname=".$env['DATABASE_NAME'], 
                                                                          $env['DATABASE_USER'], 
                                                                          $env['DATABASE_PASSWORD'], [
                                    PDO :: MYSQL_ATTR_INIT_COMMAND => "SET NAMES \"UTF8\""
                            ]);

            $pdo -> setAttribute(PDO :: MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        }
        else if($env['DATABASE_ENGINE'] == 'sqlite')
        {
            $path = '/userfiles/database/sqlite/database.sqlite';
            $file = __DIR__.'/..'.$path;
            $file = realpath($file);

            if(!is_file($file))
            {
                self :: displayErrorMessage('SQLite database file no found: ~'.$path);
                return null;
            }
            else if(!is_writable(dirname($file)))
            {
                self :: displayErrorMessage('Please make directory with sqlite file writable: '.dirname($file));
                return null;
            }

            $pdo = new PDO("sqlite:".$file);
        }

        return $pdo;
    }

    /**
     * Database initial configuration.
     */
    static public function configureDatabase()
    {
        $driver = '';

        do{
            $driver = self :: typePrompt('Please type database driver [mysql / sqlite]');
        }
        while($driver !== 'mysql' && $driver !== 'sqlite');

        self :: setEnvFileParameter('DATABASE_ENGINE', $driver);
        self :: setEnvFileParameter('DATABASE_HOST', $driver === 'mysql' ? 'localhost' : '');

        if($driver === 'sqlite')
        {
            self :: configureDatabaseSQLite();
            return true;
        }
        else if($driver === 'mysql')
            self :: displaySuccessMessage('Now please fill database settings for MySQL in .env file and run "composer database"');
    }

    /**
     * Mysql initial configuration.
     */
    static public function configureDatabaseMysql()
    {
        $env = parse_ini_file(__DIR__.'/../.env');
        $keys = ['DATABASE_HOST', 'DATABASE_USER', 'DATABASE_NAME'];

        foreach($keys as $key)
            if(!isset($env[$key]) || trim($env[$key]) === '')
            {
                self :: displayErrorMessage('Please fill "'.$key.'" parameter in .env file.');
                return;
            }

        $pdo = self :: runPdo();
        $query = $pdo -> prepare('SHOW TABLES');
        $query -> execute();
        $tables = $query -> fetchAll(PDO :: FETCH_COLUMN);
    
        if(is_array($tables) && in_array('versions', $tables))
            self :: displaySuccessMessage('MySQL initial dump has been already imported before.');
        else
        {        
            $dump_file = __DIR__.'/../userfiles/database/mysql-dump.sql';

            if(true === self :: loadMysqlDump($dump_file, $pdo))
                self :: displaySuccessMessage('MySQL initial dump has been imported.');
        }

        self :: setRootUserLogin($pdo);

        self :: displayDoneMessage('MySQL database has been successfully configurated.');
    }

    /**
     * Sqlite initial configuration.
     */
    static public function configureDatabaseSQLite()
    {
        self :: setRootUserLogin(self :: runPdo());
        self :: displayDoneMessage('SQLite database has been successfully configurated.');
    }

    /**
     * Imports mysql dump into db.
     */
    static public function loadMysqlDump(string $dump_file, PDO $pdo)
    {
        $sql = '';
        $lines = file($dump_file);
        
        foreach($lines as $line)
        {
            if(substr($line, 0, 2) == '--' || $line == '')
                continue;
            
            $sql .= $line;
            
            if(substr(trim($line), -1, 1) == ';')
            {
                try
                {
                    $pdo -> query($sql);
                } 
                catch(Exception $error)
                {
                    print_r($error -> getMessage());
                    exit();
                }
                
                $sql = '';
            }
        }

        return true;
    }

    //Root user login data

    /**
     * Updates root user login and password in databese.
     */
    static public function setRootUserLogin(PDO $pdo)
    {
        $query = $pdo -> prepare("SELECT COUNT(*) FROM `users`");
        $query -> execute();
        $total = $query -> fetch(PDO::FETCH_NUM)[0];

        $query = $pdo -> prepare("SELECT * FROM `users` WHERE `id`='1'");
        $query -> execute();
        $row = $query -> fetch(PDO::FETCH_ASSOC);

        if($total > 1)
        {
            self :: displaySuccessMessage('Database has been arready configurated.');
            return;
        }

        $login = $password = '';

        do{
            $login = self :: typePrompt('Please setup your login for MV admin panel');

            if(strlen($login) > 1)
                break;

        }
        while(true);

        do{
            $password = self :: typePrompt('Please setup your password for MV admin panel (min 6 characters)');

            if(strlen($password) >= 6)
                break;

        }
        while(true);

        $password = password_hash($password, PASSWORD_DEFAULT, ['cost' => 10]);
        $date = date('Y-m-d H:i:s');

        if(is_array($row) && isset($row['id']) && $row['id'] == 1 && $total == 1)
        {
            $query = $pdo -> prepare(
                "UPDATE `users`
                 SET `login`=".$pdo -> quote($login).", `password`=".$pdo -> quote($password).",
                 `date_registered`='".$date."' 
                 WHERE `id`='1'"
            );
        }
        else if($total == 0)
        {                        
            $query = $pdo -> prepare(
                "INSERT INTO `users`(`name`,`login`,`password`,`date_registered`,`active`)
                 VALUES ('Root', ".$pdo -> quote($login).", ".$pdo -> quote($password).", '".$date."', '1')"
            );
        }

        if($query -> execute())
            self :: displaySuccessMessage('Root user of admin panel has been successfully created.');
    }

    //Commands

    /**
     * Database configuration via CLI composer command.
     */
    static public function commandConfigureDatabase()
    {
        $env = parse_ini_file(__DIR__.'/../.env');
        
        if($env['DATABASE_ENGINE'] === 'mysql')
            self :: configureDatabaseMysql();
        else if($env['DATABASE_ENGINE'] === 'sqlite')
            self :: configureDatabaseSQLite();
        else
            self :: displayErrorMessage('Undefined database "DATABASE_ENGINE='.$env['DATABASE_ENGINE'].'" in .env file');
    }

    /**
     * Check and runs database migrations via CLI composer command.
     */
    static public function commandMigrations()
    {
        $registry = Registry :: instance();

        include __DIR__.'/../config/setup.php';
        include __DIR__.'/../config/settings.php';
        include __DIR__.'/../config/models.php';
        include __DIR__.'/../config/plugins.php';

        $mvSetupSettings['IncludePath'] = realpath(__DIR__.'/..').'/';
        $mvSetupSettings['Models'] = $mvActiveModels;
        $mvSetupSettings['Plugins'] = $mvActivePlugins;

        Registry :: generateSettings($mvSetupSettings);
        $registry -> loadSettings($mvMainSettings);
        $registry -> loadSettings($mvSetupSettings);
        $registry -> loadEnvironmentSettings() -> lowerCaseConfigNames();

        $migrations = new Migrations(true);
        $migrations -> scanModels();
        $available = $migrations -> getMigrationsQuantity();

        if($available == 0)
        {
            self :: displaySuccessMessage('No migrations available.');
            return;
        }

        echo PHP_EOL;
        self :: displaySuccessMessage('Found available migrations ('.$available.'):');
        echo implode("\n", $migrations -> getMigrationsShortList()).PHP_EOL;
        
        $answer = self :: typePrompt('Do you want to run the migrations now? [yes]');

        if($answer == '' || $answer == 'yes' || $answer == 'y')
        {
            $migrations -> runMigrations('all');
            self :: displayDoneMessage('Migrations have been executed. Your database is up to date.');
        }
    }
}
