<?php
$mayModify = (($isAclModify && $event['Event']['user_id'] == $me['id']) || ($isAclModifyOrg && $event['Event']['org'] == $me['org']));
$mayPublish = ($isAclPublish && $event['Event']['org'] == $me['org']);
?>
<div class="events view">

    <div class="row-fluid">
        <div class="span8">
            <h3>Event</h3>
            <dl>
                <dt>ID</dt>
                <dd>
                    <?php echo h($event['Event']['id']); ?>
                    &nbsp;
                </dd>
                <?php if ('true' == Configure::read('CyDefSIG.showorg') || $isAdmin): ?>
                <dt>Org</dt>
                <dd>
                    <?php echo h($event['Event']['orgc']); ?>
                    &nbsp;
                </dd>
                <?php
        endif; ?>
                <?php if ($isSiteAdmin): ?>
                <dt>Owner org</dt>
                <dd>
                    <?php echo h($event['Event']['org']); ?>
                    &nbsp;
                </dd>
                <?php
        endif; ?>
                <?php if ($isSiteAdmin || ($isAdmin && $me['org'] == $event['Event']['org'])): ?>
                <dt>Email</dt>
                <dd>
                    <?php echo h($event['User']['email']); ?>
                    &nbsp;
                </dd>
                <?php
        endif; ?>
                <dt>Detect Date</dt>
                <dd>
                    <?php echo h($event['Event']['date']); ?>
                    &nbsp;
                </dd>
                <dt>Detect Place</dt>
                <dd>
                    <?php echo h($event['DetectPlace']['name']); ?>
                    &nbsp;
                </dd>
                <dt>Detect Method</dt>
                <dd>
                    <?php echo h($event['DetectMethod']['name']); ?>
                    &nbsp;
                </dd>
                <dt>Report channel</dt>
                <dd>
                    <?php echo h($event['ReportingChannel']['reporting_channel']); ?>
                    &nbsp;
                </dd>
                <dt>Report notes</dt>
                <dd>
                    <?php echo h($event['Event']['report_notes']); ?>
                    &nbsp;
                </dd>
                <dt>Assessment Level</dt>
                <dd>
                    <?php echo h($event['AssessmentLevel']['name']); ?>
                    &nbsp;
                </dd>
                <dt>Report time</dt>
                <dd>
                    <?php echo h($event['Event']['report_time']); ?>
                    &nbsp;
                </dd>
                <dt>Start Time</dt>
                <dd>
                    <?php echo h($event['Event']['start_time']); ?>
                    &nbsp;
                </dd>
                <dt>End time</dt>
                <dd>
                    <?php echo h($event['Event']['end_time']); ?>
                    &nbsp;
                </dd>
                <dt>Targeted Organisation</dt>
                <dd>
                    <?php echo h($event['TargetedEntity']['name']); ?>
                    &nbsp;
                </dd>
                <dt>Targeted Domain</dt>
                <dd>
                    <?php echo h($event['TargetedDomain']['domain']); ?>
                    &nbsp;
                </dd>
                <dt>Threat Type</dt>
                <dd>
                    <?php echo h($event['Event']['ThreatType']); ?>
                    &nbsp;
                </dd>
                <dt>CIMBL</dt>
                <dd>
                    <?php echo h($event['CIMBL']['name']); ?>
                    &nbsp;
                </dd>
                <dt<?php echo ' title="' . $eventDescriptions['risk']['desc'] . '"';?>>Risk</dt>
                <dd>
                    <?php echo $event['ThreatLevel']['name']; ?>
                    &nbsp;
                </dd>
                <dt<?php echo ' title="' . $eventDescriptions['analysis']['desc'] . '"';?>>Analysis</dt>
                <dd>
                    <?php echo $analysisLevels[$event['Event']['analysis']]; ?>
                    &nbsp;
                </dd>

            <dt>Distribution</dt>
            <dd>
                <?php echo $event['Event']['distribution'] . ', ' . strtolower(substr(($distributionDescriptions[$event['Event']['distribution']]['formdesc']), 0, 1)) . substr($distributionDescriptions[$event['Event']['distribution']]['formdesc'], 1) . '.'; ?>
                &nbsp;
            </dd>
                <!-- dt>UUID</dt>
                <dd>
                    <?php echo $event['Event']['uuid']; ?>
                    &nbsp;
                </dd -->
                <dt>Info</dt>
                <dd>
                    <?php echo nl2br(h($event['Event']['info'])); ?>
                    &nbsp;
                </dd>
            </dl>
        </div>
        <div class="span4">
            <?php if (!empty($event['SharingGroup'])){?>
                <div class="related">
                    <h3>Sharing Groups</h3>
                    <ul>
                    <?php foreach ($event['SharingGroup'] as $group){ ?>
                        <li> <?php echo $group['name'];?></li>
                    <?php } ?>
                    </ul>
                </div>
            <?php } ?>

            <?php if (!empty($relatedEvents)){?>
                <div class="related">
                    <h3>Related Events</h3>
                    <ul>
                    <?php foreach ($relatedEvents as $relatedEvent){ ?>
                    <li><?php
                    $linkText = $relatedEvent['Event']['date'] . ' (' . $relatedEvent['Event']['id'] . ')';
                    echo "<div \" title = \"".$relatedEvent['Event']['info']."\">";
                    if ($relatedEvent['Event']['org'] == $me['org']) {
                        echo $this->Html->link($linkText, array('controller' => 'events', 'action' => 'view', $relatedEvent['Event']['id']), array('class' => 'SameOrgLink'));
                    } else {
                        echo $this->Html->link($linkText, array('controller' => 'events', 'action' => 'view', $relatedEvent['Event']['id']));
                    }
                    ?></li>
                    <?php } ?>
                    </ul>
                </div>
            <?php } ?>
        </div>
    </div>


    <div class="related">
        <h3>Attributes</h3>
        <?php
