<div id="report-links">
	<div id="pstring-links">
	<?php if(!empty($arr_pstring) && count($arr_pstring) > 1): ?>
		<a name="pstring-nav" class="label section-header" id="select-pstring">Select PString:</a> 
		<ul class="pstring-nav">
	
	
			<?php $top = count($arr_pstring);
			for( $c=0; $c < $top; $c++): ?>
				<li class="first"><a href="#chart" id="<?php echo $arr_pstring[$c]['pstring']; ?>" onclick="return updateBlock(event, this, 'pstring-links', 'pstring', '<?php echo $arr_pstring[$c]['pstring']; ?>');"><?php echo $arr_pstring[$c]['publication_name']; // . ' - ' . $arr_pstring[$c]['publication_name']; ?></a></li>
					
			<?php endfor; ?>
		</ul>
	<?php endif; ?>
	</div>
	<div id="block-links">
		<a class="label section-header" id="select-block">Select Report Block:</a><br>
		<ul class="report-nav" id="current">
		<?php
		$first = TRUE;
		if(isset($arr_pages) && is_array($arr_pages)):
			foreach($arr_pages as $e):
				if($first):
					$li_class = 'first';
					$first = FALSE;
				else :
					$li_class = FALSE; 
				endif; ?>
				<li <?php if($li_class) echo 'class="first"'; ?>><a href="#chart" id="<?php echo $e['url_segment']; ?>" onclick="return updateBlock(event, this, 'block-links', 'block', '<?php echo $e['url_segment']; ?>');"><?php echo $e['name']; ?></a></li>
<?php 		endforeach;
		endif; ?>
		</ul>
	</div>
</div>