if (typeof(obCrm) === "undefined")
{
	obCrm = {};
}

CRM = function(crmID, div, el, name, element, prefix, multiple, entityType, localize, disableMarkup, options)
{
	this.crmID = crmID;
	this.div = div;
	this.el = el;
	this.name = name;
	this.PopupEntityType = entityType;
	this.PopupEntityTypeAbbr = [];
	this.PopupTabs = {};
	this.PopupElement =  element;
	this.PopupPrefix = prefix;
	this.PopupMultiple = multiple;
	this.PopupBlock = {};
	this.PopupSearch = {};
	this.PopupSearchInput = null;
	this.PopupCustomSearchHandler = null;

	this.PopupTabsIndex = 0;
	this.PopupTabsIndexId = '';
	this.PopupLocalize = localize;

	this.popup = null;
	this.onSaveListeners = [];
	this.disableMarkup = !!disableMarkup; //disable call 'PopupCreateValue' on save
	this.onBeforeSearchListeners = [];

	this.options = {
		requireRequisiteData: false,
		searchOptions: {}
	};
	if (options && typeof(options) === "object")
	{
		if (!!options["requireRequisiteData"])
			this.options.requireRequisiteData = true;

		if (BX.type.isPlainObject(options["searchOptions"]))
		{
			this.options.searchOptions = options["searchOptions"];
		}

		if (BX.Type.isArray(options["entityTypeAbbr"]))
		{
			for (let i in options["entityTypeAbbr"])
			{
				if (
					options["entityTypeAbbr"].hasOwnProperty(i)
					&& BX.Type.isStringFilled(options["entityTypeAbbr"][i])
					&& options["entityTypeAbbr"][i].length < 20
				)
				{
					this.PopupEntityTypeAbbr[i] = options["entityTypeAbbr"][i];
				}
			}
		}

		if (
			options.hasOwnProperty("customSearchHandler")
			&& BX.Type.isFunction(options["customSearchHandler"])
		)
		{
			this.PopupCustomSearchHandler = options["customSearchHandler"];
		}
	}
};

CRM.prototype.Init = function()
{
	let i;

	this.popupShowMarkup();

	this.PopupTabs = BX.findChildren(
		BX("crm-" + this.crmID + "_" + this.name + "-tabs"),
		{className: "crm-block-cont-tabs"}
	);
	if (this.PopupTabs.length > 0)
	{
		this.PopupTabsIndex = 0;
		this.PopupTabsIndexId = this.PopupTabs[0].id;
	}

	this.PopupItem = {};
	this.PopupItemSelected = {};
	for (i in this.PopupElement)
	{
		this.PopupAddItem(this.PopupElement[i]);
	}

	this.PopupBlock = BX.findChildren(
		BX("crm-" + this.crmID + "_" + this.name + "-blocks"),
		{className: "crm-block-cont-block"}
	);
	this.PopupSearch = BX.findChildren(
		BX("crm-" + this.crmID + "_" + this.name + "-block-cont-search"),
		{className: "crm-block-cont-search-tab"}
	);
	this.PopupSearchInput = BX("crm-" + this.crmID + "_" + this.name + "-search-input");

	for (i = 0; i<this.PopupTabs.length; i++)
	{
		BX.bind(
			this.PopupTabs[i],
			"click",
			function (crmId)
			{
				return function(event)
				{
					CRM.PopupShowBlock(crmId, this);
					BX.PreventDefault(event);
				}
			}(this.crmID)
		);
	}

	for (i = 0; i<this.PopupSearch.length; i++)
	{
		BX.bind(
			this.PopupSearch[i],
			"click",
			function (crmId)
			{
				return function (event)
				{
					CRM.PopupShowSearchBlock(crmId, this);
					BX.PreventDefault(event);
				}
			}(this.crmID)
		);
	}

	BX.bind(
		this.PopupSearchInput,
		"keyup",
		function (crmId)
		{
			return function ()
			{
				CRM.SearchChange(crmId);
			};
		}(this.crmID)
	)

	this.PopupSave();

	BX.onCustomEvent(window, 'onCrmSelectorInit', [this.crmID, this.name, this]);
};

CRM.prototype.Clear = function()
{
	if (this.popup)
	{
		this.popup.close();
		this.popup.destroy();
	}

	const inputBox = BX("crm-" + this.crmID + "_" + this.name + "-input-box");
	if (inputBox)
	{
		this.div.removeChild(inputBox);
		BX.remove(inputBox);
	}

	const textBox = BX("crm-" + this.crmID + "_" + this.name + "-text-box");
	if (textBox)
	{
		BX.remove(textBox);
	}

	const htmlBox = BX("crm-" + this.crmID + "_" + this.name + "-html-box");
	if (htmlBox)
	{
		BX.remove(htmlBox);
	}
};

CRM.Set = function(el, name, subIdName, element, prefix, multiple, entityType, localize, disableMarkup, options)
{
	let crmID = false;
	if (el && BX.isNodeInDom(el))
	{
		crmID = el.id + subIdName;
		if (obCrm[crmID])
		{
			obCrm[crmID].Clear();
			delete obCrm[crmID];
		}

		obCrm[crmID] = new CRM(
			crmID,
			CRM.GetWrapperDivPa(el),
			el,
			name,
			element,
			prefix,
			multiple,
			entityType,
			localize,
			disableMarkup,
			options
		);
		obCrm[crmID].Init();
	}
	return crmID;
};

