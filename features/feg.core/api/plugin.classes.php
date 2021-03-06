<?php
/***********************************************************************
| Feg(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2009, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| By using this software, you acknowledge having read the license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://feg.answernet.com	  http://www.webgroupmedia.com/
***********************************************************************/
// Custom Field Sources

class FegPageController extends DevblocksControllerExtension {
    const ID = 'core.controller.page';
    
	function __construct($manifest) {
		parent::__construct($manifest);
	}

	/**
	 * Enter description here...
	 *
	 * @param string $uri
	 * @return string $id
	 */
	public function _getPageIdByUri($uri) {
        $pages = DevblocksPlatform::getExtensions('feg.page', false);
        foreach($pages as $manifest) { /* @var $manifest DevblocksExtensionManifest */
            if(0 == strcasecmp($uri,$manifest->params['uri'])) {
                return $manifest->id;
            }
        }
        return NULL;
	}
	
	// [TODO] We probably need a FegApplication scope for getting content that has ACL applied
	private function _getAllowedPages() {
		$active_worker = FegApplication::getActiveWorker();
		$page_manifests = DevblocksPlatform::getExtensions('feg.page', false);

		// [TODO] This may cause problems on other pages where an active worker isn't required
		// Check RSS/etc (was bugged on login)
		
		// Check worker level ACL (if set by manifest)
		foreach($page_manifests as $idx => $page_manifest) {
			// If ACL policy defined
			if(isset($page_manifest->params['acl'])) {
				if($active_worker && !$active_worker->hasPriv($page_manifest->params['acl'])) {
					unset($page_manifests[$idx]);
				}
			}
		}
		
		return $page_manifests;
	}
	
	public function handleRequest(DevblocksHttpRequest $request) {
	    $path = $request->path;
		$controller = array_shift($path);

		// [TODO] _getAllowedPages() should take over, but it currently blocks hidden stubs
        $page_id = $this->_getPageIdByUri($controller);
		$page = DevblocksPlatform::getExtension($page_id, true); /* @var $page FegPageExtension */
		
        if(empty($page)) {
	        switch($controller) {
//	        	case "portal":
//				    header("Status: 404");
//	        		die(); // 404
//	        		break;
	        		
	        	default:
	        		return; // default page
	        		break;
	        }
        }

	    @$action = DevblocksPlatform::strAlphaNumDash(array_shift($path)) . 'Action';

	    switch($action) {
	        case NULL:
	            // [TODO] Index/page render
	            break;
	            
	        default:
			    // Default action, call arg as a method suffixed with Action
			    
			    if($page->isVisible()) {
					if(method_exists($page,$action)) {
						call_user_func(array(&$page, $action)); // [TODO] Pass HttpRequest as arg?
					}
				} else {
					// if Ajax [TODO] percolate isAjax from platform to handleRequest
					// die("Access denied.  Session expired?");
				}

	            break;
	    }
	}
	
	public function writeResponse(DevblocksHttpResponse $response) {
	    $path = $response->path;
		// [JAS]: Ajax? // [TODO] Explore outputting whitespace here for Safari
//	    if(empty($path))
//			return;

		$tpl = DevblocksPlatform::getTemplateService();
		$session = DevblocksPlatform::getSessionService();
		$settings = DevblocksPlatform::getPluginSettingsService();
		$translate = DevblocksPlatform::getTranslationService();
	    $active_worker = FegApplication::getActiveWorker();
		
		$visit = $session->getVisit();
		$page_manifests = $this->_getAllowedPages();

		$controller = array_shift($path);

		// Default page [TODO] This is supposed to come from framework.config.php
		if(empty($controller)) 
			$controller = 'preferences';

	    // [JAS]: Require us to always be logged in for Feg pages
		if(empty($visit) && 0 != strcasecmp($controller,'login')) {
			$query = array();
			if(!empty($response->path))
				$query = array('url'=> urlencode(implode('/',$response->path)));
			DevblocksPlatform::redirect(new DevblocksHttpRequest(array('login'),$query));
		}

	    $page_id = $this->_getPageIdByUri($controller);
		@$page = DevblocksPlatform::getExtension($page_id, true); /* @var $page FegPageExtension */
        
        if(empty($page)) {
   		    header("Status: 404");
        	return; // [TODO] 404
		}
	    
		// [JAS]: Listeners (Step-by-step guided tour, etc.)
	    $listenerManifests = DevblocksPlatform::getExtensions('devblocks.listener.http');
	    foreach($listenerManifests as $listenerManifest) { /* @var $listenerManifest DevblocksExtensionManifest */
	         $inst = $listenerManifest->createInstance(); /* @var $inst DevblocksHttpRequestListenerExtension */
	         $inst->run($response, $tpl);
	    }

	    $tpl->assign('active_worker', $active_worker);
        $tour_enabled = false;
		
		if(!empty($visit) && !is_null($active_worker)) {
			$tour_enabled = intval(DAO_WorkerPref::get($active_worker->id, 'assist_mode', 1));

			$keyboard_shortcuts = intval(DAO_WorkerPref::get($active_worker->id,'keyboard_shortcuts',1));
			$tpl->assign('pref_keyboard_shortcuts', $keyboard_shortcuts);			
			
//	    	$active_worker_memberships = $active_worker->getMemberships();
//	    	$tpl->assign('active_worker_memberships', $active_worker_memberships);
			
			$unread_notifications = DAO_WorkerEvent::getUnreadCountByWorker($active_worker->id);
			$tpl->assign('active_worker_notify_count', $unread_notifications);
			
			DAO_Worker::logActivity($active_worker->id, $page->getActivity());
		}
		$tpl->assign('tour_enabled', $tour_enabled);
		
        // [JAS]: Variables provided to all page templates
		$tpl->assign('settings', $settings);
		$tpl->assign('session', $_SESSION);
		$tpl->assign('translate', $translate);
		$tpl->assign('visit', $visit);
		$tpl->assign('license',FegLicense::getInstance());
		
		$tpl->assign('page_manifests',$page_manifests);		
		$tpl->assign('page',$page);

		$tpl->assign('response_uri', implode('/', $response->path));
		
		$core_tpl = APP_PATH . '/features/feg.core/templates/';
		$tpl->assign('core_tpl', $core_tpl);
		
		// Prebody Renderers
		$preBodyRenderers = DevblocksPlatform::getExtensions('feg.renderer.prebody', true);
		if(!empty($preBodyRenderers))
			$tpl->assign('prebody_renderers', $preBodyRenderers);

		// Postbody Renderers
		$postBodyRenderers = DevblocksPlatform::getExtensions('feg.renderer.postbody', true);
		if(!empty($postBodyRenderers))
			$tpl->assign('postbody_renderers', $postBodyRenderers);
		
		// Timings
		$tpl->assign('render_time', (microtime(true) - DevblocksPlatform::getStartTime()));
		if(function_exists('memory_get_usage') && function_exists('memory_get_peak_usage')) {
			$tpl->assign('render_memory', memory_get_usage() - DevblocksPlatform::getStartMemory());
			$tpl->assign('render_peak_memory', memory_get_peak_usage() - DevblocksPlatform::getStartPeakMemory());
		}
		
		$tpl->display($core_tpl.'border.tpl');
		
//		$cache = DevblocksPlatform::getCacheService();
//		$cache->printStatistics();
	}
};