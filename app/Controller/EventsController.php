<?php
App::uses('AppController', 'Controller');
App::uses('Xml', 'Utility');

/**
 * Events Controller
 *
 * @property Event $Event
*/
class EventsController extends AppController {

    /**
     * Components
     *
     * @var array
     */
    public $components = array(
            'Security',
            'Email',
            'RequestHandler',
            'HidsMd5Export',
            'HidsSha1Export',
            'NidsExport',
            'IOCExport',
            'IOCImport'
    );

    public $paginate = array(
            'limit' => 40,
            //'maxLimit' => 9999,   // LATER we will bump here on a problem once we have more than 9999 events <- no we won't, this is the max a user van view/page.
            'order' => array(
                    'Event.id' => 'DESC'
            )
    );

    public $helpers = array('Js' => array('Jquery'));

    public function beforeFilter() {
        parent::beforeFilter();

        if($this->request->action == 'updateCIMBL'){
            $this->Security->validatePost = false;
            $this->Security->csrfCheck = false;
        }

        // what pages are allowed for non-logged-in users
        $this->Auth->allow('xml');
        $this->Auth->allow('nids');
        $this->Auth->allow('hids_md5');
        $this->Auth->allow('hids_sha1');
        $this->Auth->allow('text');

        $this->Auth->allow('dot');

        // TODO Audit, activate logable in a Controller
        if (count($this->uses) && $this->{$this->modelClass}->Behaviors->attached('SysLogLogable')) {
            $this->{$this->modelClass}->setUserData($this->activeUser);
        }

        // convert uuid to id if present in the url, and overwrite id field
        if (isset($this->params->query['uuid'])) {
            $params = array(
                    'conditions' => array('Event.uuid' => $this->params->query['uuid']),
                    'fields' => 'Event.id'
            );
            $result = $this->Event->find('first', $params);
            if (isset($result['Event']) && isset($result['Event']['id'])) {
                $id = $result['Event']['id'];
                $this->params->addParams(array('pass' => array($id))); // FIXME find better way to change id variable if uuid is found. params->url and params->here is not modified accordingly now
            }
        }

        // if not admin or own org, check private as well..
        if (!$this->_IsSiteAdmin()) {
            $this->paginate = Set::merge($this->paginate,array(
                    'conditions' =>
                    array("OR" => array(
                            array('Event.org =' => $this->Auth->user('org')),
                            array('Event.private !=' => 1),
                            array('Event.cluster =' => 1))),
            )
            );
        }

        //// do not show cluster outside server
        //if ('true' == Configure::read('CyDefSIG.private')) {
        //  if ($this->_isRest()) {
        //          $this->paginate = Set::merge($this->paginate,array(
        //          'conditions' =>
        //                  array(array('Event.cluster !=' => true)),
        //                  //array("AND" => array(array('Event.private !=' => 2))),
        //          ));
        //  }
        //}
    }

    /**
     * index method
     *
     * @return void
     */
    public function index() {
        if(!empty($this->request->data)){
            $redirect = array('action' => 'index');
            foreach($this->request->data['Event'] as $k => $v){
                if(!empty($v)){
                    $redirect[$k] = $v
;                }
            }
            $this->redirect($redirect);
        }

        if(!empty($this->passedArgs['key'])){
            $this->paginate['conditions'] = array('Event.info LIKE' => '%'.$this->passedArgs['key'].'%');
        }

        $this->Event->contain('TargetedEntity.name', 'TargetedDomain.domain', 'ThreatLevel.name');

        $this->paginate['fields'] = array('id', 'date', 'ThreatType', 'risk', 'info', 'CIMBL_id', 'published');


        $this->set('events', $this->paginate());
        if (!$this->Auth->user('gpgkey')) {
            $this->Session->setFlash(__('No GPG key set in your profile. To receive emails, submit your public key in your profile.'));
        }
        $this->set('eventDescriptions', $this->Event->fieldDescriptions);
        $this->set('analysisLevels', $this->Event->analysisLevels);
        $this->set('cIMBLs', $this->Event->CIMBL->find('list'));
    }

    /**
     * Compare Related Events, first sort on date then on id
     *
     * @param unknown_type $a
     * @param unknown_type $b
     */
    public function compareRelatedEvents($a, $b) {
        $retval = strnatcmp($b['Event']['date'], $a['Event']['date']);
        if (!$retval)
            return strnatcmp($b['Event']['id'], $a['Event']['id']);
        return $retval;
    }

    /**
     * view method
     *
     * @param int $id
     * @return void
     * @throws NotFoundException
     */
    public function view($id = null) {
        // If the length of the id provided is 36 then it is most likely a Uuid - find the id of the event, change $id to it and proceed to read the event as if the ID was entered.
        $perm_publish = $this->checkAction('perm_publish');
        if (strlen($id) == 36) {
            $temp = $this->Event->findByUuid($id);
            if ($temp == null) throw new NotFoundException(__('Invalid event'));
            $id = $temp['Event']['id'];
        }
        $this->Event->contain(
            'Attribute', 'Attribute.KillChain', 'Attribute.ShadowAttribute',
            'ThreatLevel.name', 'TargetedEntity.name', 'TargetedDomain.domain',
            'User.email', 'CIMBL.name', 'SharingGroup.name', 'DetectMethod.name',
            'DetectPlace.name', 'ReportingChannel.reporting_channel', 'AssessmentLevel.name');
        $this->Event->id = $id;
        if (!$this->Event->exists()) {
            throw new NotFoundException(__('Invalid event'));
        }
        $this->Event->read(null, $id);
        $userEmail = $this->Event->data['User']['email'];
        unset ($this->Event->data['User']);
        $this->Event->data['User']['email'] = $userEmail;
        if (!$this->_IsSiteAdmin()) {
            // check for non-private and re-read
            if ($this->Event->data['Event']['org'] != $this->Auth->user('org')) {
                $this->Event->hasMany['Attribute']['conditions'] = array('OR' => array(array('Attribute.private !=' => 1), array('Attribute.private =' => 1, 'Attribute.cluster =' => 1))); // TODO seems very dangerous for the correlation construction in afterSave!!!
                $this->Event->read(null, $id);
            }

            // check private
            if (($this->Event->data['Event']['private'] && !$this->Event->data['Event']['cluster']) && ($this->Event->data['Event']['org'] != $this->Auth->user('org'))) {
                $this->Session->setFlash(__('Invalid event.'));
                $this->redirect(array('controller' => 'events', 'action' => 'index'));
            }
        }
        $this->set('analysisLevels', $this->Event->analysisLevels);

        //$this->loadModel('Attribute');
        $relatedEvents = $this->Event->getRelatedEvents($this->Auth->user());
        $relatedAttributes = $this->Event->getRelatedAttributes($this->Auth->user());

        if ($this->_isRest()) {
            foreach ($this->Event->data['Attribute'] as &$attribute) {
                //  for REST requests also add the encoded attachment
                if ($this->Attribute->typeIsAttachment($attribute['type'])) {
                    //  LATER check if this has a serious performance impact on XML conversion and memory usage
                    $encodedFile = $this->Event->Attribute->base64EncodeAttachment($attribute);
                    $attribute['data'] = $encodedFile;
                }
            }
        }
        // set up the ShadowAttributes for the view - the only shadow attributes that should be passed to the view are the ones that the user is eligible to see
        // This means: Proposals of other organisations to own events, if the user is a publisher
        // Also: proposals made by the current user's organisation
        if (!$this->_isRest()) {
            foreach ($this->Event->data['Attribute'] as &$attribute) {
                // if the user is of the same org as the event and has publishing rights, just show everything
                if (($this->Auth->user('org') != $this->Event->data['Event']['org'] || !$perm_publish) && !$this->_isSiteAdmin()) {
                    $counter = 0;
                    foreach ($attribute['ShadowAttribute'] as &$shadow) {
                        if ($shadow['org'] != $this->Auth->user('org')) unset($attribute['ShadowAttribute'][$counter]);
                        $counter++;
                    }
                }
            }
            // Grab the shadow attributes that do not have an old_id - these are not proposals to edit an attribute but instead proposals to add a new one
            if ($this->Auth->user('org') == $this->Event->data['Event']['orgc'] && $this->checkAction('perm_publish')) {
                $conditions = array('AND' => array('ShadowAttribute.event_id' => $this->Event->data['Event']['id'], 'ShadowAttribute.old_id' => '0'));
            } else {
                $conditions = array('AND' => array('ShadowAttribute.event_id' => $this->Event->data['Event']['id'], 'ShadowAttribute.old_id' => '0', 'ShadowAttribute.org' => $this->Auth->user('org')));
            }
            $this->loadModel('ShadowAttribute');
            // Only load the shadow attributes, nothing related
            $this->ShadowAttribute->recursive = -1;
            $remaining = $this->ShadowAttribute->find('all', array(
                    'conditions' => $conditions
            ));
        }

        // params for the jQuery RESTfull interface
        $this->set('authkey', $this->Auth->user('authkey'));
        $this->set('baseurl', Configure::read('CyDefSIG.baseurl'));

        $this->set('relatedAttributes', $relatedAttributes);

        // passing decriptions for model fields
        $this->set('eventDescriptions', $this->Event->fieldDescriptions);
        $this->set('attrDescriptions', $this->Event->Attribute->fieldDescriptions);

        $this->set('event', $this->Event->data);
        if(isset($remaining)) {
            $this->set('remaining', $remaining);
        }
        $this->set('relatedEvents', $relatedEvents);

        $this->set('categories', $this->Event->Attribute->validate['category']['rule'][1]);

        // passing type and category definitions (explanations)
        $this->set('typeDefinitions', $this->Event->Attribute->typeDefinitions);
        $this->set('categoryDefinitions', $this->Event->Attribute->categoryDefinitions);

        $this->set('distributionDescriptions', $this->Event->distributionDescriptions);

        // combobox for analysis
        $analysiss = $this->Event->validate['analysis']['rule'][1];
        $analysiss = $this->_arrayToValuesIndexArray($analysiss);
        $this->set('analysiss',$analysiss);
        // tooltip for analysis
        $this->set('analysisDescriptions', $this->Event->analysisDescriptions);
        $this->set('analysisLevels', $this->Event->analysisLevels);
    }