CRM.GetElementForm = function (pn)
{
	return BX.findParent(pn, { "tagName":"FORM" });
};

CRM.GetWrapperDivPr = function (pn, name)
{
	return BX.findPreviousSibling(pn, {"tagName": "DIV", "property": {"name": "crm-" + name + "-box"}});
};

CRM.GetWrapperDivN = function (pn, name)
{
	return BX.findNextSibling(pn, {"tagName": "DIV", "property": {"name": "crm-" + name + "-box"}});
};

CRM.GetWrapperDivPa = function (pn, name)
{
	while (pn.nodeName !== 'DIV' && pn.name !== ('crm-' + name + '-box'))
	{
		pn = pn.parentNode;
	}

	return pn.parentNode;
};

CRM.prototype.Open = function (params)
{
	if (!BX.type.isPlainObject(params))
	{
		params = {};
	}

	const titleBar =
		(
			BX.type.isPlainObject(params["titleBar"])
			|| BX.type.isNotEmptyString(params["titleBar"])
		)
			? params["titleBar"]
			: null
	;
	const closeIcon = BX.type.isPlainObject(params["closeIcon"]) ? params["closeIcon"] : null;
	const closeByEsc = BX.type.isBoolean(params["closeByEsc"]) ? params["closeByEsc"] : false;
	const autoHide = BX.type.isBoolean(params["autoHide"]) ? params["autoHide"] : !this.PopupMultiple;
	const anchor = BX.type.isElementNode(params["anchor"]) ? params["anchor"] : this.el;
	const gainFocus = BX.type.isBoolean(params["gainFocus"]) ? params["gainFocus"] : true;

	if (BX.PopupWindowManager._currentPopup !== null
		&& BX.PopupWindowManager._currentPopup.getId() === ("CRM-" + this.crmID + "-popup"))
	{
		BX.PopupWindowManager._currentPopup.close();
	}
	else
	{
		let buttonsAr;
		if (this.PopupMultiple)
		{
			buttonsAr = [
				new BX.PopupWindowButton({
					text : this.PopupLocalize['ok'],
					className : "popup-window-button-accept",
					events : {
						click: BX.delegate(this._handleAcceptBtnClick, this)
					}
				}),

				new BX.PopupWindowButtonLink({
					text : this.PopupLocalize['cancel'],
					className : "popup-window-button-link-cancel",
					events : {
						click: function() { this.popupWindow.close(); }
					}
				})
			];
		}
		else
		{
			buttonsAr = [
				new BX.PopupWindowButton({
					text : this.PopupLocalize['close'],
					className : "popup-window-button-accept",
					events : {
						click: function() { this.popupWindow.close(); }
					}
				})
			];
		}
		this.popup = BX.PopupWindowManager.create(
			"CRM-" + this.crmID + "-popup",
			anchor,
			{
				content: BX("crm-" + this.crmID + "_" + this.name + "-block-content-wrap"),
				titleBar: titleBar,
				closeIcon: closeIcon,
				closeByEsc: closeByEsc,
				offsetTop: 2,
				offsetLeft: -15,
				zIndex: 5000,
				buttons: buttonsAr,
				autoHide: autoHide
			}
		);

		this.popup.show();

		if (gainFocus)
		{
			BX.focus(this.PopupSearchInput);
		}
	}
	return false;
};

CRM.PopupSave2 = function(crmID)
{
	if (!obCrm[crmID])
		return false;

	obCrm[crmID].PopupSave();
};

CRM.prototype._handleAcceptBtnClick = function()
{
	this.PopupSave();
	this.popup.close();
};

CRM.prototype.AddOnSaveListener = function(listener)
{
	if (typeof(listener) != 'function')
	{
		return;
	}

	const ary = this.onSaveListeners;
	for (let i = 0; i < ary.length; i++)
	{
		if (ary[i] === listener)
		{
			return;
		}
	}
	ary.push(listener);
};

CRM.prototype.RemoveOnSaveListener = function(listener)
{
	let ary = this.onSaveListeners;
	for (let i = 0; i < ary.length; i++)
	{
		if (ary[i] === listener)
		{
			ary.splice(i, 1);
			break;
		}
	}
};

CRM.prototype.AddOnBeforeSearchListener = function(listener)
{
	if (typeof(listener) != 'function')
	{
		return;
	}

	const ary = this.onBeforeSearchListeners;
	for (let i = 0; i < ary.length; i++)
	{
		if (ary[i] === listener)
		{
			return;
		}
	}
	ary.push(listener);
};

CRM.prototype.RemoveOnBeforeSearchListener = function(listener)
{
	let ary = this.onBeforeSearchListeners;
	for (let i = 0; i < ary.length; i++)
	{
		if (ary[i] === listener)
		{
			ary.splice(i, 1);
			break;
		}
	}
};

