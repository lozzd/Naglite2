<?

#
#	Naglite2
#	This file does the dirty work.
#
#

# The path to your Nagios config CGI config. 
$cgi_ini='/usr/local/nagios/etc/cgi.cfg';

# Then use the above to get the path to the main config file. 
$nagios_cfg = ini_getitem($cgi_ini, "main_config_file");

$nagios_check_command = ini_getitem($cgi_ini, "nagios_check_command");

$refresh_rate = ini_getitem($cgi_ini, "refresh_rate");

$status_file = ini_getitem($nagios_cfg, "status_file");

$mypipe = popen($nagios_check_command, "r");
$nagios_status = fgets($mypipe, 1024);
pclose($mypipe);

$pstatus["Status"] = $nagios_status;

function ini_getitem($filename,$item,$length=1024,$notrim=false)
{ 
    $fp = fopen($filename,"r");
    
    if(!$fp) {
       return false;
    }
    while (!feof($fp))
    { 
        $fileline = fgets($fp,$length);
        if ( substr($fileline,0,strlen($item)+1) == $item . "=" )
            if ( $notrim )
                return substr($fileline,strlen($item)+1,strlen($fileline)-strlen($item));
            else 
                return trim(substr($fileline,strlen($item)+1,strlen($fileline)-strlen($item)));
    }
    fclose($fp);
    return false;
}


	function handle_program($buffer)
	// If the line coming in from the buffer is a program status line, process it thusly. 
	{

		global $nagios_check_command, $pstatus;
		$blank=strtok($buffer,"\n");
		$blank=strtok("\n");
		$blank=strtok("\n");
		$nagios_pid = substr(strtok("\n"),12);
		$daemon_mode = substr(strtok("\n"),13);
		$program_start= strftime ("%b %d %Y %H:%M:%S", substr(strtok("\n"),15));
		$last_command_check = strftime ("%b %d %Y %H:%M:%S", substr(strtok("\n"),20));
		$last_log_rotation = strftime ("%b %d %Y %H:%M:%S", substr(strtok("\n"),19));
		$enable_notifications = substr(strtok("\n"),22);
		#$execute_service_checks = strtok(';');
		#$accept_passive_service_checks = strtok(';');
		#$enable_event_handlers = strtok(';');
		#$obsess_over_services = strtok(';');
		#$enable_flap_detection = strtok(';');
		#$enable_failure_prediction = strtok(';');
		#$process_performance_data = strtok('');

		$pstatus["PID"] = $nagios_pid;
		$pstatus["Start Time"] = $program_start;
		$pstatus["Last Command Check"] = $last_command_check;
		$pstatus["Enable Notifications"] = ($enable_notifications) ? "On" : "Off";


	}

	function handle_host($buffer)
	// If the line coming in from the buffer is a host status line, process it thusly. Here is some horrific parsing code. 
	{
		global $hlist;
		
		$nothing = strtok($buffer,"\n");
		$host_name = substr(strtok("\n"),11);
		$modified_attributes = substr(strtok("\n"),21);
		$check_command = substr(strtok("\n"),15);
		$check_period = substr(strtok("\n"),14);
		$notification_period = substr(strtok("\n"),21);
		$check_interval = substr(strtok("\n"),16);
		$check_retry = substr(strtok("\n"),16);
		$event_handler =  substr(strtok("\n"),15);
		$has_been_checked =  substr(strtok("\n"),20);
		$should_be_scheduled =  substr(strtok("\n"),21);
		$check_execution_time =  substr(strtok("\n"),22);
		$check_latency =  substr(strtok("\n"),15);
		$check_type = substr(strtok("\n"),12);
		$status =  substr(strtok("\n"),15);
		$last_hard_state =  substr(strtok("\n"),17);

		$last_event_id =  substr(strtok("\n"),15);
		$current_event_id =  substr(strtok("\n"),18);
		$current_problem_id =  substr(strtok("\n"),20);
		$last_problem_id =  substr(strtok("\n"),17);

		$plugin_output =  substr(strtok("\n"),15);

		$long_plugin_output =  substr(strtok("\n"),20);

		$performance_data =  substr(strtok("\n"),18);
		$last_check =  substr(strtok("\n"),12);
		$next_check =  substr(strtok("\n"),12);

		$check_options =  substr(strtok("\n"),14);

		$current_attempt =  substr(strtok("\n"),17);
		$max_attempts =  substr(strtok("\n"),14);

# You'll need to uncomment these if you have a slightly older version of Nagios. They were in twice in the older versions. 
#		$current_event_id =  substr(strtok("\n"),18);
#		$last_event_id =  substr(strtok("\n"),14);

		$state_type = substr(strtok("\n"),12);
		$last_state_change =  substr(strtok("\n"),19);
		$last_hard_state_change =  substr(strtok("\n"),24);
		$time_up = substr(strtok("\n"),14);
		$time_down = substr(strtok("\n"),16);
		$time_unreachable = substr(strtok("\n"),23);
		$last_notification = substr(strtok("\n"),19);
		$next_notification = substr(strtok("\n"),19);
		$no_more_notifications = substr(strtok("\n"),23);
		$current_notification_number = substr(strtok("\n"),29);

		$current_notification_id = substr(strtok("\n"),24);

		$notifications_enabled = substr(strtok("\n"),23);
		$problem_has_been_acknowledged = substr(strtok("\n"),31);
		$acknowledgement_type = substr(strtok("\n"),22);
		$checks_enabled = substr(strtok("\n"),22);
		$passive_checks_enabled = substr(strtok("\n"),24);
		$event_handler_enabled = substr(strtok("\n"),23);
		$flap_detection_enabled = substr(strtok("\n"),24);
		$failure_prediction_enabled = substr(strtok("\n"),28);
		$process_performance_data = substr(strtok("\n"),26);
		$obsess_over_host = substr(strtok("\n"),18);
		$last_update = strftime ("%b %d %Y %H:%M:%S", substr(strtok("\n"), 13));
		$is_flapping = substr(strtok("\n"),13);
		$percent_state_change = substr(strtok("\n"),22);
		$scheduled_downtime_depth = substr(strtok("\n"),26);


		# $last_notification = strftime ("%b %d %Y %H:%M:%S", strtok(';'));
	        $real_dur = time() - $last_state_change;
		#$duration = gmstrftime("%H:%M:%S", $real_dur);
		$duration = Duration::toString($real_dur);
		
		$last_state_change = strftime ("%b %d %Y %H:%M:%S", $last_state_change);
		$last_check = strftime ("%b %d %Y %H:%M:%S", $last_check);

		// Populate the huge array with the neccesary detail about the host. 
		$hlist[$host_name]["host"]["last_update"] = $last_update;
		$hlist[$host_name]["host"]["host_name"] = $host_name;
		$hlist[$host_name]["host"]["status"] = $status;
		$hlist[$host_name]["host"]["last_check"] = $last_check;
		$hlist[$host_name]["host"]["last_state_change"] = $last_state_change;
		$hlist[$host_name]["host"]["problem_has_been_acknowledged"] = $problem_has_been_acknowledged;
		$hlist[$host_name]["host"]["time_up"] = $time_up;
		$hlist[$host_name]["host"]["time_down"] = $time_down;
		$hlist[$host_name]["host"]["time_unreachable"] = $time_unreachable;
		$hlist[$host_name]["host"]["last_notification"] = $last_notification;
		$hlist[$host_name]["host"]["current_notification_number"] = $current_notification_number;
		$hlist[$host_name]["host"]["notifications_enabled"] = $notifications_enabled;
		$hlist[$host_name]["host"]["event_handler_enabled"] = $event_handler_enabled;
		$hlist[$host_name]["host"]["checks_enabled"] = $checks_enabled;
		$hlist[$host_name]["host"]["flap_detection_enabled"] = $flap_detection_enabled;
		$hlist[$host_name]["host"]["is_flapping"] = $is_flapping;
		$hlist[$host_name]["host"]["percent_state_change"] = $percent_state_change;
		$hlist[$host_name]["host"]["scheduled_downtime_depth"] = $scheduled_downtime_depth;
		$hlist[$host_name]["host"]["failure_prediction_enabled"] = $failure_prediction_enabled;
		$hlist[$host_name]["host"]["process_performance_data"] = $process_performance_data;
		$hlist[$host_name]["host"]["plugin_output"] = $plugin_output;

		$hlist[$host_name]["host"]["duration"] = $duration;
		$hlist[$host_name]["host"]["duration_in_secs"] = $real_dur;


	}

	function handle_service($buffer)
	// If the line coming in from the buffer is a service line, process it thusly. Enjoy some more hideous parsing. 
	{

		global $hlist;

		global $services_ok;
		global $services_warning;
		global $services_pending;
		global $services_critical;
		global $services_unknown;
		global $services_ack;

                $nothing = strtok($buffer,"\n");
		$host_name = substr(strtok("\n"),11);
		$description = substr(strtok("\n"),21);
		
		$modified_attributes = substr(strtok("\n"),21);
		$check_command = substr(strtok("\n"),15);

		$check_period = substr(strtok("\n"),13);
		$notification_period = substr(strtok("\n"),20);
		$check_interval = substr(strtok("\n"),15);
		$retry_interval = substr(strtok("\n"),15);

		$event_handler = substr(strtok("\n"),15);
		$has_been_checked = substr(strtok("\n"),18);
		$should_be_scheduled = substr(strtok("\n"),21);
		$execution_time = substr(strtok("\n"),22);
		$latency = substr(strtok("\n"),15);
		$check_type = substr(strtok("\n"),12);
		$status = substr(strtok("\n"),15);
		$last_hard_state = substr(strtok("\n"),17);

		$last_event_id = substr(strtok("\n"),14);
		$current_event_id = substr(strtok("\n"),18);
		$current_problem_id = substr(strtok("\n"),20);
		$last_problem_id = substr(strtok("\n"),17);

		$current_attempt = substr(strtok("\n"),17);
		$max_attempts = substr(strtok("\n"),14);

# If you have a slightly older version of Nagios you'll need to uncomment these. They were in the file twice in older versions. 
#		$last_event_id = substr(strtok("\n"),14);
#		$current_event_id = substr(strtok("\n"),18);

		$state_type =  substr(strtok("\n"),12);
		$last_state_change = substr(strtok("\n"),19);
		$last_hard_state_change = substr(strtok("\n"),24);
		$time_ok = substr(strtok("\n"),14);
		$time_warning = substr(strtok("\n"),19);
		$time_unknown = substr(strtok("\n"),19);
		$time_critical = substr(strtok("\n"),20);
		$plugin_output = substr(strtok("\n"),15);

		$long_plugin_output = substr(strtok("\n"),20);

		$performance_data = substr(strtok("\n"),18);
		$last_check = strftime ("%b %d %Y %H:%M:%S", substr(strtok("\n"),12));
		$next_check = substr(strtok("\n"),12);

		$check_options = substr(strtok("\n"),15);

		$current_notification_number = substr(strtok("\n"),29);

		$current_notification_id = substr(strtok("\n"),24);

		$last_notification = strftime("%b %d %Y %H:%M:%S", substr(strtok("\n"),19));
		$next_notification = substr(strtok("\n"),19);
		$no_more_notifications = substr(strtok("\n"),23);
		$notifications_enabled = substr(strtok("\n"),23);
		$checks_enabled = substr(strtok("\n"),23);
		$accept_passive_service_checks = substr(strtok("\n"),24);
		$event_handler_enabled = substr(strtok("\n"),23);
		$problem_has_been_acknowledged = substr(strtok("\n"),31);
		$acknowledgement_type = substr(strtok("\n"),22);
		$flap_detection_enabled = substr(strtok("\n"),24);
		$failure_prediction_enabled = substr(strtok("\n"),28);
		$process_performance_data = substr(strtok("\n"),26);
		$obsess_over_service = substr(strtok("\n"),21);
		$last_update = strftime ("%b %d %Y %H:%M:%S", substr(strtok("\n"),13));
		$is_flapping = substr(strtok("\n"),13);
		$percent_state_change = substr(strtok("\n"),22);
		$scheduled_downtime_depth = substr(strtok("\n"),26);

		$real_dur = time(0) - $last_state_change;
		$duration = Duration::toString($real_dur);
		
		$last_state_change = strftime ("%b %d %Y %H:%M:%S", $last_state_change);

		// Populate the wonderful array with all the details about this service. Also, increment the counters for the service status. 	
		if($status == "0") {
			$status = "OK";
			$services_ok++;
		} else if($status == "1") {
			$services_warning++;
			$status = "WARNING";
		} else if($status == "4") {
			$services_pending++;
		} else if($status == "2") {
			//the table status thing ignores critial for critical hosts, so why does the counter? 
			//it doesn't anymore!
			if ($hlist[$host_name]["host"]["status"] != "1") { 
				if (($problem_has_been_acknowledged != "1") && ($notifications_enabled == "1")) {
				$services_critical++; 
				} else {
				$services_ack++; } 
			}
			$status = "CRITICAL";
		} else if($status == "3") {
			$services_unknown++;
			$status = "Unknown";
		} else {
			$services_unknown++;
			$status = "Unknown";
		}


		if($status != "OK" && $status != "PENDING") { // Don't store the lengthy details for the OK or Pending services because that seems unneccesary
			
			$hlist[$host_name]["service"][$description]["last_update"] = $last_update;

			$hlist[$host_name]["service"][$description]["host_name"] = $host_name;
			$hlist[$host_name]["service"][$description]["description"] = $description;
			$hlist[$host_name]["service"][$description]["status"] = $status;
			$hlist[$host_name]["service"][$description]["current_attempt"] = $current_attempt;
			$hlist[$host_name]["service"][$description]["max_attempts"] = $max_attempts;
			$hlist[$host_name]["service"][$description]["state_type"] = $state_type;
			$hlist[$host_name]["service"][$description]["last_check"] = $last_check;
			$hlist[$host_name]["service"][$description]["next_check"] = $next_check;
			$hlist[$host_name]["service"][$description]["check_type"] = $check_type;
			$hlist[$host_name]["service"][$description]["checks_enabled"] = $checks_enabled;
			$hlist[$host_name]["service"][$description]["accept_passive_service_checks"] = $accept_passive_service_checks;
			$hlist[$host_name]["service"][$description]["event_handler_enabled"] = $event_handler_enabled;
			$hlist[$host_name]["service"][$description]["last_state_change"] = $last_state_change;
			$hlist[$host_name]["service"][$description]["problem_has_been_acknowledged"] = $problem_has_been_acknowledged;
			$hlist[$host_name]["service"][$description]["last_hard_state"] = $last_hard_state;
			$hlist[$host_name]["service"][$description]["time_ok"] = $time_ok;
			$hlist[$host_name]["service"][$description]["time_unknown"] = $time_unknown;
			$hlist[$host_name]["service"][$description]["time_warning"] = $time_warning;
			$hlist[$host_name]["service"][$description]["time_critical"] = $time_critical;
			$hlist[$host_name]["service"][$description]["last_notification"] = $last_notification;
			$hlist[$host_name]["service"][$description]["current_notification_number"] = $current_notification_number;
			$hlist[$host_name]["service"][$description]["notifications_enabled"] = $notifications_enabled;
			$hlist[$host_name]["service"][$description]["latency"] = $latency;
			$hlist[$host_name]["service"][$description]["execution_time"] = $execution_time;
			$hlist[$host_name]["service"][$description]["flap_detection_enabled"] = $flap_detection_enabled;
			$hlist[$host_name]["service"][$description]["is_flapping"] = $is_flapping;
			$hlist[$host_name]["service"][$description]["percent_state_change"] = $percent_state_change;
			$hlist[$host_name]["service"][$description]["scheduled_downtime_depth"] = $scheduled_downtime_depth;
			$hlist[$host_name]["service"][$description]["failure_prediction_enabled"] = $failure_prediction_enabled;
			$hlist[$host_name]["service"][$description]["process_performance_data"] = $process_performance_data;
			$hlist[$host_name]["service"][$description]["obsess_over_service"] = $obsess_over_service;
			$hlist[$host_name]["service"][$description]["plugin_output"] = $plugin_output;
			$hlist[$host_name]["service"][$description]["duration"] = $duration;
			$hlist[$host_name]["service"][$description]["duration_in_secs"] = $real_dur;
		}


	}

	function handle_line($buffer)
	// Take the line coming in from the buffer and work out what kind of line it is. Then send it to the relevant function for processing. 
	{
		if(strstr($buffer, "servicestatus {")) {
			handle_service($buffer);
		} else if(strstr($buffer, "hoststatus {")) {
			handle_host($buffer);
		} else if(strstr($buffer, "programstatus {")) {
			handle_program($buffer);
		}
	}

?>
