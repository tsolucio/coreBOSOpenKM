<?php
/*
 * vtOpenKM - vtigerCRM OpenKM Integration
 * Copyright 2012 JPL TSolucio, S.L.  --  This file is a part of vtOpenKM Integration.
 * You can copy, adapt and distribute the work under the "Attribution-NonCommercial-ShareAlike"
 * Vizsage Public License (the "License"). You may not use this file except in compliance with the
 * License. Roughly speaking, non-commercial users may share and modify this code, but must give credit
 * and share improvements. However, for proper details please read the full License, available at
 * http://vizsage.com/license/Vizsage-License-BY-NC-SA.html and the handy reference for understanding
 * the full license at http://vizsage.com/license/Vizsage-Deed-BY-NC-SA.html. Unless required by
 * applicable law or agreed to in writing, any software distributed under the License is distributed
 * on an  "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and limitations under the
 * License terms of Creative Commons Attribution-NonCommercial-ShareAlike 3.0 (the License).
*/

/*  Modified by : Mohamed Said Lokhat - mslokhat@gmail.com
	Adaptation with LogicalDoc 
	
*/

	define("DEFAULT_WORKSPACE", 4);

	function format_exception($e) {
		if (isset($e->detail)) {
			$reflectionObject = new ReflectionObject($e->detail);
			$properties = $reflectionObject->getProperties();
			$exceptionName = $properties[0]->name;
		} else {
			$exceptionName = "Exception";
		}
		return $exceptionName.": ".$e->faultstring;
	}

	class FormElementComplex {
		var $objClass = "";
		var $name = "";
		var $value = "";
		var $height = null;
		var $width = null;
		var $label = null;
		var $options = null;
		var $readonly = false;
		var $validators = null;
		var $transition = null;
		var $type = null;
	}

	class FormElementComplexArray {
		var $item;
	}

	class FolderKM{
		var $created = null;
		var $hasChilds= null;
		var $path= '/okm:root/test';
		var $author = null;
		var $permissions = null;
		var $uuid = null;
		var $subscribed = null;
		var $subscriptors = null;
		var $keywords = null;
		var $categories = null;
		var $notes = null;

		function FolderKM($path=null) {
			 $this->path = $path;
		}
	}

require_once('data/CRMEntity.php');
require_once('data/Tracker.php');

class OpenKM extends CRMEntity {
	var $db, $log; // Used in class functions of CRMEntity

	/** Indicator if this is a custom module or standard module */
	var $IsCustomModule = true;

	var $url = "http://localhost:8080/OpenKM";
	var $user = "admin";
	var $password = "admin";
	var $main_path = "/okm:root/";
	var $folder;
	var $document;
	var $soap_auth;
	var $soap_folder;
	var $soap_document;
	var $soap_security;
	var $soap_propertygroup;
	var $soap_repository;
	var $token;
	var $running;
	var $cat_path = "/okm:categories";

	//constructor
		function __construct() {
			global $adb, $current_user;
			
			$results = $adb->query('select * from vtiger_openkm_config');
			$res = $adb->query('select okmpassword from vtiger_users where id=' . $current_user->id);
			$pass = $adb->query_result($res, 0, 'okmpassword');
			$this->user = $current_user->column_fields['user_name']; //$adb->query_result($results, 0, 'openkm_user');
			$this->password = $pass; //$adb->query_result($results, 0, 'openkm_password');
			$this->main_path = $adb->query_result($results, 0, 'openkm_main_path');
			$this->url = $adb->query_result($results, 0, 'openkm_url');

			$options = array(
				'exceptions'=>1
			);
			try{
				$this->soap_auth     = @new SoapClient($this->url."/services/Auth?wsdl",$options);
				$this->soap_folder   = @new SoapClient($this->url."/services/Folder?wsdl",$options);
				$this->soap_document = @new SoapClient($this->url."/services/Document?wsdl",$options);
				$this->soap_security = @new SoapClient($this->url.'/services/Security?wsdl',$options );
				//$this->soap_propertygroup = @new SoapClient($this->url."/services/OKMPropertyGroup?wsdl",$options);
				//$this->soap_repository = @new SoapClient($this->url."/services/OKMRepository?wsdl",$options);


				$this->running = true;
			}catch(SoapFault $e){
				$this->running = false;
				echo format_exception($e);
			}
		}

