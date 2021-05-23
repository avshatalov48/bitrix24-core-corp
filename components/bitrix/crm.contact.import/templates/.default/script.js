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
			data['search'] = parseInt(data['search']);
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
						if (BX.type.isArray(data['error_data'][i]['data'][ii]))
						{
							if (tableRow === null)
							{
								tableRow = BX.create("tr");
							}
							for (var iii in data['error_data'][i]['data'][ii])
							{
								tableRowColumn = BX.create("td", {text : data['error_data'][i]['data'][ii][iii] });
								tableRow.appendChild(tableRowColumn);
							}
							BX('crm_import_example_table_body').appendChild(tableRow);
							tableRow = null;
						}
						else
						{

							tableRowColumn = BX.create("td", {text : data['error_data'][i]['data'][ii] });
							tableRow.appendChild(tableRowColumn);
						}
					}
					if (tableRow !== null)
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
			if (data['search'] > 0 || data['import'] > 0 || data['error'] > 0 || data['duplicate'] > 0)
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

		this._origin = "";
		this._defaultLangId = "";
		this._langId = "";

		this._originSelector = null;
		this._encodingSelector = null;
		this._firstHeaderChkBx = null;
		this._headerLangSelector = null;
		this._dupControlTypeDescr = null;

		this._dupControlTypes = null;
	};
	BX.CrmFileImportConfig.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : 'crm_file_import_config';
			this._settings = settings ? settings : {};

			this._origin = this.getSetting("origin", "");
			this._langId = this._defaultLangId = this.getSetting("defaultLangId", "");


			this._originSelector = BX(this.getSetting("originSelectorId"));
			this._headerLangSelector = BX(this.getSetting("headerLangSelectorId"));

			if(this._originSelector)
			{
				BX.bind(this._originSelector, "change", BX.delegate(this._onOriginChange, this));
			}
			if(this._headerLangSelector)
			{
				BX.bind(this._headerLangSelector, "change", BX.delegate(this._onHeaderLangChange, this));
			}

			this._firstHeaderChkBx = BX(this.getSetting("firstHeaderChkBxId"));
			this._separatorSelector = BX(this.getSetting("separatorSelectorId"));
			this._encodingSelector = BX(this.getSetting("encodingSelectorId"));
			var enableHeaderLangSelector = this._origin === "yandex" || this._origin === "outlook";
			var headerLangSelectorRow = BX.findParent(this._headerLangSelector, { tagName: "TR" });
			if(headerLangSelectorRow)
			{
				headerLangSelectorRow.style.display = enableHeaderLangSelector ? "" : "none";
			}

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
		getOrigin: function()
		{
			return this._origin;
		},
		getLanguageId: function()
		{
			return this._langId;
		},
		_onOriginChange: function(e)
		{
			this._origin = this._originSelector.value;
			var enableHeaderLangSelector = false;
			var langId = this._langId;
			if(this._origin === "yandex")
			{
				enableHeaderLangSelector = true;
				if(langId !== "ru" && langId !== "en")
				{
					langId = "en";
				}
			}
			else if(this._origin === "outlook")
			{
				langId = this._defaultLangId;
				enableHeaderLangSelector = true;
			}

			var headerLangSelectorRow = BX.findParent(this._headerLangSelector, { tagName: "TR" });
			if(headerLangSelectorRow)
			{
				headerLangSelectorRow.style.display = enableHeaderLangSelector ? "" : "none";
			}

			if(langId !== this._langId)
			{
				this._headerLangSelector.value = langId;
			}
			else
			{
				this._setupByOrigin(this._origin, this._langId);
			}
		},
		_onHeaderLangChange: function(e)
		{
			this._langId = this._headerLangSelector.value;
			this._setupByOrigin(this._origin, this._langId);
		},
		_setupByOrigin: function(origin, langId)
		{
			var encoding = null;
			var separator = null;
			var firstIsHeader = null;

			if(origin === "custom")
			{
				encoding = "_";
				separator = "semicolon";
				firstIsHeader = true;
			}
			else if(origin === "gmail")
			{
				encoding = "UTF-16";
				separator = "comma";
				firstIsHeader = true;
			}
			else if(origin === "yahoo")
			{
				encoding = "UTF-8";
				separator = "comma";
				firstIsHeader = true;
			}
			else if(origin === "yandex")
			{
				if(langId === "ru")
				{
					encoding = "windows-1251";
					separator = "comma";
				}
				else
				{
					encoding = "UTF-8";
					separator = "comma";
				}
				firstIsHeader = true;
			}
			else if(origin === "mailru")
			{
				encoding = "windows-1251";
				separator = "comma";
				firstIsHeader = true;
			}
			else if(origin === "outlook")
			{
				if(langId === "ru")
				{
					encoding = "windows-1251";
					separator = "comma";
				}
				else
				{
					encoding = "windows-1252";
					separator = "comma";
				}
				firstIsHeader = true;
			}
			else if(origin === "livemail")
			{
				encoding = "UTF-8";
				separator = "semicolon";
				firstIsHeader = true;
			}

			if(this._encodingSelector && encoding !== null)
			{
				this._encodingSelector.value = encoding;
			}
			if(this._separatorSelector && separator !== null)
			{
				this._separatorSelector.value = separator;
			}
			if(this._firstHeaderChkBx && firstIsHeader)
			{
				this._firstHeaderChkBx.checked = firstIsHeader;
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
	BX.CrmFileImportConfig.create = function(id, settings)
	{
		var self = new BX.CrmFileImportConfig();
		self.initialize(id, settings);
		return self;
	};
}

BX.namespace("BX.Crm");

if(typeof(BX.Crm.ContactImportSampleLink) === "undefined")
{
	BX.Crm.ContactImportSampleLink = function ()
	{
		this._id = "";
		this._settings = {};
	};
	BX.Crm.ContactImportSampleLink.prototype = {
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "crm_imp_sample_link_" +
				Math.random().toString().substring(2);
			this._settings = settings ? settings : {};
		},
		getSample: function(href)
		{
			var url = href;
			var formElement = BX(this._settings["formId"]);
			if (formElement && BX.type.isNotEmptyString(this._settings["rqImportOptionName"]))
			{
				var checkbox = formElement.querySelector(
					"input[type=checkbox][name=" + this._settings["rqImportOptionName"] + "]"
				);
				if (checkbox && checkbox.checked)
					url = BX.util.add_url_param(url, {"impRq": "Y"});
			}
			window.location.href = url;
		}
	};
	BX.Crm.ContactImportSampleLink.items = {};
	BX.Crm.ContactImportSampleLink.create = function(id, settings)
	{
		var self = new BX.Crm.ContactImportSampleLink();
		self.initialize(id, settings);
		BX.Crm.ContactImportSampleLink.items[id] = self;
		return self;
	};
	BX.Crm.ContactImportSampleLink.delete = function(id)
	{
		if (BX.Crm.ContactImportSampleLink.items.hasOwnProperty(id))
		{
			BX.Crm.ContactImportSampleLink.items[id].destroy();
			delete BX.Crm.ContactImportSampleLink.items[id];
		}
	};
}
