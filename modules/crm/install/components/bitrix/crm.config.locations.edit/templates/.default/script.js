
BX.crmLocationParams = {
	formObj: false,
	oLangs: false,
	mess:{},
	ajaxUrl: '',

	init: function(params)
	{
		for(var key in params)
			this[key] = params[key];
	},

	hideLangFields: function()
	{
		BX.crmLocationCountries.hideLangFields();
		BX.crmLocationRegions.hideLangFields();
		BX.crmLocationCities.hideLangFields();
	}
};

BX.crmLocationCountries = {

	formObj: false,
	changeCountryObj: false,
	changeCountryInputObj: false,
	oLangs: false,
	fieldsObjs: [],
	countries: {},

	init: function()
	{
		this.formObj = BX.crmLocationParams.formObj;
		this.oLangs = BX.crmLocationParams.oLangs;
		this.changeCountryObj = this.formObj['CHANGE_COUNTRY'];

		for(var i = 0, l = this.changeCountryObj.length; i < l; i++)
			if(this.changeCountryObj[i].type == 'checkbox')
				this.changeCountryInputObj = this.changeCountryObj[i];

		this.changeCountryInputObj.parentNode.parentNode.style.display = 'none';

		this.setFieldsObjs();

		this.setChange(this.formObj['COUNTRY_ID'].value, true);
		this.disableFields(this.isFieldsDisabled());

		BX.bind(this.changeCountryInputObj, "click", BX.proxy(
														function(){
															this.disableFields(this.isFieldsDisabled());
														}, this
													)
		);

		BX.bind(this.formObj['COUNTRY_ID'], "change", BX.proxy(
														function(){
															this.setChange(this.formObj['COUNTRY_ID'].value);
														}, this
													)
		);
	},

	setFieldsObjs: function()
	{
		this.fieldsObjs.push(this.formObj['COUNTRY_NAME']);
		this.fieldsObjs.push(this.formObj['COUNTRY_SHORT_NAME']);

		for(var i in this.oLangs)
		{
			this.fieldsObjs.push(this.formObj['COUNTRY_NAME_'+this.oLangs[i]]);
			this.fieldsObjs.push(this.formObj['COUNTRY_SHORT_NAME_'+this.oLangs[i]]);
		}
	},

	setChangeBox: function (bNotChange)
	{
		bChange = !bNotChange;
		this.changeCountryInputObj.checked = bChange;
	},

	unSetChangeBox: function()
	{
		this.setChangeBox(true);
	},

	hideChangeBox: function (bShow)
	{
		bHide = !bShow;
		this.changeCountryInputObj.parentNode.parentNode.style.display = bHide ? 'none' : '';
	},

	showChangeBox: function()
	{
		this.hideChangeBox(true);
	},

	isFieldsDisabled: function()
	{
		return this.changeCountryInputObj.checked;
	},

	resetFields: function()
	{
		for(var i in this.fieldsObjs)
			this.fieldsObjs[i].value = '';
	},

	setFields: function(arCountry)
	{
		for(var i in this.fieldsObjs)
		{
			if(arCountry[this.fieldsObjs[i].name])
				this.fieldsObjs[i].value = arCountry[this.fieldsObjs[i].name];
			else
				this.fieldsObjs[i].value = '';
		}
	},

	disableFields: function(bEnable)
	{
		bDisable = !bEnable;

		for(var i in this.fieldsObjs)
			this.fieldsObjs[i].disabled = bDisable;
	},

	hideFields: function(bShow)
	{
		bHide = !bShow;

		if(bHide)
			this.hideChangeBox();
		else
			this.showChangeBox();

		for(var i in this.fieldsObjs)
			this.fieldsObjs[i].parentNode.parentNode.style.display = bHide ? 'none' : '';

		this.hideLangFields();
		/*
		for(var j in this.oLangs)
			this.formObj['COUNTRY_NAME_'+this.oLangs[j]].parentNode.parentNode.previousElementSibling.style.display = bHide ? 'none' : '';
		*/
	},

	showFields: function()
	{
		this.hideFields(true);
	},

	enableFields: function()
	{
		this.disableFields(true);
	},

	setChange: function(countryId, initMode)
	{
		if(countryId === '0')
		{
			this.resetFields();
			this.showFields();
			this.hideChangeBox();
			this.unSetChangeBox();
			this.enableFields();
		}
		else if(countryId === '')
		{
			this.hideFields();
			this.resetFields();
			this.unSetChangeBox();
		}
		else
		{
			if(!initMode)
			{
				this.getParamsAjax(countryId);
				this.resetFields();
			}

			this.disableFields();
			this.showFields();
			this.unSetChangeBox();

		}
	},

	getParamsAjax: function(countryId, initMode)
	{
		if(this.countries[countryId])
		{
			var _this = this;
			setTimeout(function(){ _this.setFields(_this.countries[countryId]); }, 0);

			BX.crmLocationRegions.setSelect(this.countries[countryId].REGIONS);
			return true;
		}

		data = {
			'ID': countryId,
			'action': 'get_country_params',
			'sessid': BX.bitrix_sessid()
		};

		BX.showWait();

		BX.ajax({
			data: data,
			method: 'POST',
			dataType: 'json',
			url: BX.crmLocationParams.ajaxUrl,
			onsuccess: BX.delegate(function(result) {
										BX.closeWait();
										if(result)
										{
											if(!result.ERROR)
											{
												if(result.COUNTRY)
												{
													this.countries[result.COUNTRY.ID] = result.COUNTRY;
													this.setFields(result.COUNTRY);
												}

												if(result.COUNTRY.REGIONS)
													BX.crmLocationRegions.setSelect(result.COUNTRY.REGIONS);

											}
										}
									}, this
						),
			onfailure: function() {BX.debug('onfailure: getParamsAjax');}
		});

	},

	hideLangFields: function()
	{
		for(var i = this.fieldsObjs.length-1; i > 1; i--)
			this.fieldsObjs[i].parentNode.parentNode.style.display = 'none';
	}
};

