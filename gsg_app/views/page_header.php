<!doctype html>
<head>
	<title><?php if(isset($title)) echo $title; ?></title>
    <meta http-equiv="pragma" content="no-cache">
    <meta http-equiv="cache-control" content="no-cache">
    <meta http-equiv="content-type" content="text/html;charset=UTF-8">
    <meta name="robots" content="NO FOLLOW,NO INDEX">
    <meta name="googlebot" content="NOARCHIVE">
    <meta name="description" content="<?php if(isset($description)) echo $description; ?>">
    <meta name="keywords" content="<?php echo $this->config->item("cust_serv_company"); ?>, DHI Testing, DHI, DHIA, milk testing, soil testing, forage testing, manure testing, " />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
	<link rel="stylesheet" href='http://netdna.bootstrapcdn.com/bootstrap/3.0.3/css/bootstrap.min.css'>
<?php
	    $this->carabiner->css('http://agsource.crinet.com/css/AgSource-Cooperative-Services.css', 'screen');
		$this->carabiner->css('print.css', 'print');
		$this->carabiner->css('myags.css', 'screen');
		$this->carabiner->css('myags.css', 'print');
		$this->carabiner->display('css');
	?>
<?php 
	if(!empty($arr_head_line) && is_array($arr_head_line) !== FALSE):
		 foreach ($arr_head_line as $hl):
			echo $hl . "\r\n";
		endforeach;
	endif; ?>
	<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/headjs/0.99/head.min.js"></script>
	<!-- <script src="<?php echo $this->config->item('base_url_assets') ?>js/head.js"></script> -->
	<script type="text/javascript">
		head.js(
			{jquery: "https://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"},
			{bootstrap: "http://netdna.bootstrapcdn.com/bootstrap/3.0.3/js/bootstrap.min.js"},
			{sectionhelper: "<?php echo $this->config->item('base_url_assets'); ?>js/as_section_helper.js"}
			<?php
				if(!empty($arr_headjs_line) && is_array($arr_headjs_line) !== FALSE):
					$c = count($arr_headjs_line);
					for ($x = 0; $x < $c; $x++):
						echo ",\r\n" . $arr_headjs_line[$x];
						//if($x < ($c - 1)) echo ",\r\n";
					endfor;
				endif;
			?>
		);
	</script>
