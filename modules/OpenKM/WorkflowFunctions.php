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
require_once 'modules/OpenKM/OpenKM.php';

/* Creates a folder and assigns its uuid to the account
*/
function CreateFolder($entityData)
{
	global $adb, $log;

        $id = explode('x', $entityData->get('id'));
        if(count($id)>1)
         $id = $id[1];
        
	$okm = new OpenKM();
	
	if($okm->isRunning()){
		$okm->Authenticate();
		//CreateSimpleFolder no funciona, preguntar a Josep
		$okm->folder = new FolderKM($okm->main_path.$entityData->get('accountname'));
		$fld = $okm->CreateFolder();
		//update account adding its folder uuid
		$adb->query("update vtiger_account set folder_id = '{$fld->uuid}'
			    where accountid = '{$id}'");
		$okm->Logout();
	}
}

/* Creates/edits a folder and assigns its uuid to the account
*/
function LinkFolder($entityData)
{
	global $adb, $log;

        $id = explode('x', $entityData->get('id'));
        if(count($id)>1)
         $id = $id[1];
        
	$results = $adb->query("select accountname, folder_id from vtiger_account
			     where accountid = {$id}");
	$uuid = $adb->query_result($results,0,'folder_id');
	$acc_name = $adb->query_result($results,0,'accountname');
	
	if(!$uuid)
		CreateFolder($entityData);
	else{
		$okm = new OpenKM();
	
		if($okm->isRunning()){
			$okm->Authenticate();
			$fld_path = $okm->getPath($uuid);
			$new_name = $acc_name;
			
			$fld = $okm->RenameFolder($fld_path, $new_name);
			if(!$fld){
				echo "algopasa";
				exit;
			}
			/*var_dump($fld);exit;
			$adb->query("update vtiger_account set folder_id = '{$fld->uuid}'
			    where accountid = '{$id}'");
			*/
			$okm->Logout();
		}	
	}
}

?> 
