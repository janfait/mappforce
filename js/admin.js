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
	/*
	var drawer = $('.mdl-layout__drawer');
	var obfuscator = $('.mdl-layout__obfuscator');
	drawer.attr('aria-hidden',false);
	drawer.attr('aria-expanded',true);
	drawer.addClass('is-visible');
	obfuscator.addClass('is-visible');
	*/

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
		//loop through rows of mapping table and collect values to data array
		table.find("tr").each(function(){
            var id = $(this).attr('id');
            var row = {};
            $(this).find('input,select,textarea').each(function(i){
				if($(this).attr('type')=='checkbox'){
					row[$(this).attr('name')] = $(this).prop('checked');
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

///////////////////////SFDC FIELD TYPE SEARCH UPON EDIT OF SFDC NAME/////////////////////////////



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
/////////////////////// HELP DIALOG//////////////////////////////////////////////////////////////////
$('#help').click(function () {
    showDialog({
        title: 'Help',
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
            field: "#password",
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