CRM.prototype.PopupSave = function()
{
	const arElements = {};
	for (let i in this.PopupEntityType)
	{
		const elements = BX.findChildren(
			BX("crm-" + this.crmID + "_" + this.name + "-block-" + this.PopupEntityType[i] + "-selected"),
			{className: "crm-block-cont-block-item"}
		);
		if (elements !== null)
		{
			let el = 0;
			arElements[this.PopupEntityType[i]] = {};
			for (let e = 0; e < elements.length; e++)
			{
				const elementIdLength = "selected-crm-" + this.crmID + "_" + this.name + "-block-item-";
				const elementId = elements[e].id.substring(elementIdLength.length);

				const data = {
					'id': this.PopupItem[elementId]['id'],
					'type': this.PopupEntityType[i],
					'place': this.PopupItem[elementId]['place'],
					'title': this.PopupItem[elementId]['title'],
					'desc': this.PopupItem[elementId]['desc'],
					'url': this.PopupItem[elementId]['url'],
					'image': this.PopupItem[elementId]['image'],
					'largeImage': this.PopupItem[elementId]['largeImage']
				};

				if (typeof(this.PopupItem[elementId]['customData']) != 'undefined')
				{
					data['customData'] = this.PopupItem[elementId]['customData'];
				}
				if (typeof(this.PopupItem[elementId]['advancedInfo']) != 'undefined')
				{
					data['advancedInfo'] = this.PopupItem[elementId]['advancedInfo'];
				}

				arElements[this.PopupEntityType[i]][el] = data;

				el++;
			}
		}
	}

	const ary = this.onSaveListeners;
	if (ary.length > 0)
	{
		for (let j = 0; j < ary.length; j++)
		{
			try
			{
				ary[j](arElements);
			}
			catch(ex)
			{
			}
		}
	}

	if (!this.disableMarkup)
	{
		this.PopupCreateValue(arElements);
	}
};

CRM.prototype.ClearSelectItems = function()
{
	this.PopupItemSelected = {};
};

CRM.PopupShowBlock = function(crmID, element, search)
{
	if (!obCrm[crmID])
	{
		return false;
	}

	for (let i = 0; i < obCrm[crmID].PopupTabs.length; i++)
	{
		if (obCrm[crmID].PopupTabs[i] === element)
		{
			obCrm[crmID].PopupTabsIndex = i;
			obCrm[crmID].PopupTabsIndexId = obCrm[crmID].PopupTabs[i].id;
		}
		obCrm[crmID].PopupBlock[i].style.display = "none";
		BX.removeClass(obCrm[crmID].PopupTabs[i], "selected");
	}
	if (!search)
	{
		BX.addClass(element, "selected");
		obCrm[crmID].PopupSearchInput.value = "";
		BX('crm-'+crmID+'_'+obCrm[crmID].name+'-block-search').innerHTML = '';
	}
	else
		BX.addClass(obCrm[crmID].PopupTabs[obCrm[crmID].PopupTabsIndex], "selected");

	obCrm[crmID].PopupBlock[obCrm[crmID].PopupTabsIndex].style.display="block";
	BX('crm-'+crmID+'_'+obCrm[crmID].name+'-block-search').style.display="none";
	BX.removeClass(obCrm[crmID].PopupSearch[1], "selected");
	BX.addClass(obCrm[crmID].PopupSearch[0], "selected");

	BX.focus(obCrm[crmID].PopupSearchInput);
};

CRM.PopupShowSearchBlock = function(crmID, element)
{
	if (!obCrm[crmID])
	{
		return false;
	}

	for (let i = 0; i < obCrm[crmID].PopupBlock.length; i++)
	{
		obCrm[crmID].PopupBlock[i].style.display = "none";
	}

	if (element === obCrm[crmID].PopupSearch[0])
	{
		CRM.PopupShowBlock(crmID, BX(obCrm[crmID].PopupTabsIndexId), true);
		return false;
	}

	BX('crm-' + obCrm[crmID].crmID + "_" + obCrm[crmID].name + '-block-search').style.display = "block";
	BX.removeClass(obCrm[crmID].PopupSearch[0], "selected");
	BX.addClass(element, "selected");

	BX.focus(obCrm[crmID].PopupSearchInput);
};

