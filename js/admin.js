// ADMIN JS HERE

///////////////////////HANDLER FOR REQUIRED FIELDS ON LOGIN//////////////////////////////////////////////
$(function() {
  componentHandler.registerUpgradedCallback('MaterialTextfield', function(textfield) {
	var input = $(textfield).find('.mdl-textfield__input');
	if (input.data('required') != null) input.attr('required', true);
  });
});

///////////////////////EXPANDING DRAWER ON LOAD//////////////////////////////////////////////
$(document).ready(function(){
	
	//update existing mapping sfdc type
	$(".mapping-select").each(function(){
		var opt = $(this).find("option:selected");
		var sfdc_type = opt.data("type");
		if(typeof sfdc_type!='undefined'){
			var sfdc_restricted = opt.data("restricted"); 
			var cell = $(this).parent().siblings(".sfdc_type");
			var text = sfdc_type.toUpperCase();
			cell.text(text);
		}
	})
})


//loader for ajax requests
$(document).bind('ajaxStart',function () {
	//ajax request went so show the loading image
	showDialog({
		text: '<div id="ajax-loader" class="mdl-progress mdl-js-progress mdl-progress__indeterminate"><br></div>'
	})
}).bind('ajaxStop',function () {
});


//make select fields editable upon the .edit click
$(document).on("click", "#settings-edit", function () {
	 $(this).closest('tr').find('select').prop("disabled", false);
	 $(this).hide();
});

///////////////////////ADD NEW FOR MEMBER TABLES ////////////////////////////////////////////

$(".add-new").bind("click", function(e){
	e.preventDefault();
	var parent_table = $(this).closest('form').find('.mdl-data-table');
	var rnd_id = 'member_new_'+Math.random().toString(36).substring(7);
	$('.member-new').first().clone().attr('id',rnd_id).appendTo(parent_table);
});

//bind the remove action to any new row of a member attribute
$(document).on('click', '.remove-new', function(e){
	e.preventDefault();
	if($('.remove-new').length>1){
		$('.remove-new').last().closest('tr').remove();
	}
});

///////////////////////TABLE TOGGLE FOR MAPPING//////////////////////////////////////////////

$(".table-toggle").click(function(e) {
	e.preventDefault();
	$(this).closest("form").find(".mdl-data-table").toggle();
	$(this).siblings(".mdl-button").toggle();
	$(this).find(".button-text").text(function(i, text){
          return text === "Expand" ? "Collapse" : "Expand";
    })
	$(this).find(".button-icon").text(function(i, text){
          return text === "keyboard_arrow_up" ? "keyboard_arrow_down" : "keyboard_arrow_up";
    })	
});


///////////////////////CHECKBOX HANDLING ////////////////////////////////////////////////////
$('input[type="checkbox"]').change(function(){
	if($(this).prop('checked',false)){
		$(this).data('val',0);
	}else{
		$(this).data('val',1);
	}
});

///////////////////////FORM HANDLER FOR MAPPING//////////////////////////////////////////////
$(function(){
    $('.tabular-form').on('submit', function(e){
		//prevent redirect
		e.preventDefault();
		//collect form data
		var url = $(this).attr('action');
		var method = $(this).attr('method');
		var data={};
		var table = $(this).find('.mapping-table');
		//add csfr
		data['csrf_name'] = $("input[name='csrf_name']").val();
		data['csrf_value'] = $("input[name='csrf_value']").val();
		//loop through rows of mapping table and collect values to data array
		table.find("tr").each(function(){
            var id = $(this).attr('id');
			var row = {};
			$(this).find('input,select,textarea').each(function(i){
				if($(this).attr('type')=='checkbox'){
					if($(this).data('val') == 0){
						row[$(this).attr('name')] = false;
					}else{
						row[$(this).attr('name')] = true;
					}
				}else{
					row[$(this).attr('name')] = $(this).val();
				}
			});
			data[id]=row;

        });
		
		//submit
        $.ajax({
            url:url,
            type:method, 
            data:data,
			error: function(data){
				showDialog({
					title: 'Error',
					subtitle:'There was an error during saving. Please try again.',
					text: '',
					positive: {
						id: 'ok-button',
						title: 'OK'
					}
				})
			},
            success: function(data){
				showDialog({
					title: 'Success',
					subtitle:'Your mapping has been saved succesfully.',
					text: '',
					positive: {
						id: 'ok-button',
						title: 'OK'
					}
				})
            }
        });
    });
});
///////////////////////EDIT BEHAVIOR FOR NEW MEMBER VALUES//////////////////////////////////////////////

