<?php

class FegCustomerPage extends FegPageExtension {
	private $_TPL_PATH = '';
	const ID = 'core.page.customer';

	
	function __construct($manifest) {
		$this->_TPL_PATH = dirname(dirname(dirname(__FILE__))) . '/templates/';
		parent::__construct($manifest);
	}
		
	function isVisible() {
		// check login
		$visit = FegApplication::getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}

	function getActivity() {
		return new Model_Activity('activity.customer');
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);

		$active_worker = FegApplication::getActiveWorker();
		$visit = FegApplication::getVisit();
		$response = DevblocksPlatform::getHttpResponse();
		$translate = DevblocksPlatform::getTranslationService();
		$url = DevblocksPlatform::getUrlService();

		$stack = $response->path;
		@array_shift($stack); // customer
		
		@$customer_id = array_shift($stack);
		@$customer = DAO_CustomerAccount::get($customer_id);
		if(empty($customer)) {
			echo "<H1>".$translate->_('customer.display.invalid_customer')."</H1>";
			return;
		}
		$tpl->assign('customer_id', $customer_id);
		
		// Tabs
		$tab_manifests = DevblocksPlatform::getExtensions('feg.customer.tab', false);
		$tpl->assign('tab_manifests', $tab_manifests);

		@$tab_selected = array_shift($stack);
		if(empty($tab_selected)) $tab_selected = 'property';
		$tpl->assign('tab_selected', $tab_selected);
		
		switch($tab_selected) {
			case 'property':
				@$tab_parm = array_shift($stack);				
				break;
		}
		
		// ====== Who's Online
		$whos_online = DAO_Worker::getAllOnline();
		if(!empty($whos_online)) {
			$tpl->assign('whos_online', $whos_online);
			$tpl->assign('whos_online_count', count($whos_online));
		}
		
		$tpl->display('file:' . $this->_TPL_PATH . 'customer/index.tpl');
	}
	
	// Ajax
	function showTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_CustomerTab) {
			$inst->showTab();
		}
	}
	
	// Post
	function saveTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_CustomerTab) {
			$inst->saveTab();
		}
	}
	
	/*
	 * [TODO] Proxy any func requests to be handled by the tab directly, 
	 * instead of forcing tabs to implement controllers.  This should check 
	 * for the *Action() functions just as a handleRequest would
	 */
	function handleTabActionAction() {
		@$tab = DevblocksPlatform::importGPC($_REQUEST['tab'],'string','');
		@$action = DevblocksPlatform::importGPC($_REQUEST['action'],'string','');

		if(null != ($tab_mft = DevblocksPlatform::getExtension($tab)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_CustomerTab) {
				if(method_exists($inst,$action.'Action')) {
					call_user_func(array(&$inst, $action.'Action'));
				}
		}
	}
	
};

class FegCustomerTabProperty extends Extension_CustomerTab {
	private $_TPL_PATH = '';

	function __construct($manifest) {
		$this->_TPL_PATH = dirname(dirname(dirname(__FILE__))) . '/templates/';
		$this->DevblocksExtension($manifest,1);
	}
 
	function showTab() {
		$translate = DevblocksPlatform::getTranslationService();
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		
		@$customer_id = DevblocksPlatform::importGPC($_REQUEST['customer_id'],'integer',0);
		$tpl->assign('customer_id', $customer_id);
		
		$core_tpl = $this->_TPL_PATH;
		$tpl->assign('core_tpl', $core_tpl);

		@$customer = DAO_CustomerAccount::get($customer_id);
		if(empty($customer)) {
			echo "<H1>".$translate->_('customer.display.invalid_customer')."</H1>";
			return;
		}
		$tpl->assign('customer', $customer);
		
		@$import_source = DAO_ImportSource::getAll();
		$tpl->assign('import_source', $import_source);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'customer/tabs/property/index.tpl');
	}

	function saveCustomerAccountAction() {
		@$customer_id = DevblocksPlatform::importGPC($_REQUEST['customer_id'],'integer',0);
		@$and_close = DevblocksPlatform::importGPC($_POST['and_close'],'integer',0);
		
		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer');
		@$disabled = DevblocksPlatform::importGPC($_POST['account_is_disabled'],'integer',0);
		@$import_source = DevblocksPlatform::importGPC($_POST['customer_account_import_source'],'integer',0);
		
		@$account_number = DevblocksPlatform::importGPC($_REQUEST['customer_account_number'],'string','');
		@$account_name = DevblocksPlatform::importGPC($_REQUEST['customer_account_name'],'string','');
		
		$fields = array(
			DAO_CustomerAccount::IMPORT_SOURCE => $import_source,
			DAO_CustomerAccount::ACCOUNT_NAME => $account_name,
			DAO_CustomerAccount::ACCOUNT_NUMBER => $account_number,
			DAO_CustomerAccount::IS_DISABLED => $disabled,
		);
		// Update Customer Recipients 
		$status = DAO_CustomerAccount::update($customer_id, $fields);
		
		if($and_close) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('account')));
		} else {
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('customer', $customer_id,'property')));
		}
	}
};