if (!empty($event['Attribute'])):?>
        <table cellpadding = "0" cellspacing = "0" class="fullwidth">
        <tr>
            <th>ID</th>
            <th>Category</th>
            <th>Kill Chain</th>
            <th>Type</th>
            <th>Value</th>
            <th>Related Events</th>
            <th <?php echo "title='" . $attrDescriptions['signature']['desc'] . "'";?>>IDS Signature</th>
            <th <?php echo "title='" . $attrDescriptions['private']['desc'] . "'";?>>Distribution</th>
            <?php
    if ($isAdmin || $mayModify): ?>
            <th class="actions">Actions</th>
            <?php
    endif;?>
        </tr><?php
    foreach ($categories as $category):
        $first = 1;
        foreach ($event['Attribute'] as $attribute):
            if ($attribute['category'] != $category) continue;?>
            <tr>
                <td><?php echo $attribute['id']?></td>
                <td class="short" title="<?php if('' != $attribute['category']) echo $categoryDefinitions[$attribute['category']]['desc'];?>"><?php
            if ($first) {
                if ('' == $attribute['category']) echo '(no category)';
                    echo $attribute['category'];
            } else {
                echo '&nbsp;';
            }?></td>
            <td><?php echo $attribute['KillChain']['kill_chain'];?></td>
            <td class="short" title="<?php
            echo $typeDefinitions[$attribute['type']]['desc'];?>"><?php
            echo $attribute['type'];?></td>
                <td><?php
            $sigDisplay = nl2br($attribute['value']);
            if ('attachment' == $attribute['type'] || 'malware-sample' == $attribute['type'] ) {
                $filenameHash = explode('|', $attribute['value']);
                if (strrpos($filenameHash[0], '\\')) {
                    $filepath = substr($filenameHash[0], 0, strrpos($filenameHash[0], '\\'));
                    $filename = substr($filenameHash[0], strrpos($filenameHash[0], '\\'));
                    echo $filepath;
                    echo $this->Html->link($filename, array('controller' => 'attributes', 'action' => 'download', $attribute['id']));
                } else {
                    echo $this->Html->link($filenameHash[0], array('controller' => 'attributes', 'action' => 'download', $attribute['id']));
                }
                if (isset($filenameHash[1])) echo ' | ' . $filenameHash[1];
            } elseif (strpos($attribute['type'], '|') !== false) {
                $filenameHash = explode('|', $attribute['value']);
                echo $filenameHash[0];
                if (isset($filenameHash[1])) echo ' | ' . $filenameHash[1];
            } elseif ('vulnerability' == $attribute['type']) {
                echo $this->Html->link($sigDisplay, 'http://www.google.com/search?q=' . $sigDisplay, array('target' => '_blank'));
            } elseif ('link' == $attribute['type']) {
                echo $this->Html->link($sigDisplay, $sigDisplay);
            } else {
                echo $sigDisplay;
            }
                ?></td>
                <td class="short" style="text-align: center;">
                <?php
            $first = 0;
            if (isset($relatedAttributes[$attribute['id']]) && (null != $relatedAttributes[$attribute['id']])) {
                foreach ($relatedAttributes[$attribute['id']] as $relatedAttribute) {
                    echo '<span title="'.h($relatedAttribute['info']).'">';
                    if ($relatedAttribute['org'] == $me['org']) {
                        echo $this->Html->link($relatedAttribute['id'], array('controller' => 'events', 'action' => 'view', $relatedAttribute['id']), array ('class' => 'SameOrgLink'));
                    } else {
                        echo $this->Html->link($relatedAttribute['id'], array('controller' => 'events', 'action' => 'view', $relatedAttribute['id']));
                    }

                    echo "</span>";
                    echo ' ';
                }
            }
                ?>&nbsp;
                </td>
                <td class="short" style="text-align: center;"><?php echo $attribute['to_ids'] ? 'Yes' : 'No';?></td>
                <td class="short" style="text-align: center;"><?php echo $attribute['distribution'] != 'All communities' ? $attribute['distribution'] : 'All';?></td>
                <?php
            if ($isSiteAdmin || $mayModify): ?>
                <td class="actions">
                    <?php
                    echo $this->Html->link(__('Edit', true), array('controller' => 'attributes', 'action' => 'edit', $attribute['id']), array('class' => 'btn'));
                    echo $this->Form->postLink(__('Delete'), array('controller' => 'attributes', 'action' => 'delete', $attribute['id']), array('class' => 'btn'), __('Are you sure you want to delete this attribute? Keep in mind that this will also delete this attribute on remote MISP instances.'));
                    ?>
                </td>
                <?php
            endif;?>
            </tr>
            <?php
        endforeach; ?>
        <?php
    endforeach; ?>
        </table>
        <?php
