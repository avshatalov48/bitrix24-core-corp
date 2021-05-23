function crmImportStep(step, form_id)
{
	selectTab = 'tab_'+step;
	arDisable = new Array('tab_1', 'tab_2', 'tab_3', 'tab_4');
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
		url: BX.util.add_url_param(importUrl, { 'import': 'Y' }),
		method: 'POST',
		dataType: 'json',
		data: {},
		onsuccess: function(data)
		{
			data['import'] = parseInt(data['import']);
			data['duplicate'] = parseInt(data['duplicate']);
			data['error'] = parseInt(data['error']);
			if (data['error'] > 0)
			{
				BX('crm_import_error').style.display = "block";
				BX('crm_import_errata').style.display = "block";
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
				BX('crm_import_entity_errata').href = data['errata_url'];
			}
			if(data['duplicate'] > 0)
			{
				BX('crm_import_entity_duplicate').innerHTML = parseInt(BX('crm_import_entity_duplicate').innerHTML)+data['duplicate'];
				if(BX.type.isNotEmptyString(data['duplicate_url']))
				{
					BX('crm_import_duplicate_file_url').href = data['duplicate_url'];
					BX('crm_import_duplicate_file_wrapper').style.display = "block";
				}
			}
			if (data['import'] > 0 || data['error'] > 0 || data['duplicate'] > 0)
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
				BX('crm_import_done').disabled = false;
				BX('crm_import_again').hidden = false;

				BX.ajax({
						url: BX.util.add_url_param(importUrl, { 'complete_import': 'Y' }),
						method: 'POST',
						dataType: 'json',
						data: {}
					}
				);
			}
		},
		onfailure: function(data)
		{
			BX('crm_import_entity_progress').innerHTML = '';
			BX('crm_import_done').disabled = false;
			BX('crm_import_again').hidden = false;
		} 
	});

	return false;
}
if(typeof(BX.CrmFileImportConfig) === "undefined")
{
	BX.CrmFileImportConfig = function()
	{
		this._id = "";
		this._settings = {};
		this._dupControlTypeDescr = null;
		this._dupControlTypes = null;
	};
	BX.CrmFileImportConfig.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : 'crm_file_import_config';
			this._settings = settings ? settings : {};

			var dupControlPrefix = this.getSetting("dupControlPrefix");
			this._dupControlTypes = this.getSetting("dupControlTypes", {});
			for(var key in this._dupControlTypes)
			{
				if(!this._dupControlTypes.hasOwnProperty(key))
				{
					continue;
				}

				var element = BX(dupControlPrefix + key.toLowerCase());
				if(element)
				{
					BX.bind(element, "change", BX.delegate(this._onDupControlTypeChange, this));
				}
			}

			this._dupControlTypeDescr = BX(this.getSetting("dupControlTypeDescrId"));
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		_onDupControlTypeChange: function(e)
		{
			if(!e)
			{
				e = window.event;
			}

			var target = BX.getEventTarget(e);
			if(target && BX.type.isNotEmptyString(this._dupControlTypes[target.value]) && this._dupControlTypeDescr)
			{
				this._dupControlTypeDescr.innerHTML = BX.util.htmlspecialchars(this._dupControlTypes[target.value]);
			}

		}
	};
	BX.CrmFileImportConfig.create = function(id, settings)
	{
		var self = new BX.CrmFileImportConfig();
		self.initialize(id, settings);
		return self;
	};
}