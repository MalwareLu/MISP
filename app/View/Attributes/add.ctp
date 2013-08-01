<<<<<<< HEAD
<div class="attributes form">
<?php echo $this->Form->create('Attribute');?>
	<fieldset>
		<legend><?php echo __('Add Attribute'); ?></legend>
<?php
echo $this->Form->hidden('event_id');
echo $this->Form->input('category', array(
		'between' => $this->Html->div('forminfo', '', array('id' => 'AttributeCategoryDiv')),
		'empty' => '(choose one)'
		));
echo $this->Form->input('type', array(
		'between' => $this->Html->div('forminfo', '', array('id' => 'AttributeTypeDiv')),
		'empty' => '(first choose category)'
		));
if ('true' == Configure::read('CyDefSIG.sync')) {
	echo $this->Form->input('distribution', array('label' => 'Distribution', 'selected' => $maxDist,
		'between' => $this->Html->div('forminfo', '', array('id' => 'AttributeDistributionDiv'))
	));
}
echo $this->Form->input('to_ids', array(
			/*'checked' => true,*/
			'before' => $this->Html->div('forminfo', isset($attrDescriptions['signature']['formdesc']) ? $attrDescriptions['signature']['formdesc'] : $attrDescriptions['signature']['desc']),
			'label' => 'IDS Signature?',
			'div' => 'clear'
));
echo $this->Form->input('batch_import', array(
		'type' => 'checkbox',
		'after' => $this->Html->div('forminfo', 'Create multiple attributes one per line'),
));
echo $this->Form->input('value', array(
			'type' => 'textarea',
			'error' => array('escape' => false),
			'div' => 'clear',
			'class' => 'input-xxlarge'
));

echo $this->Form->input('kill_chain_id', array('default' => 8));
echo $this->Form->input('blacklist', array('type' => 'checkbox'));
echo $this->Form->input('malware_eradication', array('type' => 'checkbox'));
echo $this->Form->input('vuln_manag', array('type' => 'checkbox'));

// link an onchange event to the form elements
$this->Js->get('#AttributeCategory')->event('change', 'formCategoryChanged("#AttributeCategory")');
$this->Js->get('#AttributeType')->event('change', 'showFormInfo("#AttributeType")');
$this->Js->get('#AttributeDistribution')->event('change', 'showFormInfo("#AttributeDistribution")');
?>
	</fieldset>
<?php echo $this->Form->button(__('Submit'), array('class' => 'btn btn-primary'));
echo $this->Form->end();?>
</div>
<div class="actions">
	<ul>
		<?php echo $this->element('actions_menu'); ?>
	</ul>
</div>
<script type="text/javascript">
//
//Generate Category / Type filtering array
//
var category_type_mapping = new Array();
<?php
foreach ($categoryDefinitions as $category => $def) {
	echo "category_type_mapping['" . addslashes($category) . "'] = {";
	$first = true;
	foreach ($def['types'] as $type) {
		if ($first) $first = false;
		else echo ', ';
		echo "'" . addslashes($type) . "' : '" . addslashes($type) . "'";
	}
	echo "}; \n";
}
?>

function formCategoryChanged(id) {
	showFormInfo(id); // display the tooltip
	// fill in the types
	var options = $('#AttributeType').prop('options');
	$('option', $('#AttributeType')).remove();
	$.each(category_type_mapping[$('#AttributeCategory').val()], function(val, text) {
		options[options.length] = new Option(text, val);
	});
	// enable the form element
	$('#AttributeType').prop('disabled', false);
}


//
// Generate tooltip information
//
var formInfoValues = new Array();
<?php
foreach ($typeDefinitions as $type => $def) {
	$info = isset($def['formdesc']) ? $def['formdesc'] : $def['desc'];
	echo "formInfoValues['" . addslashes($type) . "'] = \"" . addslashes($info) . "\";\n";  // as we output JS code we need to add slashes
}
foreach ($categoryDefinitions as $category => $def) {
	$info = isset($def['formdesc']) ? $def['formdesc'] : $def['desc'];
	echo "formInfoValues['" . addslashes($category) . "'] = \"" . addslashes($info) . "\";\n"; // as we output JS code we need to add slashes
}
foreach ($distributionDescriptions as $type => $def) {
	$info = isset($def['formdesc']) ? $def['formdesc'] : $def['desc'];
	echo "formInfoValues['" . addslashes($type) . "'] = \"" . addslashes($info) . "\";\n";  // as we output JS code we need to add slashes
}
?>

