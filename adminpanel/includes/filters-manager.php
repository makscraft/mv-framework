
<div class="manage-filters" id="model-<? echo $system -> model -> getModelClass(); ?>">
	<p><? echo I18n :: locale('manage-filters'); ?></p>
	<select id="add-filter">
	   <option value=""><? echo I18n :: locale('add'); ?></option>
       <? 
			$html_selects = $system -> model -> filter -> displayAdminFiltersFieldsSelects($default_filters, 
																						   $show_empty_default_filters);
			echo $html_selects['add'];
       ?>
	</select>
	<select id="remove-filter">
	   <option value=""><? echo I18n :: locale('delete'); ?></option>
       <? echo $html_selects['remove']; ?>
	</select>
</div>