class FegCustomerTabRecipient extends Extension_CustomerTab {
	private $_TPL_PATH = '';

	function __construct($manifest) {
		$this->_TPL_PATH = dirname(dirname(dirname(__FILE__))) . '/templates/';
		$this->DevblocksExtension($manifest,1);
	}
 
	function showTab() {
		@$customer_id = DevblocksPlatform::importGPC($_REQUEST['customer_id'],'integer',0);
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		
//  FIXME setup ACL check.
//		$worker = FegApplication::getActiveWorker();
//		if(!$worker || !$worker->is_superuser) {
//			echo $translate->_('common.access_denied');
//			return;
//		}
		
		$tpl->assign('customer_id', $customer_id);
		
		$defaults = new Feg_AbstractViewModel();
		$defaults->name = 'Recipient List';
		$defaults->id = '_customer_view_recipient';
		$defaults->class_name = 'View_CustomerRecipient';
		$defaults->renderLimit = 15;
		
		$defaults->renderSortBy = SearchFields_CustomerRecipient::ID;
		$defaults->renderSortAsc = true;

		$view = Feg_AbstractViewLoader::getView($defaults->id, $defaults);
		$view->renderSortBy = SearchFields_CustomerRecipient::ID;
		$view->renderSortAsc = true;
		$view->params = array(
			SearchFields_CustomerRecipient::ACCOUNT_ID => new DevblocksSearchCriteria(SearchFields_CustomerRecipient::ACCOUNT_ID,'=',$customer_id),
		);
		$view->renderPage = 0;
		$view->view_columns = array(
			//SearchFields_CustomerRecipient::ID,
			//SearchFields_CustomerRecipient::ACCOUNT_ID,
			SearchFields_CustomerRecipient::IS_DISABLED,
			SearchFields_CustomerRecipient::TYPE,
			SearchFields_CustomerRecipient::ADDRESS,
			SearchFields_CustomerRecipient::ADDRESS_TO,
			SearchFields_CustomerRecipient::SUBJECT,
			SearchFields_CustomerRecipient::EXPORT_TYPE,
		);
		Feg_AbstractViewLoader::setView($view->id,$view);
		
		$tpl->assign('view', $view);
		$tpl->display('file:' . $this->_TPL_PATH . 'customer/tabs/recipient/index.tpl');
	}
	
