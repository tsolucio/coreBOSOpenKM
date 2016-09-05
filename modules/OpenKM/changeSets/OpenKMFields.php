<?php
/*************************************************************************************************
 * Copyright 2015 JPL TSolucio, S.L. -- This file is a part of TSOLUCIO coreBOS Customizations.
* Licensed under the vtiger CRM Public License Version 1.1 (the "License"); you may not use this
* file except in compliance with the License. You can redistribute it and/or modify it
* under the terms of the License. JPL TSolucio, S.L. reserves all rights not expressly
* granted by the License. coreBOS distributed by JPL TSolucio S.L. is distributed in
* the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
* warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. Unless required by
* applicable law or agreed to in writing, software distributed under the License is
* distributed on an "AS IS" BASIS, WITHOUT ANY WARRANTIES OR CONDITIONS OF ANY KIND,
* either express or implied. See the License for the specific language governing
* permissions and limitations under the License. You may obtain a copy of the License
* at <http://corebos.org/documentation/doku.php?id=en:devel:vpl11>
*************************************************************************************************/

class OpenKMFields extends cbupdaterWorker {
	private $label = 'Upload Files';
	function applyChange() {
		global $adb;
		if ($this->hasError()) $this->sendError();
		if ($this->isApplied()) {
			$this->sendMsg('Changeset '.get_class($this).' already applied!');
		} else {
			
			$Potmodule = Vtiger_Module::getInstance('Potentials');
			$blockInstance = VTiger_Block::getInstance('LBL_OPPORTUNITY_INFORMATION',$Potmodule);
				
			if($blockInstance) {				
				// Add field
				$field = Vtiger_Field::getInstance('folderid',$Potmodule);
				if (!$field) {
					$field = new Vtiger_Field(); 
					$field->label = 'Folder'; 
					$field->name = 'folderid'; 
					$field->table = $Potmodule->basetable; 
					$field->column = 'folderid'; 
					$field->columntype = 'VARCHAR(50)'; 
					$field->uitype = 26; //document folder 
					$field->typeofdata = 'V~O'; 
					$blockInstance->addField($field);
				}
			}
			
			$this->ExecuteQuery('ALTER TABLE `vtiger_attachmentsfolder`	CHANGE COLUMN `folderid` `folderid` VARCHAR(50) NOT NULL FIRST;',array());
			$query = 'SELECT okmuuid FROM vtiger_attachments WHERE 1=1';
			$result = $adb->pquery($query, array());
			if($adb->num_rows($result) <= 0){
				$this->ExecuteQuery('ALTER TABLE `vtiger_attachments` ADD COLUMN `okmuuid` VARCHAR(50) NULL DEFAULT NULL AFTER `subject`;', array());
			}
			$query = 'SELECT okmpassword FROM vtiger_users WHERE 1=1';
			$result = $adb->pquery($query, array());
			if($adb->num_rows($result) <= 0){
				$this->ExecuteQuery('ALTER TABLE `vtiger_users` ADD COLUMN `okmpassword` VARCHAR(10) NULL DEFAULT NULL ;', array());
			}
			$this->sendMsg('Changeset '.get_class($this).' applied!');
			$this->markApplied();
		}
		$this->finishExecution();
	}
	
	function undoChange() {
		if ($this->hasError()) $this->sendError();
		if ($this->isApplied()) {
			// undo your magic here
			$this->sendMsg('Changeset '.get_class($this).' undone!');
			$this->markUndone();
		} else {
			$this->sendMsg('Changeset '.get_class($this).' not applied!');
		}
		$this->finishExecution();
	}
	
}