<?php
$content = $mv -> pages -> defineCurrentPage($mv -> router);
$mv -> display404($content);
$mv -> seo -> mergeParams($content, 'name');

$fields = [
	['Name', 'char', 'name', ['required' => true]],
	['Email', 'email', 'email'],
	['Theme', 'enum', 'theme', ['empty_value' => 'Not selected', 'required' => 1,
							   'values_list' => ['business' => 'Business question',
												 'tecnical' => 'Technical question',
												 'commertial' => 'Commercial offer',
												 'other' => 'Other']]],
	
	['Image', 'image', 'image', ['required' => true, 'files_folder' => 'uploads']],


	/*
	Extra files fields to try.

	['File', 'file', 'file', ['files_folder' => 'new_files']],
	['Multi files', 'file', 'files', ['files_folder' => 'files_many', 'multiple' => 5]],
	['Multi images', 'multi_images', 'images', ['files_folder' => 'images_many']],
	*/

	['Message', 'text', 'message', ['required' => true]],
	['I agree to receive news', 'bool', 'news']
];

$form = new Form($fields);
$form -> useTokenCSRF();
$form_complete = false;

$form -> submit() -> validate();
	
if($form -> isValid())
	$form_complete = true;

include $mv -> views_path.'main-header.php';
?>
<section class="content">
	<h1><?php echo $content -> name; ?></h1>
	<?php
		echo $content -> content;
		
		if($form_complete)
		{
			echo "<div class=\"form-success\"><p>The form has been successfully completed.</p></div>\n";
			echo "<h3>Message to be sent by email</h3>\n";
			echo $form -> composeMessage();
			
			echo "<h3>Fields for SQL query</h3>\n";
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
			<button>Submit form</button>
		</div>
	</form>
	<?php endif; ?>
</section>
<?php
include $mv -> views_path.'main-footer.php';
?>