function showFormInfo(id) {
	idDiv = id+'Div';
	// LATER use nice animations
	//$(idDiv).hide('fast');
	// change the content
	var value = $(id).val();    // get the selected value
	$(idDiv).html(formInfoValues[value]);    // search in a lookup table

	// show it again
	$(idDiv).fadeIn('slow');
}

// hide the formInfo things
$('#AttributeTypeDiv').hide();
$('#AttributeCategoryDiv').hide();
$('#AttributeType').prop('disabled', true);
$('#AttributeDistributionDiv').hide();


</script>
=======
<div class="attributes form">
<?php echo $this->Form->create('Attribute');?>
	<fieldset>
		<legend><?php echo __('Add Attribute'); ?></legend>
		<?php
		echo $this->Form->hidden('event_id');
		echo $this->Form->input('category', array(
				'empty' => '(choose one)'
				));
		echo $this->Form->input('type', array(
				'empty' => '(first choose category)'
				));
		if ('true' == Configure::read('CyDefSIG.sync')) {
			echo $this->Form->input('distribution', array(
				'options' => array($distributionLevels),
				'label' => 'Distribution',
				'selected' => $currentDist,
			));
		}
		echo $this->Form->input('value', array(
				'type' => 'textarea',
				'error' => array('escape' => false),
				'div' => 'input clear',
				'class' => 'input-xxlarge'
		));
		?>
		<div class="input clear"></div>
		<?php
		echo $this->Form->input('to_ids', array(
					'checked' => false,
					'data-content' => isset($attrDescriptions['signature']['formdesc']) ? $attrDescriptions['signature']['formdesc'] : $attrDescriptions['signature']['desc'],
					'label' => 'for Intrusion Detection System',
		));
		echo $this->Form->input('batch_import', array(
				'type' => 'checkbox',
				'data-content' => 'Create multiple attributes one per line',
		));

		// link an onchange event to the form elements
		$this->Js->get('#AttributeCategory')->event('change', 'formCategoryChanged("#AttributeCategory")');
		?>
	</fieldset>
<?php
echo $this->Form->button('Submit', array('class' => 'btn btn-primary'));
echo $this->Form->end();
?>
</div>
<div class="actions <?php echo $debugMode;?>">
	<ul class="nav nav-list">
		<li><a href="/events/view/<?php echo $this->request->data['Attribute']['event_id']; ?>">View Event</a></li>
		<li><a href="/logs/event_index/<?php echo $this->request->data['Attribute']['event_id'];?>">View Event History</a></li>
		<li><a href="/events/edit/<?php echo $this->request->data['Attribute']['event_id']; ?>">Edit Event</a></li>
		<li><?php echo $this->Form->postLink('Delete Event', array('controller' => 'events', 'action' => 'delete', $this->request->data['Attribute']['event_id']), null, __('Are you sure you want to delete # %s?', $this->request->data['Attribute']['event_id'])); ?></li>
		<li class="divider"></li>
		<li class="active"><a href="/attributes/add/<?php echo $this->request->data['Attribute']['event_id']; ?>">Add Attribute</a></li>
		<li><a href="/attributes/add_attachment/<?php echo $this->request->data['Attribute']['event_id']; ?>">Add Attachment</a></li>
		<li><a href="/events/addIOC/<?php echo $this->request->data['Attribute']['event_id']; ?>">Populate from IOC</a></li>
		<li><a href="/attributes/add_threatconnect/<?php echo $this->request->data['Attribute']['event_id']; ?>">Populate from ThreatConnect</a></li>
		<li class="divider"></li>
		<li><a href="/events/contact/<?php echo $this->request->data['Attribute']['event_id']; ?>">Contact Reporter</a></li>
		<li><a href="/events/xml/download/<?php echo $this->request->data['Attribute']['event_id']; ?>">Download as XML</a></li>
		<?php if ($published): ?>
		<li><a href="/events/downloadOpenIOCEvent/<?php echo $this->request->data['Attribute']['event_id'];?>">Download as IOC</a></li>
		<li><a href="/events/csv/download/<?php echo $this->request->data['Attribute']['event_id'];?>">Download as CSV</a></li>
		<?php endif; ?>
		<li class="divider"></li>
		<li><a href="/events/index">List Events</a></li>
		<?php if ($isAclAdd): ?>
		<li><a href="/events/add">Add Event</a></li>
		<?php endif; ?>
	</ul>