    /**
     * add method
     *
     * @return void
     */
    public function add() {
        if ($this->request->is('post')) {
            if ($this->_isRest()) {
                // Distribution, reporter for the events pushed will be the owner of the authentication key
                $this->request->data['Event']['user_id'] = $this->Auth->user('id');
            }
            if (!empty($this->data)) {
                $ext = '';
                if (isset($this->data['Event']['submittedfile'])) {
                    App::uses('File', 'Utility');
                    $file = new File($this->data['Event']['submittedfile']['name']);
                    $ext = $file->ext();
                }
                $ioc = false;
                if($this->data['Event']['submittedioc']['error'] != 4) {
                    $ioc = true;
                }
                if (isset($this->data['Event']['submittedioc'])) {
                    App::uses('File', 'Utility');
                    $file = new File($this->data['Event']['submittedfile']['name']);
                    $ext = $file->ext();
                }
                if (isset($this->data['Event']['submittedfile']) && ($ext != 'zip') && $this->data['Event']['submittedfile']['size'] > 0 &&
                        is_uploaded_file($this->data['Event']['submittedfile']['tmp_name'])) {
                    //return false;
                    $this->Session->setFlash(__('You may only upload GFI Sandbox zip files.'));
                } else {
                    if ($this->_add($this->request->data, $this->Auth, $this->_isRest(),'')) {
                        if ($this->_isRest()) {
                            // REST users want to see the newly created event
                            $this->view($this->Event->getId());
                            $this->render('view');
                        } else {
                            // TODO now save uploaded attributes using $this->Event->getId() ..
                            $this->addGfiZip($this->Event->getId());
                            $this->addIOCFile($this->Event->getId());

                            // redirect to the view of the newly created event
                            if (!CakeSession::read('Message.flash')) {
                                $this->Session->setFlash(__('The event has been saved'));
                            } else {
                                $existingFlash = CakeSession::read('Message.flash');
                                $this->Session->setFlash(__('The event has been saved. ' . $existingFlash['message']));
                            }
                            if (!$ioc) {
                                $this->redirect(array('action' => 'view', $this->Event->getId()));
                            }
                        }
                    } else {
                        if ($this->_isRest()) { // TODO return error if REST
                            // REST users want to see the failed event
                            $this->view($this->Event->getId());
                            $this->render('view');
                        } else {
                            $this->Session->setFlash(__('The event could not be saved. Please, try again.'), 'default', array(), 'error');
                            // TODO return error if REST
                        }
                    }
                }
            }
        }

        // combobox for distribution
        $distributions = array_keys($this->Event->distributionDescriptions);
        $distributions = $this->_arrayToValuesIndexArray($distributions);
        $this->set('distributions', $distributions);
        // tooltip for distribution
        $this->set('distributionDescriptions', $this->Event->distributionDescriptions);

        // combobox for risks
        $risks = $this->Event->validate['risk']['rule'][1];
        $risks = $this->_arrayToValuesIndexArray($risks);
        $this->set('risks',$risks);
        // tooltip for risk
        $this->set('riskDescriptions', $this->Event->riskDescriptions);

        // combobox for analysis
        $analysiss = $this->Event->validate['analysis']['rule'][1];
        $analysiss = $this->_arrayToValuesIndexArray($analysiss);
        $this->set('analysiss',$analysiss);
        // tooltip for analysis
        $this->set('analysisDescriptions', $this->Event->analysisDescriptions);
        $this->set('analysisLevels', $this->Event->analysisLevels);

        $this->set('eventDescriptions', $this->Event->fieldDescriptions);

        $this->set('organisations', $this->Event->Reporter->find('list'));
        $this->set('channels', $this->Event->ReportingChannel->find('list'));
        $this->set('domains', $this->Event->TargetedDomain->find('list'));
        $this->set('assessmentLevels', $this->Event->AssessmentLevel->find('list'));
        $this->set('sharingGroups', $this->Event->SharingGroup->find('list'));
        $this->set('detectMethods', $this->Event->DetectMethod->find('list'));
        $this->set('threatLevels', $this->Event->ThreatLevel->find('list'));
    }

    /**
     * Low level functino to add an Event based on an Event $data array
     *
     * @return bool true if success
     */
    public function _add(&$data, &$auth, $fromXml, $or='', $passAlong = null, $fromPull = false) {
        // force check userid and orgname to be from yourself
        $data['Event']['user_id'] = $auth->user('id');
        $data['Event']['org'] = $auth->user('org');
        //$data['Event']['org'] = strlen($or) ? $or : $auth->user('org'); // FIXME security - org problem
        if (!$fromXml) {
            $data['Event']['orgc'] = $data['Event']['org'];
        }
        unset ($data['Event']['id']);
        $this->Event->create();
        //$this->Event->data = $data;
        if ($fromXml) {
            // Workaround for different structure in XML/array than what CakePHP expects
            $this->Event->cleanupEventArrayFromXML($data);
            // the event_id field is not set (normal) so make sure no validation errors are thrown
            // LATER do this with    $this->validator()->remove('event_id');
            unset($this->Event->Attribute->validate['event_id']);
            unset($this->Event->Attribute->validate['value']['unique']); // otherwise gives bugs because event_id is not set
        }
        // upstream: false = distribution
        // true = reverse distribution, back to origin
        $upstream = false;

        if (isset($data['Event']['uuid'])) {    // TODO here we start RESTful dialog
            // check if the uuid already exists
            $existingEventCount = $this->Event->find('count', array('conditions' => array('Event.uuid' => $data['Event']['uuid'])));
            if ($existingEventCount > 0) {
                // TODO RESTfull, set responce location header..so client can find right URL to edit
                if ($fromPull) return false;
                $existingEvent = $this->Event->find('first', array('conditions' => array('Event.uuid' => $data['Event']['uuid'])));
                $this->response->header('Location', Configure::read('CyDefSIG.baseurl') . '/events/' . $existingEvent['Event']['id']);
                $this->response->send();
                return false;
            }
        }

        if ($upstream) {
            $fieldList = array(
                    'Event' => array('orgc', 'date', 'risk', 'analysis', 'info', 'published', 'uuid'),
                    'Attribute' => array('event_id', 'category', 'type', 'value', 'value1', 'value2', 'to_ids', 'uuid', 'revision')
            );
        } else {
            $fieldList = array(
                    'Event' => array('org', 'orgc', 'date', 'risk', 'analysis', 'info', 'user_id', 'published', 'uuid', 'private', 'cluster', 'communitie', 'hop_count', 'dist_change', 'from'),
                    'Attribute' => array('event_id', 'category', 'type', 'value', 'value1', 'value2', 'to_ids', 'uuid', 'revision', 'private', 'cluster', 'communitie', 'dist_change')
            );
        }

        if($this->isCTI){
            $fieldList['Event'] = array_merge($fieldList['Event'], array('start_time', 'end_time', 'detect_place_id', 'detect_method_id', 'report_time', 'reporter_organisation_id', 'reporter_channel_id', 'SharingAuthorisation', 'ThreatType', 'targeted_organisation_id', 'targeted_domain_id', 'assessment_level_id', 'report_notes', 'threat_level_id', 'CIMBL_id')
            );
        }

        if ("i" == Configure::read('CyDefSIG.baseurl')) {
            // this saveAssociated() function will save not only the event, but also the attributes
            // from the attributes attachments are also saved to the disk thanks to the afterSave() fonction of Attribute
            unset($data['Attribute']);
            $this->Event->unbindModel(array('hasMany' => array('Attribute')));
            $saveResult = $this->Event->save($data, array('validate' => true, 'fieldList' => $fieldList));
        } else {
            $saveResult = $this->Event->saveAssociated($data, array('validate' => true, 'fieldList' => $fieldList));
        }
        if ($saveResult) {
            if (!empty($data['Event']['published']) && 1 == $data['Event']['published'] && $data['Event']['private'] == false) {
                // do the necessary actions to publish the event (email, upload,...)
                $this->__publish($this->Event->getId(), $passAlong);
            }
            return true;
        } else {
            //throw new MethodNotAllowedException("Validation ERROR: \n".var_export($this->Event->validationErrors, true));
            return false;
        }
    }

