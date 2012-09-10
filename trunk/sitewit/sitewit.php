<?php

/**
 * @package SiteWit
 */

/*
Plugin Name: SiteWit
Plugin URI: http://www.sitewit.com
Description: SiteWit is the most advanced Pay Per Click (PPC) management software for your search engine marketing efforts. To get started: 1) Click the "Activate" link to the left of this description, 2) <a href="http://www.sitewit.com/Signup.asp">Sign up for a FREE SiteWit account</a>, and 3) Go to your <a href="plugins.php?page=sitewit-config">SiteWit configuration page</a>, and save your SiteWit tokens.
Version: 0.1
Author: SiteWit
Author URI: http://www.sitewit.com
*/

	 //require_once('nusoap.php');

	 $API_TOKEN_OPTION_NAME = 'sw_api_token';
	 $API_TOKEN_OPTION_DISPLAY_NAME = 'SiteWit API Token';
	 $USER_TOKEN_OPTION_NAME = 'sw_user_token';
	 $USER_TOKEN_OPTION_DISPLAY_NAME = 'SiteWit User Token';
	 $TRACKING_CODE_OPTION_NAME = 'sw_tracking_code';
	 $MASTERID_OPTION_NAME = 'sw_master_id';

	 $api_token = NULL;
	 $user_token	= NULL;
	 $tracking_code = NULL;

	 $mnow = date('omd');
	 $mbefore = date('omd', strtotime('-1 month'));
	 $mliteral = date('F n, Y', strtotime('-1 month')) . ' - ' . date('F n, Y');

	 if($_SERVER['REQUEST_METHOD'] == "POST" && $_POST["sw_source"] != null){
		$api_token = $_POST[$API_TOKEN_OPTION_NAME];
		$user_token = $_POST[$USER_TOKEN_OPTION_NAME];
		$action = $_POST["submit"];

		if($api_token != NULL && $user_token != NULL && ($action=="Save Tokens" || $action == "Link my SiteWit account" )){
			update_option($API_TOKEN_OPTION_NAME, $api_token);
			update_option($USER_TOKEN_OPTION_NAME, $user_token);
			$tracking_code = sw_get_tracking_code($api_token, $user_token);
		}else if($action=="Remove Tokens"){
			$api_token = null;
			$user_token = null;
			update_option($API_TOKEN_OPTION_NAME, "");
			update_option($USER_TOKEN_OPTION_NAME, "");
			$tracking_code = null;
		}

		update_option($TRACKING_CODE_OPTION_NAME, $tracking_code);



		//display success message when both token are entered and API returns results		/
		if($api_token != NULL && $user_token != NULL && $tracking_code != NULL){

			if($action == "Save Settings"){
				add_action('admin_notices', 'sw_api_success_notice');
			}else if($action == "Link my SiteWit account"){
				//display update confirmation
				add_action('admin_notices', 'sw_update_notice');
			}
		}

	 }
	 else{
		 $api_token = get_option($API_TOKEN_OPTION_NAME);
		 $user_token = get_option($USER_TOKEN_OPTION_NAME);
		 $tracking_code = get_option($TRACKING_CODE_OPTION_NAME);
	 }

	 //init the configuration page
	 add_action('admin_menu','sw_add_config_page');

	 //display the instructional message if one of the tokens is still missing
	 if($api_token == NULL || $user_token == NULL){
		 add_action('admin_notices', 'sw_notice');
	 }


	 if($api_token != NULL && $user_token != NULL){
		 //display warning message if both tokens are entered but API returns no result
		 if($tracking_code == NULL){
			add_action('admin_notices', 'sw_api_error_notice');
		 }
		 //hook onto Gravity forms if the the Gravity forms plugin is installed
		 add_action("gform_post_submission", "sw_gravity_form_submission", 10, 2);
	 }

	 //performed each time any Gravity form is submitted. The title of the form is used to create a new goal and register a conversion
	 //if the goal already exists, the existing goal is taken and converted
	 function sw_gravity_form_submission($entry, $form){
		 $api_token = get_option('sw_api_token');
		 $user_token = get_option('sw_user_token');
		 $form_title = $form["title"];
		 $goal = sw_get_goal($api_token, $user_token, $form_title);
		 if($goal == null){
			 //a new goal has to be created
			 //else an existing goal can be used for conversion
			 $goal = sw_create_goal($api_token, $user_token, $form_title);
		 }

		 ?>
		 <script type="text/javascript">
			var loc = (("https:" == document.location.protocol) ? "https://analytics." : "http://analytics.");
			document.write(unescape("%3Cscript src='" + loc + "sitewit.com/sw.js' type='text/javascript'%3E%3C/script%3E"));
		</script>
		 <script type="text/javascript">
			var sw = new _sw_analytics();
			sw.id= "<?php echo get_option('sw_master_id'); ?>";
			sw.set_goal(<?php echo $goal->GoalNumber; ?>);
			sw.register_page_view();
		 </script>

		 <?php
	 }

	 function sw_get_goal($api_token, $user_token, $form_title){
		 $res = null;
		 $params = array('AccountToken' => $api_token, 'UserToken' => $user_token, 'IncludeJSCode' => false);
		 $client	= new SoapClient('https://api.sitewit.com/account/accountinfo.asmx?WSDL');
		 $response = $client->GetGoals($params);
		 $goals = $response->GetGoalsResult->Goals->Goal;
		 if(is_array($goals)){
			foreach($goals as $goal){
					$goal_name = $goal->GoalName;
					if($form_title == $goal_name){
						$res = $goal;
					}
		 		}
		 }
		 else{
			$goal_name = $goals->GoalName;
			 if($form_title == $goal_name){
				$res = $goals;
			}
		 }
		 return $res;
	 }

	 function sw_create_goal($api_token, $user_token, $form_title){
		 $res = null;
		 $params = array('AccountToken' => $api_token, 'UserToken' => $user_token, 'IncludeJSCode' => false, 'GoalName' => $form_title, 'GoalRevenue' => 10.00, 'PageURL' => '', 'GoalType' => 'LeadGeneration', 'IncludeJSCode' => false);
		 $client	= new SoapClient('https://api.sitewit.com/account/accountinfo.asmx?WSDL');
		 $response = $client->CreateGoal($params);
		 $res = $response->CreateGoalResult;
		 return $res;
	 }


	 function sw_get_tracking_code($api_token, $user_token){
		 $tracking_code = null;
		 $getaccount_parameters = array("AccountToken" => $api_token, "UserToken" => $user_token);
		 if(PHP_MAJOR_VERSION >= 5){
			try{
				$client	= new SoapClient('https://api.sitewit.com/account/accountinfo.asmx?WSDL');
				$response = $client->GetAccountProperties($getaccount_parameters);
				$account_number = $response->GetAccountPropertiesResult->AccountNumber;
				update_option('sw_master_id', $account_number);
				$tracking_code = '<script type="text/javascript">var loc = (("https:" == document.location.protocol) ? "https://analytics." : "http://analytics.");document.write(unescape("%3Cscript src=\'" + loc + "sitewit.com/sw.js\' type=\'text/javascript\'%3E%3C/script%3E"));</script><script type="text/javascript">var sw = new _sw_analytics();sw.id="'.$account_number.'";sw.register_page_view();</script>';
			}
			catch(SoapFault $fault){
				//nothing left to do
			}
		 }
		 /*
		 else{
			//try NuSoap in case PHP5 not installed. Highly unlikely
			try{
				$client	= new nusoap_client('https://api.sitewit.com/account/accountinfo.asmx?WSDL','wsdl');
				$account = $client->call('GetAccount',$getaccount_parameters);
				$tracking_code = $account["GetAccountResult"]["JSCode"];
			}
			catch(Exception $e){
				//can't use SoapClient or NuSoap. Nothing left to do.
			}
		 }
		 */

		 return $tracking_code;

	 }


	 //SiteWit configuration page definition. Goes under the Plugins top menu
	 function sw_config_page(){
		 $SETUP_FINISHED = false;
?>

<style type="text/css">
.linkacct_button {
	-moz-box-shadow:inset 0px 1px 0px 0px #97c4fe;
	-webkit-box-shadow:inset 0px 1px 0px 0px #97c4fe;
	box-shadow:inset 0px 1px 0px 0px #97c4fe;
	background:-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #3d94f6), color-stop(1, #1e62d0) );
	background:-moz-linear-gradient( center top, #3d94f6 5%, #1e62d0 100% );
	filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#3d94f6', endColorstr='#1e62d0');
	background-color:#3d94f6;
	-moz-border-radius:30px !important;
	-webkit-border-radius:30px !important;
	border-radius:30px !important;
	border:1px solid #337fed;
	display:inline-block;
	color:#ffffff !important;
	font-family:Trebuchet MS;
	font-size:24px;
	font-weight:bold;
	padding:8px 18px;
	text-decoration:none;
	text-shadow:1px 1px 0px #1570cd;
}.linkacct_button:hover {
	background:-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #1e62d0), color-stop(1, #3d94f6) );
	background:-moz-linear-gradient( center top, #1e62d0 5%, #3d94f6 100% );
	filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#1e62d0', endColorstr='#3d94f6');
	background-color:#1e62d0;
}.linkacct_button:active {
	position:relative;
	top:1px;
}

    .swbutton {
        -moz-box-shadow:inset 0px 1px 0px 0px #fff;
        -webkit-box-shadow:inset 0px 1px 0px 0px #fff;
        box-shadow:inset 0px 1px 0px 0px #97c4fe!important;
        display: inline;
        background: #777;
        background-image: none !important;
        border: none;
        color: #fff  !important;
        cursor: pointer !important;
        font-weight: normal !important;
        border-radius: 30px !important;
        -moz-border-radius: 30px !important;
        -webkit-border-radius: 30px !important;
        text-shadow: 1px 1px 0 #333 !important;
        padding: 8px 20px !important;
        font-family:'Trebuchet MS', tahoma !important;
        font-size: 20pt !important;
        }
    .swbutton:hover {
        background-position: 0 -48px !important;
        }
    .swbutton:active {
        background-position: 0 top !important;
        position: relative !important;
        top: 1px !important;
        padding: 6px 10px 4px !important;
        }
    .swbutton.red { background-color: #e50000 !important; }
    .swbutton.purple { background-color: #9400bf !important; }
    .swbutton.green { background-color: #58aa00 !important; }
    .swbutton.orange { background-color: #ff9c00 !important; }
    .swbutton.blue { background-color: #1e62d0 !important; }
    .swbutton.black { background-color: #333 !important; }
    .swbutton.white { background-color: #fff !important; color: #000 !important; text-shadow: 1px 1px #fff !important; }
    .swbutton.small { font-size: 75% !important; padding: 3px 7px !important; }
    .swbutton.small:hover { background-position: 0 -50px !important; }
    .swbutton.small:active { padding: 4px 7px 2px !important; background-position: 0 top !important; }

</style>

	 <script type="text/javascript">
	 	function yesAccount(){
		 	document.getElementById("swFrame").src = "http://login.sitewit.com/plugins/wordpress";
		 	document.getElementById("yesAccountDesc").style.display = "inline";
		 	document.getElementById("noAccountDesc").style.display = "none";
		 	document.getElementById("divTokens").style.display = "inline";
		 	document.getElementById("divAccount").style.display = "none";
		}

		function noAccount(){
			document.getElementById("swFrame").src = "http://login.sitewit.com/auth/newaccount-wp.aspx";
		 	document.getElementById("yesAccountDesc").style.display = "none";
		 	document.getElementById("noAccountDesc").style.display = "inline";
		 	document.getElementById("divTokens").style.display = "inline";
		 	document.getElementById("divAccount").style.display = "none";
		}

		function toggleSettings(el){
			var divTokens = document.getElementById("divTokens");
			if(divTokens.style.display == "inline"){
				divTokens.style.display = "none";
				el.value = "Show Settings";
			}
			else{
				divTokens.style.display = "inline";
				el.value = "Hide Settings";
			}
		}
	 </script>
	 <div class="wrap">
		<h2>SiteWit</h2>
		<form method="post" action="">
		<input type="hidden" name="sw_source" id="sw_source" value="1" />
		<?php
			 if(get_option('sw_api_token') == null || get_option('sw_user_token') == null){
		?>
			<!-- IFrame -->

			<iframe id="swFrame" width="800px" height="520px" scrolling="no" seamless="seamless" src="http://login.sitewit.com/auth/newaccount-wp.aspx?u=<?php echo urlencode(bloginfo('url'))?>">
			</iframe>

			<script type="text/javascript" defer="defer">

				window.addEventListener('message', receiver, false);
				function receiver(e) {

					var data = e.data.split('####');
					var func = data[0];

					if(func=='tok'){
	      					var appToken = data[1];
						var usrToken = data[2];
						document.getElementById('sw_api_token').value = appToken;
						document.getElementById('sw_user_token').value = usrToken;

						if(document.getElementById("swFrame")){
							document.getElementById("divTokens").style.display='inline';
							document.getElementById("swFrame").height = '250px';
						}
					}else if(func=='sz'){
						var size = data[1];
						if(document.getElementById("swFrame")){
							document.getElementById("divTokens").style.display='none';
							document.getElementById("swFrame").height = size;
						}
					}

				}

				function getKeys(){
					document.getElementById('swFrame').contentWindow.postMessage(document.location.protocol + '//' + document.location.host,'http://login.sitewit.com');
				}

				_interval_keys = setInterval(getKeys,500);


			</script>

			<div id="divTokens" style="display:none;">
			 <table id="tokenTable" class="form-table" style="width: 800px;text-align:center;">
				 <tr>
					<td>
						<input type="hidden" id="sw_api_token" name="sw_api_token" value="<?php echo get_option('sw_api_token')?>" style="width:230px;"/>
						<input type="hidden" id="sw_user_token" name="sw_user_token" value="<?php echo get_option('sw_user_token')?>" style="width:230px;"/>
						<input type="submit" name="submit" value="Link my SiteWit account" class="swbutton blue large" style="color:#fff;padding:18px 30px !important;" />
					</td>
				</tr>
			 </table><br/>

			 </div>

			 <?php
				}
				else{
					$SETUP_FINISHED = true;
				}
			 ?>

			 <?php if($SETUP_FINISHED){ ?>
			 		<p><?php sitewit_my_traffic();?></p>
				<p>&nbsp;</p>
			 		<p><?php sitewit_my_campaigns();?></p>
				<p>&nbsp;</p>
				<p><?php sitewit_goal_function();?></p>
				<p>&nbsp;</p>
			 		<p><?php sitewit_seo_rankings();?></p>
				<p>&nbsp;</p>

				<input type="button" value="Show Settings" onclick="toggleSettings(this)" style="-webkit-border-radius: 4px; border-radius: 4px; border: 1px solid #AAA; padding: 5px; font-size: 10pt; font-weight: bold; color:#666; background: #E0E0E0 url(http://login.sitewit.com/css/images/layout/table_head_bg.png) repeat-x top left; cursor: pointer; text-shadow: 0 1px 0 white;"/>

				<div id="divTokens" style="display: none;">

				<table id="tokenTable" class="form-table" style="width: 400px; bgcolor:#cccccc;">
					<tr>
						<th scope="row" style="font-size:16px;font-weight:bold;width:230px; color:#464646;" >API Token:</th>
						<td><input type="text" id="sw_api_token" name="sw_api_token" value="<?php echo get_option('sw_api_token')?>" style="width:230px;"/></td>
					</tr>
					<tr>
						<th scope="row" style="font-size:16px;font-weight:bold;width:230px;color:#464646;">User Token:</th>
						<td><input type="text" id="sw_user_token" name="sw_user_token" value="<?php echo get_option('sw_user_token')?>" style="width:230px;"/></td>
					</tr>
					<tr>
						<td></td>
						<td>
							<input type="submit" name="submit" value="Save Tokens" style="-webkit-border-radius: 4px; border-radius: 4px; border: 1px solid #AAA; padding: 5px; font-size: 10pt; font-weight: bold; color:#666; background: #E0E0E0 url(http://login.sitewit.com/css/images/layout/table_head_bg.png) repeat-x top left; cursor: pointer; text-shadow: 0 1px 0 white;"/>
							&nbsp;
							<input type="submit" name="submit" value="Remove Tokens" style="-webkit-border-radius: 4px; border-radius: 4px; border: 1px solid #AAA; padding: 5px; font-size: 10pt; font-weight: bold; color:#666; background: #E0E0E0 url(http://login.sitewit.com/css/images/layout/table_head_bg.png) repeat-x top left; cursor: pointer; text-shadow: 0 1px 0 white;"/>
						</td>
					</tr>
				</table><br/>

				</div>

			 <?php } ?>


	 </div>


		</form>

<?php

	 }


	 function sw_add_config_page(){
	 	add_submenu_page('plugins.php',__('SiteWit'), __('SiteWit'), 'manage_options', 'sitewit-config','sw_config_page');
	 }


	function sw_print_tracking_code(){

		$val = get_option("sw_tracking_code");

		if($val != NULL){

		 echo $val;

		}

	 }

	 function sw_notice(){
		echo	 "<div id='sw-warning' class='updated fade'><p><strong>".__('SiteWit is almost ready.')."</strong> ".sprintf(__('You must <a href="%1$s">link your SiteWit account</a> in order for it to work.'), "plugins.php?page=sitewit-config")."</p></div>";
	 }

	 function sw_update_notice(){
		echo	 "<div id='sw-update-warning' class='updated fade'><p>".__('Your SiteWit account has been successfully linked.')."</p></div>";
	 }

	 function sw_api_error_notice(){
		echo	 "<div id='sw-update-warning' class='updated fade'><p>".__('Your SiteWit accoutn is linked, but the API has returned <strong>no result</strong>.')."</p></div>";
	 }

	 function sw_api_success_notice(){
		echo	 "<div id='sw-update-warning' class='updated fade'><p>".__('SiteWit has been set up successfully. <strong>You are all set!</strong>')."</p></div>";
	 }

	 //adds the settings link to the plugin action links
	 function sw_plugin_action_links( $links, $file ) {

		if ( $file == plugin_basename( dirname(__FILE__).'/sitewit.php' ) ) {
		$links[] = '<a href="plugins.php?page=sitewit-config">'.__('Settings').'</a>';
	}

	return $links;
}

// Create the function to output the contents of our Dashboard Widget

function sitewit_dashboard_widget_function() {
	// Display whatever it is you want to show
	//Check if API is correct
	 sitewit_my_traffic();
}

// Create the function use in the action hook

function sitewit_add_dashboard_widgets() {
	wp_add_dashboard_widget('sitewit_dashboard_widget', 'SiteWit', 'sitewit_dashboard_widget_function');
}

function sitewit_goal_function() {
	// MY GOALS
	global $mnow; global $mbefore;
	$api_token = get_option('sw_api_token');
	$user_token = get_option('sw_user_token');

	try {
		$client = new SoapClient('https://api.sitewit.com/reporting/goaldata.asmx?WSDL');
		$params = array('AccountToken' => $api_token, 'UserToken' => $user_token, 'StartDate' => $mbefore, 'EndDate' => $mnow);
		$response = $client->GetOverview($params);
		$goals = $response->GetOverviewResult->Goals->GoalSummary;

		if(is_array($goals)){
			foreach($goals as $goal){
				$goal_id[] = $goal->GoalID;
				$goal_name[] = $goal->GoalName;
				$goal_number[] = $goal->Goals;
				$goal_revenue[] = $goal->Revenue;
			}
	 	}

		echo '<h2>My Goals</h2>';
		echo '<table class="widefat"><thead><tr>';
		echo '<th>Name</th><th>Conversions</th><th>Revenue</th></tr></thead>';
		$count = 5; if ($count > count($goal_name)) {$count = count($goal_name);}

		for($num = 0; $num <= $count; $num++) {
			echo '<tr><td>'.$goal_name[$num].'</td>';
			echo '<td>'.$goal_number[$num].'</td>';
			echo '<td>'.number_format($goal_revenue[$num],2).'</td></tr>';
		}

		echo '</table>';

	}catch(SoapFault $fault){
		echo '<div>'.$fault.'</div>';
	}

}

//Specific Functions to call for displaying results...
function sitewit_seo_rankings(){
	// SEO RANKINGS
	$api_token = get_option('sw_api_token');
	$user_token = get_option('sw_user_token');

	try {
		$client = new SoapClient('https://api.sitewit.com/reporting/seorankingdata.asmx?WSDL');
		$params = array('AccountToken' => $api_token, 'UserToken' => $user_token);
		$response = $client->GetSEORankings($params);

		$goals = $response->GetSEORankingsResult->Google->SEORanking;

		 if(is_array($goals)){
			foreach($goals as $goal){
				$goal_search[] = $goal->SearchPhrase;
				$goal_rank[] = $goal->Rank;
				$goal_visitors[] = $goal->Visitors;
				$goal_visits[] = $goal->Visits;
				$goal_revenue[] = $goal->Revenue;
				$goal_conversion[] = round($goal->ConversionRate,2);
			 }
		 }

		echo '<h2>My SEO Rankings</h2>';
		echo '<table class="widefat"><thead><tr>';
		echo '<th width="50%">Search Phrase</th><th>Rank</th><th>Visitors*</th><th>Visits*</th><th>Revenue*</th><th>Conversion Rate*</th></tr></thead><tr>';
		$count = 5; if ($count > count($goal_search)) {$count = count($goal_search);}

		for($num = 0; $num <= $count; $num++) {
			//if ($num % 2 == 0) {echo '<tr>';} else {echo '<tr class="off">';}
			echo '<tr><td>'.$goal_search[$num].'</td>';
			echo '<td>'.$goal_rank[$num].'</td>';
			echo '<td>'.$goal_visitors[$num].'</td>';
			echo '<td>'.$goal_visits[$num].'</td>';
			echo '<td>'.number_format($goal_revenue[$num],2).'</td>';
			echo '<td>'.$goal_conversion[$num]."%".'</td></tr>';
		}
		echo '</tr></table>';

	}catch (Exception $e){}


}

function sitewit_my_traffic(){
	//MY TRAFFIC
	global $mnow; global $mbefore; global $mliteral;
	$api_token = get_option('sw_api_token');
	$user_token = get_option('sw_user_token');

	try{
		$client = new SoapClient('https://api.sitewit.com/reporting/trafficdata.asmx?WSDL');
		$params = array('AccountToken' => $api_token, 'UserToken' => $user_token, 'StartDate' => $mbefore, 'EndDate' => $mnow);
		$response = $client->GetOverview($params);
		$goals = $response->GetOverviewResult->Data->TrafficType;
				 if(is_array($goals)){
					 foreach($goals as $goal){
						$goal_source[] = $goal->Description;
						$goal_visitors[] = $goal->Visitors;
						$goal_visits[] = $goal->Visits;
						$goal_revenue[] = $goal->Revenue;
						$goal_profit[] = $goal->Profit;
						$goal_roi[] = round($goal->ROI,2);
						$goal_conversion[] = round($goal->ConversionRate,2);
					 }
				 }
		echo '<h2>My Visitors by arrival source and first visit date <span style="font-size: 14px; color: #111;">('.$mliteral.')</span></h2>';
		echo '<table class="widefat"><thead><tr>';
		echo '<th>Source</th><th>Visitors</th><th>Visits</th><th>Revenue</th><th>Profit</th><th>ROI</th><th>Conversion rate</th></tr></thead><tr>';
		$count = 5; if ($count > count($goal_source)) {$count = count($goal_source);}
			for($num = 0; $num <= $count; $num++) {
				echo '<tr><td><strong>'.$goal_source[$num].'</strong></td>';
				echo '<td>'.$goal_visitors[$num].'</td>';
				echo '<td>'.$goal_visits[$num].'</td>';
				echo '<td>'.number_format($goal_revenue[$num],2).'</td>';
				echo '<td>'.number_format($goal_profit[$num],2).'</td>';
				echo '<td>'.$goal_roi[$num].'%</td>';
				echo '<td>'.$goal_conversion[$num].'%</td></tr>';
			}
		echo '</tr></table>';
	}catch(Exception $e){}
}

function sitewit_my_campaigns(){
	// MY CAMPAIGNS
	global $mnow; global $mbefore;
	$api_token = get_option('sw_api_token');
	$user_token = get_option('sw_user_token');

	try{
		$client = new SoapClient('https://api.sitewit.com/reporting/campaigndata.asmx?WSDL');
		$params = array('AccountToken' => $api_token, 'UserToken' => $user_token, 'StartDate' => $mbefore, 'EndDate' => $mnow);
		$response = $client->GetOverview($params);
		$goals = $response->GetOverviewResult;
		echo '<h2>My Campaigns</h2>';
		echo '<table class="widefat"><thead><tr>';
		echo '<th>Total Clicks</th><th>Total Goals</th><th>Total Conversion</th><th>Total Revenue</th><th>Total Cost</th><th>Total ROI</th></tr></thead><tr>';
		$count = 5; if ($count > count($goal_search)) {$count = count($goal_search);}
			for($num = 0; $num <= $count; $num++) {
				echo '<tr><td>'.$goals->Clicks.'</td>';
				echo '<td>'.$goals->Goals.'</td>';
				echo '<td>'.round($goals->ConversionRate,2).'%</td>';
				echo '<td>'.number_format($goals->Revenue,2).'</td>';
				echo '<td>'.number_format($goals->Cost,2).'</td>';
				echo '<td>'.round($goals->ROI,2).'%</td></tr>';
			}
		echo '</tr></table>';
	}catch(Exception $e){}
}

// Hook into the 'wp_dashboard_setup' action to register our other functions
add_action('wp_dashboard_setup', 'sitewit_add_dashboard_widgets' );
add_action('wp_footer', 'sw_print_tracking_code');
add_filter( 'plugin_action_links', 'sw_plugin_action_links', 10, 2 );

?>
