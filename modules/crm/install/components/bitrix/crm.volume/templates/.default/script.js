BX.namespace('BX.Crm.volume');
BX.Crm.MeasureClass = (function ()
{
	var xhr;

	var MeasureClass = function (param)
	{
		param = param || {};

		if(typeof(param.relUrl) === 'undefined' || param.relUrl === '')
		{
			throw new Error("BX.Crm.Measure: 'relUrl' parameter missing.");
		}

		this.amount = {};

		this.ajaxUrl = param.ajaxUrl || '/bitrix/components/bitrix/crm.volume/ajax.php';
		this.relUrl = param.relUrl;
		this.gridId = param.gridId;
		this.filterId = param.filterId;
		this.sefMode = param.sefMode || 'N';
		this.progressBar = BX('bx-crm-volume-loader-progress-bar');

		this.hasWorkerInProcess = param.hasWorkerInProcess || false;
		this.stepper = BX('bx-crm-volume-stepper');
		this.suppressStepperAlert = param.suppressStepperAlert || false;
		this.stepperAlert = BX('bx-crm-volume-message-alert');
		BX.Event.EventEmitter.subscribe("onStepperHasBeenFinished", BX.proxy(this.stepperFinished,this));
		setTimeout(BX.proxy(this.initStepperHints, this), 100);
		setTimeout(BX.proxy(this.stepperAddCancelButton, this), 110);
		setTimeout(BX.proxy(this.initLocalLinks, this), 120);
		setTimeout(BX.proxy(this.initGridLinks, this), 120);

		BX.bind(window, 'beforeunload', BX.proxy(this.onUnload, this));

		BX.addCustomEvent('Grid::updated', BX.proxy(this.initGridLinks, this));
	};

	MeasureClass.prototype.onUnload = function (ev)
	{
		if (typeof ev === 'undefined')
		{
			ev = window.event;
		}
		if (this.hasWorkerInProcess && this.suppressStepperAlert !== true)
		{
			var message = BX.message('CRM_VOLUME_CLOSE_WARNING');
			if (ev)
			{
				ev.returnValue = message;
			}

			this.stepperAlertShow();

			return message;
		}
	};

	MeasureClass.prototype.suppressUnload = function()
	{
		this.suppressStepperAlert = true;
	};

	MeasureClass.prototype.initLocalLinks = function(node)
	{
		node = node || BX('content-table');

		var reloadMeasureLinks = BX.findChildrenByClassName(node, 'crm-volume-reload-link');
		for (var i = 0; i < reloadMeasureLinks.length; i++)
		{
			BX.bind(reloadMeasureLinks[i], 'click', BX.proxy(this.repeatMeasure, this));
		}

		var linkTags = BX.findChildren(node, {tagName: 'a', attribute : {'href': /\/volume\//i}}, true);
		for (var i = 0; i < linkTags.length; i++)
		{
			BX.bind(linkTags[i], 'click', BX.proxy(this.suppressUnload, this));
		}
	};

	MeasureClass.prototype.initGridLinks = function()
	{
		var grid = this.getGrid();
		if(grid)
		{
			var gridLinks = BX.findChildrenByClassName(this.getGrid().container, 'crm-volume-link-grid');
			for (var i = 0; i < gridLinks.length; i++)
			{
				//BX.bind(gridLinks[i], 'click', BX.proxy(this.resetEntityListGrid, this));
				BX.bind(gridLinks[i], 'click', function (e) {
					BX.PreventDefault(e);
				});
				BX.bind(gridLinks[i], 'mousedown', BX.proxy(this.resetEntityListGrid, this));
				BX.bind(gridLinks[i], 'touchstart', BX.proxy(this.resetEntityListGrid, this));
			}
		}
	};

	MeasureClass.prototype.callAction = function (param)
	{
		param = param || {};

		if(typeof(param.action) === 'undefined' || param.action === '')
		{
			throw new Error("BX.Crm.Measure: 'action' parameter missing.")
		}

		if (!!param.before)
		{
			if (typeof(this[param.before]) === 'function')
			{
				this[param.before].apply(this, [param]);
			}
			else if (typeof(param.before) === 'function')
			{
				param.before.apply(this, [param]);
			}
			delete param.before;// call it only once
		}

		var reqParam = BX.clone(param);
		delete reqParam.before;
		delete reqParam.after;

		var href = this.ajaxUrl + '?action=' + param.action;

		xhr = BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: href,
			data: BX.merge(
				this.ajaxAuxParams(),
				reqParam
			),
			onsuccess: BX.proxy(function(response){ this.actionComplete(response, param); }, this)
		});
	};

	MeasureClass.prototype.ajaxAuxParams = function ()
	{
		var ret = {
			relUrl: this.relUrl,
			sefMode: this.sefMode,
			AJAX_CALL: 'Y',
			SITE_ID: BX.message('SITE_ID'),
			sessid: BX.bitrix_sessid()
		};

		return ret;
	};

	MeasureClass.prototype.actionComplete = function (response, param)
	{
		response = response || {};
		param = param || {};
		var percent;

		this.hideModal();

		if (!!response.status && response.status === 'success')
		{
			if (typeof(response.subTask) !== 'undefined' && response.subTask.length > 0)
			{
				subTask = response.subTask;
			}
			if (typeof(response.subStep) !== 'undefined' && parseInt(response.subStep) > 0)
			{
				subStep = parseInt(response.subStep);
			}
			if (subStep > 0)
			{
				param.subStep = subStep;
				param.subTask = subTask;
			}
			if (response.queueStep && response.timeout)
			{
				if (this.progressBar)
				{
					percent = Math.round((currentStep + subStep) * 100 / this.lengthQueue());
					this.progressBarShow(percent);
				}

				// repeat the same queue action
				this.callAction(BX.merge(
					{
						queueStep: (currentStep + 1),
						queueLength: queueAccumulator.length,
						subTask: subTask,
						subStep: subStep
					},
					queueAccumulator[currentStep]
				));

				return;
			}
			else if (response.timeout)
			{
				if (this.progressBar)
				{
					percent = Math.round((currentStep + subStep) * 100 / this.lengthQueue());
					this.progressBarShow(percent);
				}

				// repeat the same action
				this.callAction(param);

				return;
			}
			else if (response.queueStep)
			{
				// go to next action
				currentStep++;
				if (currentStep < queueAccumulator.length && queueAccumulator[currentStep])
				{
					if (this.progressBar)
					{
						percent = Math.round((currentStep + subStep) * 100 / this.lengthQueue());
						this.progressBarShow(percent);
					}
					else
					{
						this.modalWindow({
							content: response.message,
							showLoaderIcon: true,
							autoHide: false
						});
					}

					this.callAction(BX.merge(
						{
							queueStep: (currentStep + 1),
							queueLength: queueAccumulator.length
						},
						queueAccumulator[currentStep]
					));

					return;
				}
				else
				{
					currentStep = -1;
					this.progressBarShow(100);
				}
			}

			if (!!param.after)
			{
				if (typeof(this[param.after]) === 'function')
				{
					this[param.after].apply(this, [response, param]);
				}
				else if (typeof(param.after) === 'function')
				{
					param.after.apply(this, [response, param]);
				}
				delete param.after;// call it only once
			}

			if(typeof(response.stepper) != 'undefined')
			{
				if (response.stepper !== '')
				{
					this.stepperShow(response.stepper);
				}
				else
				{
					this.stepperHide();
				}
			}

			if (typeof(param.doNotShowModalAlert) === 'undefined' && !!response.message)
			{
				this.modalWindow(response);
			}
			else
			{
				var currentPopup = BX.PopupWindowManager.getCurrentPopup();
				if(currentPopup)
				{
					if(!currentPopup.isShown() || currentPopup.uniquePopupId === 'bx-crm-status-action')
					{
						currentPopup.destroy();
					}
				}
			}
		}
		else if (!!response.status && !!response.status)// 'denied'
		{
			this.modalWindow(response);
		}

		if (typeof(param.doNotFollowRedirect) === 'undefined')
		{
			if (!!response.url)
			{
				this.suppressUnload();
				window.location.href = response.url;
			}
		}
	};


	var currentModalId = null;
	var modalTimeoutId = null;


	MeasureClass.prototype.modalWindow = function (params)
	{
		params = params || {};
		params.title = params.title || false;
		params.bindElement = params.bindElement || null;
		params.overlay = typeof params.overlay == "undefined" ? true : params.overlay;
		params.closeIcon = typeof params.closeIcon == "undefined"? true : params.closeIcon;
		params.modalId = params.modalId || 'crm_volume_modal_window_' + (Math.random() * (200000 - 100) + 100);
		params.withoutContentWrap = typeof params.withoutContentWrap == "undefined" ? false : params.withoutContentWrap;
		params.contentClassName = params.contentClassName || '';
		params.contentStyle = params.contentStyle || {};
		params.content = params.content || [];
		params.buttons = params.buttons || false;
		params.events = params.events || {};
		params.autoHide = params.autoHide || false;
		params.autoHideTimeout = params.autoHideTimeout || 3000;

		if (!!params.status && params.status === 'error' && params.errors.length > 0)
		{
			params.content.push(params.errors.shift().message);
			params.withoutContentWrap = false;
			params.title = BX.message('CRM_VOLUME_ERROR');
		}
	
		var contentDialogChildren = [];
		if (params.withoutContentWrap)
		{
			contentDialogChildren = contentDialogChildren.concat(params.content);
		}
		else
		{
			contentDialogChildren.push(BX.create('div', {
				props: {
					className: 'crm-volume-popup-container ' + params.contentClassName
				},
				style: params.contentStyle,
				children: params.content
			}));
		}
		if (params.htmlButtons) {
			var htmlButtons = [];
			for (var i in params.htmlButtons) {
				if (!params.htmlButtons.hasOwnProperty(i)) {
					continue;
				}
				if (i > 0) {
					htmlButtons.push(BX.create('SPAN', {html: '&nbsp;'}));
				}
				htmlButtons.push(params.htmlButtons[i]);
			}
	
			contentDialogChildren.push(BX.create('div', {
				props: {
					className: 'crm-volume-popup-buttons'
				},
				children: htmlButtons
			}));
		}
	
		var contentDialog = BX.create('div', {
			props: {
				className: 'crm-volume-popup-container'
			},
			children: contentDialogChildren
		});
	
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
	
			BX.proxy_context.destroy();
		}, this);
	
		var modalWindow = BX.PopupWindowManager.create(
			params.modalId,
			params.bindElement,
			{
				titleBar: params.title,
				content: contentDialog,
				closeByEsc: true,
				closeIcon: params.closeIcon,
				overlay: params.overlay,
				events: params.events,
				buttons: params.buttons,
				zIndex : isNaN(params["zIndex"]) ? 0 : params.zIndex
			}
		);

		modalWindow.show();

		currentModalId = params.modalId;

		if(params.autoHide && params.autoHideTimeout > 0)
		{
			modalTimeoutId = setTimeout(BX.proxy(this.hideModal, this), params.autoHideTimeout);
		}

		return modalWindow;
	};

	MeasureClass.prototype.hideModal = function ()
	{
		if (!!currentModalId)
		{
			var w = BX.PopupWindowManager.getCurrentPopup();
			if (!w || w.uniquePopupId != currentModalId)
			{
				return;
			}
			w.close();
			w.destroy();

			currentModalId = null;

			if(modalTimeoutId > 0)
			{
				clearTimeout(modalTimeoutId);
				modalTimeoutId = null;
			}
		}
	};


	var subStep = -1;
	var subTask = null;
	var currentStep = -1;
	var queueAccumulator = [];
	var queueAccumulatorLength = 0;

	MeasureClass.prototype.addQueueItem = function (item)
	{
		queueAccumulator.push(item);

		queueAccumulatorLength ++;
		if(typeof(item.subTaskCount) !== 'undefined' &&  parseInt(item.subTaskCount) > 0)
		{
			queueAccumulatorLength += parseInt(item.subTaskCount);
		}
	};

	MeasureClass.prototype.runQueue = function (startStep, param)
	{
		startStep = startStep || 1;
		param = param || {};

		if(this.lengthQueue() > 0)
		{
			subStep = 0;
			subTask = null;
			currentStep = 0;
			if (startStep > 0)
			{
				currentStep = startStep - 1;
			}
			this.callAction(BX.merge(
				{queueStep: (currentStep + 1), queueLength: queueAccumulator.length},
				queueAccumulator[currentStep],
				param
			));
		}
	};

	MeasureClass.prototype.stopQueue = function ()
	{
		subStep = -1;
		subTask = null;
		currentStep = -1;

		try
		{
			xhr.abort();
		}
		catch (e){}
	};

	MeasureClass.prototype.lengthQueue = function ()
	{
		return queueAccumulatorLength;
	};

	MeasureClass.prototype.openConfirm = function (param)
	{
		var payload = param.payload;

		var messageDescription, messageConfirm = '', messageConfirmAll = '';
		if (!!param.messageConfirmId)
		{
			messageConfirm = BX.message(param.messageConfirmId);
		}
		else
		{
			messageConfirm = param.messageConfirm;
		}
		if (!!param.messageConfirmAllId)
		{
			messageConfirmAll = BX.message(param.messageConfirmAllId);
		}
		else if (!!param.messageConfirmAll)
		{
			messageConfirmAll = param.messageConfirmAll;
		}

		var squaresHtml = '';
		var filter = BX.Main.filterManager.getById(this.filterId);
		if (!!filter && (filter instanceof BX.Main.Filter))
		{

			var search = filter.getSearch();
			var squares = search.getSquares();

			for(var i in squares)
			{
				if (!squares.hasOwnProperty(i)) continue;
				var square = BX.clone(squares[i]);

				squaresHtml += square.innerHTML;
			}
		}
		if(squaresHtml != '')
		{
			messageDescription = messageConfirm + '<div>' + BX.message('CRM_VOLUME_CONFIRM_FILTER') + ': ' + squaresHtml + '</div>';
		}
		else if(messageConfirmAll != '')
		{
			messageDescription = messageConfirmAll;
		}
		else
		{
			messageDescription = messageConfirm;
		}


		var acceptButtonText = param.acceptButton || BX.message("CRM_VOLUME_DELETE");
		var cancelButtonText = param.cancelButton || BX.message("CRM_VOLUME_CANCEL");
		var titleText = param.title || BX.message('CRM_VOLUME_CONFIRM');

		delete param.payload;
		delete param.messageConfirm;
		delete param.messageConfirmId;
		delete param.messageConfirmAll;
		delete param.messageConfirmAllId;
		delete param.name;
		delete param.acceptButton;
		delete param.cancelButton;
		delete param.title;

		var buttons = [
			new BX.PopupWindowButton({
				text: acceptButtonText,
				className: "popup-window-button-accept",
				events: {
					click: BX.delegate(function (e) {
						BX.PopupWindowManager.getCurrentPopup().destroy();
						BX.PreventDefault(e);

						if (!!payload)
						{
							if (typeof(this[payload]) === 'function')
							{
								this[payload].apply(this, [param]);
							}
							else if (typeof(payload) === 'function')
							{
								payload.apply(this, [param]);
							}
						}

						return false;
					}, this)
				}
			})
		];
		buttons.push(
			new BX.PopupWindowButton({
				text: cancelButtonText,
				events: {
					click: function (e) {
						BX.PopupWindowManager.getCurrentPopup().destroy();
						BX.PreventDefault(e);
						return false;
					}
				}
			})
		);
		this.modalWindow({
			modalId: 'bx-link-unlink-confirm',
			title: titleText,
			withoutContentWrap: true,
			content: messageDescription,
			buttons: buttons
		});
	};


	MeasureClass.prototype.showAlertSetupProcess = function ()
	{
		this.modalWindow({
			content: BX.message('CRM_VOLUME_SETUP_CLEANER'),
			closeIcon: false,
			overlay:true,
			showLoaderIcon: true
		});
	};

	// totals
	MeasureClass.prototype.updateTotalSize = function (data) {
		BX.adjust(BX('bx-crm-volume-total-size'), {html: data.format});
	};
	MeasureClass.prototype.updateFileSize = function (data) {
		var node = BX('bx-crm-volume-file-size');
		BX.adjust(node, {html: data.format, style: {display: (data.size > 0 ? 'inline-block' : 'none')}});
	};



	MeasureClass.prototype.progressBarShow = function (percent)
	{
		if(this.progressBar)
		{
			var progressBarNumber = BX.findChildByClassName(this.progressBar, 'crm-volume-loader-progress-bar-number', true);
			var progressBarLine = BX.findChildByClassName(this.progressBar, 'crm-volume-loader-progress-bar-line-active', true);
			if(percent > 100) percent = 100;
			if(percent < 0) percent = 0;
			progressBarNumber.innerHTML = percent + "%";
			progressBarLine.style.width = percent + '%';

			BX.addClass(BX('bx-crm-volume-main-block'), 'crm-volume-running');
		}
	};

	MeasureClass.prototype.progressBarHide = function ()
	{
		BX.removeClass(BX('bx-crm-volume-main-block'), 'crm-volume-running');
	};



	var grid;

	MeasureClass.prototype.getGrid = function ()
	{
		if (typeof(grid) !== "object" || typeof(grid.instance) !== "object" || !grid.instance instanceof BX.Main.grid)
		{
			if (this.gridId !== "" && BX(this.gridId) && typeof(BX.Main.gridManager) !== "undefined")
			{
				grid = BX.Main.gridManager.getById(this.gridId);
			}
		}
		if (typeof(grid) === "object" && typeof(grid.instance) === "object" && grid.instance instanceof BX.Main.grid)
		{
			return grid.instance;
		}

		return null;
	};

	MeasureClass.prototype.reloadGrid = function ()
	{
		if(this.getGrid())
		{
			this.getGrid().reload();
		}
	};

	MeasureClass.prototype.getGridRow = function (rowId)
	{
		return this.getGrid().getRows().getById('' + rowId);
	};

	MeasureClass.prototype.markGridRowWait = function (rowIds)
	{
		for(var row, i = 0; i < rowIds.length; i++)
		{
			row = this.getGridRow(rowIds[i]);
			if (row)
			{
				row.getNode().style.opacity = 0.5;
			}
		}
	};

	MeasureClass.prototype.markGridRowNormal = function (rowIds)
	{
		for(var row, i = 0; i < rowIds.length; i++)
		{
			row = this.getGridRow(rowIds[i]);
			if (row)
			{
				row.getNode().style.opacity = 1;
			}
		}
	};

	MeasureClass.prototype.removeGridRow = function (rowIds)
	{
		for(var row, i = 0; i < rowIds.length; i++)
		{
			row = this.getGridRow(rowIds[i]);
			if (row)
			{
				row.getNode().remove();
			}
		}
	};

	MeasureClass.prototype.stepperShow = function (markup)
	{
		if (!!this.stepper)
		{
			this.hasWorkerInProcess = true;

			var ob = BX.processHTML(markup, false);

			this.stepper.innerHTML = ob.HTML;
			BX.ajax.processScripts(ob.SCRIPT);
			setTimeout(BX.proxy(this.stepperAddCancelButton, this), 100);

			BX.show(this.stepper);
			//BX.Event.EventEmitter.subscribe("onStepperHasBeenFinished", BX.proxy(this.stepperFinished,this));

			BX.hide(BX('bx-crm-volume-reload-warning'));
		}
	};

	MeasureClass.prototype.stepperHide = function ()
	{
		this.hasWorkerInProcess = false;

		if (this.stepper)
		{
			BX.hide(this.stepper);
			this.stepper.innerHTML = "";
		}
	};

	MeasureClass.prototype.stepperFinished = function ()
	{
		this.stepperHide();

		BX.onCustomEvent(window, 'OnCrmVolumeStepperFinished', [this]);

		BX.show(BX('bx-crm-volume-reload-warning'));

		if(this.getGrid())
		{
			this.reloadGrid();
		}
		else
		{
			this.suppressUnload();
			window.location.href = this.relUrl;
		}

	};

	MeasureClass.prototype.stepperAddCancelButton = function()
	{
		if(!!this.stepper)
		{
			var stepperBlock = BX.findChildByClassName(this.stepper, 'main-stepper');
			if (!!stepperBlock)
			{
				BX.append(
					BX.create('span', {
						attrs: {
							'id': 'bx-crm-volume-cancel-workers',
							'className': 'crm-volume-header-link',
							'title': BX.message('CRM_VOLUME_CANCEL_WORKERS')
						},
						text: BX.message('CRM_VOLUME_CANCEL')
					}),
					stepperBlock
				);

				BX.bind(BX('bx-crm-volume-cancel-workers'), 'click', BX.proxy(function () {
					this.modalWindow({
						content: BX.message('CRM_VOLUME_PERFORMING_CANCEL_WORKERS'),
						overlay: true,
						showLoaderIcon: true,
						autoHide: false
					});
					this.callAction({
						action: 'CANCEL_TASKS',
						after: 'stepperHide',
						doNotShowModalAlert: true
					});
				},this));
			}
		}
	};

	MeasureClass.prototype.stepperAlertShow = function ()
	{
		if (this.stepperAlert && this.suppressStepperAlert !== true)
		{
			BX.show(this.stepperAlert);
		}
	};

	MeasureClass.prototype.resetEntityListGrid = function (e)
	{
		BX.PreventDefault(e);
		var isLeftClick  = (BX.getEventButton(e) === BX.MSLEFT);

		var gridLink = BX(e.target),
			url = gridLink.href,
			gridId = BX.data(gridLink, 'gridId'),
			filterId = BX.data(gridLink, 'filterId'),
			fields = BX.data(gridLink, 'fields'),
			filter = BX.data(gridLink, 'filter');

		filter = JSON.parse(filter);
		filter.FIND = '';

		var filterParams = {
			'FILTER_ID': filterId,
			'GRID_ID': gridId,
			'action': 'setFilter',
			'forAll': 'false',
			'apply_filter': 'Y',
			'clear_filter': 'Y',
			'with_preset': 'N',
			'save': 'Y'
		};
		var filterData = {
			"fields": filter,
			"rows": fields,
			'preset_id': 'tmp_filter'
		};

		BX.ajax.runComponentAction(
			'bitrix:main.ui.filter',
			'setFilter',
			{
				mode: 'ajax',
				data: {'params': filterParams, 'data': filterData}
			}
		).then(function (response) {
			if(isLeftClick)
			{
				window.location.href = url;
			}
		});

		return !isLeftClick;
	};

	MeasureClass.prototype.repeatMeasure = function (e)
	{
		BX.PreventDefault(e);

		var measureLink = BX(e.target);
		var startMeasureButton = BX('bx-crm-volume-link-measure');

		if(!!startMeasureButton)
		{
			startMeasureButton.disabled = true;
			BX.addClass(startMeasureButton, 'ui-btn-disabled ui-btn-wait');

			this.progressBarShow(0);
			this.runQueue(1);
		}
		else
		{
			var url = measureLink.href;

			if (this.hasWorkerInProcess)
			{
				this.openConfirm({
					title: BX.message('CRM_VOLUME_MEASURE_DATA_REPEAT'),
					messageConfirm: BX.message('CRM_VOLUME_MEASURE_CONFIRM') + "\n\n" + BX.message('CRM_VOLUME_MEASURE_CONFIRM_QUESTION'),
					acceptButton: BX.message('CRM_VOLUME_MEASURE_ACCEPT'),
					payload: function () {
						BX.addClass(measureLink, 'ui-btn-wait ui-btn-disabled');
						window.location.href = url;
					}
				});
			}
			else
			{
				BX.addClass(measureLink, 'ui-btn-wait ui-btn-disabled');
				window.location.href = url;
			}
		}
		return true;
	};


	MeasureClass.prototype.alertShow = function (response)
	{
		response = response || {};
		if (!response.message)
		{
			if (response.status === 'success')
			{
				response.message = BX.message('CRM_VOLUME_SUCCESS');
			}
			else
			{
				response.message = BX.message('CRM_VOLUME_ERROR');
				if(response.error)
				{
					response.message += '. ' + response.error;
				}
			}
		}
		if (this.stepperAlert)
		{
			var alertText = BX.findChildByClassName(this.stepperAlert, 'ui-btn-message');

			BX.adjust(alertText, {
				text: BX.util.htmlspecialchars(response.message)
			});

			BX.removeClass(this.stepperAlert, 'ui-alert-danger');
			BX.removeClass(this.stepperAlert, 'ui-alert-success');
			BX.removeClass(this.stepperAlert, 'ui-alert-warning');

			if (response.status === 'success')
			{
				BX.addClass(this.stepperAlert, 'ui-alert-success');
			}
			else if (response.status === 'warning')
			{
				BX.addClass(this.stepperAlert, 'ui-alert-warning');
			}
			else
			{
				BX.addClass(this.stepperAlert, 'ui-alert-danger');
			}

			BX.show(this.stepperAlert);
		}
	};

	MeasureClass.prototype.alertHide = function ()
	{
		if(this.stepperAlert)
		{
			BX.hide(this.stepperAlert);
		}
	};

	return MeasureClass;
})();