BX.crmLocationRegions = {

	formObj: false,
	oLangs: false,
	fieldsObjs: [],
	regions: {},

	init: function()
	{
		this.formObj = BX.crmLocationParams.formObj;
		this.oLangs = BX.crmLocationParams.oLangs;

		this.setFieldsObjs();

		this.setChange(this.formObj['REGION_ID'].value, true);


		BX.bind(this.formObj['REGION_ID'], "change", BX.proxy(
														function(){
															this.setChange(this.formObj['REGION_ID'].value);
														}, this
													)
		);

	},

	setSelect: function(arRegions)
	{
		var regionIdObj = this.formObj['REGION_ID'];

		for(var i = regionIdObj.options.length; i > 1; i--)
			regionIdObj.remove(i);

		for(var region in arRegions)
		{
			var option=document.createElement("option");
			option.value=arRegions[region][0];
			option.text=arRegions[region][1];

			try
			{
				regionIdObj.add(option, null);
			}
			catch(ex)
			{
				regionIdObj.add(option);
			}
		}

		regionIdObj.value = '';
		this.setChange('');
	},

	setFieldsObjs: function()
	{
		this.fieldsObjs.push(this.formObj['REGION_NAME']);
		this.fieldsObjs.push(this.formObj['REGION_SHORT_NAME']);

		for(var i in this.oLangs)
		{
			this.fieldsObjs.push(this.formObj['REGION_NAME_'+this.oLangs[i]]);
			this.fieldsObjs.push(this.formObj['REGION_SHORT_NAME_'+this.oLangs[i]]);
		}
	},

	resetFields: function()
	{
		for(var i in this.fieldsObjs)
			this.fieldsObjs[i].value = '';
	},

	setFields: function(arRegion)
	{
		for(var i in this.fieldsObjs)
		{
			if(arRegion[this.fieldsObjs[i].name])
				this.fieldsObjs[i].value = arRegion[this.fieldsObjs[i].name];
			else
				this.fieldsObjs[i].value = '';
		}
	},

	hideFields: function(bShow)
	{
		bHide = !bShow;

		for(var i in this.fieldsObjs)
			this.fieldsObjs[i].parentNode.parentNode.style.display = bHide ? 'none' : '';

		this.hideLangFields();
		/*
		for(var j in this.oLangs)
			this.formObj['REGION_NAME_'+this.oLangs[j]].parentNode.parentNode.previousElementSibling.style.display = bHide ? 'none' : '';
		*/
	},

	showFields: function()
	{
		this.hideFields(true);
	},

	setChange: function(regionId, initMode)
	{
		if(regionId === '0')
		{
			this.resetFields();
			this.showFields();
		}
		else if(regionId === '')
		{
			this.hideFields();
			this.resetFields();
		}
		else
		{
			this.showFields();

			if(!initMode)
				this.getParamsAjax(regionId);
		}
	},

	getParamsAjax: function(regionId)
	{
		if(this.regions[regionId])
		{
			this.setFields(this.regions[regionId]);
			return true;
		}


		data = {
			'ID': regionId,
			'action': 'get_region_params',
			'sessid': BX.bitrix_sessid()
		};

		BX.showWait();

		BX.ajax({
			data: data,
			method: 'POST',
			dataType: 'json',
			url: BX.crmLocationParams.ajaxUrl,
			onsuccess: BX.delegate(function(result) {
										BX.closeWait();
										if(result)
										{
											if(!result.ERROR)
											{
												if(result.REGION)
												{
													this.regions[result.REGION.ID] = result.REGION;
													this.setFields(result.REGION);
												}
											}
										}
									}, this
						),
			onfailure: function() {BX.debug('onfailure: getParamsAjax');}
		});
	},
	hideLangFields: function()
	{
		for(var i = this.fieldsObjs.length-1; i > 1; i--)
			this.fieldsObjs[i].parentNode.parentNode.style.display = 'none';
	}

};