CRM.PopupSelectItem = function(crmID, element, tab, unsave, select)
{
	let i;
	if (!obCrm[crmID])
		return false;

	const flag = element;
	if (flag.check)
	{
		if (select === undefined || !select)
		{
			CRM.PopupUnselectItem(crmID, element.id, "selected-" + element.id);
		}
		return false;
	}

	const elementIdLength = "crm-" + crmID + '_' + obCrm[crmID].name + "-block-item-";
	const elementId = element.id.substring(elementIdLength.length);
	const addCrmItems = document.createElement('span');
	addCrmItems.className = "crm-block-cont-block-item";
	addCrmItems.id = "selected-" + element.id;

	const addCrmDelBut = document.createElement('i');
	const addCrmLink = document.createElement('a');
	addCrmLink.href = obCrm[crmID].PopupItem[elementId]['url'];
	addCrmLink.target = "_blank";

	let blockWrap = null;

	const indexId = obCrm[crmID].PopupTabsIndexId;
	const prefix = "crm-" + crmID + '_' + obCrm[crmID].name;
	const indexPrefix = prefix + "-tab-";
	const selectorPrefix = prefix + "-block-";
	const selectorSufix = "-selected";

	if (tab === null)
	{
		if (
			BX.Type.isStringFilled(indexId)
			&& indexId.length > indexPrefix.length
			&& indexId.substring(0, indexPrefix.length) === indexPrefix
		)
		{
			const typeString = indexId.substring(indexPrefix.length);
			if (typeString.length < 30 && /^[a-z]+(_[a-z]+)?(_[0-9]+)?$/.test(typeString))
			{
				blockWrap = BX(selectorPrefix + typeString + selectorSufix);
			}
		}
	}
	else
	{
		blockWrap = BX(selectorPrefix + tab + selectorSufix);
	}

	if (
		!blockWrap
		|| blockWrap.querySelector("[id='" + ("selected-" + element.id).replace(new RegExp("search$"), tab) + "']")
		|| blockWrap.querySelector("[id='" + ("selected-" + element.id).replace(new RegExp(tab + "$"), "search") + "']")
	)
	{
		return;
	}

	if (obCrm[crmID].PopupMultiple)
	{
		const blockTitle = BX.findChild(blockWrap, { className : "crm-block-cont-right-title-count"}, true);
		blockTitle.innerHTML = parseInt(blockTitle.innerHTML)+1;
		BX.addClass(element, "crm-block-cont-item-selected");
		BX.addClass(blockWrap, "crm-added-item");
		flag.check=1;
	}
	else
	{
		const containerId =
			"crm-" + crmID + '_' + obCrm[crmID].name + "-block-"
			+ obCrm[crmID].PopupEntityType[i] + "-selected"
		;
		for (i in obCrm[crmID].PopupEntityType)
		{
			BX.removeClass(BX(containerId), "crm-added-item");
			const elements = BX.findChildren(BX(containerId), {className: "crm-block-cont-block-item"});
			if (elements !== null)
			{
				for (i in elements)
				{
					BX.remove(elements[i]);
				}
			}

		}
	}

	blockWrap.appendChild(addCrmItems).appendChild(addCrmDelBut);

	blockWrap.appendChild(addCrmItems).appendChild(addCrmLink).innerHTML =
		BX.util.htmlspecialchars(obCrm[crmID].PopupItem[elementId]['title'])
	;

	BX.bind(
		addCrmDelBut,
		"click",
		(event) => {
			CRM.PopupUnselectItem(crmID, element.id, "selected-" + element.id);
			BX.PreventDefault(event);
		}
	);

	obCrm[crmID].PopupItemSelected[elementId] = element;

	BX.onCustomEvent(window, 'onCrmSelectedItem', [obCrm[crmID].PopupItem[elementId]]);

	if (!obCrm[crmID].PopupMultiple && (unsave === undefined || !unsave))
	{
		obCrm[crmID].PopupSave();

		if (
			BX.PopupWindowManager._currentPopup !== null
			&& BX.PopupWindowManager._currentPopup.getId() === ("CRM-" + crmID + "-popup")
		)
		{
			BX.PopupWindowManager._currentPopup.close();
		}
	}
};

CRM.PopupUnselectItem = function(crmID, element, selected)
{
	if (!obCrm[crmID])
		return false;

	if (obCrm[crmID].PopupMultiple)
	{
		if (BX(selected).parentNode.getElementsByTagName('span').length === 3)
		{
			BX.removeClass(BX(selected).parentNode, "crm-added-item");
		}

		const blockTitle = BX.findChild(
			BX(selected).parentNode,
			{ className : "crm-block-cont-right-title-count"},
			true
		);
		blockTitle.innerHTML = parseInt(blockTitle.innerHTML)-1;

		obj = BX(element);
		if (obj !== null)
		{
			obj.check=0;
			BX.removeClass(obj, "crm-block-cont-item-selected");
		}
	}
	const elementIdLength = "crm-" + crmID + '_' + obCrm[crmID].name + "-block-item-";
	const elementId = element.substring(elementIdLength.length);
	delete obCrm[crmID].PopupItemSelected[elementId];

	BX.remove(BX(selected));

	BX.onCustomEvent(window, 'onCrmUnSelectedItem', [obCrm[crmID].PopupItem[elementId]]);
};

CRM.prototype.SetPopupItems = function(place, items)
{
	this.PopupItem = {};
	this.PopupItemSelected = {};

	const placeHolder = BX('crm-' + this.crmID + '_' + this.name + '-block-' + place);
	BX.cleanNode(placeHolder);

	for (let i = 0; i < items.length; i++)
	{
		const item = items[i];
		item['place'] = place;
		//item['selected'] = 'Y';
		this.PopupAddItem(item);
	}
};

