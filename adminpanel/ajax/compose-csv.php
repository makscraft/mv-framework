<?
include "../../config/autoload.php";
$registry = Registry :: instance();
$system = new System('ajax');

if(!$system -> user -> checkUserLogin())
	exit();

$csv = new Csv();

if(isset($_GET["model"], $_GET["csv_fields"], $_GET["csv_separator"], $_GET["csv_encoding"]))
	if($system -> registry -> checkModel($_GET["model"]) && $_GET["csv_fields"])
		if($system -> user -> checkModelRights($_GET["model"], "update"))
			if(array_key_exists($_GET["csv_separator"], $csv -> getSeparators()) && 
			   in_array($_GET["csv_encoding"], $csv -> getEncodings()))
			{
				$model = new $_GET["model"]();
				$fields = explode(",", trim($_GET["csv_fields"]));
				$separators = $csv -> getSeparators();
				$file = $csv -> composeCSVFile($model, $fields, $separators[$_GET["csv_separator"]], 
											   $_GET["csv_encoding"], isset($_GET["csv_headers"]));
				
				$name = str_replace(" ", "_", $model -> getName())."_".date("d-m-Y_H-i").".csv";
				$name = ($_GET["csv_encoding"] != "utf-8") ? iconv("utf-8", $_GET["csv_encoding"], $name) : $name;
				$size = ($_GET["csv_encoding"] == "utf-8") ? mb_strlen($file, "utf-8") : strlen($file);
	
				header("Content-Description: File Transfer\r\n");
				header("Pragma: public\r\n");
				header("Expires: 0\r\n");
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0\r\n");
				header("Cache-Control: public\r\n");
				header("Content-Type: text/plain; charset=".$_GET["csv_encoding"]."\r\n");
				header("Content-Length: ".$size);
				header("Content-Disposition: attachment; filename=\"".$name."\"\r\n");	
				
				echo $file;
			}
?>