BX.crmLocationCities = {

	formObj: false,
	oLangs: false,
	fieldsObjs: [],
	withoutCityObj: false,
	withoutCityInputObj: false,

	init: function()
	{
		this.formObj = BX.crmLocationParams.formObj;
		this.oLangs = BX.crmLocationParams.oLangs;

		this.setFieldsObjs();

		this.withoutCityObj = this.formObj['WITHOUT_CITY'];

		for(var i = 0, l = this.withoutCityObj.length; i < l; i++)
			if(this.withoutCityObj[i].type == 'checkbox')
				this.withoutCityInputObj = this.withoutCityObj[i];

		this.hideFields(!this.withoutCityInputObj.checked);

		BX.bind(this.withoutCityInputObj, "click", BX.proxy(
														function(){
															this.hideFields(!this.withoutCityInputObj.checked);
														}, this
													)
		);

	},

	setFieldsObjs: function()
	{
		this.fieldsObjs.push(this.formObj['CITY_NAME']);
		this.fieldsObjs.push(this.formObj['CITY_SHORT_NAME']);

		for(var i in this.oLangs)
		{
			this.fieldsObjs.push(this.formObj['CITY_NAME_'+this.oLangs[i]]);
			this.fieldsObjs.push(this.formObj['CITY_SHORT_NAME_'+this.oLangs[i]]);
		}
	},

	resetFields: function()
	{
		for(var i in this.fieldsObjs)
			this.fieldsObjs[i].value = '';
	},

	hideFields: function(bShow)
	{
		bHide = !bShow;

		for(var i in this.fieldsObjs)
			this.fieldsObjs[i].parentNode.parentNode.style.display = bHide ? 'none' : '';

		this.hideLangFields();
		/*
		for(var j in this.oLangs)
			this.formObj['CITY_NAME_'+this.oLangs[j]].parentNode.parentNode.previousElementSibling.style.display = bHide ? 'none' : '';
		*/
	},

	showFields: function()
	{
		this.hideFields(true);
	},

	hideLangFields: function()
	{
		for(var i = this.fieldsObjs.length-1; i > 1; i--)
			this.fieldsObjs[i].parentNode.parentNode.style.display = 'none';
	}

};

BX.crmLocationZip = {
	add: function()
	{
		var obContainer = document.getElementById("zip_list");
		var obInput = document.createElement("INPUT");
		obInput.type = "text";
		obInput.name = "ZIP[]";
		obInput.size = 10;

		var obSpan = document.createElement("SPAN");
		obSpan.className = "bx-crm-location-zip-delete";
		obSpan.onclick = function(){ BX.crmLocationZip.delete(this); };
		obSpan.innerHTML = BX.crmLocationParams.mess.CRM_DEL_ZIP;

		obContainer.appendChild(obInput);
		obContainer.insertBefore(obSpan, obInput.previousElementSibling);
		obContainer.appendChild(document.createElement("BR"));

		return false;
	},

	delete: function(obj)
	{
		obj.parentNode.removeChild(obj.previousSibling);
		obj.parentNode.removeChild(obj.nextSibling);
		obj.parentNode.removeChild(obj);
	}
};