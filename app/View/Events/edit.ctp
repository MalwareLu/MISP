<?php
$mayModify = (($isAclModify && $this->request->data['Event']['user_id'] == $me['id']) || ($isAclModifyOrg && $this->request->data['Event']['org'] == $me['org']));
$mayPublish = ($isAclPublish && $this->request->data['Event']['org'] == $me['org']);

 echo $this->element('actions_menu', array(
    'event_id' => $this->request->data['Event']['id'],
    'cimbl_id' => $this->request->data['Event']['CIMBL_id'],
    'is_published' => $this->request->data['Event']['published'],
    'may_modify' => $mayModify,
    'may_publish' => $mayPublish,
    'is_admin' => $isAdmin,
    'is_site_admin' => $isSiteAdmin
)); ?>

<div class="events form">
<?php echo $this->Form->create('Event', array('type' => 'file'));?>
    <fieldset>
        <legend><?php echo __('Edit Event'); ?></legend>
<?php
echo $this->Form->input('id');
echo $this->Form->input('date', array(
    'label' => __('Detect Date'),
    'type' => 'text',
    'class' => 'datepicker'
));
echo $this->Form->input('start_time', array(
    'type' => 'text',
    'class' => 'datepicker'
));

echo $this->Form->input('detect_place_id', array('options' => $organisations));
echo $this->Form->input('detect_method_id');

echo $this->Form->input('report_time', array(
    'type' => 'text',
    'class' => 'datepicker',
    'div' => 'input clear'
));

echo $this->Form->input('end_time', array(
    'type' => 'text',
    'class' => 'datepicker'
));

echo $this->Form->input('reporter_organisation_id', array('options' => $organisations, 'label' => __('Reporter')));
echo $this->Form->input('reporter_channel_id', array('options' => $channels));


//echo $this->Form->input('CIMBL_id', array('empty' => __('None')));
echo $this->Form->input('SharingGroup', array('multiple' => 'checkbox', 'div' => 'clear', 'selected' => $selected_groups));

/*echo $this->Form->input('risk', array(
        'label' => 'Threat Level',
        'div' => 'input clear',
        'before' => $this->Html->div('forminfo', '', array('id' => 'EventRiskDiv'))));*/
echo $this->Form->input('threat_level_id');
echo $this->Form->input('ThreatType', array('type' => 'text'));
echo $this->Form->input('targeted_organisation_id', array('options' => $organisations));
echo $this->Form->input('targeted_domain_id', array('options' => $domains));

echo $this->Form->input('analysis', array(
        'options' => array($analysisLevels),
        'div' => 'input clear',
        'before' => $this->Html->div('forminfo', '', array('id' => 'EventAnalysisDiv'))
        ));
echo $this->Form->input('assessment_level_id');
if ('true' == Configure::read('CyDefSIG.sync')) {
    echo $this->Form->input('distribution', array(
            'options' => array($distributionLevels),
            'label' => 'Distribution',
'selected' => '3',
            ));
}
echo $this->Form->input('SharingAuthorisation', array('type' => 'text'));

echo $this->Form->input('CIMBL_id', array('empty' => __('None'), 'div' => 'clear'));

echo $this->Form->input('report_notes', array('div' => 'clear', 'class' => 'input-xxlarge'));
echo $this->Form->input('info', array('div' => 'clear', 'class' => 'input-xxlarge'));


echo $this->Form->input('Event.submittedgfi', array(
                'label' => '<b>GFI sandbox</b>',
                'type' => 'file',
                'div' => 'clear'
                ));
if ('true' == $canEditDist) {
    $this->Js->get('#EventDistribution')->event('change', 'showFormInfo("#EventDistribution")');
}
$this->Js->get('#EventRisk')->event('change', 'showFormInfo("#EventRisk")');
$this->Js->get('#EventAnalysis')->event('change', 'showFormInfo("#EventAnalysis")');
?>
    </fieldset>
<?php
echo $this->Form->button(__('Save'), array('class' => 'btn btn-primary'));
echo $this->Form->end();?>
</div>

<script type="text/javascript">
//
//Generate tooltip information
//
var formInfoValues = {
		'EventDistribution' : new Array(),
		'EventRisk' : new Array(),
		'EventAnalysis' : new Array()
};

<?php
foreach ($distributionDescriptions as $type => $def) {
	$info = isset($def['formdesc']) ? $def['formdesc'] : $def['desc'];
	echo "formInfoValues['EventDistribution']['" . addslashes($type) . "'] = \"" . addslashes($info) . "\";\n";	// as we output JS code we need to add slashes
}
foreach ($riskDescriptions as $type => $def) {
	$info = isset($def['formdesc']) ? $def['formdesc'] : $def['desc'];
	echo "formInfoValues['EventRisk']['" . addslashes($type) . "'] = \"" . addslashes($info) . "\";\n";	// as we output JS code we need to add slashes
}
foreach ($analysisDescriptions as $type => $def) {
	$info = isset($def['formdesc']) ? $def['formdesc'] : $def['desc'];
	echo "formInfoValues['EventAnalysis']['" . addslashes($type) . "'] = \"" . addslashes($info) . "\";\n";	// as we output JS code we need to add slashes
}
?>

$(document).ready(function() {

	$("#EventAnalysis, #EventRisk, #EventDistribution").on('mouseleave', function(e) {
	    $('#'+e.currentTarget.id).popover('destroy');
	});

	$("#EventAnalysis, #EventRisk, #EventDistribution").on('mouseover', function(e) {
	    var $e = $(e.target);
	    if ($e.is('option')) {
	        $('#'+e.currentTarget.id).popover('destroy');
	        $('#'+e.currentTarget.id).popover({
	            trigger: 'manual',
	            placement: 'right',
	            content: formInfoValues[e.currentTarget.id][$e.val()],
	        }).popover('show');
		}
	});

	// workaround for browsers like IE and Chrome that do now have an onmouseover on the 'options' of a select.
	// disadvangate is that user needs to click on the item to see the tooltip.
	// no solutions exist, except to generate the select completely using html.
	$("#EventAnalysis, #EventRisk, #EventDistribution").on('change', function(e) {
		var $e = $(e.target);
        $('#'+e.currentTarget.id).popover('destroy');
        $('#'+e.currentTarget.id).popover({
            trigger: 'manual',
            placement: 'right',
            content: formInfoValues[e.currentTarget.id][$e.val()],
        }).popover('show');
	});
});

</script>
<?php echo $this->Js->writeBuffer();