<?
#
#	Naglite2, a version of http://www.monitoringexchange.org/inventory/Utilities/AddOn-Projects/Frontends/NagLite
#	Modified by Laurie Denness
#

		# Try and prevent caching and provide some refresh intervals (customisable)
		header("Pragma: no-cache");
		if (!isset($_GET['refresh'])) { $_GET['refresh'] = "10"; } 
		header("Refresh: " .$_GET['refresh']);

		# Library for calculating friendly durations
		require_once 'Duration.php';

?>


<HTML>
<HEAD>
<TITLE>Nagios Monitoring System - Naglite2</TITLE>
<style>
body { 
	font-family: Verdana, "Tahoma", "Helvetica", "arial", "sans";
	margin-left: 0px;
	margin-right: 0px;
}

.smallack {
font-size:12px;
}

</style>
</HEAD>
<BODY BGCOLOR="#FFFFFF">


<?

# All the hard work is done in here. 
require 'inc.php';

?>
<CENTER>

<?
	if (is_file($status_file)) {
		$file = file_get_contents($status_file);
	}

        if (!$file) {
		 echo "<CENTER><FONT COLOR=\"red\" SIZE=+2>Failed to open Status file ";
        	 echo "<br /><br /><br /><img src=\"loading.gif\"></FONT></CENTER><BR>\n"; 
		die ();
	}



	$line = explode("}",$file);

	for ($x = 0; $x < count($line); $x++)
	{
		handle_line($line[$x]);
	}

?>