    /**
     * edit method
     *
     * @param int $id
     * @return void
     * @throws NotFoundException
     */
    public function edit($id = null) {
        $this->Event->id = $id;
        if (!$this->Event->exists()) {
            throw new NotFoundException(__('Invalid event'));
        }
        $this->Event->read(null, $id);
        // check for if private and user not authorised to edit, go away
        if (!$this->_isSiteAdmin() && !$this->checkAction('perm_sync') && $this->Event->data['Event']['distribution'] == 'Your organization only') {
            if (($this->Event->data['Event']['org'] != $this->_checkOrg()) || !($this->checkAction('perm_modify'))) {
                $this->Session->setFlash(__('You are not authorised to do that.'));
                $this->redirect(array('controller' => 'events', 'action' => 'index'));
            }
        }
        if (!$this->_isRest()) {
            if ($this->Event->data['Event']['org'] != $this->_checkOrg()) {
                $this->Session->setFlash(__('Invalid event.'));
                $this->redirect(array('controller' => 'events', 'action' => 'index'));
            }
        }

        // check if the user is of the creating org, if not, don't let him/her change the distribution.
        $canEditDist = false;
        if ($this->Event->data['Event']['orgc'] == $this->_checkOrg()) {
            $canEditDist = true;
            $this->set('canEditDist', true);
        } else {
            $this->set('canEditDist', false);
        }
        if ($this->request->is('post') || $this->request->is('put')) {
            if ($this->_isRest()) {
                // Workaround for different structure in XML/array than what CakePHP expects
                $this->Event->cleanupEventArrayFromXML($this->request->data);

                // the event_id field is not set (normal) so make sure no validation errors are thrown
                // LATER do this with    $this->validator()->remove('event_id');
                unset($this->Event->Attribute->validate['event_id']);
                unset($this->Event->Attribute->validate['value']['unique']); // otherwise gives bugs because event_id is not set

                // http://book.cakephp.org/2.0/en/models/saving-your-data.html
                // Creating or updating is controlled by the models id field.
                // If $Model->id is set, the record with this primary key is updated.
                // Otherwise a new record is created

                // reposition to get the event.id with given uuid
                $existingEvent = $this->Event->findByUuid($this->request->data['Event']['uuid']);
                if (count($existingEvent)) {
                    $this->request->data['Event']['id'] = $existingEvent['Event']['id'];
                }
                if ($existingEvent['Event']['orgc'] == $this->_checkOrg()) {
                    $this->set('canEditDist', true);
                } else {
                    $this->set('canEditDist', false);
                }

                $fieldList = array(
                        'Event' => array('date', 'risk', 'analysis', 'info', 'published', 'uuid', 'dist_change', 'from'),
                        'Attribute' => array('event_id', 'category', 'type', 'value', 'value1', 'value2', 'to_ids', 'uuid', 'revision', 'private', 'communitie', 'cluster', 'dist_change')
                );

                if($this->isCTI){
                    $fieldList['Event'] = array_merge($fieldList['Event'], array('start_time', 'end_time', 'detect_place_id', 'detect_method_id', 'report_time', 'reporter_organisation_id', 'reporter_channel_id', 'SharingAuthorisation', 'ThreatType', 'targeted_organisation_id', 'targeted_domain_id', 'assessment_level_id', 'report_notes', 'threat_level_id', 'CIMBL_id')
                    );
                }


                if ("ii" == Configure::read('CyDefSIG.rest')) {
                    // reposition to get the attribute.id with given uuid
                    $c = 0;
                    if (isset($this->request->data['Attribute'])) {
                        foreach ($this->request->data['Attribute'] as $attribute) {
                            $existingAttribute = $this->Event->Attribute->findByUuid($attribute['uuid']);
                            if (count($existingAttribute)) {
                                $this->request->data['Attribute'][$c]['id'] = $existingAttribute['Attribute']['id'];
                                if (!($this->request->data['Attribute'][$c]['dist_change'] > $existingAttribute['Attribute']['dist_change'])) {
                                    unset($this->request->data['Attribute'][$c]['private']);
                                    unset($this->request->data['Attribute'][$c]['cluster']);
                                    unset($this->request->data['Attribute'][$c]['communitie']);
                                }
                            }
                            $c++;
                        }
                    }
                }

                if ($this->request->data['Event']['dist_change'] > $existingEvent['Event']['dist_change']) {
                    array_push($fieldList['Event'], 'private', 'communitie', 'cluster');
                }
                if ("i" == Configure::read('CyDefSIG.rest')) {
                    // this saveAssociated() function will save not only the event, but also the attributes
                    // from the attributes attachments are also saved to the disk thanks to the afterSave() fonction of Attribute
                    // the following 2 lines can be out-commented if we opt to save associated (Event.php:263-264)
                    unset($this->request->data['Attribute']);
                    $this->Event->unbindModel(array('hasMany' => array('Attribute')));
                    $saveResult = $this->Event->save($this->request->data, array('validate' => true, 'fieldList' => $fieldList));
                } else {
                    $saveResult = $this->Event->saveAssociated($this->request->data, array('validate' => true, 'fieldList' => $fieldList));
                }
                if ($saveResult) {

                    // TODO RESTfull: we now need to compare attributes, to see if we need to do a RESTfull attribute delete

                    $message = 'Saved';

                    $this->set('event', $this->Event->data);
                    //if published -> do the actual publishing
                    if ((!empty($this->request->data['Event']['published']) && 1 == $this->request->data['Event']['published'])) {
                        // do the necessary actions to publish the event (email, upload,...)
                        $this->__publish($existingEvent['Event']['id']);
                    }

                    // REST users want to see the newly created event
                    $this->view($this->Event->getId());
                    $this->render('view');
                    return true;
                } else {
                    $message = 'Error';
                    $this->set(array('message' => $message,'_serialize' => array('message')));  // $this->Event->validationErrors
                    $this->render('edit');
                    //throw new MethodNotAllowedException("Validation ERROR: \n".var_export($this->Event->validationErrors, true));
                    return false;
                }
            }
            // say what fields are to be updated
            $fieldList = array('date', 'risk', 'analysis', 'info', 'published', 'private', 'cluster', 'communitie', 'dist_change');

            if($this->isCTI){
                $fieldList = array_merge($fieldList, array('start_time', 'end_time', 'detect_place_id', 'detect_method_id', 'report_time', 'reporter_organisation_id',
                    'reporter_channel_id', 'SharingAuthorisation', 'ThreatType', 'targeted_organisation_id', 'targeted_domain_id', 'assessment_level_id', 'report_notes', 'threat_level_id', 'CIMBL_id'
                ));
            }

            //Moved this out of (if ($this->_isAdmin()) to use for the dist_change
            $this->Event->read();

            // always force the org, but do not force it for admins
            if (!$this->_isSiteAdmin()) {
                // set the same org as existed before
                $this->request->data['Event']['org'] = $this->Event->data['Event']['org'];
            }
            // we probably also want to remove the published flag
            $this->request->data['Event']['published'] = 0;

            // If the distribution has changed, up the dist_change count
            if ($canEditDist) {
                if ($this->request->data['Event']['distribution'] != $this->Event->data['Event']['distribution']) {
                    $this->request->data['Event']['dist_change'] = 1 + $this->Event->data['Event']['dist_change'];
                }
            }

            if ($this->Event->save($this->request->data, false, $fieldList)) {
                $this->Session->setFlash(__('The event has been saved'));
                $this->redirect(array('action' => 'view', $id));
            } else {
                $this->Session->setFlash(__('The event could not be saved. Please, try again.'));
            }
        } else {
            if(!$this->checkAction('perm_modify')) $this->redirect(array('controller' => 'events', 'action' => 'index', 'admin' => false));
            $this->Event->contain('SharingGroup');
            $this->request->data = $this->Event->read(null, $id);
        }

        // combobox for distribution
        $distributions = array_keys($this->Event->distributionDescriptions);
        $distributions = $this->_arrayToValuesIndexArray($distributions);
        $this->set('distributions', $distributions);

        // tooltip for distribution
        $this->set('distributionDescriptions', $this->Event->distributionDescriptions);

        // combobox for types
        $risks = $this->Event->validate['risk']['rule'][1];
        $risks = $this->_arrayToValuesIndexArray($risks);
        $this->set('risks',$risks);

        // tooltip for risk
        $this->set('riskDescriptions', $this->Event->riskDescriptions);

        // combobox for analysis
        $analysiss = $this->Event->validate['analysis']['rule'][1];
        $analysiss = $this->_arrayToValuesIndexArray($analysiss);
        $this->set('analysiss',$analysiss);

        // tooltip for analysis
        $this->set('analysisDescriptions', $this->Event->analysisDescriptions);
        $this->set('analysisLevels', $this->Event->analysisLevels);

        $this->set('eventDescriptions', $this->Event->fieldDescriptions);

        $this->set('organisations', $this->Event->Reporter->find('list'));
        $this->set('channels', $this->Event->ReportingChannel->find('list'));
        $this->set('domains', $this->Event->TargetedDomain->find('list'));
        $this->set('assessmentLevels', $this->Event->AssessmentLevel->find('list'));
        $this->set('assessmentLevels', $this->Event->AssessmentLevel->find('list'));
        $this->set('sharingGroups', $this->Event->SharingGroup->find('list'));
        $this->set('detectMethods', $this->Event->DetectMethod->find('list'));
        $this->set('threatLevels', $this->Event->ThreatLevel->find('list'));
        $this->set('cIMBLs', $this->Event->CIMBL->find('list'));

        $selected_groups = $this->Event->getSelectedItems($this->request->data['SharingGroup']);
        $this->set(compact('selected_groups'));
    }

    /**
     * delete method
     *
     * @param int $id
     * @return void
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     */