	function showRecipientPeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$customer_id = DevblocksPlatform::importGPC($_REQUEST['customer_id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		$display_view = 0;
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		$tpl->assign('id', $id);
		$tpl->assign('customer_id', $customer_id);
		$tpl->assign('view_id', $view_id);

		$customer_recipient = DAO_CustomerRecipient::get($id);
		$tpl->assign('customer_recipient', $customer_recipient);
		
		// Custom Fields
		$custom_fields = DAO_CustomField::getBySource(FegCustomFieldSource_CustomerRecipient::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		$custom_field_values = DAO_CustomFieldValue::getValuesBySourceIds(FegCustomFieldSource_CustomerRecipient::ID, $id);
		if(isset($custom_field_values[$id]))
			$tpl->assign('custom_field_values', $custom_field_values[$id]);

		// Below is the Audit log view only avaible is the audit log plugin is enabled. 
		if (class_exists('View_MessageAuditLog',true)):
			$display_view = 1;
			$defaults = new Feg_AbstractViewModel();
			$defaults->class_name = 'View_MessageAuditLog';
			$defaults->id = '_recipient_audit_log';
			$defaults->renderLimit = 10;
			$defaults->renderSortBy = SearchFields_MessageAuditLog::CHANGE_DATE;
			$defaults->renderSortAsc = false;
			$defaults->params = array();

			$view = Feg_AbstractViewLoader::getView($defaults->id, $defaults);

			$view->name = 'Recipient Audit Log';
			$view->renderTemplate = 'peek_tab';
			$view->params = array(
				SearchFields_MessageAuditLog::RECIPIENT_ID => new DevblocksSearchCriteria(SearchFields_MessageAuditLog::RECIPIENT_ID,DevblocksSearchCriteria::OPER_EQ,$id)
			);
			$view->renderPage = 0;
			$view->renderLimit = 10;
			$view->renderSortBy = SearchFields_MessageAuditLog::CHANGE_DATE;
			$view->renderSortAsc = false;
			$view->view_columns = array(
				SearchFields_MessageAuditLog::CHANGE_DATE,
				//SearchFields_MessageAuditLog::ACCOUNT_ID,
				//SearchFields_MessageAuditLog::RECIPIENT_ID,
				SearchFields_MessageAuditLog::MESSAGE_ID,
				SearchFields_MessageAuditLog::WORKER_ID,
				SearchFields_MessageAuditLog::CHANGE_FIELD,
				SearchFields_MessageAuditLog::CHANGE_VALUE,
			);

			Feg_AbstractViewLoader::setView($view->id,$view);
			$tpl->assign('view', $view);
		endif;
		$tpl->assign('display_view', $display_view);

		$tpl->display('file:' . $this->_TPL_PATH . 'customer/tabs/recipient/peek.tpl');		
	}
	
	function saveRecipientPeekAction() {
		$translate = DevblocksPlatform::getTranslationService();
		
		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer');
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		@$delete = DevblocksPlatform::importGPC($_POST['do_delete'],'integer',0);

		@$disabled = DevblocksPlatform::importGPC($_POST['recipient_is_disabled'],'integer',0);
		@$recipient_type = DevblocksPlatform::importGPC($_POST['recipient_type'],'integer',0);
		@$recipient_account_id = DevblocksPlatform::importGPC($_POST['recipient_account_id'],'integer',0);
		@$recipient_address_to = DevblocksPlatform::importGPC($_POST['recipient_address_to'],'string',"");
		@$recipient_subject = DevblocksPlatform::importGPC($_POST['recipient_subject'],'string',"");
		@$recipient_export_type = DevblocksPlatform::importGPC($_POST['recipient_export_type'],'integer',0);

		if ($recipient_type == 255) {
			@$recipient_address = DevblocksPlatform::importGPC($_POST['recipient_slave_account_id'],'string',"");
		} else {
			@$recipient_address = DevblocksPlatform::importGPC($_POST['recipient_address'],'string',"");
		}
		
		$fields = array(
			DAO_CustomerRecipient::ACCOUNT_ID => $recipient_account_id,
			DAO_CustomerRecipient::EXPORT_TYPE => $recipient_export_type,
			DAO_CustomerRecipient::IS_DISABLED => $disabled,
			DAO_CustomerRecipient::TYPE => $recipient_type,
			DAO_CustomerRecipient::ADDRESS_TO => $recipient_address_to,
			DAO_CustomerRecipient::ADDRESS => $recipient_address,
			DAO_CustomerRecipient::SUBJECT => $recipient_subject,
		);
		
		if($id == 0) {
			// Create Customer Recipients 
			$id = $status = DAO_CustomerRecipient::create($fields);
		} else {
			// Update Customer Recipients 
			$status = DAO_CustomerRecipient::update($id, $fields);
		}
		
		if(!empty($view_id)) {
			$view = Feg_AbstractViewLoader::getView($view_id);
			$view->render();
		}
		
		//DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('setup','workers')));		
	}
	
	function setMessageRecipientStatusAction() {
		$translate = DevblocksPlatform::getTranslationService();

		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$status = DevblocksPlatform::importGPC($_REQUEST['status'],'integer',0);
		@$goto_recent = DevblocksPlatform::importGPC($_REQUEST['goto_recent'],'integer',0);
		
		$objects = DAO_MessageRecipient::get($id);
		
		$fields = get_object_vars($objects);
		$fields[DAO_MessageRecipient::SEND_STATUS] = $status;
		$status = DAO_MessageRecipient::update($id, $fields);
		// Give plugins a chance to note a message is imported.
		$eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'message.recipient.status',
                array(
                    'message_recipient_id' => $id,
					'recipient_id' => $fields['recipient_id'],
                    'message_id' => $fields['message_id'],
                    'account_id' => $fields['account_id'],
					'send_status' => $fields['send_status'],
                )
            )
	    );
		$status_text = $translate->_('feg.message_recipient.status_'.$fields['send_status']);
		if ($status_text == "") $status_text = $translate->_('feg.message_recipient.status_unknown');
		echo $status_text;
	}
	