	function isRunning(){
		return($this->running);
	}

	function SetURL($url) {
		$this->url = $url;
	}

	function SetUser($user, $password) {
		$this->user = $user;
		$this->password = $password;
	}

	function Authenticate() {
		try{
			$obj = $this->soap_auth->login(array('username' => $this->user, 'password' => $this->password));
			$this->token = $obj->return;
			//print("Token: ".$token."<br/>\n");
			return($this->token);
		} catch (Exception $e) {
			echo "Authenticating: ".format_exception($e);
		}
	}
	
	function RootAuthenticate() {
		global $adb;
			
		$results = $adb->query('select * from vtiger_openkm_config');

		$user = $adb->query_result($results, 0, 'openkm_user');
		$password = $adb->query_result($results, 0, 'openkm_password');
		$this->SetUser($user, $password);
		$this->Authenticate() ;
	}

	/* Creates a folder
	 *-based on the given path
	 *-if the given path is null, path will be based on its folder attribute
	*/
	/* OpemKM code ==> LogicalDoc TODO*/
	function CreateFolder($path=null) {
		try{
			if(!$this->folder){
				$this->CreateSimpleFolder($path);
			}else{
				if($path)
					$this->folder->path = $this->main_path.str_replace('+','%20',urlencode($path));

				// create folder
				$fld = array('created' => $this->folder->created,
							'hasChilds' => $this->folder->hasChilds,
							'path' => $this->folder->path,
							'author' => $this->user,
							'permissions' => $this->folder->permissions,
							'uuid' => $this->folder->uuid,
							'subscribed' => $this->folder->subscribed,
							'subscriptors' => $this->folder->subscriptors,
							'keywords' => $this->folder->keywords,
							'categories' => $this->folder->categories,
							'notes' => $this->folder->notes);
				$obj = $this->soap_folder->create(array('token' => $this->token, 'fld' => $fld));
				//var_dump($obj);exit;
				return $obj->return;
				//print ("Folder created with path: ".$fld->path."<br/>\n");
				//print ("Folder created with uuid: ".$fld->uuid."<br/>\n");
			}
		} catch (Exception $e) {
			echo format_exception($e);
		}
	}

	/* Retrieves the uuid for the folder $path */
	function getUUID($path) {
		try{
			$uuid = $this->soap_folder->findByPath(array('sid' => $this->token, 'path' => $path));
			return $uuid->folder->id;
		} catch (Exception $e) {
			echo format_exception($e);
		}
	}

	/* Retrieves the path for the folder $uuid */
	function getPath($uuid) {
		try{
			$folder = $this->soap_folder->getFolder(array('sid' => $this->token, 'folderId' => $uuid));
			$path = $this->createfullpath($folder);
			return $path;
		} catch (Exception $e) {
			echo format_exception($e);
		}
	}

	/* Retrieves the OKM path for an instance id, now works with accounts, could be usefull further */
	function getPath4Instance($id) {
		global $adb;
		try{
			$moduleName = getSalesEntityType($id);
			if($moduleName) {
				require_once("modules/$moduleName/$moduleName.php");
				$focus = new $moduleName();
				$focus->id = $id;
				$focus->retrieve_entity_info($id, $moduleName);
				$uuidrs = $adb->pquery("select folder_id from {$focus->table_name} where {$focus->table_index} = ?",array($id));
				$uuid = $adb->query_result($uuidrs,0,0);
				$path = $this->getPath($uuid);
				return $path;
			}else
				return false;
		} catch (Exception $e) {
			echo format_exception($e);
		}
	}

	/* Validates path
	 * Shows an error alert when an unvalid and if exists path is given and returns false
	 * true otherwise
	*/
	function CheckPath($path) {
		try{
			$is_valid = $this->soap_folder->isValid(array('token' => $this->token, 'fldPath' => $path));
			//optional message
			if(!$path)
			{
				$alert = getTranslatedString('LBL_EMPTY_PATH');
				?><script language="javascript">
				alert( " <?php echo $alert; ?>" );
				history.back(-1);
				</script>
				<?php
				return false;
			}
			if(!$is_valid)
			{
				$alert = getTranslatedString('LBL_INVALID_PATH');
				?><script language="javascript">
				alert( " <?php echo "$alert: $path"; ?>" );
				history.back(-1);
				</script>
				<?php
				return false;
			}else
				return true;
		} catch (Exception $e) {
			echo format_exception($e);
		}
	}

