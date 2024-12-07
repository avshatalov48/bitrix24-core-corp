BX.CrmSaleSettings = (function ()
{
	var CrmSaleSettings = function (parameters)
	{
		this.randomString = parameters.randomString;
		this.tabs = null;
		this.ajaxUrl = parameters.ajaxUrl;
		this.data = parameters.data;
		this.oldData = BX.clone(this.data);
		this.max_sort = {};
		this.requestIsRunning = false;
		this.totalNumberFields = parameters.totalNumberFields;
		this.checkSubmit = false;

		this.defaultColor = "#ACE9FB";
		this.defaultFinalSuccessColor = "#DBF199";
		this.defaultFinalUnSuccessColor = "#FFBEBD";
		this.defaultLineColor = "#D3EEF9";
		this.textColorLight = "#FFF";
		this.textColorDark = "#545C69";

		this.entityId = parameters.entityId;
		this.hasSemantics = !!parameters.hasSemantics;
		this.isDestroySidePanel = !!parameters.isDestroySidePanel;

		this.jsClass = "CrmSaleSettings_"+parameters.randomString;
		this.contentIdPrefix = "content_";
		this.contentClass = "crm-status-content";
		this.contentActiveClass = "crm-status-content active";

		this.fieldNameIdPrefix = "field-name-";
		this.fieldEditNameIdPrefix = "field-edit-name-";
		this.fieldHiddenNameIdPrefix = "field-hidden-name-";
		this.spanStoringNameIdPrefix = "field-title-inner-";
		this.mainDivStorageFieldIdPrefix = "field-phase-";
		this.fieldSortHiddenIdPrefix = "field-sort-";
		this.fieldHiddenNumberIdPrefix = "field-number-";
		this.extraStorageFieldIdPrefix = "extra-storage-";
		this.finalSuccessStorageFieldIdPrefix = "final-success-storage-";
		this.finalStorageFieldIdPrefix = "final-storage-";
		this.previouslyScaleIdPrefix = "previously-scale-";
		this.previouslyScaleNumberIdPrefix = "previously-scale-number-";
		this.previouslyScaleFinalSuccessIdPrefix = "previously-scale-final-success-";
		this.previouslyScaleNumberFinalSuccessIdPrefix = "previously-scale-number-final-success-";
		this.previouslyScaleFinalUnSuccessIdPrefix = "previously-scale-final-un-success-";
		this.previouslyScaleNumberFinalUnSuccessIdPrefix = "previously-scale-number-final-un-success-";
		this.previouslyScaleFinalCellIdPrefix = "previously-scale-final-cell-";
		this.previouslyScaleNumberFinalCellIdPrefix = "previously-scale-number-final-cell-";
		this.funnelSuccessIdPrefix = "config-funnel-success-";
		this.funnelUnSuccessIdPrefix = "config-funnel-unsuccess-";

		this.successFields = parameters.successFields;
		this.unSuccessFields = parameters.unSuccessFields;
		this.initialFields = parameters.initialFields;
		this.extraFields = parameters.extraFields;
		this.finalFields = parameters.finalFields;
		this.extraFinalFields = parameters.extraFinalFields;

		this.dataFunnel = [];
		this.colorFunnel = [];
		this.initAmChart = false;

		this.footer = BX("crm-configs-footer");
		this.windowSize = {};
		this.scrollPosition = {};
		this.contentPosition = {};
		this.footerPosition = {};
		this.limit = 0;
		this.footerFixed = true;
		this.blockFixed = !!parameters.blockFixed;

		this.initAmCharts();
		this.showError();
		this.init();
	};

	CrmSaleSettings.prototype.selectTab = function(tabId)
	{
		var div = BX(this.contentIdPrefix+tabId);
		if(div.className == this.contentActiveClass)
			return;

		for (var i = 0, cnt = this.tabs.length; i < cnt; i++)
		{
			var content = BX(this.contentIdPrefix+this.tabs[i]);
			if(content.className == this.contentActiveClass)
			{
				this.showTab(this.tabs[i], false);
				content.className = this.contentClass;
				break;
			}
		}

		this.showTab(tabId, true);
		div.className = this.contentActiveClass;

		BX("ACTIVE_TAB").value = "status_tab_" + tabId;
		this.entityId = tabId;
		this.hasSemantics = CrmSaleSettings.hasSemantics(this.entityId);

		this.processingFooter();

		if(this.hasSemantics)
		{
			AmCharts.handleLoad();
		}
	};

	CrmSaleSettings.prototype.showTab = function(tabId, on)
	{
		var sel = (on? "status_tab_active":"");
		BX("status_tab_"+tabId).className = "status_tab "+sel;
	};

	CrmSaleSettings.prototype.statusReset = function()
	{
		BX("ACTION").value = "reset";
		document.forms["crmStatusForm"].submit();
	};

	CrmSaleSettings.prototype.destroySidePanel = function()
	{
		if (top.BX.SidePanel.Instance)
		{
			if (top.BX.SidePanel.Instance.getTopSlider())
			{
				BX.addCustomEvent(
					top.BX.SidePanel.Instance.getTopSlider().getWindow(),
					"SidePanel.Slider:onClose",
					function (event) {
						top.BX.SidePanel.Instance.destroy(event.getSlider().getUrl());
					}
				);
			}
			top.BX.SidePanel.Instance.close();
		}
	};

	CrmSaleSettings.prototype.recoveryName = function(fieldId, name)
	{
		var fieldHiddenNumber = this.searchElement("input", this.fieldHiddenNumberIdPrefix+fieldId),
			fieldName = this.searchElement("span", this.fieldNameIdPrefix+fieldId),
			fieldHiddenName = this.searchElement("input", this.fieldHiddenNameIdPrefix+fieldId);

		fieldName.innerHTML = BX.util.htmlspecialchars(fieldHiddenNumber.value+". "+name);
		fieldHiddenName.value = name;
		this.data[this.entityId][fieldId].NAME = name;

		if(this.initAmChart)
		{
			this.recalculateSort();
		}
	};

	CrmSaleSettings.prototype.editField = function(fieldId)
	{
		var domElement, fieldDiv = this.searchElement("div", this.mainDivStorageFieldIdPrefix+fieldId),
			spanStoring = this.searchElement("span", this.spanStoringNameIdPrefix+fieldId),
			fieldName = this.searchElement("span", this.fieldNameIdPrefix+fieldId),
			fieldHiddenName = this.searchElement("input", this.fieldHiddenNameIdPrefix+fieldId);

		if(!fieldHiddenName)
		{
			return;
		}

		domElement = BX.create("span", {
			props: {className: "transaction-stage-phase-title-input-container"},
			children: [
				BX.create("input", {
					props: {id: this.fieldEditNameIdPrefix+fieldId},
					attrs: {
						type: "text",
						value: fieldHiddenName.value,
						onkeydown: "if (event.keyCode==13) {BX['"+this.jsClass+"'].saveFieldValue(\""+fieldId+"\", this);}",
						onblur: "BX['"+this.jsClass+"'].saveFieldValue(\""+fieldId+"\", this);",
						"data-onblur": "1"
					}
				})
			]
		});

		spanStoring.style.width = "100%";
		fieldDiv.setAttribute("ondblclick", "");
		fieldName.innerHTML = "";
		fieldName.appendChild(domElement);

		var fieldEditName = this.searchElement("input", this.fieldEditNameIdPrefix+fieldId);
		fieldEditName.focus();
		fieldEditName.selectionStart = BX(this.fieldEditNameIdPrefix+fieldId+"").value.length;
	};

	CrmSaleSettings.prototype.openPopupBeforeDeleteField = function(fieldId)
	{
		if(isNaN(parseInt(fieldId)))
		{
			this.deleteField(fieldId);
			return;
		}

		var message = "";
		if(this.hasSemantics)
		{
			message = BX.message("CRM_STATUS_DELETE_FIELD_QUESTION");
		}

		if(!BX.type.isNotEmptyString(message))
		{
			message = BX.message("CRM_STATUS_DELETE_FIELD_QUESTION");
		}

		var content = BX.create(
			"p",
			{
				props: { className: "bx-crm-popup-paragraph" },
				html: message
			}
		);


		this.modalWindow({
			modalId: "bx-crm-popup",
			title: BX.message("CRM_STATUS_CONFIRMATION_DELETE_TITLE"),
			overlay: false,
			content: [content],
			events : {
				onPopupClose : function() {
					this.destroy();
				}
			},
			buttons: [
				BX.create("a", {
					text : BX.message("CRM_STATUS_CONFIRMATION_DELETE_CANCEL_BUTTON"),
					props: {
						className: "webform-small-button webform-small-button-accept"
					},
					events : {
						click : BX.delegate(function (e) {
							BX.PopupWindowManager.getCurrentPopup().close();
						}, this)
					}
				}),
				BX.create("a", {
					text : BX.message("CRM_STATUS_CONFIRMATION_DELETE_SAVE_BUTTON"),
					props: {
						className: "webform-small-button webform-button-cancel"
					},
					events : {
						click : BX.delegate(function (e)
						{
							this.deleteField(fieldId);
							BX.PopupWindowManager.getCurrentPopup().close();
						}, this)
					}
				})
			]
		});
	};

	CrmSaleSettings.prototype.deleteField = function(fieldId)
	{
		var fieldDiv = this.searchElement("div", this.mainDivStorageFieldIdPrefix+fieldId),
			parentNode = fieldDiv.parentNode;

		var fieldHidden = BX.create("input", {
			attrs: {
				type: "hidden",
				value: fieldId,
				name: "LIST["+this.entityId+"][REMOVE]["+fieldId+"][FIELD_ID]"
			}
		});

		BX(this.contentIdPrefix+this.entityId).appendChild(fieldHidden);
		parentNode.removeChild(fieldDiv);
		this.recalculateSort();
	};

	CrmSaleSettings.prototype.modalWindow = function(params)
	{
		params = params || {};
		params.title = params.title || false;
		params.bindElement = params.bindElement || null;
		params.overlay = typeof params.overlay == "undefined" ? true : params.overlay;
		params.autoHide = params.autoHide || false;
		params.closeIcon = typeof params.closeIcon == "undefined"? {right: "20px", top: "10px"} : params.closeIcon;
		params.modalId = params.modalId || "crm" + (Math.random() * (200000 - 100) + 100);
		params.withoutContentWrap = typeof params.withoutContentWrap == "undefined" ? false : params.withoutContentWrap;
		params.contentClassName = params.contentClassName || "";
		params.contentStyle = params.contentStyle || {};
		params.content = params.content || [];
		params.buttons = params.buttons || false;
		params.events = params.events || {};
		params.withoutWindowManager = !!params.withoutWindowManager || false;

		var contentDialogChildren = [];
		if (params.title) {
			contentDialogChildren.push(BX.create("div", {
				props: {
					className: "bx-crm-popup-title"
				},
				text: params.title
			}));
		}
		if (params.withoutContentWrap) {
			contentDialogChildren = contentDialogChildren.concat(params.content);
		}
		else {
			contentDialogChildren.push(BX.create("div", {
				props: {
					className: "bx-crm-popup-content " + params.contentClassName
				},
				style: params.contentStyle,
				children: params.content
			}));
		}
		var buttons = [];
		if (params.buttons) {
			for (var i in params.buttons) {
				if (!params.buttons.hasOwnProperty(i)) {
					continue;
				}
				if (i > 0) {
					buttons.push(BX.create("SPAN", {html: "&nbsp;"}));
				}
				buttons.push(params.buttons[i]);
			}

			contentDialogChildren.push(BX.create("div", {
				props: {
					className: "bx-crm-popup-buttons"
				},
				children: buttons
			}));
		}

		var contentDialog = BX.create("div", {
			props: {
				className: "bx-crm-popup-container"
			},
			children: contentDialogChildren
		});

		params.events.onPopupShow = BX.delegate(function () {
			if (buttons.length) {
				firstButtonInModalWindow = buttons[0];
				BX.bind(document, "keydown", BX.proxy(this._keyPress, this));
			}

			if(params.events.onPopupShow)
				BX.delegate(params.events.onPopupShow, BX.proxy_context);
		}, this);
		var closePopup = params.events.onPopupClose;
		params.events.onPopupClose = BX.delegate(function () {

			firstButtonInModalWindow = null;
			try
			{
				BX.unbind(document, "keydown", BX.proxy(this._keypress, this));
			}
			catch (e) { }

			if(closePopup)
			{
				BX.delegate(closePopup, BX.proxy_context)();
			}

			if(params.withoutWindowManager)
			{
				delete windowsWithoutManager[params.modalId];
			}

			BX.proxy_context.destroy();
		}, this);

		var modalWindow;
		if(params.withoutWindowManager)
		{
			if(!!windowsWithoutManager[params.modalId])
			{
				return windowsWithoutManager[params.modalId]
			}
			modalWindow = new BX.PopupWindow(params.modalId, params.bindElement, {
				content: contentDialog,
				closeByEsc: true,
				closeIcon: params.closeIcon,
				autoHide: params.autoHide,
				overlay: params.overlay,
				events: params.events,
				buttons: [],
				zIndex : isNaN(params["zIndex"]) ? 0 : params.zIndex
			});
			windowsWithoutManager[params.modalId] = modalWindow;
		}
		else
		{
			modalWindow = BX.PopupWindowManager.create(params.modalId, params.bindElement, {
				content: contentDialog,
				closeByEsc: true,
				closeIcon: params.closeIcon,
				autoHide: params.autoHide,
				overlay: params.overlay,
				events: params.events,
				buttons: [],
				zIndex : isNaN(params["zIndex"]) ? 0 : params.zIndex
			});

		}

		modalWindow.show();

		return modalWindow;
	};

	CrmSaleSettings.prototype.saveFieldValue = function(fieldId, input)
	{
		var newFieldName = "", newFieldValue = input.value,
			fieldHiddenNumber = this.searchElement("input", this.fieldHiddenNumberIdPrefix+fieldId),
			fieldName = this.searchElement("span", this.fieldNameIdPrefix+fieldId),
			fieldDiv = this.searchElement("div", this.mainDivStorageFieldIdPrefix+fieldId),
			spanStoring = this.searchElement("span", this.spanStoringNameIdPrefix+fieldId),
			fieldHiddenName = this.searchElement("input", this.fieldHiddenNameIdPrefix+fieldId);

		newFieldName += fieldHiddenNumber.value+". "+newFieldValue;
		input.onblur = "";

		if(newFieldValue == "")
		{
			if(fieldHiddenNumber.value == 1)
			{
				newFieldValue = this.data[this.entityId][fieldId].NAME_INIT;
			}
			else
			{
				var name = BX.message("CRM_STATUS_NEW");
				if(this.hasSemantics)
				{
					name = BX.message("CRM_STATUS_NEW_"+this.entityId);
				}
				newFieldValue = name;
			}

		}

		fieldName.innerHTML = BX.util.htmlspecialchars(newFieldName);
		fieldDiv.setAttribute("ondblclick", "BX[\""+this.jsClass+"\"].editField(\""+fieldId+"\");");
		spanStoring.style.width = "";
		fieldHiddenName.value = newFieldValue;

		this.data[this.entityId][fieldId].NAME = newFieldValue;
		if(this.initAmChart)
		{
			this.recalculateSort();
		}
	};

	CrmSaleSettings.prototype.searchElement = function(tag, id)
	{
		var element = BX.findChildren(BX(this.contentIdPrefix+this.entityId),
			{"tag": tag, "attribute": {"id": id}}, true);
		if(element[0])
		{
			return element[0];
		}
		return null;
	};

	CrmSaleSettings.prototype.addField = function(element)
	{
		var parentNode = element.parentNode, fieldId = 1,
			color = this.defaultColor, name = BX.message("CRM_STATUS_NEW");

		if(parentNode.id == "final-storage-"+this.entityId)
		{
			color = this.defaultFinalUnSuccessColor;
			this.addCellFinalScale();
		}
		else
		{
			this.addCellMainScale();
		}

		for (var k in this.data[this.entityId])
		{
			fieldId++;
		}

		if(this.hasSemantics)
		{
			name = BX.message("CRM_STATUS_NEW_"+this.entityId);
		}
		else
		{
			color = this.defaultLineColor;
		}

		var id = "n"+fieldId;
		this.data[this.entityId][id] = {
			ID: id,
			SORT: 10,
			NAME: name,
			ENTITY_ID: this.entityId,
			COLOR: color
		};

		parentNode.insertBefore(this.createStructureHtml(id), element);
		this.recalculateSort();
		this.editField(id);
	};

	CrmSaleSettings.prototype.recalculateSort = function()
	{
		var fieldId, parentId;

		var structureFields = BX.findChildren(BX(this.contentIdPrefix+this.entityId),
			{"tag": "div", "attribute": {"data-calculate": "1"}}, true);
		if(!structureFields)
		{
			return;
		}

		for(var i = 0; i < structureFields.length; i++)
		{
			parentId = structureFields[i].parentNode.id;

			if(parentId == this.extraStorageFieldIdPrefix+this.entityId)
			{
				structureFields[i].setAttribute("data-success", "1");
			}
			else if(parentId == this.finalStorageFieldIdPrefix+this.entityId)
			{
				structureFields[i].setAttribute("data-success", "0");
			}

			var number = i+1;
			var sort = number*10;
			fieldId = structureFields[i].getAttribute("id").replace(this.mainDivStorageFieldIdPrefix, "");

			var inputFields = BX.findChildren(structureFields[i], {"tag": "input", "attribute": {"data-onblur": "1"}}, true);
			if(inputFields.length)
			{
				this.saveFieldValue(fieldId, inputFields[0]);
			}

			structureFields[i].setAttribute("data-sort", ""+sort+"");

			var fieldName = this.searchElement("span", this.fieldNameIdPrefix+fieldId),
				fieldHiddenName = this.searchElement("input", this.fieldHiddenNameIdPrefix+fieldId),
				fieldHiddenNumber = this.searchElement("input", this.fieldHiddenNumberIdPrefix+fieldId),
				fieldSortHidden = this.searchElement("input", this.fieldSortHiddenIdPrefix+fieldId);

			fieldName.innerHTML = BX.util.htmlspecialchars(number+". "+fieldHiddenName.value);
			fieldHiddenNumber.value = number;
			fieldSortHidden.value = sort;

			this.data[this.entityId][fieldId].SORT = sort;
		}

		if(this.initAmChart && this.hasSemantics)
		{
			var successFields = BX.findChildren(BX(this.contentIdPrefix+this.entityId),
				{"tag": "div", "attribute": {"data-success": "1"}}, true);
			if(successFields)
			{
				this.successFields[this.entityId] = [];
				for(var k = 0; k < successFields.length; k++)
				{
					fieldId = successFields[k].getAttribute("id").replace(this.mainDivStorageFieldIdPrefix, "");
					this.successFields[this.entityId][k] = this.data[this.entityId][fieldId];
				}
			}

			var unSuccessFields = BX.findChildren(BX(this.contentIdPrefix+this.entityId),
				{"tag": "div", "attribute": {"data-success": "0"}}, true);
			if(successFields)
			{
				this.unSuccessFields[this.entityId] = [];
				for(var j = 0; j < unSuccessFields.length; j++)
				{
					fieldId = unSuccessFields[j].getAttribute("id").replace(this.mainDivStorageFieldIdPrefix, "");
					this.unSuccessFields[this.entityId][j] = this.data[this.entityId][fieldId];
				}
			}

			AmCharts.handleLoad();
		}

		this.changeCellScale();
	};

	CrmSaleSettings.prototype.changeCellScale = function()
	{
		if(!this.hasSemantics)
		{
			return;
		}

		if(!this.successFields[this.entityId] || !this.unSuccessFields[this.entityId])
		{
			return;
		}

		var scale = BX.findChildren(BX(this.previouslyScaleIdPrefix+this.entityId), {"tag": "td",
				"attribute": {"data-scale-type": "main"}}, true),
			scaleNumber = BX.findChildren(BX(this.previouslyScaleNumberIdPrefix+this.entityId), {"tag": "td",
				"attribute": {"data-scale-type": "main"}}, true),
			scaleFinalSuccess = BX.findChildren(BX(this.previouslyScaleFinalSuccessIdPrefix+this.entityId),
				{"tag": "td"}, true),
			scaleNumberFinalSuccess = BX.findChildren(BX(this.previouslyScaleNumberFinalSuccessIdPrefix+this.entityId),
				{"tag": "td"}, true);

		var mainCount = this.successFields[this.entityId].length - 1,
			scaleCount = scale.length;

		if(mainCount > scaleCount)
		{
			for(var j = scaleCount; j<mainCount; j++)
			{
				this.addCellMainScale();
			}
			this.changeCellScale();
			return;
		}
		else if(mainCount < scaleCount)
		{
			this.deleteCellMainScale(scaleCount-mainCount);
			this.changeCellScale();
			return;
		}

		var number, color;
		for(var i = 0; i < mainCount; i++)
		{
			if(scale[i] && scaleNumber[i])
			{
				if(this.successFields[this.entityId][i].COLOR)
				{
					color = this.successFields[this.entityId][i].COLOR;
				}
				else
				{
					color = this.defaultColor;
				}

				scale[i].style.background = color;
				number = i + 1;
				scaleNumber[i].getElementsByTagName("span")[0].innerHTML = number;
			}
		}

		if(scaleFinalSuccess[0] && scaleNumberFinalSuccess[0])
		{
			if(this.successFields[this.entityId][mainCount].COLOR)
			{
				color = this.successFields[this.entityId][mainCount].COLOR;
			}
			else
			{
				color = this.defaultFinalSuccessColor;
			}
			number++;
			scaleFinalSuccess[0].style.background = color;
			scaleNumberFinalSuccess[0].getElementsByTagName("span")[0].innerHTML = number;
		}

		var scaleFinalUnSuccess = BX.findChildren(BX(this.previouslyScaleFinalUnSuccessIdPrefix+this.entityId),
			{"tag": "td"}, true),
			scaleNumberFinalUnSuccess = BX.findChildren(BX(this.previouslyScaleNumberFinalUnSuccessIdPrefix+this.entityId),
				{"tag": "td"}, true);
		var finalCount = this.unSuccessFields[this.entityId].length,
			scaleFinalUnSuccessCount = scaleFinalUnSuccess.length;

		if(finalCount > scaleFinalUnSuccessCount)
		{
			for(var l = scaleFinalUnSuccessCount; l<finalCount; l++)
			{
				this.addCellFinalScale();
			}
			this.changeCellScale();
			return;
		}
		else if(finalCount < scaleFinalUnSuccessCount)
		{
			this.deleteCellFinalScale(scaleFinalUnSuccessCount-finalCount);
			this.changeCellScale();
			return;
		}
		for(var h = 0; h < finalCount; h++)
		{
			if(scaleFinalUnSuccess[h] && scaleNumberFinalUnSuccess[h])
			{
				if(this.unSuccessFields[this.entityId][h].COLOR)
				{
					color = this.unSuccessFields[this.entityId][h].COLOR;
				}
				else
				{
					color = this.defaultFinalUnSuccessColor;
				}

				scaleFinalUnSuccess[h].style.background = color;
				number++;
				scaleNumberFinalUnSuccess[h].getElementsByTagName("span")[0].innerHTML = number;
			}
		}
	};

	CrmSaleSettings.prototype.addCellMainScale = function()
	{
		if(!this.hasSemantics)
		{
			return;
		}

		var scaleNumber = BX.findChildren(BX(this.previouslyScaleNumberIdPrefix+this.entityId), {"tag": "td",
				"attribute": {"data-scale-type": "main"}}, true),
			scaleHtml = BX.create("td", {
				attrs: {"data-scale-type": "main"},
				html: "&nbsp;"
			}),
			scaleNumberHtml = BX.create("td", {
				attrs: {"data-scale-type": "main"},
				children: [
					BX.create("span", {
						props: {className: "stage-name"},
						html: scaleNumber.length
					})
				]
			});

		BX(this.previouslyScaleIdPrefix+this.entityId).insertBefore(
			scaleHtml, BX(this.previouslyScaleFinalCellIdPrefix+this.entityId));
		BX(this.previouslyScaleNumberIdPrefix+this.entityId).insertBefore(
			scaleNumberHtml, BX(this.previouslyScaleNumberFinalCellIdPrefix+this.entityId));
	};

	CrmSaleSettings.prototype.addCellFinalScale = function()
	{
		if(!this.hasSemantics)
		{
			return;
		}

		var scaleNumber = BX.findChildren(BX(this.previouslyScaleNumberFinalUnSuccessIdPrefix+this.entityId),
			{"tag": "td"}, true),
			scaleHtml = BX.create("td", {
				html: "&nbsp;"
			}),
			scaleNumberHtml = BX.create("td", {
				children: [
					BX.create("span", {
						props: {className: "stage-name"},
						html: scaleNumber.length
					})
				]
			});

		BX(this.previouslyScaleFinalUnSuccessIdPrefix+this.entityId).appendChild(scaleHtml);
		BX(this.previouslyScaleNumberFinalUnSuccessIdPrefix+this.entityId).appendChild(scaleNumberHtml);
	};

	CrmSaleSettings.prototype.deleteCellMainScale = function(quantity)
	{
		if(!this.hasSemantics)
		{
			return;
		}

		var scaleCell = BX.findChildren(BX(this.previouslyScaleIdPrefix+this.entityId),
			{"tag": "td", "attribute": {"data-scale-type": "main"}}, true),
			scaleCellNumber = BX.findChildren(BX(this.previouslyScaleNumberIdPrefix+this.entityId),
				{"tag": "td", "attribute": {"data-scale-type": "main"}}, true);

		for(var k = 0; k < quantity; k++)
		{
			BX(this.previouslyScaleIdPrefix+this.entityId).removeChild(scaleCell[k]);
			BX(this.previouslyScaleNumberIdPrefix+this.entityId).removeChild(scaleCellNumber[k]);
		}

	};

	CrmSaleSettings.prototype.deleteCellFinalScale = function(quantity)
	{
		if(!this.hasSemantics)
		{
			return;
		}

		var scaleCell = BX.findChildren(BX(this.previouslyScaleFinalUnSuccessIdPrefix+this.entityId),
			{"tag": "td"}, true),
			scaleCellNumber = BX.findChildren(BX(this.previouslyScaleNumberFinalUnSuccessIdPrefix+this.entityId),
				{"tag": "td"}, true);

		for(var k = 0; k < quantity; k++)
		{
			BX(this.previouslyScaleFinalUnSuccessIdPrefix+this.entityId).removeChild(scaleCell[k]);
			BX(this.previouslyScaleNumberFinalUnSuccessIdPrefix+this.entityId).removeChild(scaleCellNumber[k]);
		}

	};

	CrmSaleSettings.prototype.createStructureHtml = function(fieldId)
	{
		var domElement, fieldObject = this.data[this.entityId][fieldId];

		var iconClass = "", color = this.textColorDark, blockClass="", img;
		if(this.hasSemantics)
		{
			iconClass = "light-icon";
			color = this.textColorLight;
			blockClass = "transaction-stage-phase-dark";
			img = BX.create("div", {
				props: {className: "transaction-stage-phase-panel-button"},
				attrs: {
					onclick: "BX[\""+this.jsClass+"\"].correctionColorPicker(event, \""+fieldObject.ID+"\");"
				}
			});
		}

		domElement = BX.create("div", {
			props: {id: this.mainDivStorageFieldIdPrefix+fieldObject.ID, className: "transaction-stage-phase draghandle"},
			attrs: {
				ondblclick: "BX[\""+this.jsClass+"\"].editField(\""+fieldObject.ID+"\");",
				"data-sort": fieldObject.SORT,
				"data-calculate": 1,
				"data-space": fieldObject.ID,
				"style": "background: "+fieldObject.COLOR+"; color:"+color+";"
			},
			children: [
				BX.create("div", {
					props: {
						id: "phase-panel",
						className: blockClass+" transaction-stage-phase-panel"
					},
					attrs: {
						"data-class": "transaction-stage-phase-panel"
					},
					children: [
						img,
						BX.create("div", {
							props: {className: "transaction-stage-phase-panel-button " +
								"transaction-stage-phase-panel-button-close"},
							attrs: {
								onclick: "BX[\""+this.jsClass+"\"].openPopupBeforeDeleteField(\""+fieldObject.ID+"\");"
							}
						})
					]
				}),
				BX.create("span", {
					props: {
						id: "transaction-stage-phase-icon",
						className: iconClass+" transaction-stage-phase-icon transaction-stage-phase-icon-move draggable"
					},
					attrs: {
						"data-class": "transaction-stage-phase-icon transaction-stage-phase-icon-move draggable"
					},
					children: [
						BX.create("span", {
							props: {className: "transaction-stage-phase-icon-burger"}
						})
					]
				}),
				BX.create("span", {
					props: {
						id: "phase-panel",
						className: blockClass+" transaction-stage-phase-title"
					},
					attrs: {
						"data-class": "transaction-stage-phase-title"
					},
					children: [
						BX.create("span", {
							props: {
								id: this.spanStoringNameIdPrefix+fieldObject.ID,
								className: "transaction-stage-phase-title-inner"
							},
							children: [
								BX.create("span", {
									props: {id: this.fieldNameIdPrefix+fieldObject.ID, className: "transaction-stage-phase-name"},
									html: fieldObject.ID+". "+BX.util.htmlspecialchars(fieldObject.NAME)
								}),
								BX.create("span", {
									props: {className: "transaction-stage-phase-icon-edit"},
									attrs: {
										onclick: "BX[\""+this.jsClass+"\"].editField(\""+fieldObject.ID+"\")"
									}
								})
							]
						})
					]
				}),
				BX.create("input", {
					props: {id: this.fieldHiddenNumberIdPrefix+fieldObject.ID},
					attrs: {type: "hidden", value: fieldObject.ID}
				}),
				BX.create("input", {
					props: {id: this.fieldSortHiddenIdPrefix+fieldObject.ID},
					attrs: {
						type: "hidden",
						name: "LIST["+this.entityId+"]["+fieldObject.ID+"][SORT]",
						value: fieldObject.SORT
					}
				}),
				BX.create("input", {
					props: {id: this.fieldHiddenNameIdPrefix+fieldObject.ID},
					attrs: {
						type: "hidden",
						name: "LIST["+this.entityId+"]["+fieldObject.ID+"][VALUE]",
						value: BX.util.htmlspecialchars(fieldObject.NAME)
					}
				}),
				BX.create("input", {
					props: {id: "stage-color-"+fieldObject.ID},
					attrs: {
						type: "hidden",
						name: "LIST["+this.entityId+"]["+fieldObject.ID+"][COLOR]",
						value: fieldObject.COLOR
					}
				}),
				BX.create("input", {
					props: {id: "stage-status-id-"+fieldObject.ID},
					attrs: {
						type: "hidden",
						name: "LIST["+this.entityId+"]["+fieldObject.ID+"][STATUS_ID]",
						"data-status-id": "1",
						value: this.getNewStatusId()
					}
				})
			]
		});

		return domElement;
	};

	CrmSaleSettings.prototype.getNewStatusId = function()
	{
		var newStatusId = 0;
		var listInputStatusId = BX.findChildren(BX(this.contentIdPrefix+this.entityId),
			{"tag": "input", "attribute": {"data-status-id": "1"}}, true);

		if(!listInputStatusId)
			return newStatusId;

		for(var k = 0; k < listInputStatusId.length; k++)
		{
			var statusId = +listInputStatusId[k].value;
			if(!isNaN(statusId))
			{
				if(statusId > newStatusId)
				{
					newStatusId = statusId;
				}
			}
		}
		newStatusId = newStatusId + 1;

		return newStatusId;
	};

	CrmSaleSettings.prototype.showPlaceToInsert = function(replaceableElement, e)
	{
		if(replaceableElement.className == "space-to-insert draghandle")
		{
			return;
		}

		var parentElement = replaceableElement.parentNode,
			spaceId = replaceableElement.getAttribute("data-space");

		var spaceToInsert = BX.create("div", {
			props: {
				id: "space-to-insert-"+spaceId,
				className: "space-to-insert draghandle"
			},
			attrs: {
				"data-place": "1"
			}
		});

		var coords = getCoords(replaceableElement);
		var displacementHeight = e.pageY - coords.top;
		var middleElement = replaceableElement.offsetHeight/2;
		if(displacementHeight > middleElement)
		{
			if(replaceableElement.className == "transaction-stage-addphase draghandle")
			{
				return;
			}
			this.deleteSpaceToInsert();
			this.insertAfter(spaceToInsert, replaceableElement);
		}
		else
		{
			this.deleteSpaceToInsert();
			parentElement.insertBefore(spaceToInsert, replaceableElement);
		}
	};

	CrmSaleSettings.prototype.putDomElement = function(element, parentElement, beforeElement)
	{
		if(!element || !parentElement || !beforeElement)
		{
			return false;
		}

		parentElement.insertBefore(element, beforeElement);

		return true;
	};

	CrmSaleSettings.prototype.deleteSpaceToInsert = function()
	{
		var spacetoinsert = BX.findChildren(BX("crm-container"),
			{"tag": "div", "attribute": {"data-place": "1"}}, true);

		if(spacetoinsert)
		{
			for(var i = 0; i < spacetoinsert.length; i++)
			{
				var parentElement = spacetoinsert[i].parentNode;
				parentElement.removeChild(spacetoinsert[i]);
			}
		}
	};

	CrmSaleSettings.prototype.insertAfter = function(node, referenceNode)
	{
		if (!node || !referenceNode)
			return;

		var parent = referenceNode.parentNode, nextSibling = referenceNode.nextSibling;

		if (nextSibling && parent)
		{
			parent.insertBefore(node, referenceNode.nextSibling);
		}
		else if(parent)
		{
			parent.appendChild( node );
		}
	};

	CrmSaleSettings.prototype.checkChanges = function()
	{
		if(this.checkSubmit)
		{
			return;
		}

		var newTotalNumberFields = 0, changes = false;
		for(var k in this.data)
		{
			for(var i in this.data[k])
			{
				newTotalNumberFields++;
				var newSort = parseInt(this.data[k][i].SORT),
					oldSort = parseInt(this.oldData[k][i].SORT),
					newName = this.data[k][i].NAME.toLowerCase(),
					oldName = this.oldData[k][i].NAME.toLowerCase(),
					newColor = this.data[k][i].COLOR.toLowerCase(),
					oldColor = this.oldData[k][i].COLOR.toLowerCase();

				if((newSort !== oldSort) || (newName !== oldName) || (newColor !== oldColor))
				{
					changes = true;
					break;
				}
			}
		}

		if(this.totalNumberFields !== newTotalNumberFields || changes)
		{
			return BX.message("CRM_STATUS_CHECK_CHANGES");
		}
	};

	CrmSaleSettings.prototype.confirmSubmit = function()
	{
		this.checkSubmit = true;
	};

	/* For fix statuses */
	CrmSaleSettings.prototype.fixStatuses = function()
	{
		if(this.requestIsRunning)
		{
			return;
		}
		this.requestIsRunning = true;
		if(this.ajaxUrl === "")
		{
			throw "Error: Service URL is not defined.";
		}
		BX.ajax({
			url: this.ajaxUrl,
			method: "POST",
			dataType: "json",
			data: {
				"ACTION" : "FIX_STATUSES"
			},
			onsuccess: BX.delegate(function(){
				this.requestIsRunning = false;
				window.location.reload(true);
			}, this),
			onfailure: BX.delegate(function(){
				this.requestIsRunning = false;
			}, this)
		});
	};

	CrmSaleSettings.prototype.correctionColorPicker = function(event, fieldId)
	{
		if(!fieldId)
		{
			return;
		}

		var blockColorPicker = BX("block-color-picker");
		blockColorPicker.style.left = event.pageX+"px";
		blockColorPicker.style.top = event.pageY+"px";
		var img = BX.findChildren(BX("block-color-picker"), {"tag": "IMG"}, true)[0];
		img.setAttribute("data-img", fieldId);
		img.onclick();
	};

	CrmSaleSettings.prototype.paintElement = function(color, objColorPicker)
	{
		if(!objColorPicker)
		{
			return;
		}

		var fieldId = objColorPicker.pWnd.getAttribute("data-img");
		var fields = BX.findChildren(BX(this.contentIdPrefix+this.entityId),
			{"tag": "div", "attribute": {"id": this.mainDivStorageFieldIdPrefix+fieldId}}, true);

		if(fields.length)
		{
			if(!color && fields[0].parentNode.id == this.finalStorageFieldIdPrefix+this.entityId)
			{
				color = this.defaultFinalUnSuccessColor;
			}
			else if(!color && fields[0].parentNode.id == this.finalSuccessStorageFieldIdPrefix+this.entityId)
			{
				color = this.defaultFinalSuccessColor;
			}
			else if(!color)
			{
				color = this.defaultColor;
			}

			if(!this.hasSemantics)
			{
				color = this.defaultLineColor;
			}

			fields[0].style.background = color;

			var span = BX.findChildren(fields[0], {"tag": "span", "attribute":
					{"id": "transaction-stage-phase-icon"}}, true);

			var phasePanel = BX.findChildren(fields[0], {"attribute": {"id": "phase-panel"}}, true);

			if(span.length && phasePanel.length)
			{
				BX.ajax({
					url: this.ajaxUrl,
					method: "POST",
					dataType: "json",
					data: {
						"ACTION" : "GET_COLOR",
						"COLOR" : color
					},
					onsuccess: BX.delegate(function(result) {
						fields[0].style.color = result.COLOR;
						span[0].className = result.ICON_CLASS+" "+span[0].getAttribute("data-class");
						for(var k in phasePanel)
						{
							phasePanel[k].className = result.BLOCK_CLASS+" "+phasePanel[k].getAttribute("data-class");
						}
					}, this)
				});
			}
		}
		else
		{
			return;
		}

		var hiddenInputColor = BX.findChildren(BX(this.contentIdPrefix+this.entityId),
			{"tag": "input", "attribute": {"id": "stage-color-"+fieldId}}, true);
		if(hiddenInputColor[0])
		{
			hiddenInputColor[0].value = color;
		}
		this.data[this.entityId][fieldId].COLOR = color;

		this.recalculateSort();
	};

	CrmSaleSettings.prototype.initAmCharts = function()
	{
		if (this.hasSemantics)
		{
			this.initAmChart = true;
			if (AmCharts.isReady)
			{
				this.renderAmCharts();
			}
			else
			{
				AmCharts.ready(BX.delegate(this.renderAmCharts, this));
			}
			AmCharts.handleLoad();
		}
	};

	CrmSaleSettings.prototype.renderAmCharts = function()
	{
		var charts = [];
		for(var k in this.initialFields)
		{
			charts.push(BX(this.funnelSuccessIdPrefix+k));
			charts.push(BX(this.funnelUnSuccessIdPrefix+k));
		}

		if(!charts.length)
		{
			return;
		}

		for(var i = 0; i < charts.length; i++)
		{
			if(!charts[i].id)
				continue;

			this.getDataForAmCharts(charts[i].id);

			var chart = AmCharts.makeChart(charts[i].id, {
				"type": "funnel",
				"theme": "none",
				"titleField": "title",
				"valueField": "value",
				"dataProvider": this.dataFunnel,
				"colors": this.colorFunnel,
				"labelsEnabled": false,
				"marginRight": 35,
				"marginLeft": 35,
				"labelPosition": "center",
				"funnelAlpha": 0.9,
				"startX": 200,
				"neckWidth": "40%",
				"startAlpha": 0,
				"depth3D": 100,
				"angle": 10,
				"outlineAlpha": 1,
				"outlineColor": "#FFFFFF",
				"outlineThickness": 1,
				"neckHeight": "30%",
				"balloonText": "[[title]]",
				"export": {
					"enabled": true
				}
			});
		}
	};

	CrmSaleSettings.prototype.getDataForAmCharts = function(chartId)
	{
		var fields = [], color = "", success = false;
		if(chartId == this.funnelSuccessIdPrefix+this.entityId)
		{
			fields = this.successFields[this.entityId];
			color = this.defaultColor;
			success = true;
		}
		else if(chartId == this.funnelUnSuccessIdPrefix+this.entityId)
		{
			fields = this.unSuccessFields[this.entityId];
			color = this.defaultFinalUnSuccessColor;
		}

		this.dataFunnel = [];
		this.colorFunnel = [];
		for(var i = 0; i < fields.length; i++)
		{
			if(i == (fields.length -1) && success)
			{
				color = this.defaultFinalSuccessColor;
			}
			this.dataFunnel[i] = {"title": BX.util.htmlspecialchars(fields[i].NAME), "value": 1};
			if(fields[i].COLOR)
			{
				this.colorFunnel[i] = fields[i].COLOR;
			}
			else
			{
				this.colorFunnel[i] = color;
			}
		}
	};

	CrmSaleSettings.prototype.showError = function()
	{
		var error = this.getParameterByName("ERROR");
		if(error)
		{
			var content = BX.create("p", {
				props: {className: "bx-crm-popup-paragraph"},
				html: BX.util.htmlspecialchars(error)
			});
			this.modalWindow({
				modalId: "bx-crm-popup",
				title: BX.message("CRM_STATUS_REMOVE_ERROR"),
				overlay: false,
				content: [content],
				events : {
					onPopupClose : function() {
						this.destroy();
					}
				},
				buttons: [
					BX.create("a", {
						text : BX.message("CRM_STATUS_CLOSE_POPUP_REMOVE_ERROR"),
						props: {
							className: "webform-small-button webform-small-button-accept"
						},
						events : {
							click : BX.delegate(function (e) {
								BX.PopupWindowManager.getCurrentPopup().close();
							}, this)
						}
					})
				]
			});
		}
	};

	CrmSaleSettings.prototype.getParameterByName = function(name)
	{
		name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
		var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
			results = regex.exec(location.search);
		return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
	};

	CrmSaleSettings.prototype.init = function()
	{
		if (this.isDestroySidePanel)
		{
			this.destroySidePanel();
		}

		var footer = BX("crm-configs-footer");
		if (!footer)
		{
			return;
		}

		BX.addCustomEvent(footer, "onFooterChangeState", BX.delegate(function(state)
		{
			if (state)
			{
				BX.removeClass(footer, "crm-configs-footer");
				BX.addClass(footer, "webform-buttons-fixed");
			}
			else
			{
				BX.addClass(footer, "crm-configs-footer");
				BX.removeClass(footer, "webform-buttons-fixed");
			}
		}, this));

		BX.bind(window, "scroll", BX.proxy(this.processingFooter, this));

		this.processingFooter();

		if(!this.blockFixed)
		{
			BX.onCustomEvent(this.footer, "onFooterChangeState", [false]);
		}
	};

	CrmSaleSettings.prototype.processingFooter = function()
	{
		if (!this.footer || !this.blockFixed)
		{
			return;
		}

		this.windowSize = BX.GetWindowInnerSize();
		this.scrollPosition = BX.GetWindowScrollPos();
		this.contentPosition = BX.pos(BX(this.contentIdPrefix+this.entityId));
		this.footerPosition = BX.pos(this.footer);

		this.limit = this.contentPosition.top;
		var scrollBottom = this.scrollPosition.scrollTop + this.windowSize.innerHeight;
		var pos = this.contentPosition.bottom + this.footerPosition.height;

		if(this.limit > 0 && scrollBottom < this.limit)
		{
			this.footerFixed = false;
		}
		else if(!this.footerFixed && scrollBottom < pos)
		{
			this.footerFixed = true;
		}
		else if(this.footerFixed && scrollBottom >= pos)
		{
			this.footerFixed = false;
		}

		BX.onCustomEvent(this.footer, "onFooterChangeState", [this.footerFixed]);

		var padding = parseInt(BX.style(this.footer, "paddingLeft"));

		this.footer.style.left = this.contentPosition.left + "px";
		this.footer.style.width = (this.contentPosition.width - padding*2) + "px"

	};

	CrmSaleSettings.prototype.fixFooter = function(fixButton)
	{
		this.blockFixed = !this.blockFixed;
		if(this.blockFixed)
		{
			BX.userOptions.save("crm", "crm_config_status", "fix_footer", "on");

			BX.addClass(fixButton, "crm-fixedbtn-pin");
			fixButton.setAttribute("title", BX.message("CRM_STATUS_FOOTER_PIN_OFF"));

			this.processingFooter();
		}
		else
		{
			BX.userOptions.save("crm", "crm_config_status", "fix_footer", "off");

			BX.removeClass(fixButton, "crm-fixedbtn-pin");
			fixButton.setAttribute("title", BX.message("CRM_STATUS_FOOTER_PIN_ON"));

			BX.onCustomEvent(this.footer, "onFooterChangeState", [false]);
		}
	};

	CrmSaleSettings.semanticEntityTypes = [];

	CrmSaleSettings.hasSemantics = function(entityTypeId)
	{
		var types = this.semanticEntityTypes;
		if(!BX.type.isArray(types))
		{
			return false;
		}

		for(var i = 0, l = types.length; i < l; i++)
		{
			if(types[i] === entityTypeId)
			{
				return true;
			}
		}
		return false;
	};

	return CrmSaleSettings;
})();