    public function delete($id = null) {
        if (!$this->request->is('post') && !$this->_isRest()) {
            throw new MethodNotAllowedException();
        }

        $this->Event->id = $id;
        if (!$this->Event->exists()) {
            throw new NotFoundException(__('Invalid event'));
        }

        if ('true' == Configure::read('CyDefSIG.sync')) {
            // find the uuid
            $result = $this->Event->findById($id);
            $uuid = $result['Event']['uuid'];
        }
        if (!$this->_isSiteAdmin()) {
            $this->Event->read();
            if (!$this->Event->data['Event']['org'] == $this->_checkOrg()) {
                throw new MethodNotAllowedException();
            }
        }

        if ($this->Event->delete()) {

            // delete the event from remote servers
            //if ('true' == Configure::read('CyDefSIG.sync')) { // TODO test..(!$this->_isRest()) &&
            //  $this->__deleteEventFromServers($uuid);
            //}

            $this->Session->setFlash(__('Event deleted'));
            $this->redirect(array('action' => 'index'));
        }
        $this->Session->setFlash(__('Event was not deleted'));
        $this->redirect(array('action' => 'index'));
    }

    /**
     * Uploads this specific event to all remote servers
     * TODO move this to a component
     *
     * @return bool true if success, false if, partly, failed
     */
    private function __uploadEventToServers($id, $passAlong = null) {
        // make sure we have all the data of the Event
        $this->Event->id = $id;
        $this->Event->recursive = 1;
        $this->Event->read();

        //Save the from field of the event to know where it came from originally and then set the event's from field to the current server's host org
        $from = $this->Event->data['Event']['from'];
        $this->Event->data['Event']['from'] = Configure::read('CyDefSIG.org');
        // get a list of the servers
        $this->loadModel('Server');
        $servers = $this->Server->find('all', array(
                'conditions' => array('Server.push' => true)
        ));
        // iterate over the servers and upload the event
        if(empty($servers))
            return;

        $uploaded = true;
        $failedServers = array();
        App::uses('HttpSocket', 'Network/Http');
        $HttpSocket = new HttpSocket();
        foreach ($servers as &$server) {
            //Skip servers where the event has come from.
            if (($passAlong != $server && $server['Server']['organization'] != $from)) {
                $thisUploaded = $this->Event->uploadEventToServer($this->Event->data, $server, $HttpSocket);
                if (!$thisUploaded) {
                    $uploaded = !$uploaded ? $uploaded : $thisUploaded;
                    $failedServers[] = $server['Server']['url'];
                }
            }
        }
        $this->Event->data['Event']['from'] = Configure::read('CyDefSIG.org');
        if (!$uploaded) {
            return $failedServers;
        } else {
            return true;
        }
    }

    /**
     * Delets this specific event to all remote servers
     * TODO move this to a component(?)
     */
    private function __deleteEventFromServers($uuid) {
        // get a list of the servers
        $this->loadModel('Server');
        $servers = $this->Server->find('all', array(
                'conditions' => array('Server.push' => true)
        ));

        // iterate over the servers and upload the event
        if(empty($servers))
            return;

        App::uses('HttpSocket', 'Network/Http');
        $HttpSocket = new HttpSocket();
        foreach ($servers as &$server) {
            $this->Event->deleteEventFromServer($uuid, $server, $HttpSocket);
        }
    }

    /**
     * Performs all the actions required to publish an event
     *
     * @param unknown_type $id
     */
    private function __publish($id, $passAlong = null) {
        $this->Event->id = $id;
        $this->Event->recursive = 0;
        $event = $this->Event->read(null, $id);
        // update the DB to set the published flag
        $fieldList = array('published', 'id', 'info');
        $event['Event']['published'] = 1;
        $this->Event->save($event, array('fieldList' => $fieldList));
        $event['Event']['from'] = Configure::read('CyDefSIG.org');
        $uploaded = false;
        //if ($event['Event']['distribution'] == 'Your organization only' || $event['Event']['distribution'] == 'This server-only') return true;
        if ('true' == Configure::read('CyDefSIG.sync')) {
            $uploaded = $this->__uploadEventToServers($id, $passAlong);
            if ((is_bool($uploaded) && !$uploaded) || (is_array($uploaded))) { // TODO remove bool
                $this->Event->saveField('published', 0);
            }
        }
        return $uploaded;
    }

    /**
     * Publishes the event without sending an alert email
     *
     * @throws NotFoundException
     */
    public function publish($id = null) {
        $this->Event->id = $id;
        if (!$this->Event->exists()) {
            throw new NotFoundException(__('Invalid event'));
        }
        $this->Event->saveField('from', Configure::read('CyDefSIG.org'));

        // only allow form submit CSRF protection.
        if ($this->request->is('post') || $this->request->is('put')) {
            // Performs all the actions required to publish an event
            $result = $this->__publish($id);
            if (!is_array($result)) {
                // redirect to the view event page
                $this->Session->setFlash(__('Event published, but NO mail sent to any participants.', true));
            } else {
                $lastResult = array_pop($result);
                $resultString = (count($result) > 0) ? implode(', ', $result) . ' and ' . $lastResult : $lastResult;
                $this->Session->setFlash(__(sprintf('Event not published to %s, re-try later. If the issue persists, make sure that the correct sync user credentials are used for the server link and that the sync user on the remote server has authentication privileges.', $resultString), true));
            }
            $this->redirect(array('action' => 'view', $id));
        }
    }

    /**
     * Send out an alert email to all the users that wanted to be notified.
     * Users with a GPG key will get the mail encrypted, other users will get the mail unencrypted
     *
     * @throws NotFoundException
     */
    public function alert($id = null) {
        $this->Event->id = $id;
        $this->Event->recursive = 0;
        if (!$this->Event->exists()) {
            throw new NotFoundException(__('Invalid event'));
        }
        $this->Event->saveField('from', Configure::read('CyDefSIG.org'));

        // only allow form submit CSRF protection.
        if ($this->request->is('post') || $this->request->is('put')) {
            // send out the email
            $emailResult = $this->__sendAlertEmail($id);
            if (is_bool($emailResult) && $emailResult = true) {
                // Performs all the actions required to publish an event
                $result = $this->__publish($id);
                if (!is_array($result)) {

                    // redirect to the view event page
                    $this->Session->setFlash(__('Email sent to all participants.', true));
                } else {
                    $lastResult = array_pop($result);
                    $resultString = (count($result) > 0) ? implode(', ', $result) . ' and ' . $lastResult : $lastResult;
                    $this->Session->setFlash(__(sprintf('Not published given no connection to %s but email sent to all participants.', $resultString), true));
                }
            } elseif (!is_bool($emailResult)) {
                // Performs all the actions required to publish an event
                $result = $this->__publish($id);
                if (!is_array($result)) {

                    // redirect to the view event page
                    $this->Session->setFlash(__('Published but no email sent given GnuPG is not configured.', true));
                } else {
                    $lastResult = array_pop($result);
                    $resultString = (count($result) > 0) ? implode(', ', $result) . ' and ' . $lastResult : $lastResult;
                    $this->Session->setFlash(__(sprintf('Not published given no connection to %s but no email sent given GnuPG is not configured.', $resultString), true));
                }
            } else {
                $this->Session->setFlash(__('Sending of email failed', true), 'default', array(), 'error');
            }
            $this->redirect(array('action' => 'view', $id));
        }
    }