endif; ?>
    </div>
</div>


<div class="actions">
    <?php
        if ($isSiteAdmin || $mayModify): ?>

    <ul>
        <h3>Attribute actions</h3>
        <li><?php echo $this->Html->link('Add Attribute', array('controller' => 'attributes', 'action' => 'add', $event['Event']['id']));?></li>
        <li><?php echo $this->Html->link('Add Attachment', array('controller' => 'attributes', 'action' => 'add_attachment', $event['Event']['id']));?> </li></ul>
        <?php
endif; ?>
<?php if ( 0 == $event['Event']['published'] && ($isAdmin || $mayPublish)):
    // only show button if alert has not been sent  // LATER show the ALERT button in red-ish
    ?>
    <ul><li><?php
    if ($isSiteAdmin || $mayPublish) {
        echo $this->Form->postLink('Publish Event', array('action' => 'alert', $event['Event']['id']), null, 'Are you sure this event is complete and everyone should be informed?'); ?></li><li>
        <?php echo $this->Form->postLink('Publish (no email)', array('action' => 'publish', $event['Event']['id']), null, 'Publish but do NOT send alert email? Only for minor changes!');
    }
    ?> </li></ul>
    <?php elseif (0 == $event['Event']['published']): ?>
        <ul><li>Not published</li></ul>
    <?php else: ?>
        <!-- ul><li>Alert already sent</li></ul -->
    <?php
endif; ?>
    <ul><li><?php echo $this->Html->link(__('Contact reporter', true), array('action' => 'contact', $event['Event']['id'])); ?> </li>
    <li><?php echo $this->Html->link(__('Download as XML', true), array('action' => 'download', $event['Event']['id'], 'ext' => 'xml')); ?></li>
    <li><?php echo $this->Html->link(__('Download as XML (old)', true), array('action' => 'xml', 'download', $event['Event']['id'])); ?></li>
    <li><?php echo $this->Html->link(__('Download as IOC', true), array('action' => 'downloadOpenIOCEvent', $event['Event']['id'])); ?> </li>
    <?php if(!empty($event['Event']['CIMBL_id'])){?>
    <li><?php echo $this->Html->link(__('Download CIMBL XML', true), array('controller' => 'CIMBLs', 'action' => 'download', $event['Event']['CIMBL_id'], 'ext' => 'xml')); ?></li>
    <?php } ?>
    <li><?php echo $this->Html->link(__('Download CSV', true), array('action' => 'download', $event['Event']['id'], 'ext' => 'csv')); ?></li>
    </ul>

    <ul>
    <?php
if ($isSiteAdmin || $mayModify): ?>
        <li><?php echo $this->Html->link(__('Add Attribute', true), array('controller' => 'attributes', 'action' => 'add', $event['Event']['id']));?> </li>
        <li><?php echo $this->Html->link(__('Add Attachment', true), array('controller' => 'attributes', 'action' => 'add_attachment', $event['Event']['id']));?> </li>
        <li><?php echo $this->Html->link(__('Edit Event', true), array('action' => 'edit', $event['Event']['id'])); ?> </li>
        <li><?php echo $this->Form->postLink(__('Delete Event'), array('action' => 'delete', $event['Event']['id']), null, __('Are you sure you want to delete # %s?', $event['Event']['id'])); ?></li>
    <?php
endif; ?>
        <?php echo $this->element('actions_menu'); ?>
    </ul>
</div>