<?
	if(is_array($hlist)) 
	foreach (array_keys($hlist) as $hkey) {
	
		// Print the number of hosts in the different states
		if (isset($hlist[$hkey]["host"]["status"])) {
			if ($hlist[$hkey]["host"]["status"] == "0") {  $hlist[$hkey]["host"]["status"] = "UP"; } 
			if ($hlist[$hkey]["host"]["status"] == "1") {  $hlist[$hkey]["host"]["status"] = "DOWN"; } 
			if ($hlist[$hkey]["host"]["status"] == "2") {  $hlist[$hkey]["host"]["status"] = "BLOCK"; } 

			// Do something special for blocked hosts, as they take up too much room and they're the last
			// thing you want filling up your screen if there's a network outage.. 
			if ($hlist[$hkey]["host"]["status"] == "BLOCK") 
			{
				$unreachable_hosts++;
				$listofblocked[] .= $hlist[$hkey]["host"]["host_name"];
			}

			// Also acknowledged hosts take up way too much room. Give them a seperate line. 
			if ($hlist[$hkey]["host"]["problem_has_been_acknowledged"] == "1") 
			{
				$ackhosts++;
				$listofacked[] .= $hlist[$hkey]["host"]["host_name"];
			}


		// Now the mess begins. 
		if( $hlist[$hkey]["host"]["status"] != "UP" && $hlist[$hkey]["host"]["status"] != "PENDING" && $hlist[$hkey]["host"]["status"] != "BLOCK" && $hlist[$hkey]["host"]["problem_has_been_acknowledged"] != "1") {
			$hostsout .= sprintf("<TR bgcolor=\"pink\">\n");
			$hostsout .= sprintf("\t<TD bgcolor=\"lightgrey\">%s</TD> ", $hlist[$hkey]["host"]["host_name"]);
			if($hlist[$hkey]["host"]["status"] == "BLOCK") {
				$hostsout .= sprintf("<TD BGCOLOR=\"orange\">%s</TD>", $hlist[$hkey]["host"]["status"]);
			} else if($hlist[$hkey]["host"]["problem_has_been_acknowledged"] == "1") {
				$hostsout .= sprintf("<TD BGCOLOR=\"lightgrey\" align=\"center\"><font color=\"red\" size=\"-1\">%s (a)</font></TD>", $hlist[$hkey]["host"]["status"]);			
			} else {
				$hostsout .= sprintf("<TD BGCOLOR=\"red\" align=\"center\"><font color=\"white\" style=\"text-decoration: blink;\">%s</font></TD>", $hlist[$hkey]["host"]["status"]);
			}
			$hostsout .= sprintf("<TD>%s</TD>", $hlist[$hkey]["host"]["duration"]);
			// if you want to see when the last notificaiton was, uncomment this. 
			# $hostsout .= sprintf("<TD>%s</TD>", $hlist[$hkey]["host"]["last_notification"]);
			$hostsout .= sprintf("<TD><small>%s</small></TD>\n", $hlist[$hkey]["host"]["plugin_output"]);
			$hostsout .= sprintf("</TR>\n");
			if($hlist[$hkey]["host"]["status"] == "BLOCK") { $unreachable_hosts++; } 
			else if($hlist[$hkey]["host"]["status"] == "PENDING") { $pending_hosts++; } 
			else if($hlist[$hkey]["host"]["problem_has_been_acknowledged"] == "1") { $ackhosts++; } 
			else { $badhosts++;}
		} else {
			$goodhosts++;
		}

		if(! $hlist[$hkey]["host"]["notifications_enabled"] ) {
				$notifications_off .= $hlist[$hkey]["host"]["host_name"]. "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ";
		}

		// hosts done. Lets move onto the services. 

		$services=$hlist[$hkey]["service"];

		if(is_array($services) && $hlist[$hkey]["host"]["status"] == "UP") {
			foreach( $services as $service => $svalue) {
				
				// Warnings are special cased to make them orange. 
				if($services[$service]["status"] == "WARNING") {
					$servicesout_warning .= "<TR bgcolor=\"orange\">\n";

					if($hlist[$hkey]["host"]["host_name"] == $lasthost && $lastalert == "warning")  {
						$servicesout_warning .= sprintf("<TD bgcolor=\"white\">&nbsp;</TD>");
					} else {
						$servicesout_warning .= sprintf("<TD bgcolor=\"lightgrey\">%s</TD>", 
                                                                                   $hlist[$hkey]["host"]["host_name"]);
					}

					$servicesout_warning .= sprintf("<TD>%s</TD>", $services[$service]["description"]);
					$servicesout_warning .= sprintf("<TD BGCOLOR=\"yellow\" align=\"center\">%s</TD>", $services[$service]["status"]);
					# $servicesout_warning .= sprintf("<TD>%s</TD>", $services[$service]["last_check"]);
					# $servicesout_warning .= sprintf("<TD>%s</TD>", $services[$service]["last_state_change"]);
					$servicesout_warning .= sprintf("<TD>%s</TD>", $services[$service]["duration"]);
					$servicesout_warning .= sprintf("<TD>%s/%s</TD>", $services[$service]["current_attempt"], $services[$service]["max_attempts"]);
					# $servicesout_warning .= sprintf("<TD><font size=\"2\">%s</font></TD>", $services[$service]["plugin_output"]);
					$servicesout_warning .= sprintf("</TR>\n");
					$lastalert="warning";
				// So are acknowledged and things with notifications disabled. 
				} else if (($services[$service]["problem_has_been_acknowledged"] == "1") || ($services[$service]["notifications_enabled"] == 0)) {
					$thisalert="ack";
                                        $servicesout_ack .= "<TR bgcolor=\"lightgrey\" class=\"smallack\">\n"; 

					if(($hlist[$hkey]["host"]["host_name"] != $lasthost) || ($thisalert != $lastalert)) {
						$servicesout_ack .= sprintf("<TD bgcolor=\"lightgrey\">%s</TD>", $hlist[$hkey]["host"]["host_name"]);
					} else {
						$servicesout_ack .= sprintf("<TD bgcolor=\"white\">&nbsp;</TD>");
					}
					$servicesout_ack .= sprintf("<TD>%s</TD>", $services[$service]["description"]);
					
					if($services[$service]["status"] == "Unknown") {
					$servicesout_ack .= sprintf("<TD BGCOLOR=\"lightgrey\">%s</TD>", $services[$service]["status"]);
					} else {
					$servicesout_ack .= sprintf("<TD BGCOLOR=\"lightgrey\"><font color=\"red\" size=\"-1\">%s (ack)</TD>", $services[$service]["status"]); } 

					# $servicesout_error .= sprintf("<TD>%s</TD>", $services[$service]["last_check"]);
					#$servicesout_ack .= sprintf("<TD>%s</TD>", $services[$service]["last_state_change"]);
					$servicesout_ack .= sprintf("<TD>%s</TD>", $services[$service]["duration"]);
					$servicesout_ack .= sprintf("<TD>%s/%s</TD>", $services[$service]["current_attempt"], $services[$service]["max_attempts"]);
					#$servicesout_error .= sprintf("<TD><font size=\"2\">%s</font></TD>", $services[$service]["plugin_output"]);
					$servicesout_ack .= sprintf("</TR>\n");
					$lastalert="ack";

				// Annnd unknown stuff. 
				} else {
					if($services[$service]["status"] == "Unknown") 
						{ $servicesout_error .= "<TR bgcolor=\"lightgrey\">\n"; } 
					else 
						{ $servicesout_error .= "<TR bgcolor=\"#F75D59\">\n"; }

					$thisalert="error";


				// Finally, stuff that is actually Critical. 
					if(($hlist[$hkey]["host"]["host_name"] != $lasthost) || ($thisalert != $lastalert))  {
						$servicesout_error .= sprintf("<TD bgcolor=\"lightgrey\">%s</TD>", $hlist[$hkey]["host"]["host_name"]);
					} else {
						$servicesout_error .= sprintf("<TD bgcolor=\"white\">&nbsp;</TD>");
					}
					

					if($services[$service]["status"] == "Unknown") {
					$servicesout_error .= sprintf("<TD>%s</TD>", $services[$service]["description"]);
					} else { 
					$servicesout_error .= sprintf("<TD><font color=\"white\">%s</font></TD>", $services[$service]["description"]);
					}
					
					if($services[$service]["status"] == "Unknown") {
					$servicesout_error .= sprintf("<TD BGCOLOR=\"lightgrey\">%s</TD>", $services[$service]["status"]);
					} else {

						// There is also a special case here to make SOFT notifications look less harsh. Things that are really broken get red and flashy. 
						if ($services[$service]["current_attempt"] >= $services[$service]["max_attempts"]) {
							$servicesout_error .= sprintf("<TD BGCOLOR=\"red\" align=\"center\"><font color=\"white\" style=\"text-decoration: blink;\">%s</TD>", $services[$service]["status"]); 
						} else {
							$servicesout_error .= sprintf("<TD align=\"center\"><font color=\"white\">%s (soft)</TD>", $services[$service]["status"]); 
						}

					}

					# $servicesout_error .= sprintf("<TD>%s</TD>", $services[$service]["last_check"]);
					# $servicesout_error .= sprintf("<TD>%s</TD>", $services[$service]["last_state_change"]);


					if($services[$service]["status"] == "Unknown") {
					$servicesout_error .= sprintf("<TD>%s</TD>", $services[$service]["duration"]);
					} else {					
					$servicesout_error .= sprintf("<TD><font color=\"white\">%s</font></TD>", $services[$service]["duration"]);
					}
		
					if($services[$service]["status"] == "Unknown") {
					$servicesout_error .= sprintf("<TD>%s/%s</TD>", $services[$service]["current_attempt"], $services[$service]["max_attempts"]);
					} else {
					$servicesout_error .= sprintf("<TD><font color=\"white\">%s/%s</font></TD>", $services[$service]["current_attempt"], $services[$service]["max_attempts"]);
					}

					# $servicesout_error .= sprintf("<TD><font size=\"2\">%s</font></TD>", $services[$service]["plugin_output"]);
					$servicesout_error .= sprintf("</TR>\n");
					$lastalert="error";
				}
				// reset some variables? 
				$lastalert="none";
				$lasthost = $hlist[$hkey]["host"]["host_name"];

			}
	}		

		}
	}


