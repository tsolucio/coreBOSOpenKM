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

global $currentModule;
require_once 'modules/OpenKM/OpenKM.php';


$okm = new OpenKM();
if(!$okm->isRunning()){
    echo "<br><h2>   OpenKM is not running</h2><br>";
    echo "<h3>    - Start OpenKM first </h3><br>";
    echo "<h3>    - Configure your vtiger OpenKM module settings <a href='index.php?module=OpenKM&action=OKMConfigServer&parenttab=Settings&formodule=OpenKM'> OpenKM Server Access </a><h3><br>";
    exit;
}else{
    $okm->Authenticate();
    
    //if is set account id request openkm to open this account's folder.
    if(isset($_REQUEST['src_module']) && $_REQUEST['src_module'] == 'Accounts')
    {
        $id = $_REQUEST['src_record'];
            
        $path = $okm->getPath4Instance($id);
        $valid = $okm->CheckPath($path);
        
        $okm->Logout();
        
        $okm->url.='?fldPath='.str_replace('+','%20',urlencode($path));
    }
    
    /*converting vtiger language format to okm lang format*/
    $current_language = $_SESSION['authenticated_user_language'];
    $aux = explode('_',$current_language);
    $aux[1] = strtoupper($aux[1]);
    $current_language = implode('-',$aux);
    
        ?>
        <form onsubmit="setCookie()" action="<?php echo $okm->url;?>/j_spring_security_check" method="post" name="loginform" id="loginform" target="vtIframe" >
            <input id="j_username" type="hidden" name="j_username" value="<?php echo $okm->user;?>">
            <input id="j_password" type="hidden" name="j_password" value="<?php echo $okm->password;?>">
            <input id="j_language" type="hidden" name="j_language" value="<?php echo $current_language;?>">
            <!--<input type="submit" name="submit" value="Login">-->
        </form>
        <iframe id="vtIframe" name="vtIframe" width="100%" height="600" src="<?php echo $okm->url;?>"></iframe>';
        <script type="text/javascript" language="JavaScript"><!--
        //checking cookies, ¿is okm initied?
        var okm_init;
        function getCookie(c_name)
        {
            var i,x,y,ARRcookies=document.cookie.split(";");
            for (i=0;i<ARRcookies.length;i++)
            {
              x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
              y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
              x=x.replace(/^\s+|\s+$/g,"");
              //alert(x);
              if (x==c_name)
                {
                return unescape(y);
                }
            }
        }
        function setCookie( c_name, value, expires, path)
        {
            // set time, it’s in milliseconds
            var today = new Date();
            today.setTime( today.getTime() );
            /*
            if the expires variable is set, make the correct expires time, the current script below will set
            it for x number of days, to make it for hours, delete * 24, for minutes, delete * 60 * 24
            */
            if ( expires ){
            expires = expires * 1000 * 60 * 60;
            }
            var expires_date = new Date( today.getTime() + (expires) );
            document.cookie = c_name + "=" + escape( value ) +
            ( ( expires ) ? ";expires=" + expires_date.toGMTString() : "" ) +
            ( ( path ) ? ";path=" + path : "" );
        }
        
        okm_init = getCookie('okm_init');
        
        if(okm_init != 'okm_init')
        {
            setCookie('okm_init','okm_init',1,"/");
            document.loginform.submit();
        }
    //--></script>
    <?php
}    

?>
