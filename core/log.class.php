<?php
/**
 * Logs manager class. 
 * Also saves users operations into history in admin panel.
 * Log files of the framework are located at the /log/ folder.
 */
class Log extends Model
{
	protected $name = "{users-operations}";
	
	protected $model_elements = array(
		array("{module}", "enum", "module"),
		array("{row_id}", "int", "row_id"),
		array("{date}", "date_time", "date"),
		array("{record}", "char", "name"),
		array("{user}", "enum", "user_id", array("foreign_key" => "Users")),
		array("{operation}", "enum", "operation", array("values_list" => array(
														"create" => "{creating}",
														"update" => "{editing}",
														"delete" => "{deleting}",
														"restore" => "{restoring}")))				
	);
			
	protected $model_display_params = array(
		"hidden_fields" => array('row_id'),
		"create_actions" => false,
		"update_actions" => false,
		"delete_actions" => false
	);
	
	/**
	 * Maximal allowed size of one log file.
	 * @var int size in bytes
	 */
	private const LOG_FILE_SIZE = 3072000;
	
	/**
	 * Maximal allowed quantity of log files, created by one template.
	 * @var int number of files
	 */
	private const LOG_FILES_LIMIT = 10;
	
	public function __construct()
	{
		$registry = Registry :: instance();
		$values = Database :: instance()-> getColumn("SELECT DISTINCT `module` FROM `log`");
		$values_list = [];
		
		foreach($values as $model_class)
			if($registry -> checkModel($model_class))
				{	
					$object = new $model_class();
					$values_list[$model_class] = $object -> getName();
				}
		
		natcasesort($values_list);
		
		$this -> model_elements[0][] = ['values_list' => $values_list];
		
		parent :: __construct();
		
		$this -> elements['operation'] -> defineValuesList();
		$this -> elements['user_id'] -> defineValuesList();
	}
	
	/**
	 * Adds into history one operation in admin panel.
	 */
	static public function write(string $model, int $row_id, string $name, int $user_id, string $operation)
	{
		$db = Database :: instance();

		$db -> query("INSERT INTO `log`(`module`,`row_id`,`name`,`user_id`,`operation`,`date`) 
					  VALUES(".$db -> secure($model).",'".$row_id."',".$db -> secure($name).",'".$user_id."',
					  		 ".$db -> secure($operation).",".$db -> now('with-seconds').")");
	}
	
	/**
	 * Deletes all logs related to one user in admin panel.
	 * @param int $user_id id of user of admin panel
	 */
	static public function clean(int $user_id)
	{
		Database :: instance() -> query("DELETE FROM `log` WHERE `user_id`='".$user_id."'");
	}
	
	/**
	 * Adds one message into log file in ~/log/ folder.
	 * @param string $message text to add into file
	 * @param string $file_name optinal parameter, name of log file ('api', 'service', ...)
	 */
	static public function add(string $message, string $file_name = '')
	{
		$message = I18n :: getCurrentDateTime('SQL').' '.$message;
		$folder = Registry :: get('IncludePath').'log/';
		
		if(!is_dir($folder))
			return;

		if($file_name === '')
		{
			$file_name = Registry :: get('DomainName');
			$file_name = $file_name ? $file_name : 'runtime';
			$file_name = preg_replace('/^https?:\/\/(www\.)?/', '', $file_name);
		}
		else
			$file_name = str_replace(['.', '/', '\\'], '', $file_name);

		$file_name = $folder.self :: defineLogFileName($file_name);

		if(!file_exists($file_name))
			file_put_contents($file_name, $message."\n");
		else
		{
			$content = file_get_contents($file_name);

			if(strpos($content, $message) === false)
				file_put_contents($file_name, $message."\n", FILE_APPEND);
		}
	}

	/**
	 * Calculates name of current log file, depending on file size and limit of log files.
	 * @param string $template base name of log file, without number and extension 
	 */
	static public function defineLogFileName(string $template)
	{
		$folder = Registry :: get('IncludePath').'log/';
		$total = 0;
		$oldest = $newest = ['file' => '', 'time' => 0];
		$regexp = Service :: prepareRegularExpression($template);

		if(!is_dir($folder))
			return '';

		$files = scandir($folder);

		foreach($files as $file)
			if(preg_match('/^'.$regexp.'\d*\.log$/', $file))
			{
				$total ++;
				$time = filemtime($folder.$file);
				
				if($newest['time'] === 0 || $newest['time'] < $time)
					$newest = ['file' => $file, 'time' => $time];

				if($oldest['time'] === 0 || $oldest['time'] > $time)
					$oldest = ['file' => $file, 'time' => $time];
			}

		if($total === 0)
			return $template.'.log';
		
		if(filesize($folder.$newest['file']) < self :: LOG_FILE_SIZE)
			return $newest['file'];

		if($total >= self :: LOG_FILES_LIMIT)
			unlink($folder.$oldest['file']);

		$number = str_replace([$template, '.log'], '', $newest['file']);
		$number = ($number === '' ? 0 : intval($number)) + 1;

		return $template.$number.'.log';
	}
}
