
<? $controls_url = $registry -> getSetting('AdminPanelPath')."controls/"; ?>
<input class="button-list" type="button" id="operations-list-button" value="<? echo I18n :: locale('operations'); ?>" />
<ul id="operations-menu">	
   <li><a href="<? echo $controls_url; ?>export-csv.php?model=<? echo $system -> model -> getModelClass(); ?>"><? echo I18n :: locale('export-csv'); ?></a></li>
   <li><a href="<? echo $controls_url; ?>import-csv.php?model=<? echo $system -> model -> getModelClass(); ?>"><? echo I18n :: locale('import-csv'); ?></a></li>
</ul>
