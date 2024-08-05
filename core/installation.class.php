<?php
use Composer\Script\Event;

class Installation
{
    //Heplers

    static public function typePrompt(string $message)
    {
        echo PHP_EOL.$message.": ";

        $stdin = fopen('php://stdin', 'r');
        $answer = trim(fgets($stdin));
        fclose($stdin);
        
        return $answer;
    }

    static public function displayErrorMessage(string $message)
    {
        echo "\033[41m ".$message." \033[0m".PHP_EOL.PHP_EOL;
    }

    static public function displaySuccessMessage(string $message)
    {
        echo "\033[92m".$message." \033[0m".PHP_EOL;
    }

    static public function displayDoneMessage(string $message)
    {
        echo "\033[42m ".$message." \033[0m".PHP_EOL.PHP_EOL;
    }

    static public function setEnvFileParameter(string $key, string $value)
    {
        $env_file = __DIR__.'/../.env';
        $env = file_get_contents($env_file);

        $env = preg_replace('/'.$key.'=[\/\w]*/ui', $key.'='.trim($value), $env);
        file_put_contents($env_file, $env);
    }

    static public function createFolderIfNotExists(string $folder)
    {

    }

    //Installation process

    static public function finish()
    {
        $env_file = __DIR__.'/../.env.empty';
        $env = file_get_contents($env_file);

        $value = Service :: strongRandomString(40);
        $env = str_replace('APP_TOKEN=', 'APP_TOKEN='.$value, $env);

        file_put_contents(__DIR__.'/../.env', $env);
        echo "\033[92m .env file has been configurated\033[0m\r\n";

        $file = __DIR__.'/../index.php';
        $code = file_get_contents($file);
        $code = str_replace('config/autoload.php', 'vendor/autoload.php', $code);

        file_put_contents($file, $code);
        echo "\033[92m index.php file has been configurated\033[0m\r\n";
    }

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

    static public function generateSecurityToken()
    {
        $value = Service :: strongRandomString(40);
        self :: setEnvFileParameter('APP_TOKEN', $value);
        self :: displaySuccessMessage('Security token has been generated.');   
    }

    static public function postAutoloadDump(Event $event)
    {
        //
        //echo $value;
        self :: configureFolder();
        self :: generateSecurityToken();
        return;

        $driver = '';

        do{
            $driver = self :: typePrompt('Please type database driver: mysql or sqlite');
        }
        while($driver !== 'mysql' && $driver !== 'sqlite');

        self :: setEnvFileParameter('DATABASE_ENGINE', $driver);
        self :: setEnvFileParameter('DATABASE_HOST', $driver === 'mysql' ? 'localhost' : '');

        if($driver === 'sqlite')
        {
            $pdo = self :: runPdo();
            //self :: setRootUserLogin($pdo);
        }

        

        //self :: displaySuccessMessage($driver);

        // $value = self :: typePrompt('Please type database driver: mysql or sqlite');

        // if($value !== 'mysql' && $value !== 'sqlite')
        //     $value = self :: typePrompt('Please type database driver: mysql or sqlite');
        // $a = self :: typePrompt('Say hello');
        // self :: displayErrorMessage($a);

        //self :: setEnvFileParameter('APP_ENV', '');
    }

    //Database confuguration

    static public function runPdo()
    {
        $env_file = __DIR__.'/../.env';
        $env = parse_ini_file($env_file);

        if($env['DATABASE_ENGINE'] !== 'mysql' && $env['DATABASE_ENGINE'] !== 'sqlite')
        {
            self :: displayErrorMessage('Undefined database engine in parameter DATABASE_ENGINE in .env file.');
        }

        if($env['DATABASE_ENGINE'] == 'mysql')
        {
            $pdo = new PDO("mysql:host=".$data['DATABASE_HOST'].";dbname=".$data['DATABASE_NAME'], 
                                                                           $data['DATABASE_USER'], 
                                                                           $data['DATABASE_PASSWORD'], [
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
                return;
            }
            else if(!is_writable(dirname($file)))
            {
                self :: displayErrorMessage('Please make directory with sqlite file writable: '.dirname($file));
                return;
            }

            $pdo = new PDO("sqlite:".$file);
        }

        return $pdo;
    }

    static public function configureDatabase(Event $event)
    {
        echo $event -> getName()."\r\n";
        print_r($event -> getArguments());
        exit();

        $env = __DIR__.'/../.env';

        if(!file_exists($env))
            return;

        $data = parse_ini_file($env);


        $dump_file = __DIR__.'/../userfiles/database/mysql-dump.sql';

        if(true === self :: loadMysqlDump($dump_file, $pdo))
            echo "\033[92mDatabase has been configurated.\033[0m\r\n";
    }

    static public function loadMysqlDump(string $dump_file, PDO $pdo)
    {
        $query = $pdo -> prepare('SHOW TABLES');
        $query -> execute();
        $result = $query -> fetchAll(PDO :: FETCH_COLUMN);

        //if(in_array('accounts', $tables) && in_array('versions', $tables))

        //print_r($tables);

        //!!!!!!! first admin

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

    static public function setRootUserLogin(PDO $pdo)
    {
        $query = $pdo -> prepare('SELECT * FROM `users`');
        $query -> execute();
        //$result = $query -> fetch(PDO::FETCH_ASSOC);

        print_r($result);
    }
}