    private function __sendAlertEmail($id) {
        $this->Event->recursive = 1;
        $event = $this->Event->read(null, $id);

        // The mail body, h() is NOT needed as we are sending plain-text mails.
        $body = "";
        $appendlen = 20;
        $body .= 'URL         : ' . Configure::read('CyDefSIG.baseurl') . '/events/view/' . $event['Event']['id'] . "\n";
        $body .= 'Event       : ' . $event['Event']['id'] . "\n";
        $body .= 'Date        : ' . $event['Event']['date'] . "\n";
        if ('true' == Configure::read('CyDefSIG.showorg')) {
            $body .= 'Reported by : ' . $event['Event']['org'] . "\n";
        }
        $body .= 'Risk        : ' . $event['Event']['risk'] . "\n";
        $body .= 'Analysis    : ' . $event['Event']['analysis'] . "\n";
        $relatedEvents = $this->Event->getRelatedEvents($this->Auth->user());
        if (!empty($relatedEvents)) {
            foreach ($relatedEvents as &$relatedEvent) {
                $body .= 'Related to  : ' . Configure::read('CyDefSIG.baseurl') . '/events/view/' . $relatedEvent['Event']['id'] . ' (' . $relatedEvent['Event']['date'] . ')' . "\n";

            }
        }
        $body .= 'Info  : ' . "\n";
        $body .= $event['Event']['info'] . "\n";
        $body .= "\n";
        $body .= 'Attributes  :' . "\n";
        $bodyTempOther = "";

        if (isset($event['Attribute'])) {
            foreach ($event['Attribute'] as &$attribute) {
                $line = '- ' . $attribute['type'] . str_repeat(' ', $appendlen - 2 - strlen($attribute['type'])) . ': ' . $attribute['value'] . "\n";
                if ('other' == $attribute['type']) // append the 'other' attribute types to the bottom.
                    $bodyTempOther .= $line;
                else $body .= $line;
            }
        }
        $body .= "\n";
        $body .= $bodyTempOther;    // append the 'other' attribute types to the bottom.

        // find out whether the event is private, to limit the alerted user's list to the org only
        if ($event['Event']['private'] && !$event['Event']['cluster']) {
            $eventIsPrivate = true;
        } else {
            $eventIsPrivate = false;
        }

        // sign the body
        require_once 'Crypt/GPG.php';
        try {
            $gpg = new Crypt_GPG(array('homedir' => Configure::read('GnuPG.homedir'))); // , 'debug' => true
            $gpg->addSignKey(Configure::read('GnuPG.email'), Configure::read('GnuPG.password'));
            $bodySigned = $gpg->sign($body, Crypt_GPG::SIGN_MODE_CLEAR);

            $this->loadModel('User');

            //
            // Build a list of the recipients that get a non-encrypted mail
            // But only do this if it is allowed in the bootstrap.php file.
            //
            if ($eventIsPrivate) {
                $conditions = array('User.autoalert' => 1, 'User.gpgkey =' => "", 'User.org =' => $event['Event']['org']);
            } else {
                $conditions = array('User.autoalert' => 1, 'User.gpgkey =' => "");
            }
            if ('false' == Configure::read('GnuPG.onlyencrypted')) {
                $alertUsers = $this->User->find('all', array(
                        'conditions' => $conditions,
                        'recursive' => 0,
                ));
                $alertEmails = Array();
                foreach ($alertUsers as &$user) {
                    $alertEmails[] = $user['User']['email'];
                }
                // prepare the the unencrypted email
                $this->Email->from = Configure::read('CyDefSIG.email');
                $this->Email->bcc = $alertEmails;
                $this->Email->subject = "[" . Configure::read('CyDefSIG.name') . "] Event " . $id . " - " . $event['Event']['risk'] . " - TLP Amber";
                $this->Email->template = 'body';
                $this->Email->sendAs = 'text';  // both text or html
                $this->set('body', $bodySigned);
                // send it
                $this->Email->send();
                // If you wish to send multiple emails using a loop, you'll need
                // to reset the email fields using the reset method of the Email component.
                $this->Email->reset();
            }

            //
            // Build a list of the recipients that wish to receive encrypted mails.
            //
            if ($eventIsPrivate) {
                $conditions = array('User.autoalert' => 1, 'User.gpgkey !=' => "", 'User.org =' => $event['Event']['org']);
            } else {
                $conditions = array('User.autoalert' => 1, 'User.gpgkey !=' => "");
            }
            $alertUsers = $this->User->find('all', array(
                    'conditions' => $conditions,
                    'recursive' => 0,
            )
            );
            // encrypt the mail for each user and send it separately
            foreach ($alertUsers as &$user) {
                // send the email
                $this->Email->from = Configure::read('CyDefSIG.email');
                $this->Email->to = $user['User']['email'];
                $this->Email->subject = "[" . Configure::read('CyDefSIG.name') . "] Event " . $id . " - " . $event['Event']['risk'] . " - TLP Amber";
                $this->Email->template = 'body';
                $this->Email->sendAs = 'text';      // both text or html

                // import the key of the user into the keyring
                // this is not really necessary, but it enables us to find
                // the correct key-id even if it is not the same as the emailaddress
                $keyImportOutput = $gpg->importKey($user['User']['gpgkey']);
                // say what key should be used to encrypt
                try {
                    $gpg = new Crypt_GPG(array('homedir' => Configure::read('GnuPG.homedir')));
                    $gpg->addEncryptKey($keyImportOutput['fingerprint']); // use the key that was given in the import

                    $bodyEncSig = $gpg->encrypt($bodySigned, true);

                    $this->set('body', $bodyEncSig);
                    $this->Email->send();
                } catch (Exception $e){
                    // catch errors like expired PGP keys
                    $this->log($e->getMessage());
                    // no need to return here, as we want to send out mails to the other users if GPG encryption fails for a single user
                }
                // If you wish to send multiple emails using a loop, you'll need
                // to reset the email fields using the reset method of the Email component.
                $this->Email->reset();
            }
        } catch (Exception $e){
            // catch errors like expired PGP keys
            $this->log($e->getMessage());
            return $e->getMessage();
        }

        // LATER check if sending email succeeded and return appropriate result
        return true;
    }

    /**
     * Send out an contact email to the person who posted the event.
     * Users with a GPG key will get the mail encrypted, other users will get the mail unencrypted
     *
     * @throws NotFoundException
     */
    public function contact($id = null) {
        $this->Event->id = $id;
        if (!$this->Event->exists()) {
            throw new NotFoundException(__('Invalid event'));
        }

        // User has filled in his contact form, send out the email.
        if ($this->request->is('post') || $this->request->is('put')) {
            $message = $this->request->data['Event']['message'];
            $all = $this->request->data['Event']['person'];
            if ($this->__sendContactEmail($id, $message, $all)) {
                // redirect to the view event page
                $this->Session->setFlash(__('Email sent to the reporter.', true));
            } else {
                $this->Session->setFlash(__('Sending of email failed', true), 'default', array(), 'error');
            }
            $this->redirect(array('action' => 'view', $id));
        }
        // User didn't see the contact form yet. Present it to him.
        if (empty($this->data)) {
            $this->data = $this->Event->read(null, $id);
        }
    }

    /**
     *
     * Sends out an email to all people within the same org
     * with the request to be contacted about a specific event.
     * @todo move __sendContactEmail($id, $message) to a better place. (components?)
     *
     * @param unknown_type $id The id of the event for wich you want to contact the org.
     * @param unknown_type $message The custom message that will be appended to the email.
     * @param unknown_type $all, true: send to org, false: send to person.
     *
     * @codingStandardsIgnoreStart
     * @throws \UnauthorizedException as well. // TODO Exception NotFoundException
     * @codingStandardsIgnoreEnd
     *
     * @return True if success, False if error
     */
    private function __sendContactEmail($id, $message, $all) {
        // fetch the event
        $event = $this->Event->read(null, $id);
        $this->loadModel('User');
        if (!$all) {
            //Insert extra field here: alertOrg or something, then foreach all the org members
            //limit this array to users with contactalerts turned on!
            $orgMembers = array();
            $this->User->recursive = 0;
            $temp = $this->User->findAllByOrg($event['Event']['org'], array('email', 'gpgkey', 'contactalert', 'id'));
            foreach ($temp as $tempElement) {
                if ($tempElement['User']['contactalert'] || $tempElement['User']['id'] == $event['Event']['user_id']) {
                    array_push($orgMembers, $tempElement);
                }
            }
        } else {
            $orgMembers = $this->User->findAllById($event['Event']['user_id'], array('email', 'gpgkey'));
        }

        // The mail body, h() is NOT needed as we are sending plain-text mails.
        $body = "";
        $body .= "Hello, \n";
        $body .= "\n";
        $body .= "Someone wants to get in touch with you concerning a MISP event. \n";
        $body .= "\n";
        $body .= "You can reach him at " . $this->Auth->user('email') . "\n";
        if (!$this->Auth->user('gpgkey'))
            $body .= "His GPG/PGP key is added as attachment to this email. \n";
        $body .= "\n";
        $body .= "He wrote the following message: \n";
        $body .= $message . "\n";
        $body .= "\n";
        $body .= "\n";
        $body .= "The event is the following: \n";

        // print the event in mail-format
        // LATER place event-to-email-layout in a function
        $appendlen = 20;
        $body .= 'URL        : ' . Configure::read('CyDefSIG.baseurl') . '/events/view/' . $event['Event']['id'] . "\n";
        $body .= 'Event    : ' . $event['Event']['id'] . "\n";
        $body .= 'Date      : ' . $event['Event']['date'] . "\n";
        if ('true' == Configure::read('CyDefSIG.showorg')) {
            $body .= 'Reported by : ' . $event['Event']['org'] . "\n";
        }
        $body .= 'Risk      : ' . $event['Event']['risk'] . "\n";
        $body .= 'Analysis  : ' . $event['Event']['analysis'] . "\n";
        $relatedEvents = $this->Event->getRelatedEvents($this->Auth->user());
        if (!empty($relatedEvents)) {
            foreach ($relatedEvents as &$relatedEvent) {
                $body .= 'Related to  : ' . Configure::read('CyDefSIG.baseurl') . '/events/view/' . $relatedEvent['Event']['id'] . ' (' . $relatedEvent['Event']['date'] . ')' . "\n";

            }
        }
        $body .= 'Info  : ' . "\n";
        $body .= $event['Event']['info'] . "\n";
        $body .= "\n";
        $body .= 'Attributes  :' . "\n";
        $bodyTempOther = "";
        if (!empty($event['Attribute'])) {
            foreach ($event['Attribute'] as &$attribute) {
                $line = '- ' . $attribute['type'] . str_repeat(' ', $appendlen - 2 - strlen( $attribute['type'])) . ': ' . $attribute['value'] . "\n";
                if ('other' == $attribute['type']) // append the 'other' attribute types to the bottom.
                    $bodyTempOther .= $line;
                else $body .= $line;
            }
        }
        $body .= "\n";
        $body .= $bodyTempOther;    // append the 'other' attribute types to the bottom.

        // sign the body
        require_once 'Crypt/GPG.php';
        $gpg = new Crypt_GPG(array('homedir' => Configure::read('GnuPG.homedir'))); // , 'debug' => true
        $gpg->addSignKey(Configure::read('GnuPG.email'), Configure::read('GnuPG.password'));
        $bodySigned = $gpg->sign($body, Crypt_GPG::SIGN_MODE_CLEAR);

        // Add the GPG key of the user as attachment
        // LATER sign the attached GPG key
        if (!empty($meUser['gpgkey'])) {
            // save the gpg key to a temporary file
            $tmpfname = tempnam(TMP, "GPGkey");
            $handle = fopen($tmpfname, "w");
            fwrite($handle, $meUser['gpgkey']);
            fclose($handle);
            // attach it
            $this->Email->attachments = array(
                    'gpgkey.asc' => $tmpfname
            );
        }

        foreach ($orgMembers as &$reporter) {
            if (!empty($reporter['User']['gpgkey'])) {
                // import the key of the user into the keyring
                // this isn't really necessary, but it gives it the fingerprint necessary for the next step
                $keyImportOutput = $gpg->importKey($reporter['User']['gpgkey']);
                // say what key should be used to encrypt
                try {
                    $gpg = new Crypt_GPG(array('homedir' => Configure::read('GnuPG.homedir')));
                    $gpg->addEncryptKey($keyImportOutput['fingerprint']); // use the key that was given in the import

                    $bodyEncSig = $gpg->encrypt($bodySigned, true);
                } catch (Exception $e){
                    // catch errors like expired PGP keys
                    $this->log($e->getMessage());
                    // no need to return here, as we want to send out mails to the other users if GPG encryption fails for a single user
                }
            } else {
                $bodyEncSig = $bodySigned;
                // FIXME should I allow sending unencrypted "contact" mails to people if they didn't import they GPG key?
            }

            // prepare the email
            $this->Email->from = Configure::read('CyDefSIG.email');
            $this->Email->to = $reporter['User']['email'];
            $this->Email->subject = "[" . Configure::read('CyDefSIG.name') . "] Need info about event " . $id . " - TLP Amber";
            //$this->Email->delivery = 'debug';   // do not really send out mails, only display it on the screen
            $this->Email->template = 'body';
            $this->Email->sendAs = 'text';      // both text or html
            $this->set('body', $bodyEncSig);
            // Add the GPG key of the user as attachment
            // LATER sign the attached GPG key
            if (!empty($meUser['gpgkey'])) {
                // attach the gpg key
                $this->Email->attachments = array(
                        'gpgkey.asc' => $tmpfname
                );
            }
            // send it
            $result = $this->Email->send();
            // If you wish to send multiple emails using a loop, you'll need
            // to reset the email fields using the reset method of the Email component.
            $this->Email->reset();
        }

        // remove the temporary gpg file
        if (!empty($meUser['gpgkey']))
            unlink($tmpfname);

        return $result;
    }

