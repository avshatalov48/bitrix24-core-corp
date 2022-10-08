BX.namespace("BX.Disk");
BX.Disk.MeasureClass = (function ()
{
	var REPEAT_TIMEOUT = 500;
	var REFRESH_TOTALS_INTERVAL = 1000 * 60;//1 min
	var xhr;

	var MeasureClass = function (param)
	{
		param = param || {};

		if(typeof(param.componentParams) === 'undefined' || param.componentParams === '')
		{
			throw new Error("BX.Disk.Measure: 'componentParams' parameter missing.");
		}
		this.componentParams = param.componentParams;

		this.amount = {};

		this.ajaxUrl = param.ajaxUrl || '/bitrix/components/bitrix/disk.volume/ajax.php';
		this.relUrl = param.relUrl;
		this.filterId = param.filterId;
		this.storageId = param.storageId;
		this.gridId = param.gridId;
		this.progressBar = BX('bx-disk-volume-loader-progress-bar');

		this.hasWorkerInProcess = param.hasWorkerInProcess || false;
		this.stepper = BX("bx-disk-volume-stepper");
		this.suppressStepperAlert = param.suppressStepperAlert || false;
		this.stepperAlert = BX("bx-disk-volume-stepper-alert");
		BX.Event.EventEmitter.subscribe("onStepperHasBeenFinished", BX.proxy(this.stepperFinished,this));
		setTimeout(BX.proxy(this.initStepperHints, this), 100);
		setTimeout(BX.proxy(this.stepperAddCancelButton, this), 110);
		setTimeout(BX.proxy(this.initLocalLinks, this), 120);

		// totals
		totalDiskSize = BX("bx-disk-volume-total-disk-size");
		totalDiskCount = BX("bx-disk-volume-total-disk-count");
		totalUnnecessary = BX("bx-disk-volume-total-unnecessary");
		totalUnnecessary = BX("bx-disk-volume-total-unnecessary");
		totalUnnecessaryFormat = BX("bx-disk-volume-total-unnecessary-format");
		totalTrashcan = BX("bx-disk-volume-total-trashcan");
		totalTrashcanFormat = BX("bx-disk-volume-total-trashcan-format");
		dropTotalSizeDigit = BX("bx-disk-volume-drop-size-digit");
		dropTotalSizeUnits = BX("bx-disk-volume-drop-size-units");

		// hide some params
		var url = location.href;
		url = url.replace(/(reload|expert|admin|filterId)=(Y|N|on|off|[0-9]*)/g, "").replace(/\&\&/g, "&").replace(/[\&\?]+$/, "");
		if(url !== "" && url !== location.href)
		{
			if (typeof(window.history.replaceState) === "function")
			{
				window.history.replaceState({}, null, url);
			}
			else if (typeof(window.history.pushState) === "function")
			{
				window.history.pushState({}, null, url);
			}
		}

		BX.bind(window, 'beforeunload', BX.proxy(this.onUnload, this));

		window.setTimeout(BX.proxy(this.refreshTotals, this), REFRESH_TOTALS_INTERVAL);
	};

	MeasureClass.prototype.onUnload = function (ev)
	{
		if (typeof ev === 'undefined')
		{
			ev = window.event;
		}
		if (this.hasWorkerInProcess && this.suppressStepperAlert !== true)
		{
			var message = BX.message('DISK_VOLUME_CLOSE_WARNING');
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

		var reloadMeasureLinks = BX.findChildrenByClassName(node, 'disk-volume-reload-link');
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


	MeasureClass.prototype.callAction = function (param)
	{
		param = param || {};

		if(typeof(param.action) === 'undefined' || param.action === '')
		{
			throw new Error("BX.Disk.Measure: 'action' parameter missing.")
		}

		if(typeof(param.before) === 'function')
		{
			param.before.apply(this, [param]);
			delete param.before;// call it only once
		}

		var reqParam = BX.clone(param);
		delete reqParam.before;
		delete reqParam.after;

		var href = this.ajaxUrl + '?action=' + param.action;
		if(!!param.metric)
		{
			href += '&metric=' + param.metric;
			delete param.metric;
		}
		if(!!param.metric1)
		{
			href += '&metric1=' + param.metric1;
			delete param.metric1;
		}
		if(!!param.metric2)
		{
			href += '&metric2=' + param.metric2;
			delete param.metric2;
		}

		xhr = BX.Disk.ajax({
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
			componentParams: this.componentParams
		};
		if(!!this.storageId)
		{
			ret.storageId = this.storageId;
		}

		return ret;
	};

	MeasureClass.prototype.actionComplete = function (response, param)
	{
		response = response || {};
		param = param || {};
		var percent;

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
						BX.Disk.showActionModal({
							text: response.message,
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

			if (typeof(param.after) === 'function')
			{
				param.after.apply(this, [response, param]);
			}

			if (!!response.stepper)
			{
				this.stepperShow(response.stepper);
			}

			if (typeof(param.doNotShowModalAlert) === 'undefined' && !!response.message)
			{
				BX.Disk.showModalWithStatusAction(response);
			}
			else
			{
				var currentPopup = BX.PopupWindowManager.getCurrentPopup();
				if(currentPopup)
				{
					if(!currentPopup.isShown() || currentPopup.uniquePopupId === 'bx-disk-status-action')
					{
						currentPopup.destroy();
					}
				}
			}
		}
		else if (!!response.status && !!response.status)// 'denied'
		{
			BX.Disk.showModalWithStatusAction(response);
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

		var messageDescription;
		if (!!param.messageConfirmId)
		{
			messageDescription = BX.message(param.messageConfirmId);
		}
		else
		{
			messageDescription = param.messageConfirm;
		}
		if(!!param.name)
		{
			messageDescription = messageDescription.replace('#NAME#', param.name);
		}

		var acceptButtonText = param.acceptButton || BX.message("DISK_VOLUME_DELETE_BUTTON");
		var cancelButtonText = param.cancelButton || BX.message("DISK_VOLUME_CANCEL_BUTTON");
		var titleText = param.title || BX.message('DISK_VOLUME_DELETE_CONFIRM_TITLE');

		delete param.payload;
		delete param.messageConfirm;
		delete param.messageConfirmId;
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

						if(!!payload)
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

		BX.Disk.modalWindow({
			modalId: 'bx-link-unlink-confirm',
			title: titleText,
			contentClassName: 'tac',
			contentStyle: {
				paddingTop: '70px',
				paddingBottom: '70px'
			},
			content: messageDescription,
			buttons: buttons
		});
	};

	MeasureClass.prototype.showAlertSetupProcess = function ()
	{
		BX.Disk.showActionModal({
			text: BX.message('DISK_VOLUME_SETUP_CLEANER'),
			showLoaderIcon: true,
			autoHide: false
		});
	};

	var totalDiskSize, totalDiskCount, totalUnnecessary, totalTrashcan;
	var totalUnnecessaryFormat, totalTrashcanFormat;
	var dropTotalSizeDigit, dropTotalSizeUnits;

	MeasureClass.prototype.refreshTotals = function (param)
	{
		if (currentStep >= 0 || subStep >= 0)
		{
			window.setTimeout(BX.proxy(this.refreshTotals, this), REFRESH_TOTALS_INTERVAL);
			return;
		}

		param = param || {};

		BX.Disk.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxUrl,
			data: BX.merge(
				{action: 'reloadTotals'},
				this.ajaxAuxParams(),
				param
			),
			onsuccess: BX.proxy(function (response){
				if(!!response.status && response.status === "success")
				{
					this.amount = response;

					if(!!dropTotalSizeDigit && !!response.DROP_TOTAL_SIZE_DIGIT)
					{
						dropTotalSizeDigit.innerText = response.DROP_TOTAL_SIZE_DIGIT;
					}

					if(!!dropTotalSizeUnits && !!response.DROP_TOTAL_SIZE_UNITS)
					{
						dropTotalSizeUnits.innerText = response.DROP_TOTAL_SIZE_UNITS;
					}

					if(!!totalDiskSize)
					{
						if (!!response.TOTAL_FILE_SIZE_FORMAT)
						{
							if(response.TOTAL_FILE_SIZE > 0)
							{
								totalDiskSize.innerText =
									BX.message('DISK_VOLUME_DISK_TOTAL_USEAGE').replace('#FILE_SIZE#', response.TOTAL_FILE_SIZE_FORMAT);
								BX.show(totalDiskSize, 'inline-block');
								BX.Disk.helperHint.initHints(totalDiskSize, true);
							}
							else
							{
								BX.hide(totalDiskSize);
							}
						}
					}
					if(!!totalDiskCount)
					{
						if (response.TOTAL_FILE_COUNT > 0)
						{
							totalDiskCount.innerText =
								BX.message('DISK_VOLUME_DISK_TOTAL_COUNT').replace('#FILE_COUNT#', response.TOTAL_FILE_COUNT);
							BX.show(totalDiskCount, 'inline-block');
							BX.Disk.helperHint.initHints(totalDiskCount, true);
						}
						else
						{
							BX.hide(totalDiskCount);
						}
					}
					if(!!totalUnnecessary || !!totalUnnecessaryFormat)
					{
						if (!!response.DROP_UNNECESSARY_VERSION_FORMAT)
						{
							if(!!totalUnnecessary)
							{
								if(response.DROP_UNNECESSARY_VERSION_COUNT > 0)
								{
									totalUnnecessary.innerText =
										BX.message('DISK_VOLUME_VERSION_FILES').replace('#FILE_SIZE#', response.DROP_UNNECESSARY_VERSION_FORMAT);
									BX.show(totalUnnecessary, 'inline-block');
									BX.Disk.helperHint.initHints(totalUnnecessary, true);
								}
								else
								{
									BX.hide(totalUnnecessary);
								}
							}
							if(!!totalUnnecessaryFormat)
							{
								if(response.DROP_UNNECESSARY_VERSION_COUNT > 0)
								{
									totalUnnecessaryFormat.innerText = response.DROP_UNNECESSARY_VERSION_FORMAT;
								}
								else
								{
									var button = BX.findParent(totalUnnecessaryFormat, {className: 'disc-volume-space-entity-block'});
									if(!!button)
									{
										BX.hide(button);
									}
								}
							}
						}
						else if(!!totalUnnecessary)
						{
							BX.hide(totalUnnecessary);
						}
					}
					if(!!totalTrashcan || !!totalTrashcanFormat)
					{
						if (!!response.DROP_TRASHCAN_FORMAT)
						{
							if (!!totalTrashcan)
							{
								if(response.DROP_TRASHCAN_COUNT > 0)
								{
									var totalTrashcanLink = BX.findChildren(totalTrashcan, {tagName: 'a'});
									if(!!totalTrashcanLink)
									{
										totalTrashcanLink.innerText =
											BX.message('DISK_VOLUME_TRASHCAN').replace('#FILE_SIZE#', response.DROP_TRASHCAN_FORMAT);
									}
									else
									{
										totalTrashcan.innerText =
											BX.message('DISK_VOLUME_TRASHCAN').replace('#FILE_SIZE#', response.DROP_TRASHCAN_FORMAT);
										BX.Disk.helperHint.initHints(totalTrashcan, true);
									}
									BX.show(totalTrashcan, 'inline-block');
								}
								else
								{
									BX.hide(totalTrashcan);
								}
							}
							if (!!totalTrashcanFormat)
							{
								if(response.DROP_TRASHCAN_COUNT > 0)
								{
									totalTrashcanFormat.innerText = response.DROP_TRASHCAN_FORMAT;
									if(response.DROP_TRASHCAN == 0)
									{
										totalTrashcanFormat.innerText =
											response.DROP_TRASHCAN_FORMAT +
											' (' + BX.message('DISK_VOLUME_TRASHCAN').replace('#FILE_SIZE#', response.DROP_TRASHCAN_COUNT) + ')';
									}
								}
								else
								{
									var button = BX.findParent(totalTrashcanFormat, {className: 'disc-volume-space-entity-block'});
									if(!!button)
									{
										BX.hide(button);
									}
								}
							}
						}
						else if (!!totalTrashcan)
						{
							BX.hide(totalTrashcan);
						}
					}

				}

				window.setTimeout(BX.proxy(this.refreshTotals, this), REFRESH_TOTALS_INTERVAL);

			}, this)
		});
	};

	MeasureClass.prototype.deleteFile = function (param)
	{
		this.markGridRowWait([param.fileId]);

		BX.Disk.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxUrl,
			data: BX.merge(
				{action: 'deleteFile'},
				this.ajaxAuxParams(),
				param
			),
			onsuccess: BX.proxy(function (response){
				if(response.status === "error")
				{
					this.markGridRowNormal([param.fileId]);
					BX.Disk.showModalWithStatusAction(response);
				}
				else
				{
					this.removeGridRow([param.fileId]);
					this.reloadGrid();

					BX.Disk.showActionModal({
						text: response.message,
						autoHide: true
					});
				}

			}, this)
		});
	};

	MeasureClass.prototype.deleteFileUnnecessaryVersion = function (param)
	{
		this.markGridRowWait([param.fileId]);

		BX.Disk.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxUrl,
			data: BX.merge(
				{action: 'deleteFileUnnecessaryVersion'},
				this.ajaxAuxParams(),
				param
			),
			onsuccess: BX.proxy(function (response){

				this.markGridRowNormal([param.fileId]);

				if(response.status === "error")
				{
					BX.Disk.showModalWithStatusAction(response);
				}
				else
				{
					BX.Disk.showActionModal({
						text: response.message,
						autoHide: true
					});
				}
			}, this)
		});
	};

	MeasureClass.prototype.deleteUnnecessaryVersion = function (param)
	{
		this.markGridRowWait([param.filterId]);

		BX.Disk.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxUrl,
			data: BX.merge(
				{action: 'deleteUnnecessaryVersion'},
				this.ajaxAuxParams(),
				param
			),
			onsuccess: BX.proxy(function (response){

				this.markGridRowNormal([param.filterId]);

				if(response.status === "error")
				{
					BX.Disk.showModalWithStatusAction(response);
				}
				else if(!!response.timeout && response.timeout === 'Y')
				{
					setTimeout(BX.proxy(this.deleteUnnecessaryVersion, this), REPEAT_TIMEOUT, param);
				}
				else
				{
					BX.Disk.showActionModal({
						text: response.message,
						autoHide: true
					});
				}
			}, this)
		});
	};

	MeasureClass.prototype.deleteFolder = function (param)
	{
		this.markGridRowWait([param.filterId]);

		BX.Disk.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxUrl,
			data: BX.merge(
				{action: 'deleteFolder'},
				this.ajaxAuxParams(),
				param
			),
			onsuccess: BX.proxy(function (response){

				this.markGridRowNormal([param.filterId]);

				if(response.status === "error")
				{
					BX.Disk.showModalWithStatusAction(response);
				}
				else if(!!response.timeout && response.timeout === 'Y')
				{
					setTimeout(BX.proxy(this.deleteFolder, this), REPEAT_TIMEOUT, param);
				}
				else
				{
					BX.Disk.showActionModal({
						text: response.message,
						autoHide: true
					});
					this.reloadGrid();
				}
			}, this)
		});
	};

	MeasureClass.prototype.emptyFolder = function (param)
	{
		this.markGridRowWait([param.filterId]);

		BX.Disk.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxUrl,
			data: BX.merge(
				{action: 'emptyFolder'},
				this.ajaxAuxParams(),
				param
			),
			onsuccess: BX.proxy(function (response){

				this.markGridRowNormal([param.filterId]);

				if(response.status === "error")
				{
					BX.Disk.showModalWithStatusAction(response);
				}
				else if(!!response.timeout && response.timeout === 'Y')
				{
					setTimeout(BX.proxy(this.emptyFolder, this), REPEAT_TIMEOUT, param);
				}
				else
				{
					BX.Disk.showActionModal({
						text: response.message,
						autoHide: true
					});
					this.reloadGrid();
				}
			}, this)
		});
	};

	MeasureClass.prototype.progressBarShow = function (percent)
	{
		if(this.progressBar)
		{
			var progressBarNumber = BX.findChildByClassName(this.progressBar, 'disk-volume-loader-progress-bar-number', true);
			var progressBarLine = BX.findChildByClassName(this.progressBar, 'disk-volume-loader-progress-bar-line-active', true);
			if(percent > 100) percent = 100;
			if(percent < 0) percent = 0;
			progressBarNumber.innerHTML = percent + "%";
			progressBarLine.style.width = percent + '%';

			BX.addClass(BX('bx-disk-volume-main-block'), 'disk-volume-running');
			if(!!BX('bx-disk-volume-menu'))
			{
				BX.hide(BX('bx-disk-volume-menu'));
			}
		}
	};

	MeasureClass.prototype.progressBarHide = function ()
	{
		BX.removeClass(BX('bx-disk-volume-main-block'), 'disk-volume-running');
		if(!!BX('bx-disk-volume-menu'))
		{
			BX.show(BX('bx-disk-volume-menu'));
		}
	};

	MeasureClass.prototype.groupAction = function ()
	{
		var grid = this.getGrid();
		var actionPanel = grid.getActionsPanel();
		var selectedIds = actionPanel.getSelectedIds();

		if(selectedIds.length > 0)
		{
			var actions = actionPanel.getValues();
			var currentGroupAction = actions.action_button, indicatorId;
			var data, row, rows = grid.getRows();
			var params = {
				payload: 'callAction',
				action: 'setupCleanerJob',
				filterIdsStorage: [],
				filterIdsTrashCan: [],
				fileIds: [],
				before: function () {
					BX.Disk.showActionModal({
						text: BX.message('DISK_VOLUME_SETUP_CLEANER'),
						showLoaderIcon: true,
						autoHide: false
					});
				}
			};
			for(var i in selectedIds)
			{
				row = rows.getById(selectedIds[i]);
				if (row)
				{
					data = row.getDataset();
					if (data)
					{
						indicatorId = data.indicatorid;
						params.filterIdsStorage.push(row.getId());
						if (parseInt(data.filteridtrashcan) > 0)
						{
							params.filterIdsTrashCan.push(data.filteridtrashcan);
						}
						if (indicatorId === 'Folder' || indicatorId === 'File' || indicatorId === 'FileDeleted')
						{
							if (parseInt(data.storageid) > 0)
							{
								params.storageId = data.storageid;
							}
						}
						if (indicatorId === 'File' || indicatorId === 'FileDeleted')
						{
							params.fileIds.push(row.getId());
						}
					}
				}
			}

			switch (currentGroupAction)
			{
				case 'sendNotification':
				{
					if (indicatorId === 'Storage_TrashCan')
					{
						this.callAction({
							action: 'sendNotification',
							indicatorId: indicatorId,
							filterIdsStorage: params.filterIdsTrashCan
						});
					}
					else
					{
						this.callAction({
							action: 'sendNotification',
							indicatorId: indicatorId,
							filterIdsStorage: params.filterIdsStorage
						});
					}
					break;
				}

				case 'setupCleanerJob':
				{
					params.metric = this.getMetricMark('CERTAIN_DISK_CLEAN');

					if (indicatorId === 'Storage_Storage' ||
						indicatorId === 'Storage_Common' ||
						indicatorId === 'Storage_Group' ||
						indicatorId === 'Storage_User'
					){
						params.messageConfirm = BX.message('DISK_VOLUME_GROUP_DISK_SAFE_CLEAR_CONFIRM');
						params.deleteUnnecessaryVersion = 'Y';
						params.emptyTrashcan = 'Y';
					}
					else if (indicatorId === 'Storage_Uploaded')
					{
						params.messageConfirm = BX.message('DISK_VOLUME_GROUP_UPLOADED_SAFE_CLEAR_CONFIRM');
						params.deleteUnnecessaryVersion = 'Y';
						params.emptyTrashcan = 'N';
					}
					else if (indicatorId === 'Storage_TrashCan')
					{
						params.messageConfirm = BX.message('DISK_VOLUME_GROUP_TRASHCAN_SAFE_CLEAR_CONFIRM');
						params.deleteUnnecessaryVersion = 'N';
						params.emptyTrashcan = 'Y';
					}

					this.openConfirm(params);
					break;
				}

				case 'setupFolderEmptyJob':
				{
					params.metric = this.getMetricMark('CERTAIN_FOLDER_CLEAN');
					params.messageConfirm = BX.message('DISK_VOLUME_GROUP_FOLDER_EMPTY_CONFIRM');
					params.emptyFolder = 'Y';
					delete params.filterIdsTrashCan;
					this.openConfirm(params);
					break;
				}

				case 'setupFolderDropJob':
				{
					params.metric = this.getMetricMark('CERTAIN_FOLDER_CLEAN');
					params.messageConfirm = BX.message('DISK_VOLUME_GROUP_FOLDER_DROP_CONFIRM');
					params.deleteFolder = 'Y';
					delete params.filterIdsTrashCan;
					this.openConfirm(params);
					break;
				}

				case 'setupFolderCleanerJob':
				{
					params.metric = this.getMetricMark('CERTAIN_FOLDER_CLEAN');
					params.messageConfirm = BX.message('DISK_VOLUME_GROUP_FOLDER_SAFE_CLEAR_CONFIRM');
					params.deleteUnnecessaryVersion = 'Y';
					delete params.filterIdsTrashCan;
					this.openConfirm(params);
					break;
				}

				case 'deleteFileUnnecessaryVersion':
				{
					params.messageConfirm = BX.message('DISK_VOLUME_GROUP_DELETE_FILE_UNNECESSARY_VERSION_CONFIRM');
					params.action = 'deleteGroupFileUnnecessaryVersion';
					params.deleteUnnecessaryVersion = 'Y';
					params.before = function (param)
					{
						this.markGridRowWait(param.fileIds);
					};
					params.after = function (response, param)
					{
						this.markGridRowNormal(param.fileIds);
						this.reloadGrid();
					};
					delete params.filterIdsStorage;
					this.openConfirm(params);
					break;
				}

				case 'deleteFile':
				{
					params.messageConfirm = BX.message('DISK_VOLUME_GROUP_DELETE_FILE_CONFIRM');
					params.action = 'deleteGroupFile';
					params.before = function (param)
					{
						this.markGridRowWait(param.fileIds);
					};
					params.after = function (response, param)
					{
						this.markGridRowNormal(param.fileIds);
						this.removeGridRow(param.fileIds);
						this.reloadGrid();
					};
					delete params.filterIdsStorage;
					this.openConfirm(params);
					break;
				}
			}
		}
	};


	var grid;

	/**
	 * @return {BX.Main.grid}
	 */
	MeasureClass.prototype.getGrid = function ()
	{
		if (typeof(grid) !== "object" || typeof(grid.instance) !== "object" || !grid.instance instanceof BX.Main.grid)
		{
			if (this.gridId !== "" || BX(this.gridId))
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
		if (this.stepper)
		{
			this.hasWorkerInProcess = true;

			var ob = BX.processHTML(markup, false);

			this.stepper.innerHTML = ob.HTML;
			BX.ajax.processScripts(ob.SCRIPT);
			setTimeout(BX.proxy(this.stepperAddCancelButton, this), 100);

			BX.show(this.stepper);
			//BX.addCustomEvent(window, "onStepperHasBeenFinished", BX.proxy(this.stepperFinished,this));

			BX.hide(BX('bx-disk-volume-reload-warning'));

			setTimeout(BX.proxy(this.initStepperHints, this), 10);
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

		BX.onCustomEvent(window, 'OnDiskVolumeStepperFinished', [this]);

		BX.show(BX('bx-disk-volume-reload-warning'));

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
							'id': 'bx-disk-volume-cancel-workers',
							'className': 'disk-volume-header-link',
							'title': BX.message('DISK_VOLUME_CANCEL_WORKERS')
						},
						events: {
							"click": BX.proxy(this.showHintBalloon, this)
						},
						text: BX.message('DISK_VOLUME_CANCEL_BUTTON')
					}),
					stepperBlock
				);

				BX.bind(BX('bx-disk-volume-cancel-workers'), 'click', function () {
					BX.Disk.showActionModal({
						text: BX.message('DISK_VOLUME_PERFORMING_CANCEL_WORKERS'),
						showLoaderIcon: true,
						autoHide: false
					});
					BX.Disk.measureManager.callAction({
						action: 'cancelWorkers',
						after: BX.Disk.measureManager.stepperHide,
						doNotShowModalAlert: true
					});
				});
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

	MeasureClass.prototype.initGridHeadHints = function()
	{
		if (BX.Disk.helperHint)
		{
			var headTags = BX.findChildrenByClassName(this.getGrid().getContainer(), 'disk-volume-hint');
			for (var i = 0; i < headTags.length; i++)
			{
				var hintId = BX.data(headTags[i], 'name');
				if (hintId)
				{
					var mess = BX.message('DISK_VOLUME_' + hintId.toUpperCase() + '_HINT');
					if (mess.length > 0 && typeof(BX.data(BX(headTags[i]), 'hint-exists')) === "undefined")
					{
						var headTitleTag = BX.findChildrenByClassName(BX(headTags[i]), 'main-grid-head-title');
						BX.Disk.helperHint.addHintAfter(headTitleTag[0], mess);
						BX.data(BX(headTags[i]), 'hint-exists', 1);
					}
				}
			}
		}
	};

	MeasureClass.prototype.initStepperHints = function()
	{
		if (BX.Disk.helperHint)
		{
			var stepperHint = BX.findChildByClassName(this.stepper, BX.Disk.helperHint.classNameMarker);
			if(stepperHint === null)
			{
				var stepperInner = BX.findChildByClassName(this.stepper, 'main-stepper-steps');
				if (stepperInner)
				{
					BX.addClass(stepperInner, BX.Disk.helperHint.className);
					BX.data(stepperInner, "hint", 'stepper_steps');
					BX.Disk.helperHint.initHints(this.stepper);
				}
			}
		}
	};

	MeasureClass.prototype.repeatMeasure = function (e)
	{
		BX.PreventDefault(e);

		var measureLink = BX(e.target);
		var startMeasureButton = BX('bx-disk-volume-link-measure');

		if(!!startMeasureButton)
		{
			BX.addClass(startMeasureButton, 'ui-btn-clock');
			measureLink.disabled = true;

			this.progressBarShow(0);
			this.runQueue(1);
		}
		else
		{
			var url = measureLink.href;

			if (this.hasWorkerInProcess)
			{
				this.openConfirm({
					title: BX.message('DISK_VOLUME_MEASURE'),
					messageConfirm: BX.message('DISK_VOLUME_MEASURE_CONFIRM') + "\n\n" + BX.message('DISK_VOLUME_MEASURE_CONFIRM_QUESTION'),
					acceptButton: BX.message('DISK_VOLUME_MEASURE_ACCEPT'),
					payload: function () {
						BX.addClass(measureLink, 'ui-btn-clock');
						window.location.href = url;
					}
				});
			}
			else
			{
				BX.addClass(measureLink, 'ui-btn-clock');
				window.location.href = url;
			}
		}
		return true;
	};

	MeasureClass.prototype.showStorageMeasure = function(rowId, url)
	{
		var row = this.getGridRow(rowId);
		if (row && BX.data(row,'collected') !== '1')
		{
			var storageId = parseInt(row.getDataset().storageid);
			var filterId = parseInt(row.getDataset().filterid);
			if (storageId > 0)
			{
				BX.Disk.showActionModal({
					text: BX.message('DISK_VOLUME_PERFORMING_MEASURE_DATA'),
					autoHide: false
				});

				BX.Disk.measureManager.getGrid().getLoader().show();

				BX.Disk.measureManager.callAction({
					action: 'measureStorage',
					storageId: storageId,
					filterId: filterId,
					after: function (){ window.location.href = url; }
				});
			}
		}
		else
		{
			window.location.href = url;
		}

		return true;
	};

	var metricMarkCodesMap = {};

	MeasureClass.prototype.addMetricMark = function (metricCodes)
	{
		BX.merge(metricMarkCodesMap, metricCodes);
	};

	MeasureClass.prototype.getMetricMark = function (code)
	{
		return metricMarkCodesMap[code];
	};

	return MeasureClass;
})();



