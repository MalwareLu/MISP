<?php if(empty($this->passedArgs['key'])) $this->passedArgs['key'] = '';?>
<?php //die(debug($events));?>
<div class="events index">
    <h2><?php echo __('Filters');?></h2>
    <?php echo $this->Form->create('', array('action' => 'index')); ?>
    <div class="input-append">
        <?php
        echo $this->Form->input('key', array('value' => $this->passedArgs['key'], 'div' => false, 'class' => 'input-xxlarge', 'label' => false));
        echo $this->Form->button('Go', array('class' => 'btn', 'div' => false));
        ?>
    </div>
    <?php echo $this->Form->end(); ?>

    <h2>Events</h2>
    <div class="pagination">
        <ul>
        <?php
        $this->Paginator->options(array(
            'update' => '.span12',
            'evalScripts' => true,
            'before' => '$(".progress").show()',
            'complete' => '$(".progress").hide()',
        ));

            echo $this->Paginator->prev('&laquo; ' . __('previous'), array('tag' => 'li', 'escape' => false), null, array('tag' => 'li', 'class' => 'prev disabled', 'escape' => false, 'disabledTag' => 'span'));
            echo $this->Paginator->numbers(array('modulus' => 20, 'separator' => '', 'tag' => 'li', 'currentClass' => 'active', 'currentTag' => 'span'));
            echo $this->Paginator->next(__('next') . ' &raquo;', array('tag' => 'li', 'escape' => false), null, array('tag' => 'li', 'class' => 'next disabled', 'escape' => false, 'disabledTag' => 'span'));
        ?>
        </ul>
    </div>

    <div class="progress progress-striped active hide">
        <div class="bar" style="width: 100%;"></div>
    </div>

    <table cellpadding="0" cellspacing="0" class="table table-striped table-condensed">
    <tr>
            <!--<th><?php echo $this->Paginator->sort('published', 'Valid.');?></th>-->

            <th><?php echo $this->Paginator->sort('id');?></th>
            <th><?php echo $this->Paginator->sort('date', 'Detect Time');?></th>
            <th><?php echo $this->Paginator->sort('targeted_organisation_id');?></th>
            <th><?php echo $this->Paginator->sort('targeted_domain_id');?></th>
            <th><?php echo $this->Paginator->sort('ThreatType');?></th>
            <th><?php echo $this->Paginator->sort('threat_level');?></th>
            <th><?php echo $this->Paginator->sort('info');?></th>
            <th><?php echo $this->Paginator->sort('CIMBL_id');?></th>
            <th class="actions"><?php echo __('Actions');?></th>
    </tr><?php
foreach ($events as $event):?>
    <tr>
        <!--<td class="short" onclick="document.location ='<?php echo $this->Html->url(array('action' => 'view', $event['Event']['id']), true);?>';"><?php
    if ($event['Event']['published'] == 1) {
            echo $this->Html->image('yes.png', array('title' => 'Validated', 'alt' => 'Validated', 'width' => '16', 'hight' => '16'));
    } else {
            echo $this->Html->image('no.png', array('title' => 'Not validated', 'alt' => 'Not Validated', 'width' => '16', 'hight' => '16'));
    }?>
        &nbsp;</td>-->
        <?php
        //die(debug($this->request->webroot));
        ?>
        <td>
        <?php echo $this->Html->link($event['Event']['id'], array('controller' => 'events', 'action' => 'view', $event['Event']['id'])); ?>
        &nbsp;</td>
        <td onclick="document.location ='<?php echo $this->Html->url(array('action' => 'view', $event['Event']['id']), true);?>';">
        <?php echo $event['Event']['date']; ?>&nbsp;</td>
        <td onclick="document.location ='<?php echo $this->Html->url(array('action' => 'view', $event['Event']['id']), true);?>';">
        <?php echo $event['TargetedEntity']['name']; ?>&nbsp;</td>
        <td onclick="document.location ='<?php echo $this->Html->url(array('action' => 'view', $event['Event']['id']), true);?>';">
        <?php echo $event['TargetedDomain']['domain']; ?>&nbsp;</td>
        <td onclick="document.location ='<?php echo $this->Html->url(array('action' => 'view', $event['Event']['id']), true);?>';">
        <?php echo $event['Event']['ThreatType']; ?>&nbsp;</td>
        <td onclick="document.location ='<?php echo $this->Html->url(array('action' => 'view', $event['Event']['id']), true);?>';">
        <?php echo $event['ThreatLevel']['name']; ?>&nbsp;</td>
        <td onclick="document.location ='<?php echo $this->Html->url(array('action' => 'view', $event['Event']['id']), true);?>';">
        <?php echo nl2br($event['Event']['info']); ?>&nbsp;</td>

        <td>
            <?php
            echo $this->Form->create('Event', array(
                'action' => 'updateCIMBL.json',
                'class' => 'updateCIMBLForm'));
            echo $this->Form->input('CIMBL_id', array(
                'selected' => $event['Event']['CIMBL_id'],
                'empty' => __('None'),
                'label' => false,
                'style' => 'width:auto;',
                'id' => '',
                'class' => 'updateCIMBL',
                'div' => false
                )
            );
            echo $this->Form->hidden('id', array('value' => $event['Event']['id']));
            echo $this->Form->end();

            ?>
        &nbsp;</td>

        <td class="actions">
            <?php
            if (0 == $event['Event']['published'] && ($isSiteAdmin || ($isAclPublish && $event['Event']['org'] == $me['org'])))
                echo $this->Form->postLink('Publish Event', array('action' => 'alert', $event['Event']['id']), array('class' => 'btn'), 'Are you sure this event is complete and everyone should be informed?');
            elseif (0 == $event['Event']['published']) echo 'Not published';
            ?>
        <?php
    if ($isSiteAdmin || ($isAclModify && $event['Event']['user_id'] == $me['id']) || ($isAclModifyOrg && $event['Event']['org'] == $me['org'])) {
        echo $this->Html->link(__('Edit', true), array('action' => 'edit', $event['Event']['id']), array('class' => 'btn'));
        echo $this->Form->postLink(__('Delete'), array('action' => 'delete', $event['Event']['id']), array('class' => 'btn'), __('Are you sure you want to delete # %s?', $event['Event']['id']));
    }?>
            <?php echo $this->Html->link(__('View', true), array('controller' => 'events', 'action' => 'view', $event['Event']['id']), array('class' => 'btn')); ?>
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
    ?>  </p>

    <div class="pagination">
        <ul>
        <?php
            echo $this->Paginator->prev('&laquo; ' . __('previous'), array('tag' => 'li', 'escape' => false), null, array('tag' => 'li', 'class' => 'prev disabled', 'escape' => false, 'disabledTag' => 'span'));
            echo $this->Paginator->numbers(array('modulus' => 20, 'separator' => '', 'tag' => 'li', 'currentClass' => 'active', 'currentTag' => 'span'));
            echo $this->Paginator->next(__('next') . ' &raquo;', array('tag' => 'li', 'escape' => false), null, array('tag' => 'li', 'class' => 'next disabled', 'escape' => false, 'disabledTag' => 'span'));
        ?>
        </ul>
    </div>
    <div class="progress progress-striped active hide">
        <div class="bar" style="width: 100%;"></div>
    </div>
</div>
<div class="actions">
    <ul>
        <?php echo $this->element('actions_menu'); ?>
    </ul>
</div>
<?php echo $this->Js->writeBuffer();