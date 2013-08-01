<?php
if(empty($is_site_admin)) $is_site_admin = false;
if(empty($may_modify)) $may_modify = false;
if(empty($is_published)) $is_published = false;
if(empty($is_admin)) $is_admin = false;
if(empty($may_publish)) $may_publish = false;
?>

<div class="actions <?php echo $debugMode;?>">
	<ul class="nav nav-list">
		<?php if(!empty($event_id)) { ?>
			<li><a href="/events/view/<?php echo $event_id;?>">View Event</a></li>
			<li><a href="/logs/event_index/<?php echo $event_id;?>">View Event History</a></li>
			<?php if($is_site_admin || $may_modify){?>
				<li><a href="/events/edit/<?php echo $event_id;?>">Edit Event</a></li>
				<li><?php echo $this->Form->postLink('Delete Event', array('action' => 'delete', $event_id), null, __('Are you sure you want to delete # %s?', $event_id)); ?></li>
				<li class="divider"></li>
				<li><a href="/attributes/add/<?php echo $event_id;?>">Add Attribute</a></li>
				<li><a href="/attributes/add_attachment/<?php echo $event_id;?>">Add Attachment</a></li>
				<li><a href="/events/addIOC/<?php echo $event_id;?>">Populate from IOC</a></li>
				<li><a href="/attributes/add_threatconnect/<?php echo $event_id; ?>">Populate from ThreatConnect</a></li>
			<?php }else{ ?>
				<li><a href="/shadow_attributes/add/<?php echo $event_id;?>">Propose Attribute</a></li>
				<li><a href="/shadow_attributes/add_attachment/<?php echo $event_id?>">Propose Attachment</a></li>
			<?php } ?>
			<?php if ( 0 == $is_published && ($is_admin || $may_publish)){ ?>
				<li><?php echo $this->Form->postLink('Publish Event', array('action' => 'alert', $event_id), null, 'Are you sure this event is complete and everyone should be informed?'); ?></li>
				<li><?php echo $this->Form->postLink('Publish (no email)', array('action' => 'publish', $event_id), null, 'Publish but do NOT send alert email? Only for minor changes!'); ?></li>
			<?php } ?>
			<li><a href="/events/contact/<?php echo $event_id;?>">Contact Reporter</a></li>
			<li><?php echo $this->Html->link(__('Download as XML', true), array('action' => 'download', $event_id, 'ext' => 'xml')); ?></li>
			<li><?php echo $this->Html->link(__('Download CSV', true), array('action' => 'download', $event_id, 'ext' => 'csv')); ?></li>
			<?php if(!empty($cimbl_id)){?>
		    <li><?php echo $this->Html->link(__('Download CIMBL XML', true), array('controller' => 'CIMBLs', 'action' => 'download', $cimbl_id, 'ext' => 'xml')); ?></li>
		    <?php } ?>
			<?php if ($is_published){ ?>
				<li><a href="/events/downloadOpenIOCEvent/<?php echo $event_id;?>">Download as IOC</a></li>
				<li><?php echo $this->Html->link(__('Download CSV', true), array('action' => 'download', $event_id, 'ext' => 'csv')); ?></li>
			<?php } ?>
			<li class="divider"></li>
		<?php } ?>

		<?php if(!empty($user_id) && $can_i_edit){?>
			<li><?php echo $this->Html->link(__('Edit User', true), array('action' => 'edit', $user['User']['id'])); ?></li>
			<li class="divider"></li>
		<?php } ?>

		<li><a href="/events/index">List Events</a></li>
		<?php if ($isAclAdd): ?>
		<li><a href="/events/add">Add Event</a></li>
		<?php endif; ?>
		<li class="divider"></li>
		<li><a href="/attributes/index">List Attributes</a></li>
		<li><a href="/attributes/search">Search Attributes</a></li>
		<li class="divider"></li>
		<li><a href="/events/export">Export</a></li>
		<?php if ($isAclAuth): ?>
		<li><a href="/events/automation">Automation</a></li>
		<?php endif;?>

		<?php if(!empty($page) && $page == 'logs'){?>
			<li clas="divider"></li>
			<li><?php echo $this->Html->link('List Logs', array('admin' => true, 'action' => 'index'));?></li>
			<li><?php echo $this->Html->link('Search Logs', array('admin' => true, 'action' => 'search'));?></li>
		<?php } ?>
	</ul>
</div>



		<?php
		// used? not used?
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