BX.Disk.HintClass = (function ()
{
	var hintTags = [];
	var hintText = [];
	var hintPopup = [];

	var HintClass = function (param)
	{
		this.className = param.className || 'disk-volume-hint';
		this.classNameMarker = param.classNameMarker || 'disk-volume-hint-marker';
		this.classNameBalloon = param.classNameBalloon || 'disk-volume-hint-balloon';
	};

	HintClass.prototype.initHints = function(node, force)
	{
		node = node || BX('workarea');
		force = force || false;

		var hintAnchorTags = BX.findChildrenByClassName(node, 'disk-volume-hint');

		if(BX.hasClass(node, 'disk-volume-hint'))
		{
			hintAnchorTags.push(node);
		}
		for (var i = 0; i < hintAnchorTags.length; i++)
		{
			var hintId = BX.data(hintAnchorTags[i], 'hint');
			if (hintId)
			{
				try
				{
					var mess = BX.message('DISK_VOLUME_' + hintId.toUpperCase() + '_HINT');
					if (mess.length > 0 && (typeof(BX.data(BX(hintAnchorTags[i]), 'hint-exists')) === "undefined" || force))
					{
						this.appendHint(BX(hintAnchorTags[i]), mess);
						BX.data(BX(hintAnchorTags[i]), 'hint-exists', 1);
					}
				}
				catch(ex)
				{
				}
			}
		}
	};

	HintClass.prototype.appendHint = function (node, text)
	{
		var id = hintTags.push(text);
		hintText[id] = text;

		BX.append(
			BX.create('span', {
				attrs: {
					"className": this.classNameMarker,
					"data-id": id
				},
				events: {
					"click": BX.proxy(this.showHintBalloon, this)
				},
				text: '?'
			}),
			node
		);
	};

	HintClass.prototype.addHintAfter = function (node, text)
	{
		var id = hintTags.push(text);
		hintText[id] = text;

		BX.insertAfter(
			BX.create('span', {
				attrs: {
					"className": this.classNameMarker,
					"data-id": id
				},
				events: {
					"click": BX.proxy(this.showHintBalloon, this)
				},
				text: '?'
			}),
			node
		);
	};

	HintClass.prototype.showHintBalloon = function (e)
	{
		BX.eventCancelBubble(e);
		BX.fireEvent(document, 'click');

		var node = BX(e.target);
		var id = BX.data(node, 'id');
		var content = hintText[id];


		if (typeof(hintPopup[id]) !== "object" || !hintPopup[id] instanceof BX.PopupWindow)
		{
			hintPopup[id] = new BX.PopupWindow("disk-hint-"+id, node,
				{
					className: this.classNameBalloon,
					lightShadow : true,
					//offsetTop: 0,
					offsetLeft: 6,
					autoHide: true,
					closeByEsc: true,
					angle: true,
					bindOptions: {position: "bottom"},
					content : content
				}
			);
		}
		var popup = hintPopup[id];
		popup.show();

		return BX.PreventDefault(e);
	};

	return HintClass;
})();