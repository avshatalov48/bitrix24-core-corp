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
			data['duplicate'] = parseInt(data['duplicate']);
			data['error'] = parseInt(data['error']);
			if (data['error'] > 0)
			{
				BX('crm_import_error').style.display = "block";
				BX('crm_import_errata').style.display = "block";
				BX('crm_import_example').style.display = "block";
				for (var i in data['error_data'])
				{
					if(!data['error_data'].hasOwnProperty(i))
					{
						continue;
					}

					var errorInfo = data['error_data'][i];
					BX('crm_import_example').appendChild(
						BX.create('DIV', { attrs: { className: 'error_text' }, html: errorInfo['message']})
					);

					BX('crm_import_example').appendChild(
						BX.create('CODE', { text: errorInfo['data']})
					);
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
if(typeof(BX.CrmVCardImportConfig) === "undefined")
{
	BX.CrmVCardImportConfig = function()
	{
		this._id = "";
		this._settings = {};

		this._formId = "";
		this._encodingSelector = null;
		this._dupControlTypeDescr = null;
		this._dupControlTypes = null;
	};
	BX.CrmVCardImportConfig.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : 'crm_vcard_import_config';
			this._settings = settings ? settings : {};

			this._formId = this.getSetting("formId");
			this._encodingSelector = BX(this.getSetting("encodingSelectorId"));
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
		setStep: function(step)
		{
			var tabs = ["tab_1", "tab_2", "tab_3"];
			tabs.splice(step - 1, 1);
			var bxForm = eval("bxForm_" + this._formId);
			bxForm.SelectTab(("tab_"+ step), true);
			for (var i = 0; i < tabs.length; i++)
			{
				var tabId = tabs[i];
				bxForm.ShowDisabledTab(tabId, true);
				BX("tab_cont_" + tabId).className = "bx-tab-container-disabled";
			}
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
	BX.CrmVCardImportConfig.create = function(id, settings)
	{
		var self = new BX.CrmVCardImportConfig();
		self.initialize(id, settings);
		return self;
	};
}