CRM.prototype.PopupSetItem = function(id)
{
	const ar = id.toString().split("_");

	let entityId;
	let entityType = "";

	if (ar[1] !== undefined)
	{
		const entityShortName = ar[0];
		entityId = ar[1];

		if (this.PopupEntityTypeAbbr.length > 0)
		{
			for (let i in this.PopupEntityTypeAbbr)
			{
				if (
					this.PopupEntityTypeAbbr.hasOwnProperty(i)
					&& entityShortName === this.PopupEntityTypeAbbr[i]
				)
				{
					if (this.PopupEntityType.hasOwnProperty(i))
					{
						entityType = this.PopupEntityType[i]
					}
					break;
				}
			}
		}

		if (entityType === "")
		{
			if (entityShortName === 'L')
			{
				entityType = 'lead';
			}
			else if (entityShortName === 'C')
			{
				entityType = 'contact';
			}
			else if (entityShortName === 'CO')
			{
				entityType = 'company';
			}
			else if (entityShortName === 'D')
			{
				entityType = 'deal';
			}
			else if (entityShortName === 'Q')
			{
				entityType = 'quote';
			}
			else if (entityShortName === 'O')
			{
				entityType = 'order';
			}
		}
	}
	else
	{
		for (let i in this.PopupEntityType)
		{
			entityType = this.PopupEntityType[i];
		}
		entityId = id;
	}

	const crm = this;

	const options = {
		'REQUIRE_REQUISITE_DATA': (crm.options.requireRequisiteData) ? 'Y' : 'N'
	};

	if (BX.type.isPlainObject(crm.options["searchOptions"]))
	{
		const searchOptions = crm.options["searchOptions"];
		for (let optionName in searchOptions)
		{
			if (searchOptions.hasOwnProperty(optionName))
				options[optionName] = searchOptions[optionName];
		}
	}

	const postUrl = "/bitrix/components/bitrix/crm." + entityType + ".list/list.ajax.php";
	const postData = {
		'MODE' : 'SEARCH',
		'VALUE' : '[' + entityId + ']',
		'MULTI' : crm.PopupPrefix ? 'Y': 'N',
		'OPTIONS': options
	};
	const onSuccessHandler = function(data) {
		for (let i in data) {
			data[i]['selected'] = 'Y';
			crm.PopupAddItem(data[i]);
		}
		crm.PopupSave();
	};
	const onFailureHandler = function(data) {};

	if (crm.PopupCustomSearchHandler)
	{
		postData["ENTITY_TYPE"] = entityType;
		crm.PopupCustomSearchHandler(
			postData,
			onSuccessHandler,
			onFailureHandler,
		);
	}
	else
	{
		BX.ajax({
			url: postUrl,
			method: 'POST',
			dataType: 'json',
			data: postData,
			onsuccess: onSuccessHandler,
			onfailure: onFailureHandler
		});
	}
};

