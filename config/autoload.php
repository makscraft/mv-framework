<?php
/**
 * MV - content management framework for developing internet sites and applications.
 * Released under the terms of BSD License.
 * 
 * http://mv-framework.com
 * http://mv-framework.ru
 */

if(version_compare(phpversion(), '8.0', '<'))
	exit('To run MV framework you need PHP version 8.0 or later.');

ini_set('display_errors', 1);

$mvIncludePath = preg_replace('/config\/?$/', '',  dirname(__FILE__));
$mvIncludePath = str_replace('\\', '/', $mvIncludePath);

require_once $mvIncludePath.'config/setup.php';
require_once $mvIncludePath.'core/datatypes/base.type.php';
require_once $mvIncludePath.'core/datatypes/bool.type.php';
require_once $mvIncludePath.'core/datatypes/char.type.php';
require_once $mvIncludePath.'core/datatypes/url.type.php';
require_once $mvIncludePath.'core/datatypes/enum.type.php';
require_once $mvIncludePath.'core/datatypes/file.type.php';
require_once $mvIncludePath.'core/datatypes/image.type.php';
require_once $mvIncludePath.'core/datatypes/int.type.php';
require_once $mvIncludePath.'core/datatypes/order.type.php';
require_once $mvIncludePath.'core/datatypes/text.type.php';
require_once $mvIncludePath.'core/registry.class.php';
require_once $mvIncludePath.'core/i18n.class.php';
require_once $mvIncludePath.'core/service.class.php';
require_once $mvIncludePath.'core/cache.class.php';
require_once $mvIncludePath.'core/debug.class.php';
require_once $mvIncludePath.'core/database.class.php';
require_once $mvIncludePath.'core/cache.class.php';
require_once $mvIncludePath.'core/cache_media.class.php';
require_once $mvIncludePath.'core/model_initial.class.php';
require_once $mvIncludePath.'core/model_base.class.php';
require_once $mvIncludePath.'core/model.class.php';
require_once $mvIncludePath.'core/model_simple.class.php';
require_once $mvIncludePath.'core/plugin.class.php';
require_once $mvIncludePath.'core/log.class.php';
require_once $mvIncludePath.'core/router.class.php';
require_once $mvIncludePath.'core/builder.class.php';
require_once $mvIncludePath.'core/imager.class.php';
require_once $mvIncludePath.'core/content.class.php';
require_once $mvIncludePath.'core/record.class.php';
require_once $mvIncludePath.'core/paginator.class.php';

$mvConfigFiles = [
	$mvIncludePath.'config/setup.php',
	$mvIncludePath.'config/settings.php',
	$mvIncludePath.'config/models.php',
	$mvIncludePath.'config/plugins.php'
];

//Trys to get all settings at one time from cache file
if(isset($mvSetupSettings['Build']))
{
	$mvEnvCacheFile = $mvIncludePath.$mvSetupSettings['FilesPath'].'/cache/env-'.$mvSetupSettings['Build'].'.php';

	if(is_file($mvEnvCacheFile))
	{
		$cache = require_once($mvEnvCacheFile);

		//After new build we check files modification for certain period in any environment
		$check = ($cache['CheckConfigFilesUntil'] - time()) > 0;

		//Checks config files modification time if not in production environment
		if($check || $cache['Mode'] !== 'production')
		{
			$hash = Service :: getFilesModificationTimesHash($mvConfigFiles);
			$env = $mvIncludePath.'.env';

			if($hash == $cache['ConfigFilesHash'])
				if(!is_file($env) || filemtime($env) == $cache['EnvFileTime'])
					$mvSetupSettings = array_merge(['LoadedFromCache' => time()], $cache);
		}
		else
			$mvSetupSettings = array_merge(['LoadedFromCache' => time()], $cache);
	}
}

//Creating settings list if cache has not being found
if(!isset($mvSetupSettings['LoadedFromCache']))
{
	require_once 'settings.php';
	require_once 'models.php';
	require_once 'plugins.php';
	
	$mvSetupSettings['Models'] = $mvActiveModels;
	$mvSetupSettings['Plugins'] = $mvActivePlugins;
	$mvSetupSettings['IncludePath'] = $mvIncludePath;
}

//Runs main settings storage object
$registry = Registry :: instance();

