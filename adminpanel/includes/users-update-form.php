<?
if($system -> model -> getId() != 1)
	include_once $registry -> getSetting("IncludeAdminPath")."includes/users-create-form.php";
else
{
	echo "<tr><td class=\"field-name\">".I18n :: locale("users-rights")."</td>";
	echo "<td class=\"field-content\">".I18n :: locale("root-rights")."</td></tr>\n";
}
?>