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
        echo PHP_EOL."\033[41m ".$message." \033[0m".PHP_EOL.PHP_EOL;
    }

    static public function displaySuccessMessage(string $message)
    {
        echo PHP_EOL."\033[92m ".$message." \033[0m".PHP_EOL.PHP_EOL;
    }

    static public function displayDoneMessage(string $message)
    {
        echo PHP_EOL."\033[42m ".$message." \033[0m".PHP_EOL.PHP_EOL;
    }

    static public function setEnvFileParameter(string $key, string $value)
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

    static public function configureFolder(Event $event)
    {
        $arguments = $event -> getArguments();
        $folder = $arguments[0] ?? '';
        $folder = trim($folder);

        if($folder === '')
        {
            echo "\033[44mError! You need to pass the project folder name like -- /my/application/ or -- myapp (or simply -- /).\033[0m\r\n";
            return;
        }
        else if(strpos($folder, '.') !== false)
        {
            echo "Error! Project folder name may not contain '.' character.\033[0m\r\n";
            return;
        }
        
        if(!preg_match('/^\//', $folder))
            $folder = '/'.$folder;

        if(!preg_match('/\/$/', $folder))
            $folder = $folder.'/';

        $root = realpath(__DIR__.'/../..');
        $directory = realpath(__DIR__.'/../../'.$folder);

        if(!is_dir($directory))
        {
            echo "Error! Project directory does not exist ".$root.$folder.".\033[0m\r\n";
            return;
        }

        $env_file = __DIR__.'/../.env';
        $htaccess_file = __DIR__.'/../.htaccess';
        $env = file_get_contents($env_file);
        $htaccess = file_get_contents($htaccess_file);

        $env = preg_replace('/APP_FOLDER=[\/\w]*/', 'APP_FOLDER='.$folder, $env);
        $htaccess = preg_replace('/RewriteBase\s+\/[\/\w]*/', 'RewriteBase '.$folder, $htaccess);

        file_put_contents($env_file, $env);
        file_put_contents($htaccess_file, $htaccess);

        echo "\033[92m.env file has been configurated\033[0m\r\n";
        echo "\033[92m.htaccess file has been configurated\033[0m\r\n";
    }

    static public function postAutoloadDump(Event $event)
    {
        $a = self :: typePrompt('Say hello');
        self :: displayErrorMessage($a);
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

        if($data['DATABASE_ENGINE'] == 'mysql')
        {
            $pdo = new PDO("mysql:host=".$data['DATABASE_HOST'].";dbname=".$data['DATABASE_NAME'], 
                                                                           $data['DATABASE_USER'], 
                                                                           $data['DATABASE_PASSWORD'], [
                                    PDO :: MYSQL_ATTR_INIT_COMMAND => "SET NAMES \"UTF8\""
                            ]);

            $pdo -> setAttribute(PDO :: MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        }

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
}