(function() {
	"use strict";

	BX.namespace("BX.Crm");

	BX.Crm.CommonSaleSettings = function(params)
	{
		this.ajaxUrl = params.ajaxUrl;
		this.isFramePopup = params.isFramePopup === "Y";
		this.optionPrefix = params.optionPrefix;
		this.listSiteId = params.listSiteId || [];
		this.languageId = params.languageId;

		this.init();
	};

	BX.Crm.CommonSaleSettings.prototype.init = function()
	{
		this.slider = null;

		this.formId = "common_sale_settings_form";
		this.applyButtonId = "common_sale_settings_apply_button";
		this.cancelButtonId = "common_sale_settings_close_button";

		this.useStoreControl = null;
		this.useStoreMasterControl = null;
		this.enableReservationControl = null;
		this.hideNumeratorSettingsControl = null;
		this.PCDefaultValuesButton = null;

		this.addressDifferentSet = null;
		this.addressCurrentSite = null;
		this.addressCurrentSiteValue = null;

		this.weightDifferentSet = null;
		this.weightCurrentSite = null;
		this.weightUnitTmp = {};
		this.weightUnit = {};
		this.weightKoef = {};
		this.weightCurrentSiteValue = null;

		this.initSlider();

		BX.bind(BX(this.applyButtonId), "click", BX.proxy(this.saveSettings, this));

		this.bindUseStoreMasterButton();
		this.bindUseStoreMasterControl();
		this.bindPCDefaultValuesButton();
		this.bindEnableReservationMasterControl();
		this.bindHideNumeratorSettingsControl();
		this.bindAddressControl();
		this.bindWeightControl();
	};

	BX.Crm.CommonSaleSettings.prototype.bindUseStoreMasterButton = function()
	{
		this.UseStoreMasterButton = BX("store_use_settings");
		if (this.UseStoreMasterButton)
		{
			BX.bind(this.UseStoreMasterButton, "click", BX.proxy(this.clickUseStoreMasterButton, this));
		}
	};

	BX.Crm.CommonSaleSettings.prototype.bindPCDefaultValuesButton = function()
	{
		this.PCDefaultValuesButton = BX("product_card_settings");
		if (this.PCDefaultValuesButton)
		{
			BX.bind(this.PCDefaultValuesButton, "click", BX.proxy(this.clickPCDefaultValuesButton, this));
		}
	};

	BX.Crm.CommonSaleSettings.prototype.bindUseStoreMasterControl = function()
	{
		this.useStoreMasterControl = BX.findChild(BX(this.formId), {"attribute" : {"id": 'store_use_settings'}}, true);
	};

	BX.Crm.CommonSaleSettings.prototype.bindUseStoreControl = function()
	{
		this.useStoreControl = BX.findChild(BX(this.formId), {"attr": {
			name: this.optionPrefix+"default_use_store_control", type: "checkbox"}}, true, false);
		if (this.useStoreControl)
		{
			BX.bind(this.useStoreControl, "click", BX.proxy(this.clickUseStoreControl, this));
		}
	};

	BX.Crm.CommonSaleSettings.prototype.bindEnableReservationMasterControl = function()
	{
		this.enableReservationControl = BX.findChild(BX(this.formId), {"attr": {
				name: this.optionPrefix+"enable_reservation", type: "checkbox"}}, true, false);
		if (this.enableReservationControl)
		{
			if (this.useStoreMasterControl && this.useStoreMasterControl.dataset.useStoreControl == 'Y')
			{
				this.enableReservationControl.disabled = true;
				this.setEnableReservationHidden("Y");
			}
		}
	};

	BX.Crm.CommonSaleSettings.prototype.bindEnableReservationControl = function()
	{
		this.enableReservationControl = BX.findChild(BX(this.formId), {"attr": {
			name: this.optionPrefix+"enable_reservation", type: "checkbox"}}, true, false);
		if (this.enableReservationControl)
		{
			if (this.useStoreControl && this.useStoreControl.checked)
			{
				this.enableReservationControl.disabled = true;
				this.setEnableReservationHidden("Y");
			}
		}
	};

	BX.Crm.CommonSaleSettings.prototype.bindHideNumeratorSettingsControl = function()
	{
		this.hideNumeratorSettingsControl = BX.findChild(BX(this.formId), {"attr": {
				name: this.optionPrefix+"hideNumeratorSettings", type: "checkbox"}}, true, false);
		if (this.hideNumeratorSettingsControl)
		{
			BX.bind(this.hideNumeratorSettingsControl, "click", BX.proxy(this.clickHideNumeratorSettingsControl, this));
			this.clickHideNumeratorSettingsControl();
		}
	};

	BX.Crm.CommonSaleSettings.prototype.bindAddressControl = function()
	{
		this.addressDifferentSet = BX("ADDRESS_different_set");
		this.addressCurrentSite = BX("ADDRESS_current_site");

		if (!this.addressDifferentSet || !this.addressCurrentSite)
		{
			return;
		}

		this.addressCurrentSiteValue = BX.create("input", {
			props: {
				type: "hidden",
				name: "ADDRESS_current_site",
				value: this.addressCurrentSite.value
			}
		});
		this.addressCurrentSite.parentElement.appendChild(this.addressCurrentSiteValue);

		BX.bind(this.addressDifferentSet, "change", this.changeAddressDifferentSet.bind(this, this.addressDifferentSet));
		BX.bind(this.addressCurrentSite, "change", this.changeAddressCurrentSite.bind(this, this.addressCurrentSite));
	};

	BX.Crm.CommonSaleSettings.prototype.changeAddressDifferentSet = function(element)
	{
		if (!this.addressCurrentSite)
		{
			return;
		}

		this.addressCurrentSite.disabled = !element.checked;

		this.setAddressCurrentSite(this.addressCurrentSite.value);
	};

	BX.Crm.CommonSaleSettings.prototype.changeAddressCurrentSite = function(element)
	{
		var currentSiteId = element.value, addressBlock = BX("ADDRESS_block_"+currentSiteId);

		if (!addressBlock)
		{
			return;
		}

		this.setAddressCurrentSite(currentSiteId);

		this.listSiteId.forEach(function(siteId) {
			var addressBlock = BX("ADDRESS_block_"+siteId);
			if (addressBlock)
			{
				BX.addClass(addressBlock, "crm-sale-settings-hidden-mode");
			}
		});

		BX.removeClass(addressBlock, "crm-sale-settings-hidden-mode");
	};

	BX.Crm.CommonSaleSettings.prototype.setAddressCurrentSite = function(value)
	{
		if (this.addressCurrentSiteValue)
		{
			this.addressCurrentSiteValue.value = value;
		}
	};

	BX.Crm.CommonSaleSettings.prototype.bindWeightControl = function()
	{
		this.weightDifferentSet = BX("WEIGHT_different_set");
		this.weightCurrentSite = BX("WEIGHT_site_id");

		if (!this.weightDifferentSet || !this.weightCurrentSite)
		{
			return;
		}

		this.weightCurrentSiteValue = BX.create("input", {
			props: {
				type: "hidden",
				name: "WEIGHT_site_id",
				value: this.weightCurrentSite.value
			}
		});
		this.weightCurrentSite.parentElement.appendChild(this.weightCurrentSiteValue);

		this.listSiteId.forEach(function(siteId) {
			this.weightUnitTmp[siteId] = BX("weight_unit_tmp["+siteId+"]");
			this.weightUnit[siteId] = BX("weight_unit["+siteId+"]");
			this.weightKoef[siteId] = BX("weight_koef["+siteId+"]");
		}.bind(this));

		for (var siteId in this.weightUnitTmp)
		{
			if (this.weightUnitTmp.hasOwnProperty(siteId))
			{
				BX.bind(this.weightUnitTmp[siteId], "change", this.changeWeightUnit.bind(this, this.weightUnitTmp[siteId], siteId));
			}
		}
		BX.bind(this.weightDifferentSet, "change", this.changeWeightDifferentSet.bind(this, this.weightDifferentSet));
		BX.bind(this.weightCurrentSite, "change", this.changeWeightCurrentSite.bind(this, this.weightCurrentSite));
	};

	BX.Crm.CommonSaleSettings.prototype.changeWeightDifferentSet = function(element)
	{
		if (!this.weightCurrentSite)
		{
			return;
		}

		this.weightCurrentSite.disabled = !element.checked;

		this.setWeightCurrentSite(this.weightCurrentSite.value);
	};

	BX.Crm.CommonSaleSettings.prototype.changeWeightCurrentSite = function(element)
	{
		var currentSiteId = element.value, weightBlock = BX("par_WEIGHT_"+currentSiteId);

		if (!weightBlock)
		{
			return;
		}

		this.setWeightCurrentSite(currentSiteId);

		this.listSiteId.forEach(function(siteId) {
			var weightBlock = BX("par_WEIGHT_"+siteId);
			if (weightBlock)
			{
				BX.addClass(weightBlock, "crm-sale-settings-hidden-mode");
			}
		});

		BX.removeClass(weightBlock, "crm-sale-settings-hidden-mode");
	};

	BX.Crm.CommonSaleSettings.prototype.changeWeightUnit = function(select, siteId)
	{
		if (!select.value) return;

		if (this.weightUnit[siteId] && this.weightKoef[siteId])
		{
			this.weightKoef[siteId].value = select.value;
			this.weightUnit[siteId].value = select.options[select.selectedIndex].text;
		}
	};

	BX.Crm.CommonSaleSettings.prototype.setWeightCurrentSite = function(value)
	{
		if (this.weightCurrentSiteValue)
		{
			this.weightCurrentSiteValue.value = value;
		}
	};

	BX.Crm.CommonSaleSettings.prototype.setEnableReservationHidden = function(value)
	{
		var enableReservationHidden = BX.findChild(BX(this.formId), {"attr": {
				name: this.optionPrefix+"enable_reservation", type: "hidden"}}, true, false);
		if (enableReservationHidden)
		{
			enableReservationHidden.value = value;
		}
	};

	BX.Crm.CommonSaleSettings.prototype.clickPCDefaultValuesButton = function()
	{
		this.showProductSettings();
	};

	BX.Crm.CommonSaleSettings.prototype.clickUseStoreMasterButton = function()
	{
		BX.Runtime.loadExtension('catalog.store-enable-wizard').then((exports) => {
			const { EnableWizardOpener, AnalyticsContextList, Disabler } = exports;

			const isStoreControlUsed = this.useStoreMasterControl.dataset.useStoreControl === 'Y';
			if (isStoreControlUsed)
			{
				(new Disabler({
					events: {
						onDisabled: () => {
							this.saveSettings();
						},
					},
				})).open();
			}
			else
			{
				(new EnableWizardOpener())
					.open(
						'/bitrix/components/bitrix/catalog.store.enablewizard/slider.php',
						{
							urlParams: {
								analyticsContextSection: AnalyticsContextList.OLD_SETTINGS,
							},
						},
					)
					.then(() => {
						return window.top.BX.ajax.runAction(
							'catalog.config.isUsedInventoryManagement',
							{},
						);
					})
					.then((response) => {
						if (response.data === true)
						{
							this.checkedReservationControl(response.data);
							this.saveSettings();
						}
					});
			}
		});
	};

	BX.Crm.CommonSaleSettings.prototype.clickHideNumeratorSettingsControl = function()
	{
		if (!this.hideNumeratorSettingsControl)
		{
			return false;
		}

		var fieldString = this.hideNumeratorSettingsControl.parentElement.parentElement;
		if (fieldString)
		{
			var hideNumeratorSettingsContentString = fieldString.nextElementSibling;
			if (this.hideNumeratorSettingsControl.checked)
			{
				BX.show(hideNumeratorSettingsContentString);
			}
			else
			{
				BX.hide(hideNumeratorSettingsContentString);
			}
		}
	};

	BX.Crm.CommonSaleSettings.prototype.checkedReservationControl = function(checked)
	{
		if (!this.enableReservationControl)
		{
			this.enableReservationControl = BX.findChild(BX(this.formId), {"attr": {
					name: this.optionPrefix+"enable_reservation", type: "checkbox"}}, true, false);
		}

		if (!this.enableReservationControl)
		{
			return;
		}

		if (checked)
		{
			this.enableReservationControl.checked = true;
			this.enableReservationControl.disabled = true;
			this.setEnableReservationHidden("Y");
		}
		else
		{
			this.enableReservationControl.disabled = false;
			this.setEnableReservationHidden("N");
		}
	};

	BX.Crm.CommonSaleSettings.prototype.clickUseStoreControl = function(event)
	{
		var userStoreControl = event.currentTarget;

		this.checkedReservationControl(userStoreControl.checked)
	};

	BX.Crm.CommonSaleSettings.prototype.initSlider = function()
	{
		this.slider = {
			bindClose: function (element)
			{
				BX.bind(element, "click", this.close);
			},
			close: function (callback)
			{
				window.top.BX.SidePanel.Instance.close(false, callback);
			},
			open: function (url, params)
			{
				var options = {cacheable: false, allowChangeHistory: false, events: {}};

				if(BX.Type.isPlainObject(params))
				{
					if (params !== null && typeof(params) === "object" && params.hasOwnProperty('events'))
					{
						options.events = params.events
					}
				}

				return new Promise(function (resolve)
				{
					if(BX.Type.isString(url) && url.length > 1)
					{
						options.events.onClose = function(event)
						{
							resolve(event.getSlider());
						};
						window.top.BX.SidePanel.Instance.open(url, options);
					}
					else
					{
						resolve();
					}
				}.bind(this));
			},
			reload: function ()
			{
				var sidePanel = window.top.BX.SidePanel.Instance.getTopSlider();
				sidePanel.getWindow().location.reload();
			},
			reloadList: function ()
			{
				window.top.BX.SidePanel.Instance.close();
				window.top.location.reload();
			}
		};

		this.slider.bindClose(BX(this.cancelButtonId));
	};

	BX.Crm.CommonSaleSettings.prototype.saveSettingsComponentAction= function()
	{
		return BX.ajax.runComponentAction("bitrix:crm.config.sale.settings", "saveCommonSettings", {
			mode: "class",
			data: new FormData(BX(this.formId))
		})
	}

	BX.Crm.CommonSaleSettings.prototype.saveSettings = function()
	{
		this.saveSettingsComponentAction().then(function (response) {
			top.BX.UI.Notification.Center.notify({
				content: BX.message("CRM_SALE_SETTINGS_SAVE_SUCCESS")
			});
			if (this.slider && window.top.BX.SidePanel.Instance.getTopSlider())
			{
				this.slider.close(function () {
					top.location.reload();
				});
			}
			else
			{
				location.reload();
			}
		}.bind(this), function (response) {

		}.bind(this));
	};

	BX.Crm.CommonSaleSettings.prototype.showProductSettings = function()
	{
		var obWindow, params;

		params = {"sessid": BX.bitrix_sessid(), "public_mode": "Y"};

		var obBtn = {
			title: BX.message("CRM_SALE_SETTINGS_BUTTON_CLOSE"),
			id: "close",
			name: "close",
			action: function () {
				this.parentWindow.Close();
			}
		};

		obWindow = new BX.CAdminDialog({
			"title": BX.message("CRM_SALE_PRODUCT_SETTINGS_TITLE"),
			"content_url": "/bitrix/tools/catalog/product_settings.php?lang="+this.languageId,
			"content_post": params,
			"draggable": true,
			"resizable": true,
			"buttons": [obBtn]
		});
		obWindow.Show();

		BX.addCustomEvent(obWindow, "onWindowClose", function () {
			if (this.slider)
			{
				this.slider.reload();
			}
			else
			{
				window.top.location.reload();
			}
		}.bind(this));

		return false;
	};

})();