//Loads all settings into Registry to get them from any place
if(!isset($mvSetupSettings['LoadedFromCache']))
{
	Registry :: generateSettings($mvSetupSettings);
	
	$registry -> loadSettings($mvSetupSettings);
	$registry -> loadSettings($mvMainSettings);
	
	$registry -> loadEnvironmentSettings() -> checkSettingsValues() -> lowerCaseConfigNames();
	
	//Saves cache confid file (if we have .env file in root folder)
	Cache :: createMainConfigFile($mvConfigFiles);
}
else
	$registry -> loadSettings($mvSetupSettings);

$registry -> createClassesAliases();

$mvAutoloadData = [
	'models' => $mvSetupSettings['Models'],
	'models_lower' => Registry :: get('ModelsLower'),
	'plugins' => $mvSetupSettings['Plugins'],
	'plugins_lower' => Registry :: get('PluginsLower'),
	'datatypes_lower' => Registry :: get('DataTypesLower')
];

$GLOBALS['mvAutoloadData'] = $mvAutoloadData;
$GLOBALS['mvSetupSettings'] = $mvSetupSettings;

//Defines class auto loader
spl_autoload_register(function($class_name)
{	
	$mvAutoloadData = $GLOBALS['mvAutoloadData'];
	$mvSetupSettings = $GLOBALS['mvSetupSettings'];
	$class_lower = strtolower($class_name);
	
	if(strpos($class_name, 'ModelElement') !== false || strpos($class_lower, '_model_element') !== false)
	{
		$class_name = str_replace(['modelelement', '_model_element'], '', $class_lower);

		if(array_key_exists($class_name, $mvAutoloadData['datatypes_lower']))
			$class_name = $mvAutoloadData['datatypes_lower'][$class_name];
		
		require_once $mvSetupSettings['IncludePath'].'core/datatypes/'.$class_name.'.type.php';
	}
	else if(in_array($class_lower, $mvAutoloadData['models_lower']))
		require_once $mvSetupSettings['IncludePath'].'models/'.$class_lower.'.model.php';
	else if(array_key_exists($class_lower, $mvAutoloadData['models_lower']))
		require_once $mvSetupSettings['IncludePath'].'models/'.$mvAutoloadData['models_lower'][$class_lower].'.model.php';
	else if(in_array($class_lower, $mvAutoloadData['plugins_lower']))
		require_once $mvSetupSettings['IncludePath'].'plugins/'.$class_lower.'.plugin.php';
	else if(array_key_exists($class_lower, $mvAutoloadData['plugins_lower']))
		require_once $mvSetupSettings['IncludePath'].'plugins/'.$mvAutoloadData['plugins_lower'][$class_lower].'.plugin.php';
	else if(is_file($mvSetupSettings['IncludePath'].'core/'.$class_lower.'.class.php'))
		require_once $mvSetupSettings['IncludePath'].'core/'.$class_lower.'.class.php';
});

//Sets up current localization region of the application
I18n :: instance() -> setRegion($mvSetupSettings['Region']);

//Start time for debug panel
Registry :: set('WorkTimeStart', gettimeofday());

//Error handlers functions
function errorHandlerMV($type, $message, $file, $line)
{
	$message = 'Error: '.$message.' in line '.$line.' of file ~'.Service :: removeDocumentRoot($file);
	Debug :: displayError($message, $file, $line, Registry :: onDevelopment());
}

function exceptionHandlerMV(Throwable $exception)
{
	$line = $exception -> getLine();
	$file = $exception -> getFile();

	$message = 'Exception: '.$exception -> getMessage().' in line '.$line.' of file ~'.Service :: removeDocumentRoot($file);
	Debug :: displayError($message, $file, $line);
  }

function fatalErrorHandlerMV()
{
	if(!Registry :: get('ErrorAlreadyLogged'))
		if(null !== $error = error_get_last())
		{
			$message = 'Fatal error: '.$error['message'].', in line '.$error['line'].' of file ~'.Service :: removeDocumentRoot($error['file']);
			Debug :: displayError($message, $error['file'], $error['line']);
		}
}

//Sets error handlers
set_error_handler('errorHandlerMV');
set_exception_handler('exceptionHandlerMV');
register_shutdown_function('fatalErrorHandlerMV');

//Final general settings
error_reporting(0);
ini_set('display_errors', 0);

if(isset($mvSetupSettings['HttpOnlyCookie']) && $mvSetupSettings['HttpOnlyCookie'])
	ini_set('session.cookie_httponly', 1);

session_set_cookie_params(0, $mvSetupSettings['MainPath']);

ini_set('session.use_only_cookies', 1);
ini_set('session.use_trans_sid', 0);