    public function automation() {
        // Simply display a static view
        if (!$this->checkAction('perm_auth')) {
            $this->redirect(array('controller' => 'events', 'action' => 'index'));
        }
        // generate the list of Attribute types
        $this->loadModel('Attribute');
        $this->set('sigTypes', array_keys($this->Attribute->typeDefinitions));
    }

    public function export() {
        // Simply display a static view
        // generate the list of Attribute types
        $this->loadModel('Attribute');
        $this->set('sigTypes', array_keys($this->Attribute->typeDefinitions));
    }

    public function downloadXML($id = null){
        $e = $this->Event->find('first', array(
            'conditions' => array('Event.id' => $id),
            'contain' => array(
                'Attribute' => array('KillChain'),
                'TargetedDomain',
                'ThreatLevel',

            )
        ));
        $this->set('event', $e);
        $this->response->download('misp.export.event.'.$e['Event']['id'].'.xml');
    }

    public function xml($key, $eventid=null) {
        if ($key != 'download') {
            // check if the key is valid -> search for users based on key
            $user = $this->checkAuthUser($key);
            if (!$user) {
                throw new UnauthorizedException('This authentication key is not authorized to be used for exports. Contact your administrator.');
            }
            // display the full xml
            $this->response->type('xml');   // set the content type
            $this->layout = 'xml/default';
            $this->header('Content-Disposition: inline; filename="misp.xml"');
        } else {
            if (!$this->Auth->user('id')) {
                throw new UnauthorizedException('You have to be logged in to do that.');
            }
            // display the full xml
            $this->response->type('xml');   // set the content type
            $this->layout = 'xml/default';
            if ($eventid == null) {
                $this->header('Content-Disposition: download; filename="misp.export.all.xml"');
            } else {
                $this->header('Content-Disposition: download; filename="misp.export.event' . $eventid . '.xml"');
            }
        }
        if (isset($eventid)) {
            $this->Event->id = $eventid;
            if (!$this->Event->exists()) {
                throw new NotFoundException(__('Invalid event'));
            }
            $conditions = array("Event.id" => $eventid);
        } else {
            $conditions = array();
        }
        $conditionsAttributes = array();
        //restricting to non-private or same org if the user is not a site-admin.
        if (!$this->_isSiteAdmin()) {
            $temp = array();
            $temp2 = array();
            $org = $this->_checkOrg();
            $distribution = array();
            array_push($distribution, array('Event.private =' => 0));
            array_push($distribution, array('Event.cluster =' => 1));
            array_push($temp, array('OR' => $distribution));
            array_push($temp, array('Event.org LIKE' => $org));
            $conditions['OR'] = $temp;
            $distribution2 = array();
            array_push($distribution2, array('Attribute.private =' => 0));
            array_push($distribution2, array('Attribute.cluster =' => 1));
            array_push($temp2, array('OR' => $distribution2));
            array_push($temp2, array('(SELECT events.org FROM events WHERE events.id = Attribute.event_id) LIKE' => $org));
            $conditionsAttributes['OR'] = $temp2;
            $conditionsAttributes['AND'] = array('Attribute.to_ids =' => 1);
        }

        // do not expose all the data ...
        $fields = array('Event.id', 'Event.date', 'Event.risk', 'Event.analysis', 'Event.info', 'Event.published', 'Event.uuid');
        $fieldsAtt = array('Attribute.id', 'Attribute.type', 'Attribute.category', 'Attribute.value', 'Attribute.to_ids', 'Attribute.uuid', 'Attribute.event_id');
        if ('true' == Configure::read('CyDefSIG.showorg')) {
            $fields[] = 'Event.org';
        }

        $params = array('conditions' => $conditions,
                'recursive' => 1,
                'fields' => $fields,
                'contain' => array(
                        'Attribute' => array(
                                'fields' => $fieldsAtt,
                                'conditions' => $conditionsAttributes,
                        ),
                )
        );
        $results = $this->Event->find('all', $params);
        $this->set('results', $results);
    }

    public function nids($key) {
        if ($key != 'download') {
            $this->response->type('txt');   // set the content type
            $this->header('Content-Disposition: inline; filename="misp.rules"');
            $this->layout = 'text/default';
            // check if the key is valid -> search for users based on key
            $user = $this->checkAuthUser($key);
            if (!$user) {
                throw new UnauthorizedException('This authentication key is not authorized to be used for exports. Contact your administrator.');
            }
        } else {
            //$this->autoRender = false;
            $this->response->type('txt');   // set the content type
            $this->header('Content-Disposition: download; filename="misp.nids.rules"');
            $this->layout = 'text/default';
            // check if there's a user logged in or not
            if (!$this->Auth->user('id')) {
                throw new UnauthorizedException('You have to be logged in to do that.');
            }
            $user = $this->Auth->user;
        }

        // display the full snort rulebase
        $this->loadModel('Attribute');

        //restricting to non-private or same org if the user is not a site-admin.
        $conditions['AND'] = array('Attribute.to_ids' => 1, "Event.published" => 1);
        if (!$this->_isSiteAdmin()) {
            $temp = array();
            $distribution = array();
            array_push($distribution, array('Attribute.private =' => 0));
            array_push($distribution, array('Attribute.cluster =' => 1));
            array_push($temp, array('OR' => $distribution));
            array_push($temp, array('(SELECT events.org FROM events WHERE events.id = Attribute.event_id) LIKE' => $this->_checkOrg()));
            $conditions['OR'] = $temp;
        }

        $params = array(
                'conditions' => $conditions, //array of conditions
                'recursive' => 0, //int
                'group' => array('Attribute.type', 'Attribute.value1'), //fields to GROUP BY
        );
        $items = $this->Attribute->find('all', $params);

        $rules = $this->NidsExport->export($items, $user['User']['nids_sid']);
        $this->set('rules', $rules);
    }

    public function hids($type, $key) {

        if ($key != 'download') {
            // check if the key is valid -> search for users based on key
            $user = $this->checkAuthUser($key);
            if (!$user) {
                throw new UnauthorizedException('This authentication key is not authorized to be used for exports. Contact your administrator.');
            }
            $this->response->type('txt');   // set the content type
            $this->header('Content-Disposition: inline; filename="misp.' . $type . '.rules"');
            $this->layout = 'text/default';
        } else {
            // check if there's a user logged in or not
            if (!$this->Auth->user('id')) {
                throw new UnauthorizedException('You have to be logged in to do that.');
            }
            $this->response->type(array('txt' => 'text/html')); // set the content type
            $this->header('Content-Disposition: download; filename="misp.' . $type . '.rules"');
            $this->layout = 'text/default';
        }

        // check if it's a valid type
        if ($type != 'md5' && $type != 'sha1') {
            throw new UnauthorizedException('Invalid hash type.');
        }

        $this->loadModel('Attribute');

        //restricting to non-private or same org if the user is not a site-admin.
        $conditions['AND'] = array('Attribute.to_ids' => 1, 'Event.published' => 1);
        if (!$this->_isSiteAdmin()) {
            $temp = array();
            $distribution = array();
            array_push($distribution, array('Attribute.private =' => 0));
            array_push($distribution, array('Attribute.cluster =' => 1));
            array_push($temp, array('OR' => $distribution));
            array_push($temp, array('(SELECT events.org FROM events WHERE events.id = Attribute.event_id) LIKE' => $this->_checkOrg()));
            $conditions['OR'] = $temp;
        }

        $params = array(
                'conditions' => $conditions, //array of conditions
                'recursive' => 0, //int
                'group' => array('Attribute.type', 'Attribute.value1'), //fields to GROUP BY
        );
        $items = $this->Attribute->find('all', $params);

        if ($type == 'md5') $rules = $this->HidsMd5Export->export($items);
        if ($type == 'sha1') $rules = $this->HidsSha1Export->export($items);
        $this->set('rules', $rules);
    }

