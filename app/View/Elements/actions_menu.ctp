		<h3><?php echo __('Event Actions'); ?></h3>
		<li><?php
if ($isAclAdd) echo $this->Html->link(__('New Event', true), array('controller' => 'events', 'action' => 'add')); ?></li>
		<li><?php echo $this->Html->link(__('List Events', true), array('controller' => 'events', 'action' => 'index')); ?></li>
		<li><?php echo $this->Html->link(__('List Attributes', true), array('controller' => 'attributes', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('Search Attributes', true), array('controller' => 'attributes', 'action' => 'search')); ?> </li>
		<li><?php echo $this->Html->link(__('Export', true), array('controller' => 'events', 'action' => 'export')); ?> </li>
		<li><?php
if ($isAclAuth) echo $this->Html->link(__('Automation', true), array('controller' => 'events', 'action' => 'automation')); ?></li>
		<!--<li>&nbsp;</li>
		<h3><?php echo __('Global Actions'); ?></h3>
		<li><?php echo $this->Html->link(__('News', true), array('controller' => 'users', 'action' => 'news')); ?> </li>
		<li><?php echo $this->Html->link(__('My Profile', true), array('controller' => 'users', 'action' => 'view', 'me')); ?> </li>
		<li><?php echo $this->Html->link(__('Members List', true), array('controller' => 'users', 'action' => 'memberslist')); ?> </li>
		<li><?php echo $this->Html->link(__('User Guide', true), array('controller' => 'pages', 'action' => 'display', 'documentation')); ?> </li>
		<li><?php echo $this->Html->link(__('Terms & Conditions', true), array('controller' => 'users', 'action' => 'terms')); ?> </li>
		<li><?php echo $this->Html->link(__('Log out', true), array('controller' => 'users', 'action' => 'logout')); ?> </li>-->

		<?php
if (('true' == Configure::read('CyDefSIG.sync')) && ($isAclSync || $isAdmin)): ?>
		<!--<li>&nbsp;</li>
		<h3><?php echo __('Sync Actions'); ?></h3>
		<li><?php echo $this->Html->link(__('List Servers'), array('controller' => 'servers', 'action' => 'index'));?></li>-->
        <?php
endif;?>

		<?php
		//Site admin
if($isSiteAdmin): ?>
		<!--<li>&nbsp;</li>
		<h3><?php echo __('Input Filters'); ?></h3>
		<li><?php echo $this->Html->link(__('Import Blacklist', true), array('controller' => 'blacklists', 'action' => 'index', 'admin' => true)); ?> </li>
		<li><?php echo $this->Html->link(__('Import Regexp', true), array('controller' => 'regexp', 'action' => 'index', 'admin' => true)); ?> </li>
		<li><?php echo $this->Html->link(__('Signature Whitelist', true), array('controller' => 'whitelists', 'action' => 'index', 'admin' => true)); ?> </li>
		<li>&nbsp;</li>
		<h3><?php echo __('Administration'); ?></h3>
		<li><?php echo $this->Html->link(__('New User', true), array('controller' => 'users', 'action' => 'add', 'admin' => true)); ?> </li>
		<li><?php echo $this->Html->link(__('List Users', true), array('controller' => 'users', 'action' => 'index', 'admin' => true)); ?> </li>
		<li><?php echo $this->Html->link(__('New Role', true), array('controller' => 'roles', 'action' => 'add', 'admin' => true)); ?> </li>
		<li><?php echo $this->Html->link(__('List Roles', true), array('controller' => 'roles', 'action' => 'index', 'admin' => true)); ?> </li>
		<li><?php echo $this->Html->link(__('Contact users', true), array('controller' => 'users', 'action' => 'email', 'admin' => true)); ?> </li>
		<li>&nbsp;</li>-->
		<?php
endif;?>

		<?php
		//org admin
if($isAdmin && !$isSiteAdmin): ?>
		<li>&nbsp;</li>
		<h3><?php echo __('Input Filters'); ?></h3>
		<li><?php echo $this->Html->link(__('Import Blacklist', true), array('controller' => 'blacklists', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('Import Regexp', true), array('controller' => 'regexp', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('Signature Whitelist', true), array('controller' => 'whitelists', 'action' => 'index')); ?> </li>
		<li>&nbsp;</li>
		<h3><?php echo __('Administration'); ?></h3>
		<li><?php echo $this->Html->link(__('New User', true), array('controller' => 'users', 'action' => 'add', 'admin' => true)); ?> </li>
		<li><?php echo $this->Html->link(__('List Users', true), array('controller' => 'users', 'action' => 'index', 'admin' => true)); ?> </li>
		<li><?php echo $this->Html->link(__('List Roles', true), array('controller' => 'roles', 'action' => 'index')); ?> </li>
		<li>&nbsp;</li>
		<?php
endif;?>

		<?php
		//normal user
if(!$isSiteAdmin && !$isAclAdmin): ?>
		<li>&nbsp;</li>
		<h3><?php echo __('Input Filters'); ?></h3>
		<li><?php echo $this->Html->link(__('Import Blacklist', true), array('controller' => 'blacklists', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('Import Regexp', true), array('controller' => 'regexp', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('Signature Whitelist', true), array('controller' => 'whitelists', 'action' => 'index')); ?> </li>
		<?php
endif;?>

		<?php
if($isAclAudit): ?>
		<!--<h3><?php echo __('Audit'); ?></h3>
		<li><?php echo $this->Html->link(__('List Logs', true), array('controller' => 'logs', 'action' => 'index', 'admin' => true)); ?> </li>
		<li><?php echo $this->Html->link(__('Search Logs', true), array('controller' => 'logs', 'action' => 'admin_search', 'admin' => true)); ?> </li>-->
		<?php
endif;