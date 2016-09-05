<?php

//define("HOST_URL", 'http://localhost:8080/logicaldoc'); // Community edition
//define("HOST_URL", 'http://eva00:80'); // Commercial edition (without webapp path)
define("HOST_URL", 'http://127.0.0.1:8080'); // Alternative call using IP_Address (Sometimes it works better)

define("DEFAULT_WORKSPACE", 4);

$authClient = new SoapClient ( HOST_URL . '/services/Auth?wsdl' );
$documentClient = new SoapClient ( HOST_URL . '/services/Document?wsdl' );
$folderClient = new SoapClient ( HOST_URL . '/services/Folder?wsdl' );
$searchClient = new SoapClient (  HOST_URL . '/services/Search?wsdl' );
$systemClient = new SoapClient (  HOST_URL . '/services/System?wsdl' );

// The clients below are available only on the Commercial editions of LD 
$searchClientEnterprise = new SoapClient (  HOST_URL . '/services/EnterpriseSearch?wsdl' );
//$documentMetaClient = new SoapClient ( HOST_URL . '/services/DocumentMetadata?wsdl' );

function login($user, $pwd) {
	
	global $authClient;
	
	$loginParams = array ('username' => $user, 'password' => $pwd );
	
	$result = $authClient->login ( $loginParams );
	//print_r ( $result );
	$sid = $result->return;
	//echo 'sid: '.$sid . PHP_EOL;
	
	return $sid;
}

function logout($token) {
	
	global $authClient;
	
  //print_r ("token: "  .$token );
	$logoutParams = array ('sid' => $token );
	
	$result = $authClient->logout ( $logoutParams );
	print_r ( $result );
	
	return;
}

?>