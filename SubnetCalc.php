<!--
PHP Subnet Calculator v1.3.
Copyright 06/25/2003 Raymond Ferguson ferguson_at_share-foo.com.
Released under GNU GPL.
Special thanks to krischan at jodies.cx for ipcalc.pl http://jodies.de/ipcalc
The presentation and concept was mostly taken from ipcalc.pl.
Modified by Marius Boeru <mboeru@gmail.com>
-->
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
		"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <title>PHP Subnet Calculator</title>
  <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
  <meta name="GENERATOR" content="Quanta Plus">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="css/bootstrap.min.css" >
  <!-- <script src="js/bootstrap.min.js" type="text/javascript" name="bsjs"></script> -->
</head>
<body bgcolor="#D3D3D3">
<div class="container">

 <div class="row">
 	<h1><span class="glyphicon glyphicon-info-sign"></span> <a target="_blank" href="http://sourceforge.net/projects/subntcalc/">PHP Subnet Calculator</a></h1>
		<div class="form-group">
			<form method="post" action="<?php print $_SERVER['PHP_SELF'] ?> " class="form-inline">
				<label>IP &amp; Mask or CIDR   <span class="glyphicon glyphicon-chevron-right"></span></label>
				<input type="text" name="my_net_info" value="" class="form-control" autofocus="autofocus">
				<input type="submit" class="btn btn-default" value="Calculate" name="subnetcalc">
			</form>
		</div>

<br>

<?php
//Start table
require_once 'functions.php';
print "<table class=\"table table-condensed\">";

  $end='</table><a href="http://validator.w3.org/check/referer">
      <img border="0" src="http://www.w3.org/Icons/valid-html401" alt="Valid HTML 4.01!" height="31" width="88"></a></div></div></body></html>';

if (empty($_POST['my_net_info'])){
	tr('success','Use IP & CIDR Netmask:&nbsp;', '10.0.0.1/22');
	tr('success','Or IP & Netmask:','10.0.0.1 255.255.252.0');
	tr('success','Or IP & Wildcard Mask:','10.0.0.1 0.0.3.255');
	print $end ;
	exit ;
}

$my_net_info=rtrim($_POST['my_net_info']) ;
list($address, $len) = split('[/.-]', $my_net_info);