</div>

<script type="text/javascript">
//
//Generate Category / Type filtering array
//
var category_type_mapping = new Array();
<?php
foreach ($categoryDefinitions as $category => $def) {
	echo "category_type_mapping['" . addslashes($category) . "'] = {";
	$first = true;
	foreach ($def['types'] as $type) {
		if ($first) $first = false;
		else echo ', ';
		echo "'" . addslashes($type) . "' : '" . addslashes($type) . "'";
	}
	echo "}; \n";
}
?>

function formCategoryChanged(id) {
	// fill in the types
	var options = $('#AttributeType').prop('options');
	$('option', $('#AttributeType')).remove();
	$.each(category_type_mapping[$('#AttributeCategory').val()], function(val, text) {
		options[options.length] = new Option(text, val);
	});
	// enable the form element
	$('#AttributeType').prop('disabled', false);
}


//
// Generate tooltip information
//
var formInfoValues = new Array();
<?php
foreach ($typeDefinitions as $type => $def) {
	$info = isset($def['formdesc']) ? $def['formdesc'] : $def['desc'];
	echo "formInfoValues['" . addslashes($type) . "'] = \"" . addslashes($info) . "\";\n";  // as we output JS code we need to add slashes
}
foreach ($categoryDefinitions as $category => $def) {
	$info = isset($def['formdesc']) ? $def['formdesc'] : $def['desc'];
	echo "formInfoValues['" . addslashes($category) . "'] = \"" . addslashes($info) . "\";\n"; // as we output JS code we need to add slashes
}
foreach ($distributionDescriptions as $type => $def) {
	$info = isset($def['formdesc']) ? $def['formdesc'] : $def['desc'];
	echo "formInfoValues['" . addslashes($type) . "'] = \"" . addslashes($info) . "\";\n";  // as we output JS code we need to add slashes
}
?>

$(document).ready(function() {

	$("#AttributeType, #AttributeCategory, #Attribute, #AttributeDistribution").on('mouseleave', function(e) {
	    $('#'+e.currentTarget.id).popover('destroy');
	});

	$("#AttributeType, #AttributeCategory, #Attribute, #AttributeDistribution").on('mouseover', function(e) {
	    var $e = $(e.target);
	    if ($e.is('option')) {
	        $('#'+e.currentTarget.id).popover('destroy');
	        $('#'+e.currentTarget.id).popover({
	            trigger: 'manual',
	            placement: 'right',
	            content: formInfoValues[$e.val()],
	        }).popover('show');
	    }
	});

	$("input, label").on('mouseleave', function(e) {
	    $('#'+e.currentTarget.id).popover('destroy');
	});

	$("input, label").on('mouseover', function(e) {
		var $e = $(e.target);
		$('#'+e.currentTarget.id).popover('destroy');
        $('#'+e.currentTarget.id).popover({
            trigger: 'manual',
            placement: 'right',
        }).popover('show');
	});

	// workaround for browsers like IE and Chrome that do now have an onmouseover on the 'options' of a select.
	// disadvangate is that user needs to click on the item to see the tooltip.
	// no solutions exist, except to generate the select completely using html.
	$("#AttributeType, #AttributeCategory, #Attribute, #AttributeDistribution").on('change', function(e) {
	    var $e = $(e.target);
        $('#'+e.currentTarget.id).popover('destroy');
        $('#'+e.currentTarget.id).popover({
            trigger: 'manual',
            placement: 'right',
            content: formInfoValues[$e.val()],
        }).popover('show');
	});

});



</script>
>>>>>>> develop
<?php echo $this->Js->writeBuffer(); // Write cached scripts