	function showRecipientTypeAction() {
		@$type = DevblocksPlatform::importGPC($_REQUEST['type'],'integer',0);
		@$selected_type = DevblocksPlatform::importGPC($_REQUEST['selected_type'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		$tpl->assign('type', $type);
		$tpl->assign('selected_type', $selected_type);

		$export_type = DAO_ExportType::getAll();
		$tpl->assign('export_type', $export_type);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'customer/tabs/recipient/select_export_type.tpl');		
	}	
};

class FegCustomerTabRecentMessages extends Extension_CustomerTab {
	private $_TPL_PATH = '';

	function __construct($manifest) {
		$this->_TPL_PATH = dirname(dirname(dirname(__FILE__))) . '/templates/';
		$this->DevblocksExtension($manifest,1);
	}
 
	function showTab() {
		@$customer_id = DevblocksPlatform::importGPC($_REQUEST['customer_id'],'integer',0);
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		
//  FIXME setup ACL check.
//		$worker = FegApplication::getActiveWorker();
//		if(!$worker || !$worker->is_superuser) {
//			echo $translate->_('common.access_denied');
//			return;
//		}
		
		$tpl->assign('customer_id', $customer_id);
		
		$defaults = new Feg_AbstractViewModel();
		$defaults->name = 'Message Status List';
		$defaults->id = '_customer_view_messages';
		$defaults->class_name = 'View_MessageRecipient';
		$defaults->renderLimit = 15;
		
		$defaults->renderSortBy = SearchFields_MessageRecipient::ID;
		$defaults->renderSortAsc = false;

		$view = Feg_AbstractViewLoader::getView($defaults->id, $defaults);
		$view->name = 'Message Status List';
		$view->params = array(
			SearchFields_MessageRecipient::ACCOUNT_ID => new DevblocksSearchCriteria(SearchFields_MessageRecipient::ACCOUNT_ID,'=',$customer_id),
		);
		$view->renderPage = 0;
		$view->renderSortAsc = false;
		$view->view_columns = array(
			SearchFields_MessageRecipient::ID,
			SearchFields_MessageRecipient::SEND_STATUS,
			//SearchFields_MessageRecipient::ACCOUNT_ID,
			SearchFields_MessageRecipient::RECIPIENT_ID,
			SearchFields_MessageRecipient::MESSAGE_ID,
			//SearchFields_MessageRecipient::FAX_ID,
			SearchFields_MessageRecipient::UPDATED_DATE,
			SearchFields_MessageRecipient::CLOSED_DATE,
		);
		
		Feg_AbstractViewLoader::setView($view->id,$view);
		
		$tpl->assign('view', $view);
		$tpl->display('file:' . $this->_TPL_PATH . 'customer/tabs/recent/messages.tpl');
	}

	function saveTab() {
	}
	