CRM.prototype.PopupAddItem = function(arParam)
{
	if (!BX.Type.isStringFilled(arParam['place']))
	{
		arParam['place'] = arParam['type'];
	}

	let bElementSelected = false;
	if (this.PopupItemSelected.hasOwnProperty(arParam['id'] + '-' + arParam['place']))
	{
		bElementSelected = true;
	}

	const itemBody = document.createElement("span");
	itemBody.id = 'crm-' + this.crmID + "_" + this.name + '-block-item-' + arParam['id'] + '-' + arParam['place'];
	itemBody.className = "crm-block-cont-item" + (bElementSelected ? " crm-block-cont-item-selected" : "");
	itemBody.check=bElementSelected? 1: 0;

	if (arParam['type'] === 'contact' || arParam['type'] === 'company') {
		const itemAvatar = document.createElement("span");
		itemAvatar.className = "crm-avatar";

		if (BX.Type.isStringFilled(arParam['image'])) {
			itemAvatar.style.background = 'url("' + arParam['image'] + '") no-repeat';
			itemAvatar.style.backgroundSize = 'contain';
		}

		itemBody.appendChild(itemAvatar);
	}

	const itemTitle = document.createElement("ins");
	itemTitle.appendChild(document.createTextNode(arParam['title']));
	const itemId = document.createElement("var");
	itemId.className = "crm-block-cont-var-id";
	itemId.appendChild(document.createTextNode(arParam['id']));
	const itemUrl = document.createElement("var");
	itemUrl.className = "crm-block-cont-var-url";
	itemUrl.appendChild(document.createTextNode(arParam['url']));

	const itemDesc = document.createElement("span");
	let descriptionHtml = BX.prop.getString(arParam, 'desc_html', '');
	if (descriptionHtml !== '')
	{
		descriptionHtml = BX.util.strip_tags(descriptionHtml);
	}
	else
	{
		descriptionHtml = this.prepareDescriptionHtml(BX.prop.getString(arParam, 'desc', ''));
	}
	itemDesc.innerHTML = descriptionHtml;

	const bodyBox = document.createElement("span");
	bodyBox.className = "crm-block-cont-contact-info";
	bodyBox.appendChild(itemTitle);
	bodyBox.appendChild(itemDesc);
	bodyBox.appendChild(itemId);
	bodyBox.appendChild(itemUrl);
	itemBody.appendChild(bodyBox);
	itemBody.appendChild(document.createElement("i"));

	let bDefinedItem = false;
	if (arParam['place'] !== 'search' && this.PopupItem[arParam['id'] + '-' + arParam['place']] !== undefined)
	{
		bDefinedItem = true;
	}
	else
	{
		this.PopupItem[arParam['id'] + '-' + arParam['place']] = arParam;
	}

	const placeHolder = BX("crm-" + this.crmID + "_" + this.name + "-block-" + arParam['place']);

	if (placeHolder !== null)
	{
		if (!bDefinedItem)
			placeHolder.appendChild(itemBody);

		CRM._bindPopupItem(this.crmID, itemBody, arParam["type"]);

		if (arParam['selected'] !== undefined && arParam['selected'] === 'Y')
			CRM.PopupSelectItem(this.crmID, itemBody, arParam['type'], true, true);
	}
};
CRM._bindPopupItem = function(ownerId, itemBody, type)
{
	BX.bind(
		itemBody,
		"click",
		function (e)
		{
			CRM.PopupSelectItem(ownerId, itemBody, type);

			return BX.PreventDefault(e);
		}
	);
};
CRM.prototype.prepareDescriptionHtml = function(str)
{
	return BX.type.isNotEmptyString(str) ? BX.util.htmlspecialchars(str) : "";
};
CRM.SearchChange = function(crmID)
{
	if (!obCrm[crmID])
		return false;

	const searchValue = obCrm[crmID].PopupSearchInput.value;
	if (searchValue === '')
	{
		return false;
	}

	const indexId = obCrm[crmID].PopupTabsIndexId;
	const indexPrefix = "crm-" + crmID + "_" + obCrm[crmID].name + "-tab-";
	const searchSelector = "crm-" + crmID + "_" + obCrm[crmID].name + "-block-search";
	let entityType = "";

	if (
		BX.Type.isStringFilled(indexId)
		&& indexId.length > indexPrefix.length
		&& indexId.substring(0, indexPrefix.length) === indexPrefix
	)
	{
		const typeString = indexId.substring(indexPrefix.length);
		if (typeString.length < 30 && /^[a-z]+(_[a-z]+)?(_[0-9]+)?$/.test(typeString))
		{
			entityType = typeString;
		}
	}

	if (entityType === "")
	{
		return false;
	}

	const options = {
		'REQUIRE_REQUISITE_DATA': (obCrm[crmID].options.requireRequisiteData) ? 'Y' : 'N'
	};

	if (BX.type.isPlainObject(obCrm[crmID].options["searchOptions"]))
	{
		const searchOptions = obCrm[crmID].options["searchOptions"];
		for (let optionName in searchOptions)
		{
			if (searchOptions.hasOwnProperty(optionName))
				options[optionName] = searchOptions[optionName];
		}
	}

	let postData = {
		"MODE" : "SEARCH",
		"VALUE" : searchValue,
		"MULTI" : obCrm[crmID].PopupPrefix ? "Y": "N",
		"OPTIONS": options
	};
	if (crmID === "new_invoice_product_button")
	{
		postData["ENTITY_TYPE"] = "INVOICE";
	}
	const postUrl = '/bitrix/components/bitrix/crm.' + entityType + '.list/list.ajax.php';
	const handlers = obCrm[crmID].onBeforeSearchListeners;
	if (handlers && BX.type.isArray(handlers) && handlers.length > 0)
	{
		const data = {'entityType': entityType, 'postData': postData};
		for (let j = 0; j < handlers.length; j++)
		{
			try
			{
				handlers[j](data);
			}
			catch(ex)
			{
			}

			postData = data['postData'];
		}
	}

	CRM.PopupShowSearchBlock(crmID, obCrm[crmID].PopupSearch[1]);

	setTimeout(
		function()
		{
			if (typeof(obCrm[crmID]) === "undefined")
			{
				return;
			}

			if (
				BX(searchSelector).innerHTML === ''
				&& indexId === indexPrefix + entityType
			)
			{
				const spanWait = document.createElement('div');
				spanWait.className = "crm-block-cont-search-wait";
				spanWait.innerHTML = obCrm[crmID].PopupLocalize['wait'];
				BX(searchSelector).appendChild(spanWait);
			}
		},
		3000
	);

	const onSuccessHandler = function(data)
	{
		if (indexId !== indexPrefix + entityType)
		{
			return false;
		}

		BX(searchSelector).className = 'crm-block-cont-block crm-block-cont-block-' + entityType;
		BX(searchSelector).innerHTML = '';

		let el = 0;
		for (let i in data) {
			data[i]['place'] = 'search';
			obCrm[crmID].PopupAddItem(data[i]);
			el++;
		}

		if (el === 0) {
			const spanWait = document.createElement('div');
			spanWait.className = "crm-block-cont-search-no-result";
			spanWait.innerHTML = obCrm[crmID].PopupLocalize['noresult'];
			BX(searchSelector).appendChild(spanWait);
		}
	};

	const onFailureHandler = function(data) {};

	if (obCrm[crmID].PopupCustomSearchHandler)
	{
		postData["ENTITY_TYPE"] = entityType;
		obCrm[crmID].PopupCustomSearchHandler(
			postData,
			onSuccessHandler,
			onFailureHandler,
		);
	}
	else
	{
		BX.ajax({
			url: postUrl,
			method: 'POST',
			dataType: 'json',
			data: postData,
			onsuccess: onSuccessHandler,
			onfailure: onFailureHandler
		});
	}
};

