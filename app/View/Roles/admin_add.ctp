<div class="roles form">
<?php echo $this->Form->create('Role');?>
	<fieldset>
		<legend><?php echo __('Add Role'); ?></legend>
		<?php echo $this->Form->input('name');?>
		<div class="clear">
		<?php echo $this->Form->radio('permission', $options, array('value' => '3'));?>
		<?php echo $this->Form->input('perm_sync', array('type' => 'checkbox', 'checked' => false));?>
		<?php echo $this->Form->input('perm_admin', array('type' => 'checkbox', 'checked' => false));?>
		<?php echo $this->Form->input('perm_audit', array('type' => 'checkbox', 'checked' => false));?>
		<?php echo $this->Form->input('perm_auth', array('type' => 'checkbox', 'checked' => false));?>
	</fieldset>
<?php echo $this->Form->button(__('Submit'), array('class' => 'btn btn-primary'));
echo $this->Form->end();?>
</div>
<div class="actions">
	<ul>
		<?php echo $this->element('actions_menu'); ?>
	</ul>
</div>

<?php
$this->Js->get('#RolePermission0')->event('change', 'deactivateActions()');
$this->Js->get('#RolePermission1')->event('change', 'deactivateActions()');

$this->Js->get('#RolePermSync')->event('change', 'checkPerms("RolePermSync")');
$this->Js->get('#RolePermAdmin')->event('change', 'checkPerms("RolePermAdmin")');
$this->Js->get('#RolePermAudit')->event('change', 'checkPerms("RolePermAudit")');
?>

<script type="text/javascript">
// only be able to tick perm_sync if manage org events and above.

function deactivateActions() {
	document.getElementById("RolePermSync").checked = false;
	document.getElementById("RolePermAdmin").checked = false;
	document.getElementById("RolePermAudit").checked = false;
}

function checkPerms(id) {
	if ((document.getElementById("RolePermission0").checked) || (document.getElementById("RolePermission1").checked)) {
		document.getElementById(id).checked = false;
	}
}

</script>
<?php echo $this->Js->writeBuffer();