// Start the HTML magic!

echo "<TABLE BORDER=0 width=99%>";
echo "<tr><td align=left><font size=+1><b>Host Status</b></td> <td align=right><b>";
	if($goodhosts)  { echo "<FONT COLOR=\"green\">$goodhosts UP</FONT>"; }
	if($unreachable_hosts)  { echo "<FONT COLOR=\"orange\"> - $unreachable_hosts Unreachable</FONT>"; }
	if($pending_hosts)  { echo "<FONT COLOR=\"grey\"> - $pending_hosts Pending</FONT>"; }
	if($badhosts)  { echo "<FONT COLOR=\"red\"> - $badhosts DOWN</FONT>"; }
	if($ackhosts)  { echo "<FONT COLOR=\"darkgrey\"> - $ackhosts Down (Ack)</FONT>"; }
echo "</b></td></TABLE><TABLE BORDER=0 cellspacing=2 width=98%>\n";

// If there are blocked hosts, output the orange block to inform the user in a harsh fashion, but only takes up one line to keep room for everything else. 
if(count ($listofblocked) > 0) {
	$blockedout = "<tr bgcolor=\"orange\"><td colspan=\"4\"><b>Blocked Hosts: </b>";
	$blockedout .= implode(", ", $listofblocked);
	$blockedout .= "</td></tr>";
}

// If there are acknowledged hosts, save some space by showing them all on one line. 
if(count ($listofacked) > 0) {
	$ackedout = "<tr bgcolor=\"lightgrey\"><td colspan=\"4\"><b>Acknowledged:</b>";
	$ackedout .= implode(", ", $listofacked);
	$ackedout .= "</td></tr>";
}


if(strlen($hostsout)) {
	echo "<TR bgcolor=\"lightgrey\">\n";
	echo "\t<TH>Host</TH><TH>Status</TH><TH>Duration</TH><TH>Status Information</TH>\n";
	echo "</TR>\n";
	echo $hostsout;
	echo $blockedout;
	echo $ackedout;
} else {
	echo "<TR>\n<TH COLSPAN=8 BGCOLOR=\"lightgreen\"><FONT SIZE =+1>ALL MONITORED HOSTS UP</FONT></TH>\n</TR>\n";
	echo $blockedout;
	echo $ackedout;
}
	echo "</TABLE>\n";