if (  preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}(( ([0-9]{1,3}\.){3}[0-9]{1,3})|(\/[0-9]{1,2}))$/',$my_net_info) ) {


	if (preg_match("/\//", $my_net_info , $matches)) {
		$dq_host = strtok("$my_net_info", "/");
		$cdr_nmask = strtok("/");
		if (!($cdr_nmask >= 0 && $cdr_nmask <= 32)){
			tr("danger","Invalid CIDR value. Try an integer 0 - 32.");
			print "$end";
			exit ;
		}
		$bin_nmask=cdrtobin($cdr_nmask);
		$bin_wmask=binnmtowm($bin_nmask);
	} else { //Dotted quad mask?
	    $dqs=explode(" ", $my_net_info);
		$dq_host=$dqs[0];
		$bin_nmask=dqtobin($dqs[1]);
		$bin_wmask=binnmtowm($bin_nmask);
		if (ereg("0",rtrim($bin_nmask, "0"))) {  //Wildcard mask then? hmm?
			$bin_wmask=dqtobin($dqs[1]);
			$bin_nmask=binwmtonm($bin_wmask);
			if (ereg("0",rtrim($bin_nmask, "0"))){ //If it's not wcard, whussup?
				tr("Invalid Netmask.");
				print "$end";
				exit ;
			}
		}
		$cdr_nmask=bintocdr($bin_nmask);
	}

	//Check for valid $dq_host
	if (preg_match("/^0./", $dq_host)) {
		foreach( explode(".",$dq_host) as $octet ){
			if($octet > 255){ 
				tr("Invalid IP Address");
				print $end ;
				exit;
			}
		
		}
	}

	$bin_host=dqtobin($dq_host);
	$bin_bcast=(str_pad(substr($bin_host,0,$cdr_nmask),32,1));
	$bin_net=(str_pad(substr($bin_host,0,$cdr_nmask),32,0));
	$bin_first=(str_pad(substr($bin_net,0,31),32,1));
	$bin_last=(str_pad(substr($bin_bcast,0,31),32,0));
	$host_total=(bindec(str_pad("",(32-$cdr_nmask),1)) - 1);

	if ($host_total <= 0){  //Takes care of 31 and 32 bit masks.
		$bin_first="N/A" ; $bin_last="N/A" ; $host_total="N/A";
		if ($bin_net === $bin_bcast) $bin_bcast="N/A";
	}

	//Determine Class
	if (preg_match("/^0/", $bin_net)) {
		$class="A";
		$dotbin_net= "<font color=\"Green\">0</font>" . substr(dotbin($bin_net,$cdr_nmask),1) ;

	}elseif (preg_match("/^10/", $bin_net)) {
		$class="B";
		$dotbin_net= "<font color=\"Green\">10</font>" . substr(dotbin($bin_net,$cdr_nmask),2) ;

	}elseif (preg_match("/^110/", $bin_net)) {
		$class="C";
		$dotbin_net= "<font color=\"Green\">110</font>" . substr(dotbin($bin_net,$cdr_nmask),3) ;
	}elseif (ereg('^1110',$bin_net)){
		$class="D";
		$dotbin_net= "<font color=\"Green\">1110</font>" . substr(dotbin($bin_net,$cdr_nmask),4) ;
		$special="<font color=\"Green\">Class D = Multicast Address Space.</font>";
	}else{
		$class="E";
		$dotbin_net= "<font color=\"Green\">1111</font>" . substr(dotbin($bin_net,$cdr_nmask),4) ;
		$special="<font color=\"Green\">Class E = Experimental Address Space.</font>";
	}


	if (preg_match("/^(00001010)|(101011000001)|(1100000010101000)/", $bin_net)) {
		 $special='<a href="http://www.ietf.org/rfc/rfc1918.txt">( RFC-1918 Private Internet Address. )</a>';
	}

	// Print Results
	tr('info','Address:',"<font color=\"blue\">$dq_host</font>",
		'<font color="brown">'.dotbin($bin_host,$cdr_nmask).'</font>');
	tr('info','Netmask:','<font color="blue">'.bintodq($bin_nmask)." = $cdr_nmask</font>",
		'<font color="red">'.dotbin($bin_nmask, $cdr_nmask).'</font>');
	tr('active','Wildcard:', '<font color="blue">'.bintodq($bin_wmask).'</font>',
		'<font color="brown">'.dotbin($bin_wmask, $cdr_nmask).'</font>');
	tr('active','Network:', '<font color="blue">'.bintodq($bin_net).'</font>',
		"<font color=\"brown\">$dotbin_net</font> <font color=\"green\">(Class $class)</font>");
	tr('active','Broadcast:','<font color="blue">'.bintodq($bin_bcast).'</font>',
		'<font color="brown">'.dotbin($bin_bcast, $cdr_nmask).'</font>');
	tr('info','HostMin:', '<font color="blue">'.bintodq($bin_first).'</font>',
		'<font color="brown">'.dotbin($bin_first, $cdr_nmask).'</font>');
	tr('info','HostMax:', '<font color="blue">'.bintodq($bin_last).'</font>',
		'<font color="brown">'.dotbin($bin_last, $cdr_nmask).'</font>');
	@tr('warning','Hosts/Net:', '<font color="blue">'.$host_total." ".$special.'</font>',"");
/*} else if ( preg_match('/^(((?=(?>.*?(::))(?!.+\3)))\3?|([\dA-F]{1,4}(\3|:(?!$)|$)|\2))(?4){5}((?4){2}|(25[0-5]|(2[0-4]|1\d|[1-9])?\d)(\.(?7)){3})\z/i^i', $my_net_info )) { */
} else if ( valid_ipv6_address($address)) {

	$calc = new IPV6SubnetCalculator();

	if ($calc->testValidAddress($address))
	{
		$rangedata = $calc->getAddressRange($address, $len);

		//$ret = array(
			tr('info',"Abbreviated Address: ", $calc->abbreviateAddress($address));
			tr('info',"Unabbreviated Address: ", $calc->unabbreviateAddress($address));
			tr('info',"Prefix Length: " , $len);
			tr('info',"Number of IPs: " , $calc->getInterfaceCount($len));
			tr('info',"Start IP: " , $rangedata['start_address']);
			tr('info',"End IP: " , $rangedata['end_address']);
			tr('info',"Prefix Address: ", $rangedata['prefix_address']);
		//);
	} else {
		tr('critical','That is not a valid IPv6 Address',"");
	}

} else {
//	if(! preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}(( ([0-9]{1,3}\.){3}[0-9]{1,3})|(\/[0-9]{1,2}))$/',$my_net_info) ) {
		tr("danger","<span style=\"color: red;\">Invalid Input.</span>","");
		tr("danger",'Use IP & CIDR Netmask:&nbsp;', '10.0.0.1/22');
		tr("danger",'Or IP & Netmask:','10.0.0.1 255.255.252.0');
		tr("danger",'Or IP & Wildcard Mask:','10.0.0.1 0.0.3.255');
//		print $end ;
		exit ;
}


	print "$end";


?>