</head>
<body>
<?php $url = site_url(); ?>
<div class="container">
	<div id="inter-section-nav">
		<ul>
		<?php
			if(isset($user_sections) && is_array($user_sections)):
				$first = TRUE;
				foreach($user_sections as $a):
					$class_name = $first?'first':'';
					$first = FALSE;
					$arr_cur_path = explode('/', $this->uri->uri_string());
					$arr_link_path = explode('/', $a['path']);
					if(!empty($a['path']) && $arr_cur_path[0] == $arr_link_path[0]) $class_name .= ' current';
					if($arr_link_path[0] == $arr_cur_path[0]) $class_name .= ' current'; 
						$href = $url . $a['path']; ?>
						<li<?php if(!empty($class_name)) echo ' class="' . $class_name . '"'; ?>><?php echo anchor($href, $a['name']);?></li>
				<?php endforeach;
			endif; ?>

			<?php
			// AGSOURCE DM
			if(false): //$credentials = $this->dm_model->get_credentials()): //AgSourceDM is not fully integrated so we need to use a manual process
				$class_name = $first?'first':'';
				$first = FALSE; ?>
			 	<form action="http://newdata.crinet.com/agsourcedm/" method="post" name="agsourcedm" id="agsourcedm" style="display:none;" target="_blank">
				  <input type="hidden" name="UserID" value="<?php echo $credentials['UserID']; ?>">
				  <input type="hidden" name="Password" value="<?php echo $credentials['Password']; ?>">
				  <!-- <input type="submit" value="LOG IN"> -->
				</form>
				<li<?php if(!empty($class_name)) echo ' class="' . $class_name . '"'; ?>><?php echo anchor('', 'AgSource DM', 'id="dm-anchor"'); ?></li>
			<?php endif;
			//END AGSOURCE DM ?>
		</ul>
	</div>
	<div id="header">
		<ul id="session-nav">
			<li><?php echo anchor('http://agsource.crinet.com', 'AgSource Site'); ?></li>
			<?php if(($this->as_ion_auth->logged_in())): ?>
				<li><?php echo anchor('auth/logout', 'Log Out'); ?></li>
				<?php if($this->as_ion_auth->has_permission("View non-own w permission")): ?>
					<li><?php echo anchor('auth/consult_manage_herds', 'Manage Herd Access'); ?></li>
					<li><?php echo anchor('auth/consult_request', 'Request Herd Access'); ?></li>
				<?php endif; ?>
				<?php if($this->session->userdata('active_group_id') == 2): ?>
					<li><?php echo anchor('auth/manage_consult', 'Manage Consultant Access'); ?></li>
					<!-- <li><?php echo anchor('auth/consult_access', 'Grant Herd Access'); ?></li> -->
				<?php endif; ?>
				<?php if($this->as_ion_auth->has_permission("Select Herd")): ?>
					<li><?php echo anchor('change_herd/select', 'Change Herd'); ?></li>
				<?php endif; ?>
				<?php if($this->as_ion_auth->has_permission("Request Herd")): ?>
					<li><?php echo anchor('change_herd/request', 'Request Herd'); ?></li>
				<?php endif; ?>
				<li><?php echo anchor('', 'My Account'); ?></li>
			<?php else:?>
				<li><?php echo anchor('auth/login', 'Log In');?></li>
				<li><?php echo anchor('auth/create_user', 'Register');?></li>
			<?php endif; ?>
		</ul>
		<?php echo anchor('', '<div class="header-link">&nbsp;</div>'); ?>
	</div> <!-- header -->
	<!--top navigation-->
	<ul class="primary-nav dropdown">
		<?php if(isset($section_nav) && !empty($section_nav)):
			echo $section_nav;
		endif;
		if(($this->as_ion_auth->logged_in())): ?>
			<li class="sectionnav"><a name="section-nav">Select Report</a><br />
				<ul class="sub-menu">
					<?php $arr_tmp = $user_sections;
					if(isset($arr_tmp) && is_array($arr_tmp)):
						foreach($arr_tmp as $a): ?>
							<li><?php echo anchor($url . $a['path'], $a['name']);?></li>
						<?php endforeach;
					endif; ?>
				</ul>
			</li> <!-- close "Select Section" li -->
			<?php $arr_groups = $this->session->userdata('arr_groups');
			if(isset($arr_groups) && is_array($arr_groups) && count($arr_groups) > 1): ?>
			<li class="groupnav"><a><?php echo $arr_groups[$this->session->userdata('active_group_id')]; ?></a><br />
				<ul class="sub-menu">
					<?php foreach($arr_groups as $k=>$v): ?>
						<li><?php echo anchor($url . 'auth/set_role/'. $k, $v);?></li>
					<?php endforeach; ?>
				</ul>
			</li> <!-- close "Select Section" li -->
			<?php endif; ?>
		<?php endif; ?>
	</ul>
	<!--left side of page-->
	<div id="main-content">
	<?php if (!empty($page_heading)) echo heading($page_heading);
	if(isset($error)):
		if (is_array($error) &&!empty($error)):
			foreach($error as $e) {?>
				<div id="errors"><?php echo $e;?></div>
			<?php }
		else: ?>
			<div id="errors"><?php echo $error;?></div>
<?php 	endif;
	elseif(isset($messages)):
		if (is_array($messages) && !empty($messages)):
			foreach($messages as $m) {?>
				<div id="infoMessage"><?php echo $m;?></div>
			<?php }
		elseif(is_array($messages) == FALSE): ?>
			<div id="infoMessage"><?php echo $messages;?></div>
<?php 	endif;
	elseif($this->session->flashdata('message') != ''): ?>
			<div id="infoMessage"><?php echo $this->session->flashdata('message');?></div>
<?php
	endif;
 ?>	