CRM.prototype.PopupCreateValue = function(arElements)
{
	let cellObject;
	let addInput;
	const inputBox = BX("crm-" + this.crmID + "_" + this.name + "-input-box");
	let textBox = BX("crm-" + this.crmID + "_" + this.name + "-text-box");

	if (!inputBox || !textBox)
	{
		return;
	}

	inputBox.innerHTML = '';

	const textBoxNew = document.createElement('DIV');
	textBoxNew.id = textBox.id;
	textBox.parentNode.replaceChild(textBoxNew, textBox);
	textBox = textBoxNew;

	const tableObject = document.createElement('table');
	tableObject.className = "field_crm";
	tableObject.cellPadding = "0";
	tableObject.cellSpacing = "0";
	const tbodyObject = document.createElement('TBODY');

	let nEl = 0;
	for (let type in arElements)
	{
		const rowObject = document.createElement("TR");
		rowObject.className = "crmPermTableTrHeader";

		if (this.PopupEntityType.length > 1)
		{
			cellObject = document.createElement("TD");
			cellObject.className = "field_crm_entity_type";
			cellObject.appendChild(document.createTextNode(this.PopupLocalize[type] + ":"));
			rowObject.appendChild(cellObject);
		}

		cellObject = document.createElement("TD");
		cellObject.className = "field_crm_entity";

		let nTypeEl = 0;
		for (let i in arElements[type])
		{
			addInput = document.createElement('input');
			addInput.type = 'text';
			addInput.name = this.name + (this.PopupMultiple ? '[]' : '');
			addInput.value = arElements[type][i]['id'];

			inputBox.appendChild(addInput);

			const addCrmLink = document.createElement('a');
			addCrmLink.href = arElements[type][i]['url'];
			addCrmLink.target = "_blank";
			addCrmLink.appendChild(document.createTextNode(arElements[type][i]['title']));
			cellObject.appendChild(addCrmLink);

			const addCrmDeleteLink = document.createElement('span');
			addCrmDeleteLink.className = "crm-element-item-delete";
			addCrmDeleteLink.id =
				"deleted-crm-" + this.crmID + '_' + this.name + "-block-item-"
				+ arElements[type][i]['id'] + '-' + arElements[type][i]['place']
			;
			BX.bind(
				addCrmDeleteLink,
				"click",
				function (crmId)
				{
					return function ()
					{
						CRM.PopupUnselectItem(
							crmId,
							this.id.substring(8),
							"selected-" + this.id.substring(8)
						);
						CRM.PopupSave2(crmId);
					};
				}(this.crmID)
			);
			cellObject.appendChild(addCrmDeleteLink);

			//Strongly required for user field value change event
			BX.fireEvent(addInput, "change");

			nTypeEl++;
			nEl++;
		}

		if (nTypeEl > 0)
		{
			rowObject.appendChild(cellObject);
			tbodyObject.appendChild(rowObject);
		}

	}
	if (nEl === 0)
	{
		addInput = document.createElement('input');
		addInput.type = 'text';
		addInput.name = this.name + (this.PopupMultiple ? '[]' : '');
		addInput.value = '';
		inputBox.appendChild(addInput);

		//Strongly required for user field value change event
		BX.fireEvent(addInput, "change");
	}
	tableObject.appendChild(tbodyObject);
	textBox.appendChild(tableObject);

	if (this.el)
	{
		if (nEl > 0)
		{
			this.el.innerHTML = this.PopupLocalize['edit'];
		}
		else {
			BX.cleanNode(textBox, false);

			if (BX.browser.IsIE())
			{
				// HACK: empty DIV has height in IE7 - make it collapse to zero.
				textBox.style.fontSize = '0px';
				textBox.style.lineHeight = '0px';
			}
			this.el.innerHTML = this.PopupLocalize['add'];
		}
	}
};