    public function text($key, $type="") {
        if ($key != 'download') {
            // check if the key is valid -> search for users based on key
            $user = $this->checkAuthUser($key);
            if (!$user) {
                throw new UnauthorizedException('This authentication key is not authorized to be used for exports. Contact your administrator.');
            }
            $this->response->type('txt');   // set the content type
            $this->header('Content-Disposition: inline; filename="misp.' . $type . '.txt"');
            $this->layout = 'text/default';
        } else {
            if (!$this->Auth->user('id')) {
                throw new UnauthorizedException('You have to be logged in to do that.');
            }
            $this->response->type('txt');   // set the content type
            $this->header('Content-Disposition: download; filename="misp.' . $type . '.txt"');
            $this->layout = 'text/default';
        }

        $this->loadModel('Attribute');

        //restricting to non-private or same org if the user is not a site-admin.
        $conditions['AND'] = array('Attribute.type' => $type, 'Attribute.to_ids =' => 1);
        if (!$this->_isSiteAdmin()) {
            $temp = array();
            $distribution = array();
            array_push($distribution, array('Attribute.private =' => 0));
            array_push($distribution, array('Attribute.cluster =' => 1));
            array_push($temp, array('OR' => $distribution));
            array_push($temp, array('(SELECT events.org FROM events WHERE events.id = Attribute.event_id) LIKE' => $this->_checkOrg()));
            $conditions['OR'] = $temp;
        }

        $params = array(
                'conditions' => $conditions, //array of conditions
                'recursive' => 0, //int
                'fields' => array('Attribute.value'), //array of field names
                'order' => array('Attribute.value'), //string or array defining order
                'group' => array('Attribute.value'), //fields to GROUP BY
        );
        $attributes = $this->Attribute->find('all', $params);
        $this->set('attributes', $attributes);
    }

    //public function dot($key) {
    //  // check if the key is valid -> search for users based on key
    //  $this->loadModel('User');
    //  // no input sanitization necessary, it's done by model
    //  $this->User->recursive=0;
    //  $user = $this->User->findByAuthkey($key);
    //  if (empty($user)) {
    //      throw new UnauthorizedException('Incorrect authentication key');
    //  }
    //  // display the full snort rulebase
    //  $this->response->type('txt');   // set the content type
    //  $this->header('Content-Disposition: inline; filename="cydefsig.rules"');
    //  $this->layout = 'text/default';

    //  $rules= array();
    //  $this->loadModel('Attribute');

    //  $params = array(
    //          'recursive' => 0,
    //          'fields' => array('Attribute.*')
    //  );
    //  $items = $this->Attribute->find('all', $params);

    //  $composite_types = $this->Attribute->getCompositeTypes();
    //  // rebuild the array with the correct data
    //  foreach ($items as &$item) {
    //      if (in_array($item['Attribute']['type'], $composite_types)) {
    //          // create a new item that will contain value2
    //          $new_item = $item;
    //          // set the correct type for the first item
    //          $pieces = explode('|', $item['Attribute']['type']);
    //          $item['Attribute']['type'] = $pieces[0];
    //          // set the correct data for the new item
    //          $new_item['Attribute']['type'] = (isset($pieces[1]))? $pieces[1] : 'md5';
    //          $new_item['Attribute']['value'] = $item['Attribute']['value2'];
    //          unset($new_item['Attribute']['value1']);
    //          unset($new_item['Attribute']['value2']);
    //          // store the new item
    //          $items[] = $new_item;
    //      }
    //      // set the correct fields for the attribute
    //      if (isset($item['Attribute']['value1'])) {
    //          $item['Attribute']['value'] = $item['Attribute']['value1'];
    //      }
    //      unset($item['Attribute']['value1']);
    //      unset($item['Attribute']['value2']);
    //  }
    //  debug($items);

    //  // iterate over the array to build the GV links
    //  require_once 'Image/GraphViz.php';
    //  $gv = new Image_GraphViz();
    //  $gv->addEdge(array('wake up'        => 'visit bathroom'));
    //  $gv->addEdge(array('visit bathroom' => 'make coffee'));
    //  foreach ($items as &$item) {
    //      $gv->addNode('Node 1',
    //              array(''));
    //  }
    //  debug($gv);
    //  $gv->image();
    //}

    public function addGfiZip($id) {
        if (!empty($this->data) && $this->data['Event']['submittedfile']['size'] > 0 &&
                is_uploaded_file($this->data['Event']['submittedfile']['tmp_name'])) {
            $zipData = fread(fopen($this->data['Event']['submittedfile']['tmp_name'], "r"),
                    $this->data['Event']['submittedfile']['size']);

            // write
            $rootDir = APP . "files" . DS . $id . DS;
            App::uses('Folder', 'Utility');
            $dir = new Folder($rootDir, true);
            $destpath = $rootDir;
            $file = new File ($destpath);
            if (!preg_match('@^[\w-,\s]+\.[A-Za-z0-9_]{2,4}$@', $this->data['Event']['submittedfile']['name'])) throw new Exception ('Filename not allowed');
            $zipfile = new File ($destpath . DS . $this->data['Event']['submittedfile']['name']);
            $result = $zipfile->write($zipData);
            if (!$result) $this->Session->setFlash(__('Problem with writing the zip file. Please report to administrator.'));

            // extract zip..
            $execRetval = '';
            exec("unzip " . $zipfile->path . ' -d "' . $rootDir . '"', $execOutput, $execRetval);
            $execOutput = array();
            if ($execRetval != 0) { // not EXIT_SUCCESS
                // do some?
            }

            // now open the xml..
            $xml = $rootDir . DS . 'Analysis' . DS . 'analysis.xml';
            $fileData = fread(fopen($xml, "r"), $this->data['Event']['submittedfile']['size']);

            // read XML
            $this->readGfiXML($fileData, $id);
        }
    }

    public function addIOCFile($id) {
        if (!empty($this->data) && $this->data['Event']['submittedioc']['size'] > 0 &&
                is_uploaded_file($this->data['Event']['submittedioc']['tmp_name'])) {
            $iocData = fread(fopen($this->data['Event']['submittedioc']['tmp_name'], "r"),
                    $this->data['Event']['submittedioc']['size']);

            // write
            $rootDir = APP . "files" . DS . $id . DS;
            App::uses('Folder', 'Utility');
            $dir = new Folder($rootDir . 'ioc', true);
            $destpath = $rootDir . 'ioc';
            $file = new File ($destpath);
            if (!preg_match('@^[\w-,\s]+\.[A-Za-z0-9_]{2,4}$@', $this->data['Event']['submittedioc']['name'])) throw new Exception ('Filename not allowed');
            $iocfile = new File ($destpath . DS . $this->data['Event']['submittedioc']['name']);
            $result = $iocfile->write($iocData);
            if (!$result) $this->Session->setFlash(__('Problem with writing the ioc file. Please report to administrator.'));

            // now open the xml..
            $xml = $rootDir . DS . 'Analysis' . DS . 'analysis.xml';
            $fileData = fread(fopen($destpath . DS . $this->data['Event']['submittedioc']['name'], "r"), $this->data['Event']['submittedioc']['size']);

            // read XML
            $event = $this->IOCImport->readXML($fileData, $id);

            // make some changes to have $saveEvent in the format that is needed to save the event together with its attributes
            $fails = $event['Fails'];
            $saveEvent['Attribute'] = $event['Attribute'];
            // we've already stored these elsewhere, unset them so we can extract the event related data
            unset($event['Attribute']);
            unset($event['Fails']);
            // save the event related data into $saveEvent['Event']
            $saveEvent['Event'] = $event;
            $saveEvent['Event']['id'] = $id;
            $fieldList = array(
                        'Event' => array('info', 'uuid'),
                        'Attribute' => array('event_id', 'category', 'type', 'value', 'value1', 'value2', 'to_ids', 'uuid')
                        );

            // Save it all
            $saveResult = $this->Event->saveAssociated($saveEvent, array('validate' => true, 'fieldList' => $fieldList));

            // set stuff for the view and render the showIOCResults view.
            $this->set('attributes', $saveEvent['Attribute']);
            if (isset($fails)) {
                $this->set('fails', $fails);
            }
            $this->set('eventId', $id);
            $this->render('showIOCResults');
        }
    }

