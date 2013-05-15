<div class="sharingGroups form">
<?php echo $this->Form->create('SharingGroup'); ?>
	<fieldset>
		<legend><?php echo __('Edit Sharing Group'); ?></legend>
	<?php
		echo $this->Form->input('id');
		echo $this->Form->input('name');
		echo $this->Form->input('category');
		echo $this->Form->input('description', array('class' => 'input-xxlarge', 'div' => 'clear'));
	?>
	</fieldset>
<?php echo $this->Form->button(__('Submit'), array('class' => 'btn btn-primary')); ?>
<?php echo $this->Form->end(); ?>
</div>
<div class="actions">
	<h3><?php echo __('Actions'); ?></h3>
	<ul>

		<li><?php echo $this->Form->postLink(__('Delete'), array('action' => 'delete', $this->Form->value('SharingGroup.id')), null, __('Are you sure you want to delete # %s?', $this->Form->value('SharingGroup.id'))); ?></li>
		<li><?php echo $this->Html->link(__('List Sharing Groups'), array('action' => 'index')); ?></li>
		<li><?php echo $this->Html->link(__('List Events'), array('controller' => 'events', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Event'), array('controller' => 'events', 'action' => 'add')); ?> </li>
	</ul>
</div>
