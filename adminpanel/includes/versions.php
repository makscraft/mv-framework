<? 
	if(isset($_SESSION['mv']['settings']['versions-pager-limit']))
		$system -> versions -> pager -> setLimit($_SESSION['mv']['settings']['versions-pager-limit']);	          
	          	  
	if(isset($_REQUEST['versions-page']) && intval($_REQUEST['versions-page']))
	    $system -> versions -> pager -> definePage(intval($_REQUEST['versions-page']));
	else
		$system -> versions -> pager -> definePage(1);
		
	$versions_limit = $system -> versions -> getLimit();
?>

<table id="versions-table">
	<?
		if($versions_limit)
			echo $system -> versions -> display(); 
	?>
</table>
<div id="versions-limit"<? echo $versions_limit ? "" : ' class="versions-disabled"'; ?>>
	<? echo $versions_limit ? I18n :: locale("versions-limit").": ".$versions_limit : I18n :: locale("versions-disabled"); ?>
</div>
<div id="versions-pager">
	<div class="limit">
      <?
      	  if($versions_limit)
      	  {
      	  	  echo "<span>".I18n :: locale('pager-limit')."</span>\n";
      	  	  echo "<select>\n".$system -> versions -> pager -> displayPagerLimits(array(5,10,15,20,25,30,50,100))."</select>\n";
      	  }
      ?>
	</div>
	<?
		if($versions_limit)
			echo $system -> versions -> displayPager(); 
	?>
    <div class="clear"></div>
</div>