    public function readGfiXML($data, $id) {
        $this->loadModel('Attribute');

        // import XML class
        App::uses('Xml', 'Utility');
        // now parse it
        $parsedXml =& Xml::build($data, array('return' => 'simplexml'));

        // xpath..

        //Payload delivery -- malware-sample
        $results = $parsedXml->xpath('/analysis');
        foreach ($results as $result) {
            foreach ($result[0]->attributes() as $key => $val) {
                if ((string)$key == 'filename') $realFileName = (string)$val;
            }
        }
        $realMalware = $realFileName;
        $rootDir = APP . "files" . DS . $id . DS;
        $malware = $rootDir . DS . 'sample';
        $this->Event->Attribute->uploadAttachment($malware, $realFileName,  true, $id);

        //Network activity -- .pcap
        $realFileName = 'analysis.pcap';
        $rootDir = APP . "files" . DS . $id . DS;
        $malware = $rootDir . DS . 'Analysis' . DS . 'analysis.pcap';
        $this->Event->Attribute->uploadAttachment($malware, $realFileName,  false, $id, 'Network activity');

        //Artifacts dropped -- filename|md5
        $files = array();
        // TODO what about stored_modified_file ??
        $results = $parsedXml->xpath('/analysis/processes/process/stored_files/stored_created_file');
        foreach ($results as $result) {
            $arrayItemKey = '';
            $arrayItemValue = '';
            foreach ($result[0]->attributes() as $key => $val) {
                if ($key == 'filename') $arrayItemKey = (string)$val;
                if ($key == 'md5') $arrayItemValue = (string)$val;
            }

            $files[$arrayItemKey] = $arrayItemValue;
        }
        //$files = array_unique($files);

        // write content..
        foreach ($files as $key => $val) {
            $keyName = $key;

            if (!strpos($key, $realMalware)) {
                $itsType = 'malware-sample';
            } else {
                $itsType = 'filename|md5';
            }

            // the actual files..
            // seek $val in dirs and add..
            $ext = substr($key, strrpos($key, '.'));
            $actualFileName = $val . $ext;
            $actualFileNameBase = str_replace('\\', '/', $key);
            $actualFileNameArray[] = basename($actualFileNameBase);
            $realFileName = end(explode('\\', $key));
            // have the filename, now look at parents parent for the process number
            $express = "/analysis/processes/process/stored_files/stored_created_file[@md5='" . $val . "']/../..";
            $results = $parsedXml->xpath($express);
            foreach ($results as $result) {
                foreach ($result[0]->attributes() as $key => $val) {
                    if ((string)$key == 'index') $index = (string)$val;
                }
            }
            $actualFile = $rootDir . DS . 'Analysis' . DS . 'proc_' . $index . DS . 'modified_files' . DS . $actualFileName;
            $extraPath = 'Analysis' . DS . 'proc_' . $index . DS . 'modified_files' . DS;
            $file = new File($actualFile);
            if ($file->exists()) { // TODO put in array for test later
                $this->Event->Attribute->uploadAttachment($actualFile, $realFileName, true, $id, null, $extraPath, $keyName); // TODO was false
            }
        }

        //Network activity -- ip-dst
        $ips = array();
        $results = $parsedXml->xpath('/analysis/processes/process/networkpacket_section/connect_to_computer');
        foreach ($results as $result) {
            foreach ($result[0]->attributes() as $key => $val) {
                if ($key == 'remote_ip') $ips[] = (string)$val;
            }
        }
        // write content..
        foreach ($ips as $ip) {
            // add attribute..
            $this->Attribute->read(null, 1);
            $this->Attribute->save(array(
                    'event_id' => $id,
                    'category' => 'Network activity',
                    'type' => 'ip-dst',
                    'value' => $ip,
                    'to_ids' => false));
        }

        // Persistence mechanism -- regkey|value
        $regs = array();
        $results = $parsedXml->xpath('/analysis/processes/process/registry_section/set_value');
        foreach ($results as $result) {
            $arrayItemKey = '';
            $arrayItemValue = '';
            foreach ($result[0]->attributes() as $key => $val) {
                if ($key == 'key_name') $arrayItemKey = (string)$val;
                if ($key == 'data') $arrayItemValue = (string)$val;
            }
            $regs[$arrayItemKey] = str_replace('(UNICODE_0x00000000)', '', $arrayItemValue);
        }
        //$regs = array_unique($regs);

        // write content..
        foreach ($regs as $key => $val) {
            // add attribute..
            $this->Attribute->read(null, 1);
            if ($val == '[binary_data]') {
                $itsCategory = 'Persistence mechanism';
                $itsType = 'regkey';
                $itsValue = $key;
            } else {
                if ($this->strposarray($val,$actualFileNameArray)) {
                    $itsCategory = 'Persistence mechanism';
                    $itsType = 'regkey|value';
                    $itsValue = $key . '|' . $val;
                } else {
                    $itsCategory = 'Artifacts dropped'; // Persistence mechanism
                    $itsType = 'regkey|value';
                    $itsValue = $key . '|' . $val;
                }
            }
            $this->Attribute->save(array(
                    'event_id' => $id,
                    'category' => $itsCategory, // 'Persistence mechanism'
                    'type' => $itsType,
                    'value' => $itsValue,
                    'to_ids' => false));
        }
    }

    public function strposarray($string, $array) {
        $toReturn = false;
        foreach ($array as $item) {
            if (strpos($string,$item)) {
                $toReturn = true;
            }
        }
        return $toReturn;
    }

    /**
     * generateAllFor<FieldName>
     **/
    public function generateAllFor($field) {
        parent::generateAllFor($field);
    }

    public function downloadSearchResult() {
        $idList = $this->Session->read('search_find_idlist');
        $this->Session->write('search_find_idlist', '');
        // display the full xml
        $this->response->type('xml');   // set the content type
        $this->layout = 'xml/default';
        $this->header('Content-Disposition: download; filename="misp.search.results.xml"');
        $put['OR'] = array();
        foreach ($idList as $listElement) {
            $put['OR'][] = array('Event.id' => $listElement);
        }
        $conditions['AND'][] = $put;
        // Restricting to non-private or same org if the user is not a site-admin.
        if (!$this->_isSiteAdmin()) {
            $temp = array();
            $temp2 = array();
            $org = $this->_checkOrg();
            $distribution = array();
            array_push($distribution, array('Event.private =' => 0));
            array_push($distribution, array('Event.cluster =' => 1));
            array_push($temp, array('OR' => $distribution));
            array_push($temp, array('Event.org LIKE' => $org));
            $put2['OR'] = $temp;
            $conditions['AND'][] = $put2;
            $distribution2 = array();
            array_push($distribution2, array('Attribute.private =' => 0));
            array_push($distribution2, array('Attribute.cluster =' => 1));
            array_push($temp2, array('OR' => $distribution2));
            array_push($temp2, array('(SELECT events.org FROM events WHERE events.id = Attribute.event_id) LIKE' => $org));
            $conditionsAttributes['OR'] = $temp2;
            $conditionsAttributes['AND'] = array('Attribute.to_ids =' => 1);
        }

        // do not expose all the data ...
        $fields = array('Event.id', 'Event.date', 'Event.risk', 'Event.analysis', 'Event.info', 'Event.published', 'Event.uuid');
        $fieldsAtt = array('Attribute.id', 'Attribute.type', 'Attribute.category', 'Attribute.value', 'Attribute.to_ids', 'Attribute.uuid', 'Attribute.event_id');
        if ('true' == Configure::read('CyDefSIG.showorg')) {
            $fields[] = 'Event.org';
        }

        $params = array('conditions' => $conditions,
                'recursive' => 1,
                'fields' => $fields,
                'contain' => array(
                        'Attribute' => array(
                                'fields' => $fieldsAtt,
                                'conditions' => $conditionsAttributes,
                        ),
                )
        );
        $results = $this->Event->find('all', $params);
        $this->set('results', $results);
        $this->render('xml');
    }

    public function downloadOpenIOCEvent($eventid) {

        // return a downloadable text file called misp.openIOC.<eventId>.ioc for individual events
        // TODO implement mass download of all events - maybe in a zip file?
        $this->response->type('text');  // set the content type
        if ($eventid == null) {
            throw new Exception('Not yet implemented');
            // $this->header('Content-Disposition: download; filename="misp.openIOC.ioc"');
        } else {
            $this->header('Content-Disposition: download; filename="misp.openIOC' . $eventid . '.ioc"');
        }
        $this->layout = 'text/default';

        // get the event if it exists and load it together with its attributes
        $this->Event->id = $eventid;
        if (!$this->Event->exists()) {
            throw new NotFoundException(__('Invalid event'));
        }
        $this->Event->recursive = 1;
        $event = $this->Event->read(null, $eventid);

        // set up helper variables for the authorisation check in the component
        $isMyEvent = false;
        if ($this->Auth->User == $event['Event']['org']) $isMyEvent = true;
        $isSiteAdmin = $this->_isSiteAdmin();

        // send the event and the vars needed to check authorisation to the Component
        $final = $this->IOCExport->buildAll($event, $isMyEvent, $isSiteAdmin);
        $this->set('final', $final);
    }

    public function updateCIMBL(){
        $this->autoRender = false;
        if($this->request->is('post')){
            $this->Event->Behaviors->unload('SysLogLogable.SysLogLogable');
            $this->Event->id = $this->request->data['Event']['id'];
            if(!$this->Event->saveField('CIMBL_id', $this->request->data['Event']['CIMBL_id'], false)){
                echo json_encode(array('msg' => 'Could not update CIMBL'));
            }
        }

    }
}