	/* Creates a folder by the most simple way
	 * Doesn't work!
	 * returns an object wich contains its path and its identifier
	 * false when the given path is not valid
	 */
	private function CreateSimpleFolder($path) {
		try{
			//echo "creo carpeta simple en : $path";exit;
			$obj = $this->soap_folder->createPath(array('sid' => $this->token, 'parentId'=> DEFAULT_WORKSPACE, 'path' => $this->folder->path));
			$fld = $obj->folder;
			//$fld->path
			//$fld->uuid
			return $fld;
		} catch (Exception $e) {
			echo format_exception($e);
		}
	}

	/* Renames a openkm folder
	 * returns false when the given path is not valid
	*/
	function RenameFolder($path, $new_name) {
		try{
			if(!$this->CheckPath($path))
			{
				return false;
			}else{
				echo "<br>rename<br>";
				$this->soap_folder->rename(array('token' => $this->token,'fldPath' => $path,'newName' => $new_name));//($this->token, $path, $new_path);
				//(array('token' => $this->token, 'fldPath' => $this->folder->path))
				echo "error?";
				return true;
			}
		}catch (Exception $e){
			echo format_exception($e);
		}
	}
	
	
	function createfullpath($folder) {
		$path = $this->soap_folder->getPath(array('sid' => $this->token, 'folderId' => $folder->folder->id));
		$i = 0;
		foreach ($path->folders as $fld) {
			$rpath .= $fld->name . ($i++ == 0 ? '' : '/');
		}
		return $rpath;
	}
	
	/*
		Get a Folder and all subfolders in an array
	*/
	function getFolders($id, $parent = 0, &$arr) {	
	
		if ($parent == 0) {
			$id = DEFAULT_WORKSPACE;
		}
		$folder = $this->soap_folder->getFolder(array('sid' => $this->token, 'folderId' => $id));
		$fldpath = $this->createfullpath($folder);
		$arr[] = array('uuid'=> $id, 'name'=> $fldpath, 'parent'=> $parent);
			
		
		// List folders
		$getChildrenResp = $this->soap_folder->listChildren(array('sid' => $this->token, 'folderId' => $folder->folder->id));
		
		if (is_array($getChildrenResp->folder)) {
		  foreach ($getChildrenResp->folder as $subfolder) {
			$this->getFolders($subfolder->id, $id, $arr);
		  }
		} else {			
			$flarr = (array)$getChildrenResp;
			if (! empty($flarr) )
				$this->getFolders($getChildrenResp->folder->id, $id, $arr);
		}
		return $arr;		
	}
	
	/* 
		Get All Categories
	*/
	function getCategories($path, $parent = 0, &$arr) {
		$uuid = $this->getUUID($path);
		if ($parent == 0) {
			$parent = $this->getUUID($this->cat_path);
			$path = $this->cat_path.'/';
		}
		$arr[] = array('uuid'=> $uuid, 'name'=> str_replace($this->cat_path,'',$path), 'parent'=> $parent);
		// List folders
		try {
			$getChildrenResp = $this->soap_folder->getChildren(array('token' => $this->token, 'fldPath' => $path));
			$folderArray = $getChildrenResp->return;
			if (is_array($folderArray)) {
			  foreach ($folderArray as $subfolder) {
				  $this->getCategories($subfolder->path, $uuid, $arr);		
			  }
			} 
		}catch (Exception $e){
			echo format_exception($e);
		}
		return $arr;		
		
	}
	
	
	
	
	// Documents Functions -------------------------------------------------

