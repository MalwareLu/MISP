<div class="servers index">
	<h2><?php echo __('Servers'); ?></h2>
	<table cellpadding="0" cellspacing="0" class="table table-striped table-condensed">
	<tr>
			<th><?php echo $this->Paginator->sort('push');?></th>
			<th><?php echo $this->Paginator->sort('pull');?></th>
			<th><?php echo $this->Paginator->sort('url');?></th>
			<th>From</th>
			<?php

if ($isAdmin): ?>
			<th><?php echo $this->Paginator->sort('org');?></th>
			<?php
endif; ?>
			<th>Last Pulled ID</th>
			<th>Last Pushed ID</th>
			<th class="actions"><?php echo __('Actions');?></th>
	</tr>
	<?php
foreach ($servers as $server): ?>
	<tr>
		<td class="short" style="text-align: center;"><?php echo ($server['Server']['push'])? 'Yes' : 'No'; ?>&nbsp;</td>
		<td class="short" style="text-align: center;"><?php echo ($server['Server']['pull'])? 'Yes' : 'No'; ?>&nbsp;</td>
		<td><?php echo h($server['Server']['url']); ?>&nbsp;</td>
		<td><?php echo h($server['Server']['organization']); ?>&nbsp;</td>
		<?php
	if ($isAdmin): ?>
		<td class="short"><?php echo h($server['Server']['org']); ?>&nbsp;</td>
		<?php
	endif; ?>
		<td class="short"><?php echo $server['Server']['lastpulledid']; ?></td>
		<td class="short"><?php echo $server['Server']['lastpushedid']; ?></td>
		<td class="actions">
			<?php
			$mayModify = ($me['org'] == 'ADMIN' || $me['org'] == $server['Server']['organization']) || ($isAdmin && ($server['Server']['organization'] == $me['org']));
			if ($mayModify) echo $this->Html->link(__('Edit'), array('action' => 'edit', $server['Server']['id']), array('class' => 'btn'));
			if ($mayModify) echo $this->Form->postLink(__('Delete'), array('action' => 'delete', $server['Server']['id']), array('class' => 'btn'), __('Are you sure you want to delete # %s?', $server['Server']['id'])); ?>

			<?php // if ($server['Server']['pull']) echo $this->Form->postLink(__('Pull'), array('action' => 'pull', $server['Server']['id']) ); ?>
			<?php // if ($server['Server']['push']) echo $this->Form->postLink(__('Push'), array('action' => 'push', $server['Server']['id']) ); ?>

			<?php if ($server['Server']['pull'] && $me['org'] == 'ADMIN') echo $this->Form->postLink(__('Pull All'), array('action' => 'pull', $server['Server']['id'], 'full'), array('class' => 'btn') ); ?>
			<?php if ($server['Server']['push'] && $me['org'] == 'ADMIN') echo $this->Form->postLink(__('Push All'), array('action' => 'push', $server['Server']['id'], 'full'), array('class' => 'btn') ); ?>
		</td>
	</tr>
	<?php
endforeach; ?>
	</table>
	<p>
	<?php
	echo $this->Paginator->counter(array(
	'format' => __('Page {:page} of {:pages}, showing {:current} records out of {:count} total, starting on record {:start}, ending on {:end}')
	));
	?>	</p>

	<div class="pagination">
		<ul>
		<?php
			echo $this->Paginator->prev('&laquo; ' . __('previous'), array('tag' => 'li', 'escape' => false), null, array('tag' => 'li', 'class' => 'prev disabled', 'escape' => false, 'disabledTag' => 'span'));
			echo $this->Paginator->numbers(array('modulus' => 20, 'separator' => '', 'tag' => 'li', 'currentClass' => 'active', 'currentTag' => 'span'));
			echo $this->Paginator->next(__('next') . ' &raquo;', array('tag' => 'li', 'escape' => false), null, array('tag' => 'li', 'class' => 'next disabled', 'escape' => false, 'disabledTag' => 'span'));
		?>
		</ul>
	</div>

</div>
<div class="actions">
	<ul>
		<li><?php if ($isAclAdd && $me['org'] == 'ADMIN') echo $this->Html->link(__('New Server'), array('controller' => 'servers', 'action' => 'add')); ?></li>
		<li><?php echo $this->Html->link(__('List Servers'), array('controller' => 'servers', 'action' => 'index'));?></li>
		<li>&nbsp;</li>
		<?php echo $this->element('actions_menu'); ?>
	</ul>
</div>
