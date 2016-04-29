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

		function FolderKM($path=null) 
		 {
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
            var $user = "okmAdmin";
            var $password = "admin";
	    var $main_path = "/okm:root/";
	    var $folder;
	    var $document;
            var $soap_auth;
	    var $soap_folder;
	    var $soap_document;
	    var $soap_propertygroup;
	    var $soap_repository;
	    var $token;
	    var $running;
	    //constructor
	    function __construct()
	    {
			global $adb;
			$results = $adb->query('select * from vtiger_openkm_config');
			
			$this->user = $adb->query_result($results, 0, 'openkm_user');
			$this->password = $adb->query_result($results, 0, 'openkm_password');
			$this->main_path = $adb->query_result($results, 0, 'openkm_main_path');
			$this->url = $adb->query_result($results, 0, 'openkm_url');
			
			xdebug_disable();
			$options = array(
			'exceptions'=>1
			); 
			try{
				    $this->soap_auth = @new SoapClient($this->url."/services/OKMAuth?wsdl",$options);
				    $this->soap_folder = @new SoapClient($this->url."/services/OKMFolder?wsdl",$options);
				    $this->soap_document = @new SoapClient($this->url."/services/OKMDocument?wsdl",$options);
				    $this->soap_propertygroup = @new SoapClient($this->url."/services/OKMPropertyGroup?wsdl",$options);
				    $this->soap_repository = @new SoapClient($this->url."/services/OKMRepository?wsdl",$options);
				    $this->running = true;
				    
			}catch(SoapFault $e){
				    $this->running = false;   
			}
			xdebug_enable();
	    }
	    
	    function isRunning(){
			return($this->running);
	    }
	    
	    function SetURL($url)
	    {
		    $this->url = $url;
	    }

	    function SetUser($user, $password)
	    {
		    $this->user = $user;
		    $this->password = $password;
	    }

	    function Authenticate()
	    {
	        try{
			$obj = $this->soap_auth->login(array('user' => $this->user, 'password' => $this->password));
			$this->token = $obj->return;
			//print("Token: ".$token."<br/>\n");
			return($this->token);
	        } catch (Exception $e) {
                    echo "Authenticating: ".format_exception($e);
		}
	    }
	    
	    /* Creates a folder
	      *-based on the given path
	      *-if the given path is null, path will be based on its folder attribute
	    */
	    function CreateFolder($path=null)
	    {
		try{
			    if(!$this->folder)
				    $this->CreateSimpleFolder($path);
			    else{
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
	    function getUUID($path)
	    {
		try{
			$uuid = $this->soap_repository->getNodeUuid(array('token' => $this->token, 'path' => $path));		 
			return $uuid;
                } catch (Exception $e) {
                    echo format_exception($e);
		}
	    }
	    
	    /* Retrieves the path for the folder $uuid */
	    function getPath($uuid)
	    {
		try{
			$path = $this->soap_folder->getPath(array('token' => $this->token, 'uuid' => $uuid));		 
			return $path->return;
                } catch (Exception $e) {
                    echo format_exception($e);
		}
	    }
	    
	    /* Retrieves the OKM path for an instance id,
	       now works with accounts, could be usefull further
	    */
	    function getPath4Instance($id)
	    {
	        global $adb;
		try{
			$moduleName = getSalesEntityType($id);
			
			if($moduleName)
			{
				    require_once("modules/$moduleName/$moduleName.php");
				    $focus = new $moduleName();
				    $focus->id = $id;
				    $focus->retrieve_entity_info($id, $moduleName);
			
				    $uuid = $adb->getOne("select folder_id from {$focus->table_name}
				    where {$focus->table_index} = {$id}");
	    
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
	    function CheckPath($path)
	    {
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
	    private function CreateSimpleFolder($path)
	    {
		try{
		    
			//echo "creo carpeta simple en : $path";exit;
			$obj = $this->soap_folder->createSimple(array('token' => $this->token, 'fldPath' => $this->folder->path));
			$fld = $obj->return;
			
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
	    function RenameFolder($path, $new_name)
	    {
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
	    function CreateDocument()
	    {
		 try{
		    // create document
			 // faltaria crear clase document, no implemento por no ser necesario de momento
		    $file = '/etc/hosts';
		    $doc = array('path' => '/okm:root/hosts.txt', 
					     'mimeType' => null,
					     'actualVersion' => null, 
					     'author' => null, 
					     'checkedOut' => false,
					     'created' => null, 
					     'keywords' => 'nada', 
					     'language' => null,
					     'lastModified' => null, 
					     'lockInfo' => null, 
					     'locked' => false,
					     'permissions' => 0, 
					     'size' => 0, 
					     'subscribed' => false, 
					     'uuid' => null,
					     'convertibleToPdf' => false, 
					     'convertibleToSwf' => false,
					     'compactable' => false, 
					     'training' => false, 
					     'convertibleToDxf' => false);
		    $obj = $this->soap_document->create(array('token' => $this->token, 'doc' => $doc, 'content' => file_get_contents($file)));
		    $doc = $obj->return;
		    print ("[DOCUMENT] Path: ".$doc->path.", Author: ".$doc->author.", Size: ".$doc->actualVersion->size.", UUID".$doc->uuid."<br/>\n");
                } catch (Exception $e) {
                    echo format_exception($e);
		}
	    } 

	    function CreateSimpleDocument()
	    {
		try{
			    // create document simple
			    $file = '/etc/hosts';
			    $docPath = '/okm:root/test/hosts.txt'; 
			    $obj = $this->soap_document->createSimple(array('token' => $this->token, 'docPath' => $docPath, 'content' => file_get_contents($file)));
			    $doc = $obj->return;
			    print ("[DOCUMENT] Path: ".$doc->path.", Author: ".$doc->author.", Size: ".$doc->actualVersion->size.", UUID".$doc->uuid."<br/>\n");
			    //AddPropertyGroup($docPath);
			    return $doc;
                } catch (Exception $e) {
                    echo format_exception($e);
		}
	    }		    

	    // add property group ( metadata )
	    function AddPropertyGroup($docPath)
	    {
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

	    // Logout
	    function Logout()
	    {
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