	function CreateDocument($file, $filename, $fldid) {
		try{
			// create document
			// faltaria crear clase document, no implemento por no ser necesario de momento
			
			$doc ['language'] = 'en';
			$doc ['fileName'] =  $filename;
			$doc ['folderId'] = $fldid; // create the document in DEFAULT_WORKSPACE

			// Requested parameters 
			// (although they are not evaluated during document creation)
			$doc ['creatorId'] = 0;
			$doc ['dateCategory'] = 0;
			$doc ['docType'] = 0;
			$doc ['exportStatus'] = 0;
			$doc ['fileSize'] = 0;
			$doc ['id'] = 0;
			$doc ['immutable'] = 0;
			$doc ['indexed'] = 0;
			$doc ['lengthCategory'] = 0;
			$doc ['publisherId'] = 0;
			$doc ['signed'] = 0;
			$doc ['size'] = 0;
			$doc ['status'] = 0; // Status = 0: document unlocked
			$doc ['published'] = 1;	
			$doc ['nature'] = 0;
			$doc ['pages'] = -1; // -1 = default
			$doc ['stamped'] = 0; // 0 = default (not stamped)  
			$fh = fopen ( $file, 'r' );
			$theData = fread ( $fh, filesize ( $file ) );
			fclose ( $fh );
			$obj = $this->soap_document->create(array('sid' => $this->token, 'document' => $doc, 'content' => $theData));
			//$doc = $obj->return;
			//print ("[DOCUMENT] Path: ".$doc->path.", Author: ".$doc->author.", Size: ".$doc->actualVersion->size.", UUID".$doc->uuid."<br/>\n");
			return $obj->document->id;
		} catch (Exception $e) {
			echo format_exception($e);
			return "";
		}
	}

	function CreateSimpleDocument($file, $docPath) {
		try{
			// create document simple
			$fh = fopen ( $file, 'r' );
			$theData = fread ( $fh, filesize ( $file ) );
			fclose ( $fh );
			$obj = $this->soap_document->create(array('token' => $this->token, 'docPath' => $docPath, 'content' => $theData));
			$doc = $obj->return;
			//print ("[DOCUMENT] Path: ".$doc->path.", Author: ".$doc->author.", Size: ".$doc->actualVersion->size.", UUID".$doc->uuid."<br/>\n");
			//AddPropertyGroup($docPath);
			return $doc->uuid;
		} catch (Exception $e) {
			echo format_exception($e);
			return "";
		}
	}

	// add property group ( metadata )
	function AddPropertyGroup($docPath) {
		try{
			// add property group ( metadata )
			$this->soap_propertygroup->addGroup(array('token' => $this->token, 'nodePath' => $docPath, 'grpName'=>'okg:technology'));
			// Add property group values ( metadata values )
			//$properties = array(array('key'=>'okp:technology.comment','value' => 'Other comment from PHP'));
			//$this->propertygroup->setPropertiesSimple(array('token' => $token, 'nodePath' => $docPath, 'grpName'=>'okg:technology', 'properties' => $properties));
		} catch (Exception $e) {
			echo format_exception($e);
		}
	}
	
	function getContentsByUUID($uuid) {
		try{
			$content = $this->soap_document->getContent(array('sid' => $this->token, 'docId' => $uuid));
			return $content->return;
		} catch (Exception $e) {
			echo format_exception($e);
			return "";
		}
	}
	
	/* Retrieves the path for the document $uuid */
	function getDocumentPath($uuid) {
		try{
			$doc = $this->soap_document->getDocument(array('sid' => $this->token, 'docId' => $uuid));
			$folder = $this->soap_folder->getFolder(array('sid' => $this->token, 'folderId' => $doc->document->folderId));
			$path = $this->createfullpath($folder);
			return $path;
		} catch (Exception $e) {
			//echo format_exception($e);
			return "undefined";
		}
	}

	
	// Others functions ---------------------------------------------------------------------------------
	
	//create User 
	// TODO : check groupsIds
	
	function createUser($name, $firstname, $lastname, $email, $password) {
		$user['userName'] = $name;
		$user['firstName'] = $firstname;
		$user['name'] = $lastname;
		$user['password'] = $password;
		$user['groupIds'] = array(2,-10000);;
		$user['enabled'] = 1;
		$user['id'] = 0;
		$user['passwordExpires'] = 0;
		$user['quota'] = -1;
		$user['quotaCount'] = 0;
		$user['source'] = 0;
		$user['type'] = 0;
		$user['language'] = 'en';
		$user['email'] = $email;
		$user['lastModified'] = date('Y-m-d');
		$obj = (object) $user;
		try {
			$result = $this->soap_security->storeUser(array('sid' => $this->token, 'user'=> $obj));
		} catch (Exception $e) {
			echo format_exception($e);				
		}
	}
	
