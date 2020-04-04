(function() {

if (!BX.Tasks)
	BX.Tasks = {};

if (BX.Tasks.lwPopup)
	return;

BX.Tasks.lwPopup = {
	ajaxUrl : '/bitrix/components/bitrix/tasks.list/ajax.php',
	onTaskAdded : null,
	onTaskAddedMultiple : null,
	loggedInUserId : null,
	loggedInUserFormattedName : null,

	garbageAreaId : 'garbageAreaId_id',
	functions : {},
	functionsCount : 0,
	firstRunDone : false,

	createForm : {
		objPopup    : null,
		objTemplate : null,
		callbacks   : {
			onAfterPopupCreated : null,
			onBeforePopupShow   : null,
			onAfterPopupShow    : null,
			onAfterEditorInited : null,
			onPopupClose        : null
		}
	},

	anyForm : [],
	anyFormsCount : 0,


	registerForm : function(params)
	{
		params = params || { callbacks : {} };

		var anyFormIndex = this.anyFormsCount++;

		this.anyForm[anyFormIndex] = {
			formIndex   : anyFormIndex,
			objPopup    : null,
			objTemplate : null,
			callbacks   : params.callbacks
		};

		return (this.anyForm[anyFormIndex]);
	},


	__runAnyFormCallback : function (formIndex, callbackName, args)
	{
		args = args || [];

		if ( ! this.anyForm[formIndex] )
			throw Error('Form with index ' + formIndex + ' not exists');

		if (
			BX.Tasks.lwPopup.anyForm[formIndex].callbacks.hasOwnProperty(callbackName)
			&& (BX.Tasks.lwPopup.anyForm[formIndex].callbacks[callbackName] !== null)
		)
		{
			BX.Tasks.lwPopup.anyForm[formIndex].callbacks[callbackName].apply(
				BX.Tasks.lwPopup.anyForm[formIndex].objTemplate,
				args
			);
		}
	},


	showForm : function(formIndex, pData)
	{
		pData = typeof pData !== 'undefined' ? pData : {};

		if ( ! this.anyForm[formIndex] )
			throw Error('Form with index ' + formIndex + ' not exists');

		var oForm = this.anyForm[formIndex];

		BX.Tasks.lwPopup.__firstRun();

		var isPopupJustCreated = false;

		if (oForm.objPopup === null)
		{
			this.buildForm(formIndex, pData);

			isPopupJustCreated = true;
		}

		this.__runAnyFormCallback(
			formIndex,
			'onBeforePopupShow',
			[ pData, { isPopupJustCreated: isPopupJustCreated } ]
		);

		oForm.objPopup.show();
	},


	buildForm : function(formIndex, pData, zIndexIn)
	{
		var zIndex = -110;

		pData = typeof pData !== 'undefined' ? pData : {};

		if (typeof zIndexIn !== 'undefined')
			zIndex = zIndexIn;

		if ( ! this.anyForm[formIndex] )
			throw Error('Form with index ' + formIndex + ' not exists');

		var oForm = this.anyForm[formIndex];

		BX.Tasks.lwPopup.__firstRun();

		oForm.objPopup = new BX.PopupWindow(
			'bx-tasks-quick-popup-anyForm-' + formIndex,
			null,
			{
				zIndex       : zIndex,
				autoHide     : false,
				buttons      : oForm.objTemplate.prepareButtons(),
				closeByEsc   : false,
				overlay      : true,
				draggable    : true,
				bindOnResize : false,
				titleBar     : oForm.objTemplate.prepareTitleBar(),
				closeIcon    : { right : "12px", top : "10px"},
				events       : {
					onPopupClose : function() {
						BX.Tasks.lwPopup.__runAnyFormCallback(
							formIndex,
							'onPopupClose',
							[]
						);
					},
					onPopupFirstShow : function() {
						BX.Tasks.lwPopup.__runAnyFormCallback(
							formIndex,
							'onPopupFirstShow',
							[]
						);
					},
					onPopupShow : function() {
						BX.Tasks.lwPopup.__runAnyFormCallback(
							formIndex,
							'onPopupShow',
							[]
						);
					},
					onAfterPopupShow : function() {
						BX.Tasks.lwPopup.__runAnyFormCallback(
							formIndex,
							'onAfterPopupShow',
							[]
						);
					}
				},
				content : oForm.objTemplate.prepareContent(pData)
			}
		);

		this.__runAnyFormCallback(
			formIndex,
			'onAfterPopupCreated',
			[pData]
		);
	},


	__runCreateFormCallback : function (callbackName, args)
	{
		args = args || [];

		if (
			BX.Tasks.lwPopup.createForm.callbacks.hasOwnProperty(callbackName)
			&& (BX.Tasks.lwPopup.createForm.callbacks[callbackName] !== null)
		)
		{
			BX.Tasks.lwPopup.createForm.callbacks[callbackName].apply(
				BX.Tasks.lwPopup.createForm.objTemplate,
				args
			);
		}
	},


	showCreateForm : function(pTaskData)
	{
		pTaskData = typeof pTaskData !== 'undefined' ? pTaskData : {};

		BX.Tasks.lwPopup.__firstRun();

		if ( ! pTaskData.RESPONSIBLE_ID )
		{
			pTaskData.RESPONSIBLE_ID = BX.Tasks.lwPopup.loggedInUserId;
			pTaskData['META:RESPONSIBLE_FORMATTED_NAME'] = BX.Tasks.lwPopup.loggedInUserFormattedName;
		}
		else if (
			(pTaskData.RESPONSIBLE_ID == BX.Tasks.lwPopup.loggedInUserId)
			&& ( ! pTaskData.hasOwnProperty('META:RESPONSIBLE_FORMATTED_NAME') )
		)
		{
			pTaskData['META:RESPONSIBLE_FORMATTED_NAME'] = BX.Tasks.lwPopup.loggedInUserFormattedName;
		}

		var isPopupJustCreated = false;

		if (BX.Tasks.lwPopup.createForm.objPopup === null)
		{
			BX.Tasks.lwPopup.createForm.objPopup = new BX.PopupWindow(
				'bx-tasks-quick-popup-create-new-task',
				null,
				{
					zIndex       : -110,
					autoHide     : false,
					buttons      : BX.Tasks.lwPopup.createForm.objTemplate.prepareButtons(),
					closeByEsc   : false,
					overlay      : true,
					draggable    : true,
					bindOnResize : false,
					titleBar     : BX.Tasks.lwPopup.createForm.objTemplate.prepareTitleBar(),
					closeIcon    : { right : "12px", top : "10px"},
					events       : {
						onPopupClose : function() {
							BX.Tasks.lwPopup.__runCreateFormCallback(
								'onPopupClose',
								[]
							);
						},
						onPopupFirstShow : function() {
						},
						onPopupShow : function() {
						},
						onAfterPopupShow : function() {
							if (
								BX('bx-panel')
								&& (parseInt(BX.Tasks.lwPopup.createForm.objPopup.popupContainer.style.top) < 147)
							)
							{
								BX.Tasks.lwPopup.createForm.objPopup.popupContainer.style.top = 147 + "px";
							}

							BX.Tasks.lwPopup.__runCreateFormCallback(
								'onAfterPopupShow',
								[]
							);
						}
					},
					content : BX.Tasks.lwPopup.createForm.objTemplate.prepareContent(pTaskData)
				}
			);

			BX.Tasks.lwPopup.__runCreateFormCallback(
				'onAfterPopupCreated',
				[pTaskData]
			);

			isPopupJustCreated = true;
		}

		BX.Tasks.lwPopup.__runCreateFormCallback(
			'onBeforePopupShow',
			[ pTaskData, { isPopupJustCreated: isPopupJustCreated } ]
		);

		BX.Tasks.lwPopup.createForm.objPopup.show();
	},


	_createTask : function(params)
	{
		params = params || {};

		var onceMore = false;
		var callbackOnSuccess = null;
		var callbackOnFailure = null;
		var columnsIds = null;

		var taskData = {};
		if (params.hasOwnProperty('taskData'))
			taskData = params.taskData;

		if (params.hasOwnProperty('onceMore'))
			onceMore = params.onceMore;

		if (params.hasOwnProperty('columnsIds'))
			columnsIds = params.columnsIds;

		if (params.hasOwnProperty('callbackOnSuccess'))
			callbackOnSuccess = params.callbackOnSuccess;

		if (params.hasOwnProperty('callbackOnFailure'))
			callbackOnFailure = params.callbackOnFailure;

		if ( ! taskData.hasOwnProperty('TITLE') )
			taskData.TITLE = '';

		if ( ! taskData.hasOwnProperty('RESPONSIBLE_ID') )
			taskData.RESPONSIBLE_ID = this.loggedInUserId;

		BX.CJSTask.createItem(taskData, {
			columnsIds : columnsIds,
			callback: (function(onceMore, callbackOnSuccess) {
				return function(oTask, precachedData, legacyDataFormat, legacyHtmlTaskItem) {
					var newDataPack = {
						oTask                       : oTask,
						taskData                    : precachedData.taskData,
						allowedTaskActions          : precachedData.allowedTaskActions,
						allowedTaskActionsAsStrings : precachedData.allowedTaskActionsAsStrings,
						params                      : { onceMore : onceMore }
					};

					if (callbackOnSuccess)
						callbackOnSuccess(newDataPack);

					if (BX.Tasks.lwPopup.onTaskAdded && (onceMore === false))
						BX.Tasks.lwPopup.onTaskAdded(legacyDataFormat, null, null, newDataPack, legacyHtmlTaskItem);
					else if (BX.Tasks.lwPopup.onTaskAddedMultiple && (onceMore === true))
						BX.Tasks.lwPopup.onTaskAddedMultiple(legacyDataFormat, null, null, newDataPack, legacyHtmlTaskItem);
				};
			})(onceMore, callbackOnSuccess),
			callbackOnFailure : (function(callbackOnFailure){
				return function(data)
				{
					if (callbackOnFailure)
						callbackOnFailure(data);
				}
			})(callbackOnFailure)
		});
	},


	__initSelectors : function(arParams)
	{
		var cnt = arParams.length;

		var bUserSelectorPresents = false;

		BX.Tasks.lwPopup.__firstRun();

		for (var i=0; i<cnt; i++)
		{
			if (arParams[i]['requestedObject'] === 'intranet.user.selector.new')
			{
				bUserSelectorPresents = true;
				break;
			}
		}

		var userSelectorAreaId = null;
		if (bUserSelectorPresents)
		{
			// use new function number for unique id
			var newFuncIndex = BX.Tasks.lwPopup.functionsCount++;
			BX.Tasks.lwPopup.functions['f' + newFuncIndex] = function(){};

			userSelectorAreaId =  BX.Tasks.lwPopup.garbageAreaId
				+ '__userSelectors_' + newFuncIndex + '_loadedHtml';
			BX(BX.Tasks.lwPopup.garbageAreaId).appendChild(
				BX.create(
					'DIV',
					{
						props: { id: userSelectorAreaId }
					}
				)
			);
		}

		var ajaxData  = {
			sessid        : BX.message('bitrix_sessid'),
			requestsCount : cnt
		};
		var sData     = [];
		var selectors = [];

		for (var i=0; i<cnt; i++)
		{
			if (arParams[i]['requestedObject'] === 'intranet.user.selector.new')
				sData[i] = this.__prepareUserSelectorsData(arParams[i]);
			else if (arParams[i]['requestedObject'] === 'socialnetwork.group.selector')
				sData[i] = this.__prepareGroupsSelectorsData(arParams[i]);
			else if (arParams[i]['requestedObject'] === 'LHEditor')
				sData[i] = this.__prepareLheData(arParams[i]);
			else if (arParams[i]['requestedObject'] === 'system.field.edit::CRM')
			{
				sData[i] = this.__prepareUserFieldData(arParams[i]);

				for (var key in sData[i]['postData'])
					ajaxData[key] = sData[i]['postData'][key];
			}
			else if (arParams[i]['requestedObject'] === 'system.field.edit::WEBDAV')
			{
				sData[i] = this.__prepareUserFieldDataWebdav(arParams[i]);

				for (var key in sData[i]['postData'])
					ajaxData[key] = sData[i]['postData'][key];
			}

			ajaxData['data_' + i] = sData[i]['ajaxParams'];
			selectors[i]          = sData[i]['object'];
		}

		BX.ajax({
			method      : 'POST',
			dataType    : 'html',
			url         : '/bitrix/components/bitrix/tasks.iframe.popup/ajax_loader.php?SITE_ID=' + BX.message('SITE_ID'),
			data        :  ajaxData,
			processData :  true,
			autoAuth    :  true,
			onsuccess   : (function(selectors, userSelectorAreaId, bUserSelectorPresents){
				return function(reply)
				{
					if (bUserSelectorPresents)
						BX(userSelectorAreaId).innerHTML = reply;

					var cnt = selectors.length;

					for (var i=0; i<cnt; i++)
					{
						if (selectors[i].hasOwnProperty('onLoadedViaAjax'))
							selectors[i].onLoadedViaAjax();
					}
				};
			})(selectors, userSelectorAreaId, bUserSelectorPresents)
		});

		return (selectors);
	},


	__prepareUserFieldData : function(params)
	{
		var newFuncIndex    =  BX.Tasks.lwPopup.functionsCount++;
		var nameContainerId = 'OBJ_TASKS_CONTAINER_NAME_ID_' + newFuncIndex;
		var dataContainerId = 'OBJ_TASKS_CONTAINER_DATA_ID_' + newFuncIndex;
		var ajaxParams      = {
			requestedObject : 'system.field.edit::CRM',
			userFieldName   :  params['userFieldName'],
			taskId          :  params['taskId'],
			nameContainerId :  nameContainerId,
			dataContainerId :  dataContainerId,
			values          :  params['value']
		};

		var newArr = [];
		newArr.push.apply(newArr, params['value']);

		BX.Tasks.lwPopup.functions['f' + newFuncIndex] = {
			allParams       : params,
			ajaxParams      : ajaxParams,
			ready           : false,
			available       : null,
			timeoutId       : null,
			valuesBuffer    : newArr,
			nameContainerId : nameContainerId,
			dataContainerId : dataContainerId,
			onLoadedViaAjax : function()
			{
				if (BX(this.nameContainerId))
					this.available = true;
				else
					this.available = false;

				if ( ! this.available )
					return (false);

				var fieldLabel = BX(this.nameContainerId).innerHTML;
				BX.remove(BX(this.nameContainerId));
				this.allParams.callbackOnRedraw(fieldLabel, this.dataContainerId);
				this.ready = true;
			},
			getValue : function()
			{
				var itemsIds = [];

				if (this.ready === true)
				{
					var arItems = document.getElementsByName('UF_CRM_TASK[]');

					if (arItems)
					{
						var cnt = arItems.length;

						for (var i=0; i<cnt; i++)
							itemsIds.push(arItems[i].value);
					}
				}
				else
				{
					itemsIds = this.valuesBuffer;
				}

				return (itemsIds);
			},
			setValue : function(values)
			{
				// Skip data set, if it's the same
				if (this.valuesBuffer.length === values.length)
				{
					//slice so we do not effect the original
					//sort makes sure they are in order
					//join makes it a string so we can do a string compare
					var cA = this.valuesBuffer.slice().sort().join(";");
					var cB = values.slice().sort().join(";");

					if (cA === cB)
						return;		// arrays are equal
				}

				this.valuesBuffer = [];
				this.valuesBuffer.push.apply(this.valuesBuffer, values);
				this.__delayedSetContent(30);
			},
			__delayedSetContent : function(delay)
			{
				if (this.available === false)
					return (false);

				if (this.ready === false)
				{
					if (this.timeoutId !== null)
						window.clearTimeout(this.timeoutId);

					this.timeoutId = window.setTimeout(
						function()
						{
							var newDelay = delay + 100;

							if (delay < 30)
								newDelay = 30;
							else if (delay > 500)
								newDelay = 500;

							BX.Tasks.lwPopup.functions['f' + newFuncIndex].__delayedSetContent(newDelay);
						},
						delay
					);
				}
				else
				{
					if (BX(this.nameContainerId))
						BX.remove(BX(this.nameContainerId));

					if (BX(this.dataContainerId))
						BX.remove(BX(this.dataContainerId));

					var urlParams = '';
					var cnt = this.valuesBuffer.length;
					for (var i=0; i<cnt; i++)
						urlParams = urlParams + '&UF_CRM_TASK[]=' + this.valuesBuffer[i];

					// Reload user field from server
					var ajaxData  = {
						sessid        : BX.message('bitrix_sessid'),
						requestsCount : 1,
						data_0        : this.ajaxParams
					};
					BX.ajax({
						method      : 'POST',
						dataType    : 'html',
						url         : '/bitrix/components/bitrix/tasks.iframe.popup/ajax_loader.php?SITE_ID=' + BX.message('SITE_ID') + urlParams,
						data        :  ajaxData,
						processData :  true,
						autoAuth    :  true,
						//async       :  false,
						onsuccess   : (function(selfObj){
							return function(reply)
							{
								BX(BX.Tasks.lwPopup.garbageAreaId).appendChild(
									BX.create(
										'div',
										{
											html : reply
										}
									)
								);

								selfObj.ready = true;

								var fieldLabel = BX(selfObj.nameContainerId).innerHTML;
								BX.remove(BX(selfObj.nameContainerId));
								selfObj.allParams.callbackOnRedraw(fieldLabel, selfObj.dataContainerId);
							};
						})(this)
					});
				}
			}
		};

		var rc = {
			object     : BX.Tasks.lwPopup.functions['f' + newFuncIndex],
			ajaxParams : ajaxParams,
			postData   : {
				UF_CRM_TASK : params['value']
			}
		};

		return (rc);
	},


	__prepareUserFieldDataWebdav : function(params)
	{
		var newFuncIndex    =  BX.Tasks.lwPopup.functionsCount++;
		var nameContainerId = 'OBJ_TASKS_CONTAINER_NAME_ID_' + newFuncIndex;
		var dataContainerId = 'OBJ_TASKS_CONTAINER_DATA_ID_' + newFuncIndex;
		var ajaxParams      = {
			requestedObject : 'system.field.edit::WEBDAV',
			userFieldName   :  params['userFieldName'],
			taskId          :  params['taskId'],
			nameContainerId :  nameContainerId,
			dataContainerId :  dataContainerId,
			values          :  params['value']
		};

		var newArr = [];
		newArr.push.apply(newArr, params['value']);

		BX.Tasks.lwPopup.functions['f' + newFuncIndex] = {
			allParams       : params,
			ajaxParams      : ajaxParams,
			ready           : false,
			available       : null,
			timeoutId       : null,
			valuesBuffer    : newArr,
			nameContainerId : nameContainerId,
			dataContainerId : dataContainerId,
			onLoadedViaAjax : function()
			{
				if (BX(this.nameContainerId))
					this.available = true;
				else
					this.available = false;

				if ( ! this.available )
					return (false);

				var fieldLabel = BX(this.nameContainerId).innerHTML;
				BX.remove(BX(this.nameContainerId));
				this.allParams.callbackOnRedraw(fieldLabel, this.dataContainerId);
				this.ready = true;
			},
			getValue : function()
			{
				var itemsIds = [];

				if (this.ready === true)
				{
					var arItems = document.getElementsByName('UF_TASK_WEBDAV_FILES[]');

					if (arItems)
					{
						var cnt = arItems.length;

						for (var i=0; i<cnt; i++)
							itemsIds.push(arItems[i].value);
					}
				}
				else
				{
					itemsIds = this.valuesBuffer;
				}

				return (itemsIds);
			},
			setValue : function(values)
			{
				// Skip data set, if it's the same
				if (this.valuesBuffer.length === values.length)
				{
					//slice so we do not effect the original
					//sort makes sure they are in order
					//join makes it a string so we can do a string compare
					var cA = this.valuesBuffer.slice().sort().join(";");
					var cB = values.slice().sort().join(";");

					if (cA === cB)
						return;		// arrays are equal
				}

				this.valuesBuffer = [];
				this.valuesBuffer.push.apply(this.valuesBuffer, values);
				this.__delayedSetContent(30);
			},
			__delayedSetContent : function(delay)
			{
				if (this.available === false)
					return (false);

				if (this.ready === false)
				{
					if (this.timeoutId !== null)
						window.clearTimeout(this.timeoutId);

					this.timeoutId = window.setTimeout(
						function()
						{
							var newDelay = delay + 100;

							if (delay < 30)
								newDelay = 30;
							else if (delay > 500)
								newDelay = 500;

							BX.Tasks.lwPopup.functions['f' + newFuncIndex].__delayedSetContent(newDelay);
						},
						delay
					);
				}
				else
				{
					if (BX(this.nameContainerId))
						BX.remove(BX(this.nameContainerId));

					if (BX(this.dataContainerId))
						BX.remove(BX(this.dataContainerId));

					var urlParams = '';
					var cnt = this.valuesBuffer.length;
					for (var i=0; i<cnt; i++)
						urlParams = urlParams + '&UF_TASK_WEBDAV_FILES[]=' + this.valuesBuffer[i];

					// Reload user field from server
					var ajaxData  = {
						sessid        : BX.message('bitrix_sessid'),
						requestsCount : 1,
						data_0        : this.ajaxParams
					};
					BX.ajax({
						method      : 'POST',
						dataType    : 'html',
						url         : '/bitrix/components/bitrix/tasks.iframe.popup/ajax_loader.php?SITE_ID=' + BX.message('SITE_ID') + urlParams,
						data        :  ajaxData,
						processData :  true,
						autoAuth    :  true,
						//async       :  false,
						onsuccess   : (function(selfObj){
							return function(reply)
							{
								BX(BX.Tasks.lwPopup.garbageAreaId).appendChild(
									BX.create(
										'div',
										{
											html : reply
										}
									)
								);

								selfObj.ready = true;

								var fieldLabel = BX(selfObj.nameContainerId).innerHTML;
								BX.remove(BX(selfObj.nameContainerId));
								selfObj.allParams.callbackOnRedraw(fieldLabel, selfObj.dataContainerId);
							};
						})(this)
					});
				}
			}
		};

		var rc = {
			object     : BX.Tasks.lwPopup.functions['f' + newFuncIndex],
			ajaxParams : ajaxParams,
			postData   : {
				UF_TASK_WEBDAV_FILES : params['value']
			}
		};

		return (rc);
	},


	__prepareLheData : function(params)
	{
		var newFuncIndex =  BX.Tasks.lwPopup.functionsCount++;
		var jsObjectName = 'OBJ_TASKS_LHEDITOR_NS_' + newFuncIndex;
		var elementId    = 'OBJ_TASKS_ELEMENT_ID_NS_' + newFuncIndex;
		var inputId      = 'OBJ_TASKS_INPUT_ID_NS_' + newFuncIndex;

		BX.Tasks.lwPopup.functions['f' + newFuncIndex] = {
			allParams       : params,
			jsObjectName    : jsObjectName,
			elementId       : elementId,
			editor          : null,
			inputId         : inputId,
			content         : '',
			getContent      : function()
			{
				if (this.editor !== null)
				{
					this.editor.SaveContent();
					return (this.editor.GetContent());
				}
				else
				{
					if (BX(this.inputId))
						return (BX(this.inputId).value);
					else
						return ('');
				}
			},
			setContent : function(content)
			{
				this['content'] = content;
				this.__delayedSetContent(30);
			},
			__delayedSetContent : function(delay)
			{
				if (this.editor === null)
				{
					window.setTimeout(
						function()
						{
							var newDelay = delay + 100;

							if (delay < 30)
								newDelay = 30;
							else if (delay > 500)
								newDelay = 500;

							BX.Tasks.lwPopup.functions['f' + newFuncIndex].__delayedSetContent(newDelay);
						},
						delay
					);
				}
				else
				{
					if(BX.type.isString(this['content']))
					{
						this.editor.SetContent(this['content']);
						if(this['content'].length == 0)
							this.editor.ResizeSceleton(false, 200);

						if(BX.browser.IsChrome() || BX.browser.IsIE11() || BX.browser.IsIE())
						{
							var input = BX('lwPopup-task-title');

							if(BX.type.isElementNode(input))
							{
								this.editor.Focus(false);
								input.focus();
							}
						}
					}
				}
			}
		};

		BX.addCustomEvent(
			window,
			'OnEditorInitedAfter',
			(function(selfObj, attachTo){
				var inited = false;
				return function(editor){

					if ( (!inited) && (editor.id == selfObj.elementId) )
					{
						selfObj.editor = editor;

						var attachToNode = BX(attachTo);

						attachToNode.innerHTML = '';
						attachToNode.appendChild(editor.dom.cont);
						inited = true;

						setTimeout(function()
						{
							editor.CheckAndReInit();
							editor.SetContent(selfObj['content']);

							if(BX.browser.IsChrome() || BX.browser.IsIE11() || BX.browser.IsIE())
							{
								var input = BX('lwPopup-task-title');

								if(BX.type.isElementNode(input))
								{
									editor.Focus(false);
									input.focus();
								}
							}
						}, 500);

						BX.Tasks.lwPopup.__runCreateFormCallback('onAfterEditorInited', []);
					}
				}
			})(BX.Tasks.lwPopup.functions['f' + newFuncIndex], params.attachTo)
		);

		/*
		BX.addCustomEvent(
			window,
			'LHE_OnInit',
			(function(selfObj, attachTo){
				var inited = false;

				return function(data){
					if ( (!inited) && (data.id == selfObj.elementId) )
					{
						selfObj.editor = data;

						BX(attachTo).innerHTML = '';
						BX(attachTo).appendChild(data.pFrame.parentNode.removeChild(data.pFrame));
						inited = true;
						data.ReInit(selfObj['content']);

						BX.Tasks.lwPopup.__runCreateFormCallback('onAfterEditorInited', []);
					}
				}
			})(BX.Tasks.lwPopup.functions['f' + newFuncIndex], params.attachTo)
		);
		*/

		var rc = {
			object     : BX.Tasks.lwPopup.functions['f' + newFuncIndex],
			ajaxParams : {
				requestedObject : 'LHEditor',
				jsObjectName    :  jsObjectName,
				elementId       :  elementId,
				inputId         :  inputId
			}
		};

		return (rc);
	},


	__prepareGroupsSelectorsData : function(params)
	{
		var newFuncIndex =  BX.Tasks.lwPopup.functionsCount++;
		var jsObjectName = 'OBJ_TASKS_GROUP_SELECTOR_NS_' + newFuncIndex;

		BX.Tasks.lwPopup.functions['f' + newFuncIndex] = {
			allParams       : params,
			jsObjectName    : jsObjectName,
			bindElement     : params.bindElement,
			onLoadedViaAjax : function()
			{
				BX.bind(
					BX(this.bindElement),
					'click',
					(function(obj){
						return function(e) {
							if (!e)
								e = window.event;

							var oGroupObject = window[obj.jsObjectName];

							if (oGroupObject)
							{
								oGroupObject.popupWindow.params.zIndex = 1400;
								oGroupObject.show();
							}

							BX.PreventDefault(e);
						};
					})(this)
				);

				if (this.allParams.onLoadedViaAjax)
					this.allParams.onLoadedViaAjax(this.jsObjectName);
			},
			setSelected : function(groupData)
			{
				// If object is not loaded yet => we shouldn't set
				// group id, because it will be setted on PHP side
				// during initialization of group.selector
				if ( ! window[this.jsObjectName] )
					return;

				if (groupData.id == 0)
				{
					var currentItem = null;

					if (window[this.jsObjectName].selected[0])
					{
						currentItem = window[this.jsObjectName].selected[0];
						window[this.jsObjectName].deselect(currentItem.id);
					}
				}
				else
					window[this.jsObjectName].select(groupData);
			},
			deselect : function (groupId)
			{
				window[this.jsObjectName].deselect(groupId);
			}
		};

		// groups selector needs function in window
		var onSelectFunctionName = 'FUNC_TASKS_GROUP_SELECTOR_NS_' + newFuncIndex;
		window[onSelectFunctionName] = (function(callbackOnSelect){
			return function(arGroups){
				if (callbackOnSelect)
					callbackOnSelect(arGroups);
			}
		})(params.callbackOnSelect);

		var rc = {
			object     : BX.Tasks.lwPopup.functions['f' + newFuncIndex],
			ajaxParams : {
				requestedObject  : 'socialnetwork.group.selector',
				jsObjectName     :  jsObjectName,
				bindElement      :  params.bindElement,
				onSelectFuncName :  onSelectFunctionName
			}
		};

		return (rc);
	},


	__prepareUserSelectorsData : function(params)
	{
		var userInputId = null;
		var bindClickTo = null;
		var GROUP_ID_FOR_SITE = 0;

		if (params.hasOwnProperty('userInputId'))
			userInputId = params.userInputId;

		if (params.hasOwnProperty('bindClickTo'))
			bindClickTo = params.bindClickTo;
		else
			bindClickTo = userInputId;

		var callbackOnSelect   =  params.callbackOnSelect;
		var selectedUsersIds   =  params.selectedUsersIds;
		var anchorId           =  params.anchorId;
		var multiple           =  params['multiple'];
		var newFuncIndex       =  BX.Tasks.lwPopup.functionsCount++;
		var nsObjectName       = 'OBJ_TASKS_USER_SELECTOR_NS_' + newFuncIndex;

		if (params.GROUP_ID_FOR_SITE)
			GROUP_ID_FOR_SITE = params.GROUP_ID_FOR_SITE;

		// Register named function for callback (onUserSelect)
		// We need name for functions, because we transmit this name to PHP,
		// which than generates js-code who calls our callback.
		BX.Tasks.lwPopup.functions['f' + newFuncIndex] = {
			allParams          : params,
			multiple           : multiple,
			popupId            : nsObjectName + '_popupId',
			bindClickTo        : bindClickTo,
			userInputId        : userInputId,
			anchorId           : anchorId,
			userPopupWindow    : null,
			nsObjectName       : nsObjectName,
			onLoadedViaAjax    : function()
			{
				var obj = this;

				if (this.userInputId)
				{
					BX.bind(
						BX(this.userInputId),
						'focus',
						function(e)
						{
							obj.showUserSelector(e);
						}
					);

					if (BX(this.bindClickTo))
					{
						BX.bind(
							BX(this.bindClickTo),
							'click',
							function(e) {
								if (!e)
									e = window.event;

								BX(obj.userInputId).focus();
								BX.PreventDefault(e);
							}
						);
					}
				}

				if (this.allParams.onLoadedViaAjax)
					this.allParams.onLoadedViaAjax();

				if (this.allParams.onReady)
				{
					(function(objName, callbackOnReady){
						var wait = function(delay, timeout, callbackOnReady)
						{
							if (typeof window[objName] === 'undefined')
							{
								if (timeout > 0)
								{
									window.setTimeout(
										function() {
											wait(delay, timeout - delay, callbackOnReady);
										},
										delay
									);
								}
							}
							else
							{
								callbackOnReady(window[objName]);
							}
						}

						wait(100, 15000, callbackOnReady);	// every 100ms, not more than 15000ms
					})('O_' + this.nsObjectName, this.allParams.onReady)
				}
			},
			onPopupClose : function(selfObj)
			{
				var O_USER_DATA = window['O_' + selfObj.nsObjectName];
				var emp = O_USER_DATA.arSelected.pop();

				if (emp)
				{
					O_USER_DATA.arSelected.push(emp);
					O_USER_DATA.searchInput.value = emp.name;
				}
			},
			setSelectedUsers : function(selectedUsers, timeCalled)
			{
				var timeCalled = timeCalled || 1;

				if (timeCalled > 100)
					return;

				if ( ! window['O_' + this.nsObjectName] )
				{
					window.setTimeout(
						(function(selfObj, timeCalled, selectedUsers){
							return function()
							{
								selfObj.setSelectedUsers(selectedUsers, timeCalled + 1);
							}
						})(this, timeCalled, selectedUsers),
						50
					);

					return;
				}

				var O_USER_DATA = window['O_' + this.nsObjectName];

				O_USER_DATA.setSelected(selectedUsers);
			},
			showUserSelector : function(e)
			{
				if (!e)
					e = window.event;

				if (
					(this.userPopupWindow !== null)
					&& (this.userPopupWindow.popupContainer.style.display == "block")
				)
				{
					return;		// Popup already showed
				}

				var anchor  = BX(this.anchorId);
				var buttons = null;
				var obj     = this;

				if (this['multiple'] === 'Y')
				{
					buttons = [
						new BX.PopupWindowButton({
							text      :  this.allParams.btnSelectText,
							className : 'popup-window-button-accept',
							events    : {
								click : function(e)
								{
									obj.btnSelectClick(e);
									obj.userPopupWindow.close();
								}
							 }
						}),

						new BX.PopupWindowButtonLink({
							text      :  this.allParams.btnCancelText,
							className : 'popup-window-button-link-cancel',
							events    : {
								click : function(e)
								{
									if (!e)
										e = window.event;

									obj.userPopupWindow.close();

									if (e)
										BX.PreventDefault(e);
								}
							}
						})
					];
				}

				this.userPopupWindow = BX.PopupWindowManager.create(
					this.popupId,
					anchor,
					{
						offsetTop  : 1,
						autoHide   : true,
						closeByEsc : true,
						content    : BX(this.nsObjectName + "_selector_content"),
						buttons    : buttons
					}
				);

				if (this['multiple'] === 'N')
				{
					BX.addCustomEvent(
						this.userPopupWindow,
						"onPopupClose",
						function()
						{
							obj.onPopupClose(obj);
						}
					);
				}
				else
				{
					BX.addCustomEvent(
						this.userPopupWindow,
						'onAfterPopupShow',
						function(e) { setTimeout(
							function() { window['O_' + obj.nsObjectName].searchInput.focus(); }, 100
						);}
					);
				}

				this.userPopupWindow.show();
				BX(this.userPopupWindow.uniquePopupId).style.zIndex = 1400;
				BX.focus(anchor);
				BX.PreventDefault(e);
			}
		}

		if (multiple === 'N')
		{
			BX.Tasks.lwPopup.functions['f' + newFuncIndex].onUserSelect =
				(function(callbackOnSelect){
					var obj = BX.Tasks.lwPopup.functions['f' + newFuncIndex];

					return function(arUser){
						if (obj.userPopupWindow)
							obj.userPopupWindow.close();

						callbackOnSelect(arUser);
					}
				})(callbackOnSelect);

			BX.Tasks.lwPopup.functions['f' + newFuncIndex].btnSelectClick = function(){};
		}
		else
		{
			BX.Tasks.lwPopup.functions['f' + newFuncIndex].onUserSelect = function(){};

			BX.Tasks.lwPopup.functions['f' + newFuncIndex].btnSelectClick =
				(function(callbackOnSelect){
					return function(e){
						if (!e)
							e = window.event;

						var arAllUsers = window['O_' + this.nsObjectName].arSelected;
						var arAllUsersCount = arAllUsers.length;
						var arUsers = [];

						for (i = 0; i < arAllUsersCount; i++)
						{
							if (arAllUsers[i])
								arUsers.push(arAllUsers[i]);
						}

						callbackOnSelect(arUsers);
					}
				})(callbackOnSelect);
		}

		var ajaxParams = {
			requestedObject      : 'intranet.user.selector.new',
			multiple             :  multiple,
			namespace            :  nsObjectName,
			inputId              :  userInputId,
			onSelectFunctionName : 'BX.Tasks.lwPopup.functions.f' + newFuncIndex + '.onUserSelect',
			GROUP_ID_FOR_SITE    :  GROUP_ID_FOR_SITE,
			selectedUsersIds     :  selectedUsersIds
		};

		if (params.callbackOnChange)
		{
			BX.Tasks.lwPopup.functions['f' + newFuncIndex].onUsersChange = params.callbackOnChange;

			ajaxParams.onChangeFunctionName = 'BX.Tasks.lwPopup.functions.f' + newFuncIndex + '.onUsersChange'
		}

		var rc = {
			object     : BX.Tasks.lwPopup.functions['f' + newFuncIndex],
			ajaxParams : ajaxParams
		};

		return (rc);
	},

	_getDefaultTimeForInput: function(input)
	{
		if(BX.type.isDomNode(input))
		{
			var defaultTime = BX.data(input, 'default-time');
			if(typeof defaultTime != 'undefined')
			{
				var dtParts = defaultTime.toString().split(':');
				defaultTime = {
					h: +dtParts[0],
					m: +dtParts[1],
					s: +dtParts[2]
				};
			}
			else
			{
				defaultTime = {
					h: 19,
					m: 0,
					s: 0
				};
			}
		}

		return defaultTime;
	},

	_showCalendar : function(node, field, params)
	{
		if (typeof(params) === 'undefined')
			var params = {};

		var bTime = true;
		if (params.hasOwnProperty('bTime'))
			bTime = params.bTime;

		var bHideTime = false;
		if (params.hasOwnProperty('bHideTime'))
			bHideTime = params.bHideTime;

		var callback_after = null;
		if (params.hasOwnProperty('callback_after'))
			callback_after = params.callback_after;

		/*
		var curDate = new Date();

		if (!!field.value)
			var selectedDate = field.value;
		else
		{
			var defaultTime = this._getDefaultTimeForInput(field);
			var selectedDate = BX.date.convertToUTC(new Date(
				curDate.getFullYear(),
				curDate.getMonth(),
				curDate.getDate(),
				defaultTime.h,
				defaultTime.m,
				defaultTime.s
			)); // strip time zone
		}
		*/

		BX.calendar({
			node        : node,
			field       : field,
			bTime       : bTime,
			value       : BX.CJSTask.ui.getInputDateTimeValue(field),
			bHideTime   : bHideTime,
			callback_after : callback_after
		});
	},


	__firstRun : function()
	{
		if (BX.Tasks.lwPopup.firstRunDone)
			return;		// do nothing, if already run

		BX.Tasks.lwPopup.firstRunDone = true;

		var body = document.getElementsByTagName('body')[0];

		// Init garbage area
		if ( ! BX(BX.Tasks.lwPopup.garbageAreaId) )
		{
			body.appendChild(
				BX.create(
					'DIV',
					{
						props: { id: BX.Tasks.lwPopup.garbageAreaId }
					}
				)
			);
		}
	}
}

})();
