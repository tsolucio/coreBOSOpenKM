<?php
/*+***********************************************************************************
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
 *************************************************************************************/
require_once('Smarty_setup.php');

include_once dirname(__FILE__) . '/OpenKM.php';

global $theme, $currentModule, $mod_strings, $app_strings, $current_user;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$smarty = new vtigerCRM_Smarty();
$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("APP", $app_strings);
$smarty->assign("CMOD", $mod_strings);
$smarty->assign("MODULE_LBL",$currentModule);
// Operation to be restricted for non-admin users.
if(!is_admin($current_user)) {	
	$smarty->display(vtlib_getModuleTemplate('Vtiger','OperationNotPermitted.tpl'));	
} else {

	$mode = $_REQUEST['mode'];

	if(!empty($mode) && $mode == 'Save') {
		$adb->query("delete from vtiger_openkm_config");
		$adb->query("insert into vtiger_openkm_config values(
			    '1',
			    '{$_GET['okm_url']}',
			    '{$_GET['okm_user']}',
			    '{$_GET['okm_password']}',
			    '{$_GET['okm_main_path']}'
			    )");
	}
	$okm = new OpenKM();
	
		?>	
	<div style="margin:2em;">
	<?php $smarty->display('SetMenu.tpl'); ?>
	<h2><?php echo getTranslatedString('SERVER_CONFIGURATION');?></h2>
	<form name="myform" action="index.php" method="GET">
	<input type="hidden" name="module" value="OpenKM">
	<input type="hidden" name="action" value="OKMConfigServer">
	<input type="hidden" name="parenttab" value="Settings">
	<input type="hidden" name="formodule" value="OpenKM">
	<input type="hidden" name="mode" value="Save">
	<table>
	  <tr>
	    <td>
	      <b><?php echo getTranslatedString('URL');?></b>
	    </td>
	    <td>
	      <input type="text" name="okm_url" value=<?php echo $okm->url;?>>
	    </td>
	  </tr>
	  <tr>
	    <td>
	      <b><?php echo getTranslatedString('MAIN PATH');?></b>
	    </td>
	    <td>
	      <input type="text" name="okm_main_path" value=<?php echo $okm->main_path;?>>
	    </td>
	  </tr>
	  <tr>
	    <td>
	      <b><?php echo getTranslatedString('USER');?></b>
	    </td>
	    <td>
	      <input type="text" name="okm_user" value=<?php echo $okm->user;?>>
	    </td>
	  </tr>
	  <tr>
	    <td>
	      <b><?php echo getTranslatedString('PASSWORD');?></b>
	    </td>
	    <td>
	      <input type="text" name="okm_password" value=<?php echo $okm->password;?>>
	    </td>
	  </tr>
	  <tr>
		<td>
	      <input type='submit' value='Save'>
		</td> 
	  </tr>
	</table>
	</form>
	<?php
	
}

?>