	function showMessagePeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$customer_id = DevblocksPlatform::importGPC($_REQUEST['customer_id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		$display_view = 0;
	
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		$tpl->assign('id', $id);
		$tpl->assign('customer_id', $customer_id);
		$tpl->assign('view_id', $view_id);

		$message = DAO_Message::get($id);
		$tpl->assign('message', $message);

		$message_lines = explode('\n',substr($message->message,1,-1));
		$tpl->assign('message_lines', $message_lines);
		
		$account = DAO_CustomerAccount::get($message->account_id);
		$tpl->assign('account', $account);

		// Below is the Audit log view only avaible is the audit log plugin is enabled. 
		if (class_exists('View_MessageAuditLog',true)):
			$display_view = 1;
			$defaults = new Feg_AbstractViewModel();
			$defaults->class_name = 'View_MessageAuditLog';
			$defaults->id = '_message_recipient_audit_log';
			$defaults->renderLimit = 10;
			$defaults->renderSortBy = SearchFields_MessageAuditLog::CHANGE_DATE;
			$defaults->renderSortAsc = false;
			$defaults->params = array();

			$view = Feg_AbstractViewLoader::getView($defaults->id, $defaults);

			$view->name = 'Message Recipient Audit Log';
			$view->renderTemplate = 'peek_tab';
			$view->params = array(
				SearchFields_MessageAuditLog::MESSAGE_ID => new DevblocksSearchCriteria(SearchFields_MessageAuditLog::MESSAGE_ID, 
					DevblocksSearchCriteria::OPER_EQ, $id),
			);
			$view->renderPage = 0;
			$view->renderLimit = 10;
			$view->view_columns = array(
				SearchFields_MessageAuditLog::CHANGE_DATE,
				//SearchFields_MessageAuditLog::ACCOUNT_ID,
				//SearchFields_MessageAuditLog::RECIPIENT_ID,
				//SearchFields_MessageAuditLog::MESSAGE_ID,
				SearchFields_MessageAuditLog::MESSAGE_RECIPIENT_ID,
				SearchFields_MessageAuditLog::WORKER_ID,
				SearchFields_MessageAuditLog::CHANGE_FIELD,
				SearchFields_MessageAuditLog::CHANGE_VALUE,
			);

			Feg_AbstractViewLoader::setView($view->id,$view);
			$tpl->assign('view', $view);
		endif;
		$tpl->assign('display_view', $display_view);

		$tpl->display('file:' . $this->_TPL_PATH . 'customer/tabs/recent/message_peek.tpl');		
	}

	function showMessageRecipientPeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$customer_id = DevblocksPlatform::importGPC($_REQUEST['customer_id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		$display_view = 0;
	
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		$tpl->assign('id', $id);
		$tpl->assign('customer_id', $customer_id);
		$tpl->assign('view_id', $view_id);

		$message_recipient = DAO_MessageRecipient::get($id);
		$tpl->assign('message_recipient', $message_recipient);
		
		$message = DAO_Message::get($message_recipient->message_id);
		$tpl->assign('message', $message);

		$message_lines = explode('\n',substr($message->message,1,-1));
		$tpl->assign('message_lines', $message_lines);
		
		$recipient = DAO_CustomerRecipient::get($message_recipient->recipient_id);
		$tpl->assign('recipient', $recipient);
		
		$account = DAO_CustomerAccount::get($message_recipient->account_id);
		$tpl->assign('account', $account);

		// Below is the Audit log view only avaible is the audit log plugin is enabled. 
		if (class_exists('View_MessageAuditLog',true)):
			$display_view = 1;
			$defaults = new Feg_AbstractViewModel();
			$defaults->class_name = 'View_MessageAuditLog';
			$defaults->id = '_message_recipient_audit_log';
			$defaults->renderLimit = 10;
			$defaults->renderSortBy = SearchFields_MessageAuditLog::CHANGE_DATE;
			$defaults->renderSortAsc = false;
			$defaults->params = array();

			$view = Feg_AbstractViewLoader::getView($defaults->id, $defaults);

			$view->name = 'Message Recipient Audit Log';
			$view->renderTemplate = 'peek_tab';
			$view->params = array(
				SearchFields_MessageAuditLog::MESSAGE_RECIPIENT_ID => new DevblocksSearchCriteria(SearchFields_MessageAuditLog::MESSAGE_RECIPIENT_ID, 
					DevblocksSearchCriteria::OPER_EQ, $id),
			);
			$view->renderPage = 0;
			$view->renderLimit = 10;
			$view->view_columns = array(
				SearchFields_MessageAuditLog::CHANGE_DATE,
				//SearchFields_MessageAuditLog::ACCOUNT_ID,
				//SearchFields_MessageAuditLog::RECIPIENT_ID,
				//SearchFields_MessageAuditLog::MESSAGE_ID,
				SearchFields_MessageAuditLog::WORKER_ID,
				SearchFields_MessageAuditLog::CHANGE_FIELD,
				SearchFields_MessageAuditLog::CHANGE_VALUE,
			);

			Feg_AbstractViewLoader::setView($view->id,$view);
			$tpl->assign('view', $view);
		endif;
		$tpl->assign('display_view', $display_view);

		$tpl->display('file:' . $this->_TPL_PATH . 'customer/tabs/recent/peek.tpl');		
	}

};

class FegCustomerTabMessages extends Extension_CustomerTab {
	private $_TPL_PATH = '';

	function __construct($manifest) {
		$this->_TPL_PATH = dirname(dirname(dirname(__FILE__))) . '/templates/';
		$this->DevblocksExtension($manifest,1);
	}
 
	function showTab() {
		@$customer_id = DevblocksPlatform::importGPC($_REQUEST['customer_id'],'integer',0);
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		
//  FIXME setup ACL check.
//		$worker = FegApplication::getActiveWorker();
//		if(!$worker || !$worker->is_superuser) {
//			echo $translate->_('common.access_denied');
//			return;
//		}
		
		$tpl->assign('customer_id', $customer_id);
		
		$defaults = new Feg_AbstractViewModel();
		$defaults->name = 'Message List';
		$defaults->id = '_view_customer_messages';
		$defaults->class_name = 'View_Message';
		$defaults->renderLimit = 15;
		
		$defaults->renderSortBy = SearchFields_Message::ID;
		$defaults->renderSortAsc = 0;

		$view = Feg_AbstractViewLoader::getView($defaults->id, $defaults);
		$view->name = 'Message List';
		$view->params = array(
			SearchFields_Message::ACCOUNT_ID => new DevblocksSearchCriteria(SearchFields_Message::ACCOUNT_ID,'=',$customer_id),
		);
		$view->renderPage = 0;
		$view->view_columns = array(
			SearchFields_Message::ID,
			//SearchFields_Message::ACCOUNT_ID,
			SearchFields_Message::IMPORT_STATUS,
			SearchFields_Message::CREATED_DATE,
			SearchFields_Message::UPDATED_DATE,
			// SearchFields_Message::PARAMS_JSON,
			// SearchFields_Message::PARAMS,
			//SearchFields_Message::MESSAGE,
		);

		Feg_AbstractViewLoader::setView($view->id,$view);
		
		$tpl->assign('view', $view);
		$tpl->display('file:' . $this->_TPL_PATH . 'customer/tabs/messages/messages.tpl');
	}

	function saveTab() {
	}
	
	function setMessageStatusAction() {
		$translate = DevblocksPlatform::getTranslationService();
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$status = DevblocksPlatform::importGPC($_REQUEST['status'],'integer',0);
		@$goto_recent = DevblocksPlatform::importGPC($_REQUEST['goto_recent'],'integer',0);
		
		$message_obj = DAO_Message::get($id);
		
		$fields = get_object_vars($message_obj);
		$fields[DAO_Message::IMPORT_STATUS] = $status;
		
		$mr_status = DAO_Message::update($id, $fields);
		// Give plugins a chance to note a message is imported.
		$eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'message.status',
                array(
                    'message_id' => $id,
                    'account_id' => $message_obj->account_id,
					'import_status' => $status,
                )
            )
	    );
		$status_text = $translate->_('feg.message.import_status_'.$status);
		if ($status_text == "") $status_text = $translate->_('feg.message_recipient.status_unknown');
		echo $status_text;
	}
};

