<?php
class Autodate {	
	protected const JS_HANDLER_FILE="../js_inc/autodate_ua_manager.js";	
	protected const DB_TABLE_POSTFIX="autodate_data";
	protected const AJAX_ANSWER_SEPARATOR="-|-";
	protected const SHORTCODE_NAME="autodate";
	
	public const ACTIV_DEACTIV_DATA=array(
		"activation"=>array(false,"start"),
		"deactivation"=>array(false,"stop")
	);
	
	public const ACT_AND_FUNC_DATA=array(
		"menu"=>array("admin_menu","intergrate"),
		"ajax_main"=>array("wp_ajax_autodate_manager","manager_callback"),
		"ajax_delete"=>array("wp_ajax_autodate_delete_manager","manager_delete_callback"),
		"js_add"=>array("admin_enqueue_scripts","manager_javascript")
	);
	
	public const SHORTCODE_DATA=array(self::SHORTCODE_NAME,"handle_shortcode");
	
	protected $js_handler_file_path;
	static public $wpdb_obj;
	static public $table_name;
	static public $date_format;
	
	
	public function __construct(){
		global $wpdb;
		self::$wpdb_obj=$wpdb;
		self::$table_name=self::$wpdb_obj->get_blog_prefix().self::DB_TABLE_POSTFIX;
		
		$this->js_handler_file_path=plugins_url(self::JS_HANDLER_FILE,__FILE__);
		
		self::$date_format=get_option("date_format","Y-m-d");
	}
	