	function changePassword($user, $old, $new) {
		try {
			$users = $this->soap_security->listUsers(array('sid' => $this->token));
			$userid= -1;
			foreach ($users->users as $usr) {
				if ($usr->userName == $user)
					$userid = $usr->id;
			}
			$result = $this->soap_security->changePassword(array('sid' => $this->token, 'userId'=> $userid, 'oldPassword'=> $old,'newPassword'=>$new));
		} catch (Exception $e) {
			echo format_exception($e);				
		}
		
	}

	// Logout
	function Logout() {
		try{
			$this->soap_auth->logout($this->token);
		} catch (Exception $e) {
			echo format_exception($e);
		}
	}

	/**
	 * Invoked when special actions are performed on the module.
	 * @param String Module name
	 * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	function vtlib_handler($modulename, $event_type) {
		if($event_type == 'module.postinstall') {
			// TODO Handle post installation actions
			require_once 'include/utils/utils.php';
			require_once 'modules/com_vtiger_workflow/include.inc';
			require_once 'modules/com_vtiger_workflow/tasks/VTEntityMethodTask.inc';
			require_once 'modules/com_vtiger_workflow/VTEntityMethodManager.inc';
			global $adb;
			// Account workflow on creation/modification
			// Creating Default workflows

			$workflowManager = new VTWorkflowManager($adb);
			$taskManager = new VTTaskManager($adb);

			$accountWorkFlow = $workflowManager->newWorkFlow("Accounts");
			$accountWorkFlow->description = "Workflow for Folder Creation when creating Accounts";
			$accountWorkFlow->executionCondition = VTWorkflowManager::$ON_EVERY_SAVE;
			$accountWorkFlow->defaultworkflow = 1;
			$workflowManager->save($accountWorkFlow);

			// Register Entity Methods
			$emm = new VTEntityMethodManager($adb);
			//$workflowManager->addEntityMethod($moduleName, $methodName, $functionPath, $functionName){
			$emm->addEntityMethod("Accounts", "linkAccount2openKM", "modules/OpenKM/WorkflowFunctions.php", "LinkFolder");

			$task = $taskManager->createTask('VTEntityMethodTask', $accountWorkFlow->id);
			$task->active = true;
			$task->summary = 'Creates/edits an OpenKM folder for the Account';
			$task->methodName = "linkAccount2openKM";
			$taskManager->saveTask($task);

			//Adding new field folder_id to ACCOUNTS
			include_once('vtlib/Vtiger/Module.php');

			$module = Vtiger_Module::getInstance('Accounts');

			if($module) {
				$blockInstance = VTiger_Block::getInstance('LBL_ACCOUNT_INFORMATION',$module);

				if($blockInstance) {
					$field = new Vtiger_Field();
					$field->name = 'folder_id';
					$field->label= 'UUID OpenKM';
					$field->table = $module->basetable;
					$field->column = 'folder_id';
					$field->columntype = 'VARCHAR(100)';
					$field->uitype = 1;
					$field->displaytype = 2;
					$field->typeofdata = 'V~O';
					$field->presence = 0;
					$blockInstance->addField($field);
					echo "<br><b>Added Field to ".$module->name." module.</b><br>";
				} else {
					echo "<b>Failed to find ".$module->name." block</b><br>";
				}
				$module->addLink(
					'DETAILVIEWBASIC',
					'Ir a documentos',
					'index.php?module=OpenKM&action=index&src_module=$MODULE$&src_record=$RECORD$'
				);

			} else {
				echo "<b>Failed to find ".$module->name." module.</b><br>";
			}

		} else if($event_type == 'module.disabled') {
			// TODO Handle actions when this module is disabled.
		} else if($event_type == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
		} else if($event_type == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
		} else if($event_type == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} else if($event_type == 'module.postupdate') {
			// TODO Handle actions after this module is updated.
		}
	}

}
?>