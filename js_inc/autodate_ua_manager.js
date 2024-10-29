jQuery(document).ready(function($){
	function createListRow(tableRow,dataSlice){
		let shortCode;
		dataSlice['id']+="";
		if (dataSlice['id'].match(/^new/)==null){
			shortCode="[autodate id='"+dataSlice['id']+"']";
		}
		else {
			shortCode=messages["shortCode"];
		}
		$(tableRow)
			.append("<td><label><input type='radio' name='is_running_radio_"+dataSlice['id']+"' value='enb'"+dataSlice['enbFlag']+">"+messages["flagEnabled"]+"</label><br><label><input type='radio' name='is_running_radio_"+dataSlice['id']+"' value='dsb'"+dataSlice['dsbFlag']+">"+messages["flagDisabled"]+"</label></td>")
			.append("<td class='short_code_cell'>"+shortCode+"</td>")
			.append("<td><input type='text' class='regular_text' name='short_desc_"+dataSlice['id']+"' value='"+dataSlice['shortDesc']+"'></td>")
			.append("<td><input type='date' name='target_date_"+dataSlice['id']+"' value='"+dataSlice['tdValue']+"'></td>")
			.append("<td><input name='off_target_"+dataSlice['id']+"' type='number' step='1' min='1' value='"+dataSlice['toIntl']+"' class='small-text'></td>")
			
			.append("<td><input type='date' name='upd_date_"+dataSlice['id']+"' value='"+dataSlice['udValue']+"'></td>")
			.append("<td><input name='int_per_update_"+dataSlice['id']+"' type='number' step='1' min='1' value='"+dataSlice['uiIntl']+"' class='small-text'></td>")
			
			.append("<td><span class='button button_primary save_interval_change' data-target-id='"+dataSlice['id']+"'>"+messages['saveButton']+"</span></td>")
			.append("<td><span class='button button_primary delete_interval_button' data-target-id='"+dataSlice['id']+"' title='"+messages['deleteButtonTip']+"'>X</span></td>")
	}
	
	function attachSaveButtonEvent(btn){
		$(btn).click(function(){
			let sp_class="special_wpdu_row_attention";
			
			let uId=$(this).attr("data-target-id");
			let shortDesc=$("[name=short_desc_"+uId+"]").val();
			let updDate=$("[name=upd_date_"+uId+"]").val();
			let uiIntl=$("[name=int_per_update_"+uId+"]").val();
			let tdValue=$("[name=target_date_"+uId+"]").val();
			let toIntl=$("[name=off_target_"+uId+"]").val();
			let runningFlag=$("[name=is_running_radio_"+uId+"]:checked").val();
			
			let outgoingData={
				action:"autodate_manager",
				id:uId,
				short_desc:shortDesc,
				update_date:updDate,
				update_interval:uiIntl,
				target_date:tdValue,
				target_offset:toIntl,
				is_running:runningFlag
			}
			
			let parentRow=$(this).parentsUntil("tr").eq(-1).parent("tr");
			$(parentRow).addClass(sp_class);
			
			$.post( ajaxurl, outgoingData, function( response ){
				let rqst_results=response.split("-|-");
				
				if (rqst_results[0]!="none" && rqst_results[0]!="old"){
					$(parentRow).find("input").each(function(){
						let nameVal=$(this).attr("name");
						nameVal=nameVal.replace(/new[0-9]+/,rqst_results[0]);
						$(this).attr("name",nameVal);
					});
					let short_code='[autodate id="'+rqst_results[0]+'"]';
					$(parentRow).find("td.short_code_cell").text(short_code);
					$(parentRow).find(".delete_interval_button").attr("data-target-id",rqst_results[0]);
					$(btn).attr("data-target-id",rqst_results[0]);
				}
				alert( rqst_results[1] );
				setTimeout(function(){ $(parentRow).removeClass(sp_class); },5000);
			});
		});
	}
	
	function attachDeleteButtonEvent(btn){
		$(btn).click(function(){
			let sp_class="special_wpdu_row_attention";
			let parentRow=$(this).parentsUntil("tr").eq(-1).parent("tr");
			$(parentRow).addClass(sp_class);
			
			let answer=confirm(messages["confirmQuestion"]);
			if (answer===false){
				return false;
			}
			
			let uId=$(this).attr("data-target-id"); 
			
			if (uId.match(/^new/)==null){
				let outgoingData={
					action:"autodate_delete_manager",
					id:uId
				};
				$.post( ajaxurl, outgoingData, function( response ){
					if (response=="data corrupted" || response=="idle request"){
						alert(messages["deleteFailMsg"]);
						setTimeout(function(){ $(parentRow).removeClass(sp_class); },5000);
					}
					else {
						alert(messages["deleteSuccessMsg"]);
						$(parentRow).remove();
					}
				});
			}
			else {
				$(parentRow).remove();	
				alert(messages["deleteSuccessMsg"]);
			}
			
		});
	}
	
	for (a=0;a<dateListData.length;a++){
		$("#date_intervals_list").append("<tr class='dates_list_row'></tr>");
		let targetRow=$("#date_intervals_list").find("tr.dates_list_row:last-child");
		createListRow(targetRow,dateListData[a]);
		
		let saveButton=$("#date_intervals_list").find("tr.dates_list_row:last-child").find(".save_interval_change");
		attachSaveButtonEvent(saveButton);
		let deleteButton=$("#date_intervals_list").find("tr.dates_list_row:last-child").find(".delete_interval_button");
		attachDeleteButtonEvent(deleteButton);
	}
	
	$("#add_interval_date").click(function(){
		let answer=confirm(messages["questionForAdd"]);
		if (answer===false){
			return false;
		}
		
		let counter=$(this).attr("data-new-counter");
		let tempID="new"+counter;
		counter=parseInt(counter);
		counter++;
		$(this).attr("data-new-counter",counter);
		
		$("#date_intervals_list").append("<tr class='dates_list_row'></tr>");
		targetRow=$("#date_intervals_list").find("tr.dates_list_row:last-child");
		
		let rowElemsOptions={
			id:tempID,
			shortDesc:"",
			enbFlag:"",
			dsbFlag:"checked='checked'",
			udValue:currentDate,
			uiIntl:1,
			tdValue:currentDate,
			toIntl:1
		}
		
		createListRow(targetRow,rowElemsOptions);
		let saveButton=$("#date_intervals_list").find("tr.dates_list_row:last-child").find(".save_interval_change");
		attachSaveButtonEvent(saveButton);		
		let deleteButton=$("#date_intervals_list").find("tr.dates_list_row:last-child").find(".delete_interval_button");
		attachDeleteButtonEvent(deleteButton);
	});
	
	$(".sidebar-name").click(function(){
		$(this).parentsUntil(".widgets-holder-wrap").eq(0).parent(".widgets-holder-wrap").toggleClass("closed");
	});
});