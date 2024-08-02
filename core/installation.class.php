<?php
class Installation
{
    static public function finish()
    {
        $env = __DIR__.'/../.env.empty';
        $text = file_get_contents($env);

        $value = Service :: strongRandomString(40);
        $text = str_replace('APP_TOKEN=', 'APP_TOKEN='.$value, $text);

        file_put_contents(__DIR__.'/../.env', $text);
        echo ".env file has been configurated\r\n";

        $file = __DIR__.'/../index.php';
        $text = file_get_contents($file);
        $text = str_replace('config/autoload.php', 'vendor/autoload.php', $text);
        file_put_contents($file, $text);
        echo "index.php file has been configurated\r\n";
    }

    static public function postAutoloadDump()
    {
        //echo ".env file has been configurated\r\n";
    }

    static public function configureDatabase()
    {
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
            echo "database has been configurated\r\n";
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
