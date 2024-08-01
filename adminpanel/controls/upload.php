<?
include_once "../../config/autoload.php";
$system = new System("ajax");

$result = [];

if(!isset($_GET['ck-image'], $_FILES['upload']))
	$result = ['error' => ['message' => I18n :: locale('error-data-transfer')]];
else
{
	$object = new ImageModelElement(I18n :: locale('upload-image'), 'image', 'image', ['files_folder' => 'images']);
	$object -> setValue($_FILES['upload']);

	if($object -> getError())
	{
		$error = [$object -> getCaption(), $object -> getError(), 'image'];
		$error = Model :: processErrorText($error, $object);
		$error = str_replace(['&laquo;', '&raquo;'], '"', $error);

		$result = ['error' => ['message' => $error]];
	}
	else
		$result = ['url' => Service :: addRootPath($object -> copyFile())];
}

header('Content-Type: application/json');
echo json_encode($result);
?>