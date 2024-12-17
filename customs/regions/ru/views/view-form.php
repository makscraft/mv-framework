<?php
$content = $mv -> pages -> defineCurrentPage($mv -> router);
$mv -> display404($content);
$mv -> seo -> mergeParams($content, 'name');

$fields = [
	['Имя', 'char', 'name', ['required' => true]],
	['Email', 'email', 'email'],
	['Тема', 'enum', 'theme', ['empty_value' => 'Не выбрано', 'required' => 1,
							   'values_list' => ['business' => 'Организационный вопрос',
												 'tecnical' => 'Технический вопрос',
												 'commertial' => 'Коммерческое предложение',
												 'other' => 'Другое']]],
	
	['Изображение', 'image', 'image', ['required' => true, 'files_folder' => 'uploads']],


	/*
	Extra files fields to try.

	['Файл', 'file', 'file', ['files_folder' => 'new_files']],
	['Список файлов', 'file', 'files', ['files_folder' => 'files_many', 'multiple' => 5]],
	['Массив изображений', 'multi_images', 'images', ['files_folder' => 'images_many']],
	*/

	['Сообщение', 'text', 'message', ['required' => true]],
	['Согласен получать новости', 'bool', 'news']
];

$form = new Form($fields);
$form -> useTokenCSRF();
$form_complete = false;

$form -> submit() -> validate();
	
if($form -> isValid())
	$form_complete = true;

include $mv -> views_path.'main-header.php';
?>
<main>
	<section>
		<h1><?php echo $content -> name; ?></h1>
		<section class="content editable">
			<?php echo $content -> content; ?>
		</section>		
		<?php
			if($form_complete)
			{
				echo "<div class=\"form-success\"><p>Форма была успешно заполнена.</p></div>\n";
				echo "<h3>Сообщение для отправки по email</h3>\n";
				echo $form -> composeMessage();
				
				echo "<h3>Поля для SQL запроса</h3>\n";
				Debug::pre($form -> getAllValues());
			}
			else
				echo $form -> displayErrors();
				
			if(!$form_complete):
		?>
		<form method="post" enctype="multipart/form-data">
			<?php echo $form -> display(); ?>
			<div class="buttons">
				<?php echo $form -> displayTokenCSRF(); ?>
				<button>Отправить</button>
			</div>
		</form>
		<?php endif; ?>
	</section>
</main>
<?php
include $mv -> views_path.'main-footer.php';
?>