CRM.prototype.popupShowMarkup = function()
{
	let i;
	let layer5;
	const layer1 = document.createElement("div");
	layer1.id = "crm-" + this.crmID + "_" + this.name + "-block-content-wrap";
	layer1.className = "crm-block-content";
	const table1 = document.createElement('table');
	table1.className = "crm-box-layout";
	if (!this.PopupMultiple)
	{
		table1.className = table1.className + " crm-single-column";
	}
	table1.cellSpacing = "0";

	const table1body = document.createElement('tbody');
	const table1bodyTr1 = document.createElement("TR");
	const table1bodyTd1 = document.createElement("TD");
	table1bodyTd1.className = "crm-block-cont-left";

	let layer4 = document.createElement("div");
	layer4.id = "crm-" + this.crmID + "_" + this.name + "-tabs";
	layer4.className = "crm-block-cont-tabs-wrap";
	if (this.PopupEntityType.length === 1)
	{
		layer4.className = layer4.className + " crm-single-entity";
	}

	let firstTab = true;
	for (i in this.PopupEntityType)
	{
		const tab1 = document.createElement("span");
		tab1.className = "crm-block-cont-tabs" + (firstTab ? " selected" : '');
		tab1.id = "crm-" + this.crmID + "_" + this.name + "-tab-" + this.PopupEntityType[i];
		const tab1span = document.createElement("span");
		const tab1span1 = document.createElement("span");
		tab1span1.appendChild(document.createTextNode(this.PopupLocalize[this.PopupEntityType[i]]));
		tab1span.appendChild(tab1span1);
		tab1.appendChild(tab1span);
		layer4.appendChild(tab1);
		firstTab = false;
	}

	table1bodyTd1.appendChild(layer4);

	layer4 = document.createElement("div");
	layer4.id = "crm-" + this.crmID + "_" + this.name + "-block-cont-search";
	layer4.className = "crm-block-cont-search";

	const input = document.createElement("input");
	input.type = "text";
	input.id = "crm-" + this.crmID + "_" + this.name + "-search-input";
	layer4.appendChild(input);

	let search1 = document.createElement("span");
	search1.className = "crm-block-cont-search-tab selected";
	search1.appendChild(document.createElement("span"));

	let search1a = document.createElement("a");
	search1a.href = "#";
	search1a.appendChild(document.createTextNode(this.PopupLocalize['last']));
	search1.appendChild(search1a);

	search1.appendChild(document.createElement("span"));
	layer4.appendChild(search1);

	search1 = document.createElement("span");
	search1.className = "crm-block-cont-search-tab";
	search1.appendChild(document.createElement("span"));

	search1a = document.createElement("a");
	search1a.href = "#";
	search1a.appendChild(document.createTextNode(this.PopupLocalize['search']));
	search1.appendChild(search1a);

	search1.appendChild(document.createElement("span"));
	layer4.appendChild(search1);

	table1bodyTd1.appendChild(layer4);

	layer4 = document.createElement("div");
	layer4.className = "popup-window-hr popup-window-buttons-hr";
	layer4.appendChild(document.createElement("b"));
	table1bodyTd1.appendChild(layer4);

	layer4 = document.createElement("div");
	layer4.id = "crm-" + this.crmID + "_" + this.name + "-blocks";
	layer4.className = "crm-block-cont-blocks-wrap";

	firstTab = true;
	for (i in this.PopupEntityType){
		layer5 = document.createElement("div");
		layer5.id = "crm-" + this.crmID + "_" + this.name + "-block-" + this.PopupEntityType[i];
		layer5.className = "crm-block-cont-block crm-block-cont-block-" + this.PopupEntityType[i];
		layer5.style.display = firstTab ? "block" : "none";
		layer4.appendChild(layer5);
		firstTab = false;
	}

	layer5 = document.createElement("div");
	layer5.id = "crm-" + this.crmID + "_" + this.name + "-block-search";
	layer5.className = "crm-block-cont-block";
	layer5.style.display = "none";
	layer4.appendChild(layer5);

	layer5 = document.createElement("div");
	layer5.id = "crm-" + this.crmID + "_" + this.name + "-block-declared";
	layer5.className = "crm-block-cont-block";
	layer5.style.display = "none";
	layer4.appendChild(layer5);

	table1bodyTd1.appendChild(layer4);
	table1bodyTr1.appendChild(table1bodyTd1);
	const table1bodyTd2 = document.createElement("TD");
	table1bodyTd2.className = "crm-block-cont-right";

	const layer2 = document.createElement("div");
	layer2.className = "crm-block-cont-right-wrap-item";

	for (i in this.PopupEntityType)
	{
		const layer3 = document.createElement("div");
		layer3.className = "crm-block-cont-right-item";
		layer3.id = "crm-" + this.crmID + "_" + this.name + "-block-" + this.PopupEntityType[i] + "-selected";
		const layer3cont = document.createElement("span");
		layer3cont.className = "crm-block-cont-right-title";
		layer3cont.appendChild(document.createTextNode(this.PopupLocalize[this.PopupEntityType[i]]));
		layer3cont.appendChild(document.createTextNode(' ('));
		const spanDigit = document.createElement("span");
		spanDigit.className = "crm-block-cont-right-title-count";
		spanDigit.appendChild(document.createTextNode('0'));
		layer3cont.appendChild(spanDigit);
		layer3cont.appendChild(document.createTextNode(')'));
		layer3.appendChild(layer3cont);
		layer2.appendChild(layer3);
	}

	table1bodyTd2.appendChild(layer2);
	table1bodyTr1.appendChild(table1bodyTd2);
	table1body.appendChild(table1bodyTr1);
	table1.appendChild(table1body);
	layer1.appendChild(table1);

	const placeHolder = document.createElement("div");
	document.body.appendChild(placeHolder);

	placeHolder.id = "crm-" + this.crmID + "_" + this.name + "-html-box";
	placeHolder.className = "crm-place-holder";
	placeHolder.appendChild(layer1);

	if (this.div)
	{
		const inputBox = document.createElement("div");
		inputBox.id = "crm-" + this.crmID + "_" + this.name + "-input-box";
		inputBox.style.display = "none";
		this.div.appendChild(inputBox);

		const textBoxId = "crm-" + this.crmID + "_" + this.name + "-text-box";
		if (BX(textBoxId))
		{
			throw  "Already exists " + textBoxId;
		}

		const textBox = document.createElement("div");
		this.div.insertBefore(textBox, this.div.firstChild);
		textBox.id = "crm-" + this.crmID + "_" + this.name + "-text-box";
	}
};