	public function start(){
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = "DEFAULT CHARACTER SET ".self::$wpdb_obj->charset." COLLATE ".self::$wpdb_obj->collate;
		
		$sql="CREATE TABLE IF NOT EXISTS `".self::$table_name."` (
`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
`short_desc` varchar(255) DEFAULT NULL,
`target_date` varchar(10) DEFAULT NULL,
`target_offset` smallint(5) unsigned DEFAULT NULL,
`update_date` varchar(10) DEFAULT NULL,
`update_interval` smallint(5) unsigned DEFAULT NULL,
`is_running` tinyint(1) unsigned NOT NULL DEFAULT '0',
PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 {$charset_collate}";
		
		dbDelta($sql);
	}
	
	public function stop(){ return true; }
	
	public function intergrate(){
		add_menu_page(__('Autodate management','autodate'), __('Autodate','autodate'), 'manage_options','autodate',array($this,"create_admin_page"));
	}
	
	public function manager_javascript(){
		wp_enqueue_script("autodate_ajax_js",$this->js_handler_file_path,array('jquery'),false,true);
	}
	
	public function manager_delete_callback(){
		if(isset($_POST['id'])===false){
			echo "data corrupted";
			wp_die();
		}
		
		$result=self::$wpdb_obj->delete(self::$table_name,array("id"=>(int)$_POST['id']), array("%d") );
		
		if ($result<1){
			echo "idle request";
		}
		else {
			echo "success";
		}
		
		wp_die();
	}
	
	public function manager_callback(){		
		if(isset($_POST['id'])===false || isset($_POST['short_desc'])===false || isset($_POST['update_date'])===false || isset($_POST['update_interval'])===false || isset($_POST['target_date'])===false || isset($_POST['target_offset'])===false || isset($_POST['is_running'])===false){
			echo "none-|-".__("An error occurred while transferring data.\n Refresh the Autodate page and try changing the data again",'autodate');
			wp_die();
		}
		
		$data_pattern=array(
			"short_desc"=>array(
				"type"=>"simple_str",
				"db_type"=>"%s",
				"db_col_name"=>"short_desc",
				"error"=>__("Failed to update data for field *Short description*",'autodate')
			),
			"update_date"=>array(
				"type"=>"date_str",
				"db_type"=>"%s",
				"db_col_name"=>"update_date",
				"error"=>__("Failed to update data for field *When to update the date*",'autodate')
			),
			"update_interval"=>array(
				"type"=>"interval_num",
				"db_type"=>"%d",
				"db_col_name"=>"update_interval",
				"error"=>__("Failed to update data for field *Update frequency*",'autodate')
			),
			"target_date"=>array(
				"type"=>"date_str",
				"db_type"=>"%s",
				"db_col_name"=>"target_date",
				"error"=>__("Failed to update data for field *Displayed date*",'autodate')
			),
			"target_offset"=>array(
				"type"=>"interval_num",
				"db_type"=>"%d",
				"db_col_name"=>"target_offset",
				"error"=>__("Failed to update data for field *How much to shift the date*",'autodate')
			),
			"is_running"=>array(
				"type"=>"bool_num",
				"db_type"=>"%d",
				"db_col_name"=>"is_running",
				"error"=>__("Failed to update the state of switch *Switched on/Switched off*",'autodate')
			)
			
		);
		
		$data_arr=array();
		$data_format_arr=array();
		$data_err=array();
		
		foreach($data_pattern as $field=>$settings){
			switch($settings['type']){
				case "simple_str":
					$_POST[$field]=sanitize_text_field($_POST[$field]);
					if(strlen($_POST[$field])>0){
						$data_arr[$settings['db_col_name']]=$_POST[$field];
						array_push($data_format_arr,$settings['db_type']);
					}
					else {
						array_push($data_err,$settings['error']);
					}
				break;
				case "date_str":
					$_POST[$field]=sanitize_text_field($_POST[$field]);
					$preg_result=preg_match("/^[0-9]{4}\-[0-9]{1,2}\-[0-9]{1,2}$/",$_POST[$field]);
					if($preg_result!==false || $preg_result>0){
						$data_arr[$settings['db_col_name']]=$_POST[$field];
						array_push($data_format_arr,$settings['db_type']);
					}
					else {
						array_push($data_err,$settings['error']);
					}
				break;
				case "interval_num":
					$_POST[$field]=(int)sanitize_text_field($_POST[$field]);
					if ($_POST[$field]>0){
						$data_arr[$settings['db_col_name']]=$_POST[$field];
						array_push($data_format_arr,$settings['db_type']);
					}
					else {
						array_push($data_err,$settings['error']);
					}
				break;
				case "bool_num":
					$_POST[$field]=sanitize_text_field($_POST[$field]);					
					if($_POST[$field]=="enb"){
						$data_arr[$settings['db_col_name']]=1;
						array_push($data_format_arr,$settings['db_type']);
					}
					else if($_POST[$field]=="dsb") {
						$data_arr[$settings['db_col_name']]=0;
						array_push($data_format_arr,$settings['db_type']);
					}
					else {
						array_push($data_err,$settings['error']);
					}
				break;
			}
		}
		
		$_POST["id"]=sanitize_text_field($_POST["id"]);
		$preg_result=preg_match("/^new/",$_POST["id"]);
		if ($preg_result===false || $preg_result<1){
			$action="update";
			$result=self::$wpdb_obj->update(self::$table_name,$data_arr,array("id"=>(int)$_POST['id']),$data_format_arr,array("%d"));
		}
		else {
			$action="insert";
			$result=self::$wpdb_obj->insert(self::$table_name,$data_arr,$data_format_arr);
		}
		
		if (
		(is_bool($result)===false && $result>0)
		||
		(is_bool($result)===true && $result===true)
		){
			if($action=="insert"){
				$sql="SELECT max(`id`) as last_id FROM `".self::$table_name."`;";
				$last_id_rqst_result=self::$wpdb_obj->get_results($sql,ARRAY_A);
				
				if($last_id_rqst_result==NULL || count($last_id_rqst_result)<1){
					echo "none-|-".__("An error occured while saving your changes.\n Refresh the plugin page",'autodate');
				}
				else {
					echo esc_attr($last_id_rqst_result[0]['last_id'])."-|-".__("New date information has been saved",'autodate');
				}
			}
			else {
				echo "old-|-".__("Data changes saved",'autodate');
			}
		}
		else {
			if($action=="update"){
				echo "old-|-".__("Data changes saved",'autodate');
			}
			else {
				echo "none-|-".__("An error occurred while saving data.\n Refresh the Autodate page and try changing the data again",'autodate');
			}
		}
		
		if (empty($data_err)===false){
			echo "\n--------------------------------\n";
			for ($a=0,$b=count($data_err);$a<$b;$a++){
				echo "\n".esc_attr($data_err[$a]);
			}
		}

		wp_die();
	}
	
	public function handle_shortcode($atts){
		$atts=shortcode_atts(array("id"=>1),$atts);
		
		$sql="SELECT `id`,`target_date` as td,`target_offset` as toff,`update_date` as ud,`update_interval` as ui,`is_running` as ir FROM `".self::$table_name."` WHERE `id`={$atts['id']}";
		
		$target_date=self::$wpdb_obj->get_results($sql,ARRAY_A);
		if ($target_date==NULL || $target_date[0]['td']==NULL || $target_date[0]['toff']==NULL || $target_date[0]['ud']==NULL || $target_date[0]['ui']==NULL || $target_date[0]['ir']==NULL || $target_date[0]['ir']==0){
			return "";
		}
		
		$target_date[0]=self::audit_date_data($target_date[0]);
		$returned_date=new DateTime($target_date[0]['td']);
			
		return esc_attr($returned_date->format(self::$date_format));
	}
	
	public static function audit_date_data($dataSlice){
		$current_time=new DateTime(current_time("Y-m-d"));
		$update_time=new DateTime($dataSlice['ud']);
		
		$time_delta=$current_time->diff($update_time);
		if ($time_delta->invert>0 || ($time_delta->invert<1 && $time_delta->days==0)){
			$target_time=new DateTime($dataSlice['td']);
			
			if ($time_delta->days%$dataSlice['ui']==0){
				$additional_num=1;
			}
			else {
				$additional_num=0;
			}
			$interval_factor=ceil($time_delta->days/$dataSlice['ui'])+$additional_num;
		
			$update_date_offset=$interval_factor*$dataSlice['ui']; 
			$target_date_offset=$interval_factor*$dataSlice['toff']; 
			$update_span=new DateInterval("P{$update_date_offset}D");
			$target_span=new DateInterval("P{$target_date_offset}D");
			
			$update_time->add($update_span);
			$target_time->add($target_span);
			$temp_ud=$update_time->format("Y-m-d");
			$temp_ti=$target_time->format("Y-m-d");
			
			$result=self::$wpdb_obj->update(
				self::$table_name,
				array("target_date"=>$temp_ti,"update_date"=>$temp_ud),
				array("id"=>(int)$dataSlice['id']),
				array("%s","%s"),
				array("%d")
			);
			if($result!==false && $result>0){
				$dataSlice['ud']=$update_time->format("Y-m-d");
				$dataSlice['td']=$target_time->format("Y-m-d");
			}
			return $dataSlice;
		}
		else {
			return $dataSlice;
		}
	}
	
	public function create_admin_page(){
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.','autodate' ) );
		}
		$sql="SELECT * FROM `".self::$table_name."`";
		$units = self::$wpdb_obj->get_results($sql,ARRAY_A);
			
		$data_js_arr="let dateListData=[";
		for($a=0,$b=count($units);$a<$b;$a++){
			if ($units[$a]['target_date']!=NULL && $units[$a]['target_offset']!=NULL && $units[$a]['update_date']!=NULL && $units[$a]['update_interval']!=NULL){
				$units[$a]['td']=&$units[$a]['target_date'];
				$units[$a]['toff']=&$units[$a]['target_offset'];
				$units[$a]['ud']=&$units[$a]['update_date'];
				$units[$a]['ui']=&$units[$a]['update_interval'];
				$units[$a]=self::audit_date_data($units[$a]);
			}
			
			$data_js_arr.="{id:{$units[$a]['id']},shortDesc:'{$units[$a]['short_desc']}'";
			if($units[$a]['is_running']==NULL || $units[$a]['is_running']<1){
				$enb_flag="";
				$dsb_flag=" checked=\'checked\'";
			}
			else {
				$enb_flag=" checked=\'checked\'";
				$dsb_flag="";
			}
			$data_js_arr.=",enbFlag:'{$enb_flag}',dsbFlag:'{$dsb_flag}'";
				
			if($units[$a]['update_date']==NULL){
				$ud_value=current_time("Y-m-d");
			}
			else {
				$ud_value=$units[$a]['update_date'];
			}
			$data_js_arr.=",udValue:'{$ud_value}'";
				
			if($units[$a]['update_interval']==NULL){
				$ui_intl=1;
			}
			else {
				$ui_intl=$units[$a]['update_interval'];
			}
			$data_js_arr.=",uiIntl:{$ui_intl}";
				
			if($units[$a]['target_date']==NULL){
				$td_value=current_time("Y-m-d");
			}
			else {
				$td_value=$units[$a]['target_date'];
			}
			$data_js_arr.=",tdValue:'{$td_value}'";
				
			if($units[$a]['target_offset']==NULL){
				$to_intl=1;
			}
			else {
				$to_intl=$units[$a]['target_offset'];
			}
			$data_js_arr.=",toIntl:{$to_intl}},";
		}
		$data_js_arr.="];";
		$data_js_arr.="\nlet currentDate='".current_time("Y-m-d")."';";
		$data_js_arr.="\nlet messages={shortCode:'".__("does not exist",'autodate')."',flagEnabled:'".__("Switched on",'autodate')."',flagDisabled:'".__("Switched off",'autodate')."',saveButton:'".__("Save changes",'autodate')."',deleteButtonTip:'".__("Delete this data",'autodate')."',confirmQuestion:'".__("Are you sure you want to delete this data?",'autodate')."',deleteFailMsg:'".__("Data has not been deleted!\\n Refresh the Autodate page and try again",'autodate')."',deleteSuccessMsg:'".__("Data deleted successfully",'autodate')."',questionForAdd:'".__("Want to add new data?",'autodate')."'};";
		wp_add_inline_script("autodate_ajax_js",$data_js_arr);
		wp_register_style( 'autodate_custom_style', false );
		wp_enqueue_style('autodate_custom_style');
		wp_add_inline_style("autodate_custom_style","tr.special_wpdu_row_attention{outline:2px solid #faa;} .wp-core-ui .delete_interval_button {color: #f00; border-color: #f00;} table.tips-list {box-shadow: none; background-color: transparent; border: none;} table.tips-list tr.tips-row { background-color: transparent; } table.tips-list col { width:40%; } table.tips-list .widgets-holder-wrap { padding:8px; } .autodate-unordered-list{ margin-left: 3%; }");
		echo "<h1>".__("Autodate management",'autodate')."</h1><p>".__("On this plugin page you can: manage automatically updated dates, create new ones, delete existing ones",'autodate')."</p>";
		echo "<div class='wrap'>
	<table class='wp-list-table widefat striped tips-list'>
		<col>
		<col>
		<tr class='tips-row'>
			<td>
				<div class='widgets-holder-wrap closed' style=''>
					<div class='widgets-sortables ui-droppable'>
						<div class='sidebar-name'>
							<button type='button' class='handlediv hide-if-no-js' aria-expanded='true'>
								<span class='screen-reader-text'>".__("Autodate usage hint",'autodate')."</span>
								<span class='toggle-indicator'></span>
							</button>
							<h2>".__("How to use Autodate",'autodate')." <span class='spinner'></span></h2>
						</div>
						<div class='description'>
							<p>".__("To use the plugin, you need to take a few steps:",'autodate')."</p>
							<ol>
								<li>".__("Create new date (button <strong>Add date</strong>)",'autodate')."</li>
								<li>".__("Customize the date:",'autodate')."
									<ol>
										<li>".__("Specify the value of the <strong>Displayed date</strong>",'autodate')." </li>
										<li>".__("Specify the value of the <strong>How much to shift the date</strong>",'autodate')."</li>
										<li>".__("Specify the value of the <strong>When to update the date</strong>",'autodate')."</li>
										<li>".__("Specify the value of the <strong>Update frequency</strong>",'autodate')."</li>
										<li>".__("For further convenience of editing the date, it is recommended to specify a value for the <strong>short description</strong>",'autodate')."</li>
									</ol>
								</li>
								<li>".__("Switch on date (<strong>Switched on/Switched off</strong>)<br><em>Please note that if the target date is <strong>switched off</strong>, then it will not be displayed on the site, BUT it will be updated in accordance with the specified settings.</em>",'autodate')."</li>
								<li>".__("Save the date (button <strong>Save changes</strong>)",'autodate')."</li>
								<li>".__("Copy the generated <strong>shortcode</strong>",'autodate')."</li>
								<li>".__("Add <strong>shortcode</strong> to post or page content<br><em>Please note that the date will be displayed in the format specified in your Wordpress settings ( <strong>Settings</strong> --> <strong>General</strong> --> item <strong>Date format</strong> )</em><br>P.S. Please note that the <strong style='color: #f00;'>Autodate only works with the date</strong>, data that determine the time (hours, minutes, seconds) are not taken into account.",'autodate')."</li>
							</ol>
							<p>".__("You can also:",'autodate')."</p>
							<ul class='autodate-unordered-list'>
								<li>".__("Delete previously created dates (red button <strong>X</strong>)",'autodate')."</li>
								<li>".__("Edit settings of previously created dates (Remember to save your changes - button <strong>Save changes</strong>)",'autodate')."</li>
							</ul>
						</div>
					</div>
				</div>
			</td>
			<td>
				<div class='widgets-holder-wrap closed'>
					<div class='widgets-sortables ui-droppable'>
						<div class='sidebar-name'>
							<button type='button' class='handlediv hide-if-no-js' aria-expanded='true'>
								<span class='screen-reader-text'>".__("How to manage date settings",'autodate')."</span>
								<span class='toggle-indicator'></span>
							</button>
							<h2>".__("How to manage date settings",'autodate')." <span class='spinner'></span></h2>
						</div>
						<div class='description'>
							<p>".__("Let's say the Autodate settings fields contain the following values:",'autodate')."</p><ul class='autodate-unordered-list'><li>".__("<strong>Displayed date:</strong> 15.01.2021",'autodate')."</li><li><strong>".__("How much to shift the date",'autodate')."</strong>: 10</li><li>".__("<strong>When to update the date:</strong> 13.01.2021",'autodate')."</li><li><strong>".__("Update frequency",'autodate')."</strong>: 10</li></ul><p>".__("This means that until <strong>13.01.2021</strong> the website will display the date <strong>15.01.2021</strong>.<br>From <strong>13.01.2021</strong> to <strong>22.01.2021</strong> <em>(13.01 + 10 is the date of the next update)</em> the date on the website will be as follows: <strong>25.01.2021</strong> <em>(15.01 + 10 is the date shift)</em>",'autodate')."</p>
						</div>
					</div>
				</div>
			</td>
		</tr>
	</table>
</div> ";
		echo "<div class='wrap'><table class='wp-list-table widefat striped table-view-list' id='date_intervals_list'>\n<thead><tr>".__("<th>Switch on/Switch off </th><th>Shortcode</th><th>Short description</th><th>Displayed date</th><th>How much to shift the date</th><th>When to update the date</th><th>Update frequency</th>",'autodate')."<th></th><th></th></tr></thead></table><br><span class='button button_primary' data-new-counter='0' id='add_interval_date'>".__("Add date",'autodate')."</span></div>";		
	}
}