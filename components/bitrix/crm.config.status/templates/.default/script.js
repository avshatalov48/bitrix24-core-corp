BX.CrmConfigStatusClass = (function ()
{
	var CrmConfigStatusClass = function (parameters)
	{
		this.randomString = parameters.randomString;
		this.tabs = parameters.tabs;
		this.ajaxUrl = parameters.ajaxUrl;
		this.data = parameters.data;
		this.oldData = BX.clone(this.data);
		this.max_sort = {};
		this.requestIsRunning = false;
		this.totalNumberFields = parameters.totalNumberFields;
		this.checkSubmit = false;

		this.defaultColors = [ '#39A8EF', '#2FC6F6', '#55D0E0', '#47E4C2', '#FFA900' ];
		this.defaultFinalSuccessColor = '#7BD500';
		this.defaultFinalUnSuccessColor = '#FF5752';

		this.defaultLineColor = '#D3EEF9';
		this.textColorLight = '#FFF';
		this.textColorDark = '#545C69';

		this.entityId = parameters.entityId;
		this.hasSemantics = !!parameters.hasSemantics;

		this.jsClass = 'CrmConfigStatusClass_'+parameters.randomString;
		this.contentIdPrefix = 'content_';
		this.contentClass = 'crm-status-content';
		this.contentActiveClass = 'crm-status-content active';

		this.fieldNameIdPrefix = 'field-name-';
		this.fieldEditNameIdPrefix = 'field-edit-name-';
		this.fieldHiddenNameIdPrefix = 'field-hidden-name-';
		this.spanStoringNameIdPrefix = 'field-title-inner-';
		this.mainDivStorageFieldIdPrefix = 'field-phase-';
		this.fieldSortHiddenIdPrefix = 'field-sort-';
		this.fieldHiddenNumberIdPrefix = 'field-number-';
		this.extraStorageFieldIdPrefix = 'extra-storage-';
		this.finalSuccessStorageFieldIdPrefix = 'final-success-storage-';
		this.finalStorageFieldIdPrefix = 'final-storage-';
		this.previouslyScaleIdPrefix = 'previously-scale-';
		this.previouslyScaleNumberIdPrefix = 'previously-scale-number-';
		this.previouslyScaleFinalSuccessIdPrefix = 'previously-scale-final-success-';
		this.previouslyScaleNumberFinalSuccessIdPrefix = 'previously-scale-number-final-success-';
		this.previouslyScaleFinalUnSuccessIdPrefix = 'previously-scale-final-un-success-';
		this.previouslyScaleNumberFinalUnSuccessIdPrefix = 'previously-scale-number-final-un-success-';
		this.previouslyScaleFinalCellIdPrefix = 'previously-scale-final-cell-';
		this.previouslyScaleNumberFinalCellIdPrefix = 'previously-scale-number-final-cell-';
		this.funnelSuccessIdPrefix = 'config-funnel-success-';
		this.funnelUnSuccessIdPrefix = 'config-funnel-unsuccess-';

		this.successFields = parameters.successFields;
		this.unSuccessFields = parameters.unSuccessFields;
		this.initialFields = parameters.initialFields;
		this.extraFields = parameters.extraFields;
		this.finalFields = parameters.finalFields;
		this.extraFinalFields = parameters.extraFinalFields;

		this.dataFunnel = [];
		this.colorFunnel = [];
		this.initAmChart = false;

		this.footer = BX('crm-configs-footer');
		this.windowSize = {};
		this.scrollPosition = {};
		this.contentPosition = {};
		this.footerPosition = {};
		this.limit = 0;
		this.footerFixed = true;
		this.blockFixed = !!parameters.blockFixed;

		this.dragStartParentElement = null;

		this.initAmCharts();
		this.showError();
		this.init();
	};

	CrmConfigStatusClass.prototype.selectTab = function(tabId)
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

		BX('ACTIVE_TAB').value = 'status_tab_' + tabId;
		this.entityId = tabId;
		this.hasSemantics = CrmConfigStatusClass.hasSemantics(this.entityId);

		this.processingFooter();

		if(this.hasSemantics)
		{
			AmCharts.handleLoad();
		}
	};

	CrmConfigStatusClass.prototype.showTab = function(tabId, on)
	{
		var sel = (on? 'status_tab_active':'');
		BX('status_tab_'+tabId).className = 'status_tab '+sel;
	};

	CrmConfigStatusClass.prototype.statusReset = function()
	{
		BX('ACTION').value = 'reset';
		document.forms["crmStatusForm"].submit();
	};

	CrmConfigStatusClass.prototype.recoveryName = function(fieldId, name)
	{
		var fieldHiddenNumber = this.searchElement('input', this.fieldHiddenNumberIdPrefix+fieldId),
			fieldName = this.searchElement('span', this.fieldNameIdPrefix+fieldId),
			fieldHiddenName = this.searchElement('input', this.fieldHiddenNameIdPrefix+fieldId);

		fieldName.innerHTML = BX.util.htmlspecialchars(fieldHiddenNumber.value+'. '+name);
		fieldHiddenName.value = name;
		this.data[this.entityId][fieldId].NAME = name;

		if(this.initAmChart)
		{
			this.recalculateSort();
		}
	};

	CrmConfigStatusClass.prototype.editField = function(fieldId)
	{
		var domElement, fieldDiv = this.searchElement('div', this.mainDivStorageFieldIdPrefix+fieldId),
			spanStoring = this.searchElement('span', this.spanStoringNameIdPrefix+fieldId),
			fieldName = this.searchElement('span', this.fieldNameIdPrefix+fieldId),
			fieldHiddenName = this.searchElement('input', this.fieldHiddenNameIdPrefix+fieldId);

		if(!fieldHiddenName)
		{
			return;
		}

		domElement = BX.create('span', {
			props: {className: 'transaction-stage-phase-title-input-container'},
			children: [
				BX.create('input', {
					props: {id: this.fieldEditNameIdPrefix+fieldId},
					attrs: {
						type: 'text',
						value: fieldHiddenName.value,
						onkeydown: 'if (event.keyCode==13) {BX["'+this.jsClass+'"].saveFieldValue(\''+fieldId+'\', this);}',
						onblur: 'BX["'+this.jsClass+'"].saveFieldValue(\''+fieldId+'\', this);',
						'data-onblur': '1'
					}
				})
			]
		});

		spanStoring.style.width = "100%";
		fieldDiv.setAttribute('ondblclick', '');
		fieldName.innerHTML = '';
		fieldName.appendChild(domElement);

		var fieldEditName = this.searchElement('input', this.fieldEditNameIdPrefix+fieldId);
		fieldEditName.focus();
		fieldEditName.selectionStart = BX(this.fieldEditNameIdPrefix+fieldId+'').value.length;
	};

	CrmConfigStatusClass.prototype.openPopupBeforeDeleteField = function(fieldId)
	{
		if(isNaN(parseInt(fieldId)))
		{
			this.deleteField(fieldId);
			return;
		}

		BX.ajax({
			url: this.ajaxUrl,
			method: 'POST',
			dataType: 'json',
			data: {
				'ACTION' : 'CHECK_ENTITY_EXISTENCE',
				'ENTITY_ID': this.entityId,
				'FIELD_ID': fieldId
			},
			onsuccess: BX.delegate(function(result) {
				if (result.ERROR)
				{
					var popup = new BX.PopupWindow({
						titleBar: result.TITLE,
						closeIcon: true,
						autoHide: true,
						closeByEsc: true,
						content: result.ERROR,
						width: 400,
						buttons: [
							new BX.PopupWindowButton({
								text : BX.message('CRM_STATUS_CLOSE_POPUP_REMOVE_ERROR'),
								className : 'popup-window-button popup-window-button-link ' +
									'popup-window-button-link-cancel',
								events : {
									click: function() {
										popup.close();
									}.bind(this)
								}
							})
						]
					});
					popup.show();
				}
				else
				{
					this.deleteField(fieldId);
				}
			}, this)
		});
	};

	CrmConfigStatusClass.prototype.deleteField = function(fieldId)
	{
		var fieldDiv = this.searchElement('div', this.mainDivStorageFieldIdPrefix+fieldId),
			parentNode = fieldDiv.parentNode;

		var fieldHidden = BX.create('input', {
			attrs: {
				type: 'hidden',
				value: fieldId,
				name: 'LIST['+this.entityId+'][REMOVE]['+fieldId+'][FIELD_ID]'
			}
		});

		BX(this.contentIdPrefix+this.entityId).appendChild(fieldHidden);
		parentNode.removeChild(fieldDiv);
		this.recalculateSort();
	};

	CrmConfigStatusClass.prototype.modalWindow = function(params)
	{
		params = params || {};
		params.title = params.title || false;
		params.bindElement = params.bindElement || null;
		params.overlay = typeof params.overlay == "undefined" ? true : params.overlay;
		params.autoHide = params.autoHide || false;
		params.closeIcon = typeof params.closeIcon == "undefined"? {right: "20px", top: "10px"} : params.closeIcon;
		params.modalId = params.modalId || 'crm' + (Math.random() * (200000 - 100) + 100);
		params.withoutContentWrap = typeof params.withoutContentWrap == "undefined" ? false : params.withoutContentWrap;
		params.contentClassName = params.contentClassName || '';
		params.contentStyle = params.contentStyle || {};
		params.content = params.content || [];
		params.buttons = params.buttons || false;
		params.events = params.events || {};
		params.withoutWindowManager = !!params.withoutWindowManager || false;

		var contentDialogChildren = [];
		if (params.title) {
			contentDialogChildren.push(BX.create('div', {
				props: {
					className: 'bx-crm-popup-title'
				},
				text: params.title
			}));
		}
		if (params.withoutContentWrap) {
			contentDialogChildren = contentDialogChildren.concat(params.content);
		}
		else {
			contentDialogChildren.push(BX.create('div', {
				props: {
					className: 'bx-crm-popup-content ' + params.contentClassName
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
					buttons.push(BX.create('SPAN', {html: '&nbsp;'}));
				}
				buttons.push(params.buttons[i]);
			}

			contentDialogChildren.push(BX.create('div', {
				props: {
					className: 'bx-crm-popup-buttons'
				},
				children: buttons
			}));
		}

		var contentDialog = BX.create('div', {
			props: {
				className: 'bx-crm-popup-container'
			},
			children: contentDialogChildren
		});

		params.events.onPopupShow = BX.delegate(function () {
			if (buttons.length) {
				firstButtonInModalWindow = buttons[0];
				BX.bind(document, 'keydown', BX.proxy(this._keyPress, this));
			}

			if(params.events.onPopupShow)
				BX.delegate(params.events.onPopupShow, BX.proxy_context);
		}, this);
		var closePopup = params.events.onPopupClose;
		params.events.onPopupClose = BX.delegate(function () {

			firstButtonInModalWindow = null;
			try
			{
				BX.unbind(document, 'keydown', BX.proxy(this._keypress, this));
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

	CrmConfigStatusClass.prototype.saveFieldValue = function(fieldId, input)
	{
		var newFieldName = '', newFieldValue = input.value,
			fieldHiddenNumber = this.searchElement('input', this.fieldHiddenNumberIdPrefix+fieldId),
			fieldName = this.searchElement('span', this.fieldNameIdPrefix+fieldId),
			fieldDiv = this.searchElement('div', this.mainDivStorageFieldIdPrefix+fieldId),
			spanStoring = this.searchElement('span', this.spanStoringNameIdPrefix+fieldId),
			fieldHiddenName = this.searchElement('input', this.fieldHiddenNameIdPrefix+fieldId);

		newFieldName += fieldHiddenNumber.value+'. '+newFieldValue;
		input.onblur = '';

		if(newFieldValue == '')
		{
			if(fieldHiddenNumber.value == 1)
			{
				newFieldValue = this.data[this.entityId][fieldId].NAME_INIT;
			}
			else
			{
				var name = BX.message('CRM_STATUS_NEW');
				if(this.hasSemantics)
				{
					name = BX.message('CRM_STATUS_NEW_'+this.entityId);
				}
				newFieldValue = name;
			}

		}

		fieldName.innerHTML = BX.util.htmlspecialchars(newFieldName);
		fieldDiv.setAttribute('ondblclick', 'BX["'+this.jsClass+'"].editField(\''+fieldId+'\');');
		spanStoring.style.width = "";
		fieldHiddenName.value = newFieldValue;

		this.data[this.entityId][fieldId].NAME = newFieldValue;
		if(this.initAmChart)
		{
			this.recalculateSort();
		}
	};

	CrmConfigStatusClass.prototype.searchElement = function(tag, id)
	{
		var element = BX.findChildren(BX(this.contentIdPrefix+this.entityId),
			{'tag': tag, 'attribute': {'id': id}}, true);
		if(element[0])
		{
			return element[0];
		}
		return null;
	};

	CrmConfigStatusClass.prototype.getDefaultColor = function()
	{
		var currentColorIndex = -1;
		var values = BX.util.objectSort(Object.values(this.data[this.entityId]), 'SORT', 'asc');
		for(var i = (values.length - 1); i > 0; i--)
		{
			var color = values[i]['COLOR'];
			var colorIndex = BX.util.array_search(color, this.defaultColors);
			if(colorIndex >= 0)
			{
				currentColorIndex = colorIndex;
				break;
			}
		}

		if(currentColorIndex < 0 || currentColorIndex === (this.defaultColors.length - 1))
		{
			currentColorIndex = 0;
		}
		else
		{
			currentColorIndex++;
		}

		return this.defaultColors[currentColorIndex];
	};

	CrmConfigStatusClass.prototype.addField = function(element)
	{
		var parentNode = element.parentNode;
		var fieldId = 1;
		var	color = this.getDefaultColor();
		var name = BX.message('CRM_STATUS_NEW');
		var semantics = '';
		var categoryId = CrmConfigStatusClass.getCategoryId(this.entityId);

		if(parentNode.id == 'final-storage-'+this.entityId)
		{
			color = this.defaultFinalUnSuccessColor;
			this.addCellFinalScale();
			semantics = 'F';
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
			name = BX.message('CRM_STATUS_NEW_'+this.entityId);
		}
		else
		{
			color = this.defaultLineColor;
		}

		var id = 'n'+fieldId;
		this.data[this.entityId][id] = {
			ID: id,
			SORT: 10,
			NAME: name,
			ENTITY_ID: this.entityId,
			COLOR: color,
			SEMANTICS: semantics,
			CATEGORY_ID: categoryId
		};

		parentNode.insertBefore(this.createStructureHtml(id), element);
		this.recalculateSort();
		this.editField(id);
	};

	CrmConfigStatusClass.prototype.recalculateSort = function()
	{
		var fieldId, parentId;

		var structureFields = BX.findChildren(BX(this.contentIdPrefix+this.entityId),
			{'tag': 'div', 'attribute': {'data-calculate': '1'}}, true);
		if(!structureFields)
		{
			return;
		}

		for(var i = 0; i < structureFields.length; i++)
		{
			parentId = structureFields[i].parentNode.id;

			if(parentId == this.extraStorageFieldIdPrefix+this.entityId)
			{
				structureFields[i].setAttribute('data-success', '1');
			}
			else if(parentId == this.finalStorageFieldIdPrefix+this.entityId)
			{
				structureFields[i].setAttribute('data-success', '0');
			}

			var number = i+1;
			var sort = number*10;
			fieldId = structureFields[i].getAttribute('id').replace(this.mainDivStorageFieldIdPrefix, '');

			var inputFields = BX.findChildren(structureFields[i], {'tag': 'input', 'attribute': {'data-onblur': '1'}}, true);
			if(inputFields.length)
			{
				this.saveFieldValue(fieldId, inputFields[0]);
			}

			structureFields[i].setAttribute('data-sort', ''+sort+'');

			var fieldName = this.searchElement('span', this.fieldNameIdPrefix+fieldId),
				fieldHiddenName = this.searchElement('input', this.fieldHiddenNameIdPrefix+fieldId),
				fieldHiddenNumber = this.searchElement('input', this.fieldHiddenNumberIdPrefix+fieldId),
				fieldSortHidden = this.searchElement('input', this.fieldSortHiddenIdPrefix+fieldId);

			fieldName.innerHTML = BX.util.htmlspecialchars(number+'. '+fieldHiddenName.value);
			fieldHiddenNumber.value = number;
			fieldSortHidden.value = sort;

			this.data[this.entityId][fieldId].SORT = sort;
		}

		if(this.initAmChart && this.hasSemantics)
		{
			var successFields = BX.findChildren(BX(this.contentIdPrefix+this.entityId),
				{'tag': 'div', 'attribute': {'data-success': '1'}}, true);
			if(successFields)
			{
				this.successFields[this.entityId] = [];
				for(var k = 0; k < successFields.length; k++)
				{
					fieldId = successFields[k].getAttribute('id').replace(this.mainDivStorageFieldIdPrefix, '');
					this.successFields[this.entityId][k] = this.data[this.entityId][fieldId];
				}
			}

			var unSuccessFields = BX.findChildren(BX(this.contentIdPrefix+this.entityId),
				{'tag': 'div', 'attribute': {'data-success': '0'}}, true);
			if(successFields)
			{
				this.unSuccessFields[this.entityId] = [];
				for(var j = 0; j < unSuccessFields.length; j++)
				{
					fieldId = unSuccessFields[j].getAttribute('id').replace(this.mainDivStorageFieldIdPrefix, '');
					this.unSuccessFields[this.entityId][j] = this.data[this.entityId][fieldId];
				}
			}

			AmCharts.handleLoad();
		}

		this.changeCellScale();
	};

	CrmConfigStatusClass.prototype.changeCellScale = function()
	{
		if(!this.hasSemantics)
		{
			return;
		}

		if(!this.successFields[this.entityId] || !this.unSuccessFields[this.entityId])
		{
			return;
		}

		var scale = BX.findChildren(BX(this.previouslyScaleIdPrefix+this.entityId), {'tag': 'td',
				'attribute': {'data-scale-type': 'main'}}, true),
			scaleNumber = BX.findChildren(BX(this.previouslyScaleNumberIdPrefix+this.entityId), {'tag': 'td',
				'attribute': {'data-scale-type': 'main'}}, true),
			scaleFinalSuccess = BX.findChildren(BX(this.previouslyScaleFinalSuccessIdPrefix+this.entityId),
				{'tag': 'td'}, true),
			scaleNumberFinalSuccess = BX.findChildren(BX(this.previouslyScaleNumberFinalSuccessIdPrefix+this.entityId),
				{'tag': 'td'}, true);

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
					color = this.getDefaultColor();
				}

				scale[i].style.background = color;
				number = i + 1;
				scaleNumber[i].getElementsByTagName('span')[0].innerHTML = number;
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
			scaleNumberFinalSuccess[0].getElementsByTagName('span')[0].innerHTML = number;
		}

		var scaleFinalUnSuccess = BX.findChildren(BX(this.previouslyScaleFinalUnSuccessIdPrefix+this.entityId),
				{'tag': 'td'}, true),
			scaleNumberFinalUnSuccess = BX.findChildren(BX(this.previouslyScaleNumberFinalUnSuccessIdPrefix+this.entityId),
				{'tag': 'td'}, true);
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
				scaleNumberFinalUnSuccess[h].getElementsByTagName('span')[0].innerHTML = number;
			}
		}
	};

	CrmConfigStatusClass.prototype.addCellMainScale = function()
	{
		if(!this.hasSemantics)
		{
			return;
		}

		var scaleNumber = BX.findChildren(BX(this.previouslyScaleNumberIdPrefix+this.entityId), {'tag': 'td',
				'attribute': {'data-scale-type': 'main'}}, true),
			scaleHtml = BX.create('td', {
				attrs: {'data-scale-type': 'main'},
				html: '&nbsp;'
			}),
			scaleNumberHtml = BX.create('td', {
				attrs: {'data-scale-type': 'main'},
				children: [
					BX.create('span', {
						props: {className: 'stage-name'},
						html: scaleNumber.length
					})
				]
			});

		BX(this.previouslyScaleIdPrefix+this.entityId).insertBefore(
			scaleHtml, BX(this.previouslyScaleFinalCellIdPrefix+this.entityId));
		BX(this.previouslyScaleNumberIdPrefix+this.entityId).insertBefore(
			scaleNumberHtml, BX(this.previouslyScaleNumberFinalCellIdPrefix+this.entityId));
	};

	CrmConfigStatusClass.prototype.addCellFinalScale = function()
	{
		if(!this.hasSemantics)
		{
			return;
		}

		var scaleNumber = BX.findChildren(BX(this.previouslyScaleNumberFinalUnSuccessIdPrefix+this.entityId),
				{'tag': 'td'}, true),
			scaleHtml = BX.create('td', {
				html: '&nbsp;'
			}),
			scaleNumberHtml = BX.create('td', {
				children: [
					BX.create('span', {
						props: {className: 'stage-name'},
						html: scaleNumber.length
					})
				]
			});

		BX(this.previouslyScaleFinalUnSuccessIdPrefix+this.entityId).appendChild(scaleHtml);
		BX(this.previouslyScaleNumberFinalUnSuccessIdPrefix+this.entityId).appendChild(scaleNumberHtml);
	};

	CrmConfigStatusClass.prototype.deleteCellMainScale = function(quantity)
	{
		if(!this.hasSemantics)
		{
			return;
		}

		var scaleCell = BX.findChildren(BX(this.previouslyScaleIdPrefix+this.entityId),
				{'tag': 'td', 'attribute': {'data-scale-type': 'main'}}, true),
			scaleCellNumber = BX.findChildren(BX(this.previouslyScaleNumberIdPrefix+this.entityId),
				{'tag': 'td', 'attribute': {'data-scale-type': 'main'}}, true);

		for(var k = 0; k < quantity; k++)
		{
			BX(this.previouslyScaleIdPrefix+this.entityId).removeChild(scaleCell[k]);
			BX(this.previouslyScaleNumberIdPrefix+this.entityId).removeChild(scaleCellNumber[k]);
		}

	};

	CrmConfigStatusClass.prototype.deleteCellFinalScale = function(quantity)
	{
		if(!this.hasSemantics)
		{
			return;
		}

		var scaleCell = BX.findChildren(BX(this.previouslyScaleFinalUnSuccessIdPrefix+this.entityId),
				{'tag': 'td'}, true),
			scaleCellNumber = BX.findChildren(BX(this.previouslyScaleNumberFinalUnSuccessIdPrefix+this.entityId),
				{'tag': 'td'}, true);

		for(var k = 0; k < quantity; k++)
		{
			BX(this.previouslyScaleFinalUnSuccessIdPrefix+this.entityId).removeChild(scaleCell[k]);
			BX(this.previouslyScaleNumberFinalUnSuccessIdPrefix+this.entityId).removeChild(scaleCellNumber[k]);
		}

	};

	CrmConfigStatusClass.prototype.createStructureHtml = function(fieldId)
	{
		var domElement, fieldObject = this.data[this.entityId][fieldId];

		var iconClass = '', color = this.textColorDark, blockClass='', img;
		if(this.hasSemantics)
		{
			iconClass = 'light-icon';
			color = this.textColorLight;
			blockClass = 'transaction-stage-phase-dark';
			img = BX.create('div', {
				props: {className: 'transaction-stage-phase-panel-button'},
				attrs: {
					onclick: 'BX["'+this.jsClass+'"].correctionColorPicker(event, \''+fieldObject.ID+'\');'
				}
			});
		}

		domElement = BX.create('div', {
			props: {id: this.mainDivStorageFieldIdPrefix+fieldObject.ID, className: 'transaction-stage-phase draghandle'},
			attrs: {
				ondblclick: 'BX["'+this.jsClass+'"].editField(\''+fieldObject.ID+'\');',
				'data-sort': fieldObject.SORT,
				'data-calculate': 1,
				'data-space': fieldObject.ID,
				'style': 'background: '+fieldObject.COLOR+'; color:'+color+';'
			},
			children: [
				BX.create('div', {
					props: {
						id: 'phase-panel',
						className: blockClass+' transaction-stage-phase-panel'
					},
					attrs: {
						"data-class": 'transaction-stage-phase-panel'
					},
					children: [
						img,
						BX.create('div', {
							props: {className: 'transaction-stage-phase-panel-button ' +
							'transaction-stage-phase-panel-button-close'},
							attrs: {
								onclick: 'BX["'+this.jsClass+'"].openPopupBeforeDeleteField(\''+fieldObject.ID+'\');'
							}
						})
					]
				}),
				BX.create('span', {
					props: {
						id: 'transaction-stage-phase-icon',
						className: iconClass+' transaction-stage-phase-icon transaction-stage-phase-icon-move draggable'
					},
					attrs: {
						"data-class": 'transaction-stage-phase-icon transaction-stage-phase-icon-move draggable'
					},
					children: [
						BX.create('span', {
							props: {className: 'transaction-stage-phase-icon-burger'}
						})
					]
				}),
				BX.create('span', {
					props: {
						id: 'phase-panel',
						className: blockClass+' transaction-stage-phase-title'
					},
					attrs: {
						"data-class": 'transaction-stage-phase-title'
					},
					children: [
						BX.create('span', {
							props: {
								id: this.spanStoringNameIdPrefix+fieldObject.ID,
								className: 'transaction-stage-phase-title-inner'
							},
							children: [
								BX.create('span', {
									props: {id: this.fieldNameIdPrefix+fieldObject.ID, className: 'transaction-stage-phase-name'},
									html: fieldObject.ID+'. '+BX.util.htmlspecialchars(fieldObject.NAME)
								}),
								BX.create('span', {
									props: {className: 'transaction-stage-phase-icon-edit'},
									attrs: {
										onclick: 'BX["'+this.jsClass+'"].editField(\''+fieldObject.ID+'\')'
									}
								})
							]
						})
					]
				}),
				BX.create('input', {
					props: {id: this.fieldHiddenNumberIdPrefix+fieldObject.ID},
					attrs: {type: 'hidden', value: fieldObject.ID}
				}),
				BX.create('input', {
					props: {id: this.fieldSortHiddenIdPrefix+fieldObject.ID},
					attrs: {
						type: 'hidden',
						name: 'LIST['+this.entityId+']['+fieldObject.ID+'][SORT]',
						value: fieldObject.SORT
					}
				}),
				BX.create('input', {
					props: {id: this.fieldHiddenNameIdPrefix+fieldObject.ID},
					attrs: {
						type: 'hidden',
						name: 'LIST['+this.entityId+']['+fieldObject.ID+'][VALUE]',
						value: BX.util.htmlspecialchars(fieldObject.NAME)
					}
				}),
				BX.create('input', {
					props: {id: 'stage-color-'+fieldObject.ID},
					attrs: {
						type: 'hidden',
						name: 'LIST['+this.entityId+']['+fieldObject.ID+'][COLOR]',
						value: fieldObject.COLOR
					}
				}),
				BX.create('input', {
					props: {id: 'stage-status-id-'+fieldObject.ID},
					attrs: {
						type: 'hidden',
						name: 'LIST['+this.entityId+']['+fieldObject.ID+'][STATUS_ID]',
						'data-status-id': '1',
						value: this.getNewStatusId()
					}
				}),
				BX.create('input', {
					props: {id: 'stage-semantics-'+fieldObject.ID},
					attrs: {
						type: 'hidden',
						name: 'LIST['+this.entityId+']['+fieldObject.ID+'][SEMANTICS]',
						value: fieldObject.SEMANTICS
					}
				}),
				BX.create('input', {
					props: {id: 'stage-categoryId-' + fieldObject.ID},
					attrs: {
						type: 'hidden',
						name: 'LIST['+this.entityId+']['+fieldObject.ID+'][CATEGORY_ID]',
						value: fieldObject.CATEGORY_ID
					}
				})
			]
		});

		return domElement;
	};

	CrmConfigStatusClass.prototype.getNewStatusId = function()
	{
		var newStatusId = 0;
		var listInputStatusId = BX.findChildren(BX(this.contentIdPrefix+this.entityId),
			{'tag': 'input', 'attribute': {'data-status-id': '1'}}, true);

		if(!listInputStatusId)
			return newStatusId;

		for(var k = 0; k < listInputStatusId.length; k++)
		{
			var parsedInputValue = listInputStatusId[k].value;
			parsedInputValue = parsedInputValue.indexOf(':') > 0 ?
				parsedInputValue.substring(parsedInputValue.indexOf(':') + 1) : parsedInputValue;

			var statusId = parseInt(parsedInputValue);
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

	CrmConfigStatusClass.prototype.showPlaceToInsert = function(replaceableElement, e)
	{
		if(replaceableElement.className == 'space-to-insert draghandle')
		{
			return;
		}

		var parentElement = replaceableElement.parentNode,
			spaceId = replaceableElement.getAttribute('data-space');

		var dragStartParentElement = this.getDragStartParentElement(), categoryPrefix = null;
		if (dragStartParentElement)
		{
			categoryPrefix = dragStartParentElement.id.replace(this.entityId, "");
		}
		if (categoryPrefix)
		{
			switch (categoryPrefix)
			{
				case "final-storage-":
					if (parentElement.id.indexOf("extra-storage-") !== -1)
					{
						return;
					}
					break;
				case "extra-storage-":
					if (parentElement.id.indexOf("final-storage-") !== -1)
					{
						return;
					}
					break;
			}
		}

		var spaceToInsert = BX.create('div', {
			props: {
				id: 'space-to-insert-'+spaceId,
				className: 'space-to-insert draghandle'
			},
			attrs: {
				"data-place": '1'
			}
		});

		var coords = getCoords(replaceableElement);
		var displacementHeight = e.pageY - coords.top;
		var middleElement = replaceableElement.offsetHeight/2;
		if(displacementHeight > middleElement)
		{
			if(replaceableElement.className == 'transaction-stage-addphase draghandle')
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

	CrmConfigStatusClass.prototype.setDragStartParentElement = function(element)
	{
		this.dragStartParentElement = element;
	};

	CrmConfigStatusClass.prototype.getDragStartParentElement = function()
	{
		return this.dragStartParentElement;
	};

	CrmConfigStatusClass.prototype.putDomElement = function(element, parentElement, beforeElement)
	{
		if(!element || !parentElement || !beforeElement)
		{
			return false;
		}

		var categoryPrefix = parentElement.id.replace(this.entityId, ""),
			dragStartParentElement = this.getDragStartParentElement();
		switch (categoryPrefix)
		{
			case "final-storage-":
				if (dragStartParentElement && dragStartParentElement.id.indexOf("extra-storage-") !== -1)
				{
					return false;
				}
				break;
			case "extra-storage-":
				if (dragStartParentElement && dragStartParentElement.id.indexOf("final-storage-") !== -1)
				{
					return false;
				}
				break;
		}

		parentElement.insertBefore(element, beforeElement);

		return true;
	};

	CrmConfigStatusClass.prototype.deleteSpaceToInsert = function()
	{
		var spacetoinsert = BX.findChildren(BX('crm-container'),
			{'tag': 'div', 'attribute': {'data-place': '1'}}, true);

		if(spacetoinsert)
		{
			for(var i = 0; i < spacetoinsert.length; i++)
			{
				var parentElement = spacetoinsert[i].parentNode;
				parentElement.removeChild(spacetoinsert[i]);
			}
		}
	};

	CrmConfigStatusClass.prototype.insertAfter = function(node, referenceNode)
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

	CrmConfigStatusClass.prototype.checkChanges = function()
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

				if (this.oldData[k] && this.oldData[k][i])
				{
					var newSort = parseInt(this.data[k][i].SORT),
						oldSort = parseInt(this.oldData[k][i].SORT),
						newName = this.data[k][i].NAME.toLowerCase(),
						oldName = this.oldData[k][i].NAME.toLowerCase(),
						newColor = this.data[k][i].COLOR.toLowerCase(),
						oldColor = this.oldData[k][i].COLOR.toLowerCase();

					if ((newSort !== oldSort) || (newName !== oldName) || (newColor !== oldColor))
					{
						changes = true;
						break;
					}
				}
			}
		}

		if(this.totalNumberFields !== newTotalNumberFields || changes)
		{
			return BX.message('CRM_STATUS_CHECK_CHANGES');
		}
	};

	CrmConfigStatusClass.prototype.confirmSubmit = function()
	{
		this.checkSubmit = true;
	};

	/* For fix statuses */
	CrmConfigStatusClass.prototype.fixStatuses = function()
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

	CrmConfigStatusClass.prototype.correctionColorPicker = function(event, fieldId)
	{
		if(!fieldId)
		{
			return;
		}

		var blockColorPicker = BX('block-color-picker');
		blockColorPicker.style.left = event.pageX+'px';
		blockColorPicker.style.top = event.pageY+'px';
		var img = BX.findChildren(BX('block-color-picker'), {'tag': 'IMG'}, true)[0];
		img.setAttribute('data-img', fieldId);
		img.onclick();
	};

	CrmConfigStatusClass.prototype.paintElement = function(color, objColorPicker)
	{
		if(!objColorPicker)
		{
			return;
		}

		var fieldId = objColorPicker.pWnd.getAttribute('data-img');
		var fields = BX.findChildren(BX(this.contentIdPrefix+this.entityId),
			{'tag': 'div', 'attribute': {'id': this.mainDivStorageFieldIdPrefix+fieldId}}, true);

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
				color = this.getDefaultColor();
			}

			if(!this.hasSemantics)
			{
				color = this.defaultLineColor;
			}

			fields[0].style.background = color;

			var span = BX.findChildren(fields[0], {'tag': 'span', 'attribute':
				{'id': 'transaction-stage-phase-icon'}}, true);

			var phasePanel = BX.findChildren(fields[0], {'attribute': {'id': 'phase-panel'}}, true);

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
						span[0].className = result.ICON_CLASS+' '+span[0].getAttribute('data-class');
						for(var k in phasePanel)
						{
							phasePanel[k].className = result.BLOCK_CLASS+' '+phasePanel[k].getAttribute('data-class');
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
			{'tag': 'input', 'attribute': {'id': 'stage-color-'+fieldId}}, true);
		if(hiddenInputColor[0])
		{
			hiddenInputColor[0].value = color;
		}
		this.data[this.entityId][fieldId].COLOR = color;

		this.recalculateSort();
	};

	CrmConfigStatusClass.prototype.initAmCharts = function()
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

		if(this.hasSemantics)
		{
			AmCharts.handleLoad();
		}
	};

	CrmConfigStatusClass.prototype.renderAmCharts = function()
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

	CrmConfigStatusClass.prototype.getDataForAmCharts = function(chartId)
	{
		var fields = [], color = '', success = false;
		if(chartId == this.funnelSuccessIdPrefix+this.entityId)
		{
			fields = this.successFields[this.entityId];
			color = this.getDefaultColor();
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
			this.dataFunnel[i] = {'title': BX.util.htmlspecialchars(fields[i].NAME), 'value': 1};
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

	CrmConfigStatusClass.prototype.showError = function()
	{
		var error = this.getParameterByName('ERROR');
		if(error)
		{
			var content = BX.create('p', {
				props: {className: 'bx-crm-popup-paragraph'},
				html: BX.util.htmlspecialchars(error)
			});
			this.modalWindow({
				modalId: 'bx-crm-popup',
				title: BX.message("CRM_STATUS_REMOVE_ERROR"),
				overlay: false,
				content: [content],
				events : {
					onPopupClose : function() {
						this.destroy();
					}
				},
				buttons: [
					BX.create('a', {
						text : BX.message("CRM_STATUS_CLOSE_POPUP_REMOVE_ERROR"),
						props: {
							className: 'webform-small-button webform-small-button-accept'
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

	CrmConfigStatusClass.prototype.getParameterByName = function(name)
	{
		name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
		var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
			results = regex.exec(location.search);
		return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
	};

	CrmConfigStatusClass.prototype.init = function()
	{
		var footer = BX('crm-configs-footer');
		if (!footer)
		{
			return;
		}

		BX.addCustomEvent(footer, 'onFooterChangeState', BX.delegate(function(state)
		{
			if (state)
			{
				BX.removeClass(footer, 'crm-configs-footer');
				BX.addClass(footer, 'webform-buttons-fixed');
			}
			else
			{
				BX.addClass(footer, 'crm-configs-footer');
				BX.removeClass(footer, 'webform-buttons-fixed');
			}
		}, this));

		BX.bind(window, 'scroll', BX.proxy(this.processingFooter, this));

		this.processingFooter();

		if(!this.blockFixed)
		{
			BX.onCustomEvent(this.footer, 'onFooterChangeState', [false]);
		}
	};

	CrmConfigStatusClass.prototype.processingFooter = function()
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

		BX.onCustomEvent(this.footer, 'onFooterChangeState', [this.footerFixed]);

		var padding = parseInt(BX.style(this.footer, 'paddingLeft'));

		this.footer.style.left = this.contentPosition.left + 'px';
		this.footer.style.width = (this.contentPosition.width - padding*2) + 'px'

	};

	CrmConfigStatusClass.prototype.fixFooter = function(fixButton)
	{
		this.blockFixed = !this.blockFixed;
		if(this.blockFixed)
		{
			BX.userOptions.save('crm', 'crm_config_status', 'fix_footer', 'on');

			BX.addClass(fixButton, 'crm-fixedbtn-pin');
			fixButton.setAttribute('title', BX.message('CRM_STATUS_FOOTER_PIN_OFF'));

			this.processingFooter();
		}
		else
		{
			BX.userOptions.save('crm', 'crm_config_status', 'fix_footer', 'off');

			BX.removeClass(fixButton, 'crm-fixedbtn-pin');
			fixButton.setAttribute('title', BX.message('CRM_STATUS_FOOTER_PIN_ON'));

			BX.onCustomEvent(this.footer, 'onFooterChangeState', [false]);
		}
	};

	CrmConfigStatusClass.semanticEntityTypes = [];
	CrmConfigStatusClass.entityInfos = [];

	CrmConfigStatusClass.hasSemantics = function(entityTypeId)
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

	CrmConfigStatusClass.getCategoryId = function(entityId)
	{
		var entityInfos = this.entityInfos;
		if(!entityInfos || !entityInfos[entityId])
		{
			return null;
		}

		return entityInfos[entityId]['CATEGORY_ID'] || null;
	};

	return CrmConfigStatusClass;
})();