$(".input-new").on('keyup',function(){
	var v = $(this).val();
	$(this).siblings("input[name='cep_name']").val(v);
	$(this).siblings("input[name='cep_api_name']").val("user.MemberAttribute."+v);
});

///////////////////////SFDC FIELD TYPE SEARCH UPON EDIT OF SFDC NAME/////////////////////////////

$(".mapping-select").on('change',function(){
	var opt = $(this).find("option:selected");
	var sfdc_type = opt.data("type");
	if(typeof sfdc_type!='undefined'){
		var sfdc_restricted = opt.data("restricted"); 
		var cell = $(this).parent().siblings(".sfdc_type");
		var text = sfdc_type.toUpperCase();
		cell.text(text);
	}
})


///////////////////////SORT BEHAVIOR FOR MDL TABLE///////////////////////////////////////////////
var options = {valueNames: ['cep', 'cep_type', 'sfdc']}
var documentTable = new List('mapping_lead_standard', options);

$('input.search').on('keyup', function (e) {
	if (e.keyCode === 27) {
		$(e.currentTarget).val('');
		documentTable.search('');
	}
});



/////////////////////// USER DIALOG//////////////////////////////////////////////////////////////
$('#user').click(function () {
    showDialog({
        title: 'User Profile',
        text: $('#user-profile').html(),
		positive: {
			id: 'ok-button',
			title: 'OK'
		}
	})
});
/////////////////////// OAUTH TEST /////////////////////////////////
$('#setting_sfdc_test_oauth_connection').click(function (e) {
	e.preventDefault();
	$('#test_oauth_connection').submit();
});

/////////////////////// CONNECTION TEST /////////////////////////////////
$('#setting_sfdc_test_connection').click(function (e) {
	e.preventDefault();
	$('#test_connection').submit();
});

$('#test_connection').submit(function (e) {
		//prevent redirect
		e.preventDefault();
		//collect form data
		var url = $(this).attr('action');
		var method = $(this).attr('method');
		var response = $.ajax({
            url:url,
			dataType: 'text',
            type:method,
			async:false
        }).responseText;

		var j_response = JSON.parse(response);
		//if the user object is empty
		if(!j_response){
			var result_title = 'Error';
			var result = 'Connection to Salesforce has failed. Please set or review your Salesforce credentials in the Settings section';
			var con = "";
		}else{
			var result_title = 'Success'
			var result = 'Connection to Salesforce was successful. Please review the data of the authenticated user.';
			var con = "<pre>'"+JSON.stringify(j_response,null,2)+"'</pre>";
		}
	
    showDialog({
        title: result_title,
		subtitle: result,
        text: con,
		positive: {
			id: 'ok-button',
			title: 'OK'
		}
	})
});
/////////////////////// GENERATION OF JSON MAP /////////////////////////////////
$('#generate_json_map').submit(function (e) {
		//prevent redirect
		e.preventDefault();
		//collect form data
		var url = $(this).attr('action');
		var method = $(this).attr('method');
		var response = $.ajax({
            url:url,
			dataType:'text',
            type:method,
			async:false
        }).responseText;

		var map = "<pre>'"+JSON.stringify(JSON.parse(response),null,2)+"'</pre>";

    showDialog({
        title: 'JSON Map',
		subtitle:'Copy the content of the below field and paste it in your Mapp CEP automation HTTP Request body to make calls. <br> You can replace the CEP placeholders with static values.',
        text: map,
		positive: {
			id: 'ok-button',
			title: 'OK'
		}
	})
});




//////////////////////////PASSWORD TOGGLE FOR SETTINGS///////////////////////////////////////////////
(function ($) {
    $.toggleShowPassword = function (options) {
        var settings = $.extend({
            field: "input[type='password']",
            control: ".toggle-show-password",
        }, options);

        var control = $(settings.control);
        var field = $(settings.field);

        control.bind('mousedown', function () {
           field.attr('type', 'text')
        })
		control.bind('mouseup', function () {
           field.attr('type', 'password')
        })
    };
}(jQuery));

$.toggleShowPassword({
    field: "input[type='password']",
    control: '.toggle-show-password'
});