echo "<P>\n";


	echo "<FONT SIZE=-1>\n";

        echo "<TABLE BORDER=0 width=99%>";
        echo "<tr><td align=left><font size=+1><b>Service Status</b><font size=2> &nbsp;&nbsp;</td> <td align=right><b>";
        if($services_ok) { echo "<FONT COLOR=\"green\">$services_ok OK</FONT> "; }
        if($services_warning) { echo "<FONT COLOR=\"orange\"> - $services_warning Warn</FONT> "; }
        if($services_critical) { echo "<FONT COLOR=\"red\"> - $services_critical Crit</FONT> "; }
        if($services_pending) { echo "<FONT COLOR=\"grey\"> - $services_pending Pending</FONT> "; }
        if($services_unknown) { echo "<FONT COLOR=\"lightgrey\"> - $services_unknown Unknown</FONT> "; }


	echo "</b></td></TABLE><TABLE BORDER=0 cellspacing=2 width=98%>\n";

if(strlen($servicesout_warning) || strlen($servicesout_error)) {
	echo "<TR bgcolor=\"lightgrey\">\n";
	echo "\t<TH>Host</TH><TH nowrap=\"nowrap\">Service</TH><TH>Status</TH><TH>Duration</TH><TH><font size=\"-1\">Attempt</font></TH>\n";
	echo "</TR>\n";
	echo $servicesout_error;
	echo $servicesout_warning;
} else {
	echo "<TR>\n<TH COLSPAN=8 BGCOLOR=\"lightgreen\"><FONT SIZE =+1>ALL MONITORED SERVICES OK</FONT></TH>\n</TR>\n";
}





echo "</TABLE><br /><br />";


// Ackknowledged services get a seperate line to save space
if(strlen($servicesout_ack)) {

        echo "<TABLE BORDER=0 width=99%>";
        echo "<tr><td align=left><b>Acknowledged Services</b><font size=2> &nbsp;&nbsp;</td> <td align=right><b>";
        echo "<FONT COLOR=\"darkgrey\">$services_ack Acknowledged</FONT> ";
	echo "</b></td></TABLE>\n";
	echo "<TABLE BORDER=0 cellspacing=2 width=98%><TR bgcolor=\"lightgrey\" class=\"smallack\">\n";
	echo "\t<TH>Host</TH><TH>Service</TH><TH>Status</TH><TH>Duration</TH><TH><font size=\"1\">A</font></TH>\n";
	echo "</TR>\n";
	echo $servicesout_ack;
	echo "</TABLE>\n";
}

// Show hosts with notifications off. 
if(strlen($notifications_off)) {
	echo "<P>\n";
	echo "<TABLE BORDER=0 celspacing=2 width=90%>\n";
	echo "<TR>\n<TH COLSPAN=8><FONT SIZE =+1>Hosts With Notifications Turned off</FONT></TH>\n</TR>\n";
	echo "<TR bgcolor=\"yellow\">\n";
	echo "<TD>".$notifications_off."</TD>\n";
	echo "</TR></TABLE>\n";
}

	echo "<P>"; ?>
<img src="http://cdn.last.fm/flatness/badges/lastfm_grey_small.gif" alt="this ugly code hacked into submission by lozzd" align="right">

<?
	if ($_GET["debug"] == true) {
	echo "<br></CENTER><TABLE BORDER=0 celspacing=2 width=700 style=\"font-size:10px\">\n";
	echo "<TR>\n<TD COLSPAN=2><b>Nagios Monitor Status</b></TD>\n</TR>\n";
	echo "<TR>\n";
	echo "<TD width=\"200\">Process ID: </TD>\n". "<TD>".$pstatus["PID"]."</TD>\n";
	echo "</TR>\n";
	echo "<TR>\n";
	echo "<TD width=\"200\">Start Time: </TD>\n". "<TD>".$pstatus["Start Time"]."</TD>\n";
	echo "</TR>\n";
	echo "<TR>\n";
	echo "<TD width=\"200\">Check Status: </TD>\n". "<TD>".$pstatus["Status"]."</TD>\n";
	echo "</TR>\n";
	echo "<TR>\n";
	echo "<TD width=\"200\">Webpage refresh interval (s): </TD>\n". "<TD>".$_GET['refresh']  . "</TD>\n";
	echo "</TR>\n";
	echo "</TABLE>\n"; }
?>
</BODY>
</HTML>
