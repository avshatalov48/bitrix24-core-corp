function crmImportStep(step, form_id)
{
	selectTab = 'tab_'+step;
	arDisable = new Array('tab_1', 'tab_2', 'tab_3');
	arDisable.splice(step-1,1);
	var bxForm = eval('bxForm_'+form_id);
	bxForm.SelectTab(selectTab, true);
	for (var elDisable in arDisable) {
		bxForm.ShowDisabledTab(arDisable[elDisable], true);
		BX('tab_cont_'+arDisable[elDisable]).className = 'bx-tab-container-disabled';
	}
}

	
function crmImportAjax(importUrl)
{
	BX.ajax({
		url: importUrl, 
		method: 'POST',
		dataType: 'json',
		data: {},
		onsuccess: function(data)
		{
			data['import'] = parseInt(data['import']);
			data['error'] = parseInt(data['error']);		
			if (data['error'] > 0)
			{
				BX('crm_import_error').style.display = "block";
				BX('crm_import_example').style.display = "block";
				if (parseInt(BX('crm_import_example').style.height) < 399)
					BX('crm_import_example').style.height = (parseInt(BX('crm_import_example').style.height)+68*data['error'])+'px';

				for (var i in data['error_data']) {
					tableRow = BX.create("tr");
					tableRowColumn = BX.create("td", { props : { colSpan : data['column'], className : 'crm_import_example_table_td_error' }});
					tableRowColumn.innerHTML = data['error_data'][i]['message'];
					tableRow.appendChild(tableRowColumn);
					BX('crm_import_example_table_body').appendChild(tableRow);
					
					tableRow = BX.create("tr");
					for (var ii in data['error_data'][i]['data']) {
						tableRowColumn = BX.create("td", {text : data['error_data'][i]['data'][ii] });
						tableRow.appendChild(tableRowColumn);
					}
					BX('crm_import_example_table_body').appendChild(tableRow);
					
				}
				BX('crm_import_entity_error').innerHTML = parseInt(BX('crm_import_entity_error').innerHTML)+data['error'];
			}
			if (data['import'] > 0 || data['error'] > 0)
			{
				if(data['import'] > 0)
				{
					BX('crm_import_entity').innerHTML = parseInt(BX('crm_import_entity').innerHTML) + data['import'];
				}
				crmImportAjax(importUrl);
			}
			else
			{
				BX('crm_import_entity_progress').innerHTML = '';
				BX('crm_import_done').hidden = false;
				BX('crm_import_again').hidden = false;
			}
		},
		onfailure: function(data)
		{
			BX('crm_import_entity_progress').innerHTML = '';
			BX('crm_import_done').hidden = false;
			BX('crm_import_again').hidden = false;
		} 
	});

	return false;
}