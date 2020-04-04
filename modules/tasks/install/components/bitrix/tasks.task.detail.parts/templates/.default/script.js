var tasksDetailPartsNS = {
	tasksListAjaxUrl : '/bitrix/components/bitrix/tasks.list/ajax.php?SITE_ID=' + BX.message('SITE_ID'),
	detailsAjaxUrl   : '/bitrix/components/bitrix/tasks.task.detail/ajax.php?SITE_ID=' + BX.message('SITE_ID'),
	responsiblePopup : null,
	accomplicesPopup : null,
	auditorsPopup    : null,
	arAccomplices    : [],
	arAuditors       : [],
	timerCallback    : null,
	insertAfterItemId : null,
	checklistCounter : 0,
	loadedSelectors  : {
		DELEGATE    : null,
		RESPONSIBLE : null,
		ACCOMPLICES : null,
		AUDITORS    : null
	},
	getSerialId : (function(){
		var id = 0;
		return function() {
			if(arguments[0]===0)
				id=0;
			return id++;
		}
	})(),
	toggleFavorite: function(taskId, way)
	{
		if(typeof tasksDetailsNS != 'undefined')
		{
			tasksDetailsNS.toggleFavorite(taskId, way);
		}
	},
	initChecklist: function(inCreateMode)
	{
		if(inCreateMode)
			return;

		// show\hide completed
		if(typeof tasksDetailPartsNS.checkListCompletedShown == 'undefined')
		{
			tasksDetailPartsNS.checkListCompletedShown = false;
		}
		tasksDetailPartsNS.toggleCompletedCheckListItems(tasksDetailPartsNS.checkListCompletedShown);

		try
		{
			BX.bind(BX('task-detail-checklist-show-completed'), 'click', tasksDetailPartsNS.showCompletedCheckListItems);
		}
		catch(e)
		{}

		try
		{
			BX.bind(BX('task-detail-checklist-hide-completed'), 'click', tasksDetailPartsNS.hideCompletedCheckListItems);
		}
		catch(e)
		{}
	},
	checklistToggle : function(isTaskCreateMode, taskId, itemId, checkboxDomNode)
	{
		var itemDomNode = BX('task-detail-checklist-item-' + itemId);

		// Prevent duplicate actions
		if (BX.hasClass(itemDomNode, 'task-detail-checklist-item-processing'))
			return;

		BX.addClass(itemDomNode, 'task-detail-checklist-item-processing');

		var oTask = null;

		if ( ! isTaskCreateMode )
		 	oTask = new BX.CJSTask.Item(taskId);

		if (checkboxDomNode.checked)
		{
			if (isTaskCreateMode)
			{
				BX.addClass(itemDomNode, 'task-detail-checklist-item-complete');
				BX.removeClass(itemDomNode, 'task-detail-checklist-item-processing');

				BX('task-detail-checklist-item-' + itemId + '-hiddenIsChecked').value = 'Y';
			}
			else
			{
				oTask.checklistComplete(itemId, {
					callbackOnSuccess: (function(itemDomNode){
						return function(data){
							BX.addClass(itemDomNode, 'task-detail-checklist-item-complete');
							BX.removeClass(itemDomNode, 'task-detail-checklist-item-processing');
						}
					})(itemDomNode),
					callbackOnFailure: (function(itemDomNode, checkboxDomNode){
						return function(data){
							BX.removeClass(itemDomNode, 'task-detail-checklist-item-processing');
							checkboxDomNode.checked = false;
							tasksDetailPartsNS.recalcChecklist();
						}
					})(itemDomNode, checkboxDomNode)
				});
			}
		}
		else
		{
			if (isTaskCreateMode)
			{
				BX.removeClass(itemDomNode, 'task-detail-checklist-item-complete');
				BX.removeClass(itemDomNode, 'task-detail-checklist-item-processing');

				BX('task-detail-checklist-item-' + itemId + '-hiddenIsChecked').value = 'N';
			}
			else
			{
				oTask.checklistRenew(itemId, {
					callbackOnSuccess: (function(itemDomNode){
						return function(data){
							BX.removeClass(itemDomNode, 'task-detail-checklist-item-complete');
							BX.removeClass(itemDomNode, 'task-detail-checklist-item-processing');
						}
					})(itemDomNode),
					callbackOnFailure: (function(itemDomNode, checkboxDomNode){
						return function(data){
							BX.removeClass(itemDomNode, 'task-detail-checklist-item-processing');
							checkboxDomNode.checked = true;
							tasksDetailPartsNS.recalcChecklist();
						}
					})(itemDomNode, checkboxDomNode)
				});
			}
		}

		tasksDetailPartsNS.recalcChecklist();
	},
	checklistAddItem : function(isTaskCreateMode, taskId)
	{
		// Prevent duplicate button press
		if (BX('task-detail-checklist-add-text').disabled)
			return;

		var title = BX('task-detail-checklist-add-text').value;

		if (title.length == 0)
			return;

		//title = BX.util.strip_tags(title);

		BX('task-detail-checklist-add-text').disabled = true;

		BX('task-detail-checklist-add-link').style.display = 'none';

		if (isTaskCreateMode)
		{
			var itemId = 'xxx_' + (Date.now()) + '_' + tasksDetailPartsNS.getSerialId();
			BX('task-detail-checklist-items').appendChild(
				this.renderChecklistItem(
					isTaskCreateMode,
					taskId,
					itemId,
					title,
					false
				)
			);

			this.initChecklistItem(
				isTaskCreateMode,
				taskId,
				itemId,
				title,
				false
			);

			// Clear input field
			BX('task-detail-checklist-add-text').value = '';

			BX('task-detail-checklist-add-text').disabled = false;
			BX('task-detail-checklist-add-text').focus();

			BX('task-detail-checklist-add-link').style.display = '';

			tasksDetailPartsNS.recalcChecklist();
		}
		else
		{
			var oTask = new BX.CJSTask.Item(taskId);

			oTask.checklistAddItem(
				title,
				{
					callbackOnSuccess: (function(selfObj, isTaskCreateMode, taskId){
						return function(reply){
							var itemId = reply.rawReply.data[0]['justCreatedId'];
							var itemTitle = reply.rawReply.data[0]['requestedData']['TITLE'];
							var isComplete = (reply.rawReply.data[0]['requestedData']['IS_COMPLETE'] === 'Y');

							BX('task-detail-checklist-items').appendChild(
								selfObj.renderChecklistItem(isTaskCreateMode, taskId, itemId, itemTitle, isComplete)
							);

							selfObj.initChecklistItem(isTaskCreateMode, taskId, itemId, itemTitle, isComplete);

							// Clear input field
							BX('task-detail-checklist-add-text').value = '';

							BX('task-detail-checklist-add-text').disabled = false;
							BX('task-detail-checklist-add-text').focus();

							BX('task-detail-checklist-add-link').style.display = '';

							tasksDetailPartsNS.recalcChecklist();
						}
					})(this, isTaskCreateMode, taskId),
					callbackOnFailure: function(data){
						BX('task-detail-checklist-add-text').disabled = false;

						BX('task-detail-checklist-add-link').style.display = '';
					}
				}
			);
		}
	},
	checklistRemoveItem : function(isTaskCreateMode, taskId, itemId)
	{
		var itemDomNode = BX('task-detail-checklist-item-' + itemId);

		// Prevent duplicate actions
		if (BX.hasClass(itemDomNode, 'task-detail-checklist-item-processing'))
			return;

		BX.addClass(itemDomNode, 'task-detail-checklist-item-processing');

		if (isTaskCreateMode)
		{
			BX.remove(itemDomNode);
			tasksDetailPartsNS.recalcChecklist();
		}
		else
		{
			var oTask = new BX.CJSTask.Item(taskId);

			oTask.checklistDelete(itemId, {
				callbackOnSuccess: (function(itemDomNode){
					return function(data){
						BX.remove(itemDomNode);
						tasksDetailPartsNS.recalcChecklist();
					}
				})(itemDomNode),
				callbackOnFailure: (function(itemDomNode){
					return function(data){
						BX.removeClass(itemDomNode, 'task-detail-checklist-item-processing');
					}
				})(itemDomNode)
			});
		}
	},
	checklistRename : function(isTaskCreateMode, taskId, itemId, newTitle)
	{
		if (newTitle.length == 0)
			return;

		//newTitle = BX.util.strip_tags(newTitle);

		var itemDomNode = BX('task-detail-checklist-item-' + itemId);

		// Prevent duplicate actions
		if (BX.hasClass(itemDomNode, 'task-detail-checklist-item-processing-saving'))
			return;

		BX.addClass(itemDomNode, 'task-detail-checklist-item-processing-saving');

		if (isTaskCreateMode)
		{
			BX.removeClass(itemDomNode, 'task-detail-checklist-item-processing');
			BX.removeClass(itemDomNode, 'task-detail-checklist-item-processing-saving');
			BX.removeClass(itemDomNode, 'task-detail-checklist-item-processing-edit');

			BX('task-detail-checklist-item-' + itemId + '-title').innerHTML = BX.util.htmlspecialchars(newTitle);
			BX('task-detail-checklist-item-' + itemId + '-hiddenTitle').value = newTitle;

			window.jsDD.Enable();
		}
		else
		{
			var oTask = new BX.CJSTask.Item(taskId);

			oTask.checklistRename(
				itemId,
				newTitle,
				{
					callbackOnSuccess: (function(selfObj, taskId, itemId, newTitle){
						return function(reply){
							var itemDomNode = BX('task-detail-checklist-item-' + itemId);
							BX.removeClass(itemDomNode, 'task-detail-checklist-item-processing');
							BX.removeClass(itemDomNode, 'task-detail-checklist-item-processing-saving');
							BX.removeClass(itemDomNode, 'task-detail-checklist-item-processing-edit');

							BX('task-detail-checklist-item-' + itemId + '-title').innerHTML = BX.util.htmlspecialchars(newTitle);

							window.jsDD.Enable();
						}
					})(this, taskId, itemId, newTitle),
					callbackOnFailure: (function(taskId, itemId, newTitle){
						return function(reply){
							var itemDomNode = BX('task-detail-checklist-item-' + itemId);
							BX.removeClass(itemDomNode, 'task-detail-checklist-item-processing-saving');

							window.jsDD.Enable();
						}
					})(taskId, itemId, newTitle)
				}
			);
		}
	},
	checklistMoveAfterItem : function(isTaskCreateMode, taskId, selectedItemId, insertAfterItemId)
	{
		if ( ! isTaskCreateMode )
		{
			var itemDomNode = BX('task-detail-checklist-item-' + selectedItemId);

			// Prevent duplicate actions
			if (BX.hasClass(itemDomNode, 'task-detail-checklist-item-processing-saving'))
				return;

			BX.addClass(itemDomNode, 'task-detail-checklist-item-processing-saving');

			if (insertAfterItemId > 0)
				BX.addClass(BX('task-detail-checklist-item-' + insertAfterItemId), 'task-detail-checklist-item-processing-saving');

			var oTask = new BX.CJSTask.Item(taskId);

			oTask.checklistMoveAfterItem(
				selectedItemId,
				insertAfterItemId,
				{
					callbackOnSuccess: (function(selfObj, taskId, selectedItemId, insertAfterItemId){
						return function(reply){
							BX.removeClass(BX('task-detail-checklist-item-' + selectedItemId), 'task-detail-checklist-item-processing-saving');

							if (insertAfterItemId > 0)
								BX.removeClass(BX('task-detail-checklist-item-' + insertAfterItemId), 'task-detail-checklist-item-processing-saving');
						}
					})(this, taskId, selectedItemId, insertAfterItemId),
					callbackOnFailure: (function(taskId, selectedItemId, insertAfterItemId){
						return function(reply){
							BX.removeClass(BX('task-detail-checklist-item-' + selectedItemId), 'task-detail-checklist-item-processing-saving');

							if (insertAfterItemId > 0)
								BX.removeClass(BX('task-detail-checklist-item-' + insertAfterItemId), 'task-detail-checklist-item-processing-saving');
						}
					})(taskId, selectedItemId, insertAfterItemId)
				}
			);
		}

		tasksDetailPartsNS.recalcChecklist();
	},
	checklistEditItem : function(taskId, itemId)
	{
		var itemDomNode = BX('task-detail-checklist-item-' + itemId);
		var labelDomNode = BX('task-detail-checklist-item-' + itemId + '-label');

		// Prevent duplicate actions
		if (BX.hasClass(itemDomNode, 'task-detail-checklist-item-processing'))
			return;

		window.jsDD.Disable();

		BX.addClass(itemDomNode, 'task-detail-checklist-item-processing');
		BX.addClass(itemDomNode, 'task-detail-checklist-item-processing-edit');
		BX('task-detail-checklist-item-' + itemId + '-editInput').focus();
		this.setCursorPosition(BX('task-detail-checklist-item-' + itemId + '-editInput'), BX('task-detail-checklist-item-' + itemId + '-editInput').value.length);
	},
	renderChecklistItem : function(isTaskCreateMode, taskId, itemId, itemTitle, isChecked, parameters)
	{
		var clsName = 'task-detail-checklist-item task-detail-checklist-allow-edit task-detail-checklist-allow-remove';

		if (isChecked)
			clsName = clsName + ' task-detail-checklist-item-complete';

		if (typeof parameters == 'undefined')
			parameters = {};

		//if(BX.type.isString(itemTitle))
		//	itemTitle = BX.util.strip_tags(itemTitle);

		var item = BX.create('div', {
			props : {
				id        : 'task-detail-checklist-item-' + itemId,
				className : clsName
			},
			children : [
				BX.create('label', {
					props : {
						id : 'task-detail-checklist-item-' + itemId + '-label',
						className : 'task-detail-checklist-item-label'
					},
					children : [
						parameters['readonly'] ? false : BX.create('input', {
							props : { type : 'checkbox', checked : isChecked },
							events : {
								click : (function(isTaskCreateMode, taskId, itemId, selfObj){
									return function() {
										selfObj.checklistToggle(
											isTaskCreateMode, taskId, itemId, this
										);
									}
								})(isTaskCreateMode, taskId, itemId, this)
							}
						}),
						BX.create('span', {
							props : { className : 'task-detail-checklist-item-counter' },
							style: parameters['readonly'] ? {cursor : 'default'} : {},
							html : (++tasksDetailPartsNS.checklistCounter) + '. '
						}),
						BX.create('span', {
							props : { id : 'task-detail-checklist-item-' + itemId + '-title' },
							style: parameters['readonly'] ? {cursor : 'default'} : {},
							html : (typeof parameters.display != 'undefined' && parameters.display.toString().length > 0 ? parameters.display : BX.util.htmlspecialchars(itemTitle))
						})
					],
					events : {
						click : function(e){
							if (!e)
								var e = window.event;

							if (e)
							{
								var eTarget = e.target || e.srcElement;

								var isA =	eTarget && typeof eTarget.nodeName != 'undefined' && eTarget.nodeName == 'A';
								var isCB =	eTarget && typeof eTarget.nodeName != 'undefined' && eTarget.nodeName == 'INPUT' && eTarget.checked != 'undefined';

								// block click through nodes that differ from checkbox and anchor
								if (!isA && !isCB)
								{
									BX.PreventDefault(e);
								}
							}
						}
					}
				}),
				BX.create('input', {
					props : {
						type : 'text',
						className : 'task-detail-checklist-item-edit',
						id : 'task-detail-checklist-item-' + itemId + '-editInput',
						maxlength : 250,
						value : itemTitle /*BX.util.strip_tags(itemTitle)*/
					},
					events : {
						keypress : (function(isTaskCreateMode, taskId, itemId){
							return function(e){
								tasksDetailPartsNS.checklistEditOnKeyPress(e, isTaskCreateMode, taskId, itemId);
							}
						})(isTaskCreateMode, taskId, itemId)
					}
				}),
				BX.create('input', {
					props : {
						id : 'task-detail-checklist-item-' + itemId + '-hiddenId',
						type : 'hidden',
						name : 'CHECKLIST_ITEM_ID[]',
						value : itemId
					}
				}),
				BX.create('input', {
					props : {
						id : 'task-detail-checklist-item-' + itemId + '-hiddenTitle',
						type : 'hidden',
						name : 'CHECKLIST_ITEM_TITLE[' + itemId + ']',
						value : itemTitle /*BX.util.strip_tags(itemTitle)*/
					}
				}),
				BX.create('input', {
					props : {
						id : 'task-detail-checklist-item-' + itemId + '-hiddenIsChecked',
						type : 'hidden',
						name : 'CHECKLIST_ITEM_IS_CHECKED[' + itemId + ']',
						value : (isChecked ? 'Y' : 'N')
					}
				}),
				BX.create('span', {
					props : { className : 'task-detail-checklist-save' },
					events : {
						click : (function(isTaskCreateMode, taskId, itemId, selfObj){
							return function() {
								var inputDomNodeId = 'task-detail-checklist-item-' + itemId + '-editInput';
								selfObj.checklistRename(isTaskCreateMode, taskId, itemId, BX(inputDomNodeId).value)
							};
						})(isTaskCreateMode, taskId, itemId, this)
					}
				}),
				parameters['readonly'] ? false : BX.create('span', {
					props : { className : 'task-table-edit' },
					events : {
						click : (function(isTaskCreateMode, taskId, itemId, selfObj){
							return function() {
								selfObj.checklistEditItem(
									taskId, itemId
								);
							}
						})(isTaskCreateMode, taskId, itemId, this)
					}
				}),
				parameters['readonly'] ? false : BX.create('span', {
					props : { className : 'task-table-remove' },
					events : {
						click : (function(isTaskCreateMode, taskId, itemId, selfObj){
							return function() {
								selfObj.checklistRemoveItem(
									isTaskCreateMode, taskId, itemId
								);
							}
						})(isTaskCreateMode, taskId, itemId, this)
					}
				})
			]
		});

		return (item);
	},
	recalcChecklist : function()
	{
		var checkboxes = BX.findChildren(
			BX('task-detail-checklist-items'),
			{
				tagName   : 'input',
				attribute : {type: 'checkbox'}
			},
			true
		);

		var counters = BX.findChildren(
			BX('task-detail-checklist-items'),
			{
				tagName   : 'span',
				className : 'task-detail-checklist-item-counter'
			},
			true
		);

		var i;
		var total   = 0;
		var checked = 0;

		for (i in checkboxes)
		{
			if (checkboxes[i].checked)
				checked++;

			total++;
		}

		if (checked > 0)
		{
			BX('task-detail-checklist-title-text').innerHTML = BX.message('TASKS_DETAIL_CHECKLIST_DETAILED')
				.replace('#CHECKED#', checked)
				.replace('#TOTAL#', total);
		}
		else
			BX('task-detail-checklist-title-text').innerHTML = BX.message('TASKS_DETAIL_CHECKLIST');

		tasksDetailPartsNS.checklistCounter = 0;

		for (i in counters)
			counters[i].innerHTML = (++tasksDetailPartsNS.checklistCounter) + '. ';
	},
	showCompletedCheckListItems: function()
	{
		tasksDetailPartsNS.toggleCompletedCheckListItems(true);
	},
	hideCompletedCheckListItems: function()
	{
		tasksDetailPartsNS.toggleCompletedCheckListItems(false);
	},
	toggleCompletedCheckListItems: function(way)
	{
		BX[way ? 'removeClass' : 'addClass'](BX('task-detail-checklist-scope'), 'task-detail-checklist-hide-completed');
		tasksDetailPartsNS.checkListCompletedShown = way;
	},
	initChecklistItem : function(isTaskCreateMode, taskId, itemId, itemTitle, isChecked)
	{
		var row = BX('task-detail-checklist-item-' + itemId)
		row.onbxdragstart = function(){
			window.bxblank = this.parentNode.insertBefore(
				BX.create(
					'DIV', {
						style: {
							height: tasksDetailPartsNS.getFullHeight(BX(this.id)) + 'px',
							margin: '3px 5px 3px 5px',
							paddingLeft: '40px'
						}
					}
				),
				this
			);

			this.style.position = 'absolute';
		};

		row.onbxdrag = function(x, y){
			var tmp = BX.pos(BX('task-detail-checklist-items'));
			y -= (tmp.top + 5);
			this.style.top = y + 'px';
		};

		row.onbxdragstop  = function(){
			this.parentNode.replaceChild(this, window.bxblank);
			this.style.position = 'static';

			var insertAfterItemId = null;

			if (tasksDetailPartsNS.insertAfterItemId === 'task-detail-checklist-top-land-zone')
				insertAfterItemId = 0;
			else if (tasksDetailPartsNS.insertAfterItemId.substr(0, 27) === 'task-detail-checklist-item-')
				insertAfterItemId = parseInt(tasksDetailPartsNS.insertAfterItemId.substr(27, tasksDetailPartsNS.insertAfterItemId.length - 27));

			if (insertAfterItemId !== null)
			{
				tasksDetailPartsNS.checklistMoveAfterItem(
					isTaskCreateMode,
					taskId, 
					itemId,
					insertAfterItemId
				);
			}
		};

		row.onbxdraghover = function(dest, x, y){
			if (this.id === dest.id)
				return;

			dest.parentNode.insertBefore(window.bxblank, dest.nextSibling);

			tasksDetailPartsNS.insertAfterItemId = window.bxblank.previousSibling.id;
		};

		window.jsDD.registerDest(row);
		window.jsDD.registerObject(row);
	},
	setCursorPosition : function(elem, pos) {
		if (elem.setSelectionRange)
		{
			elem.setSelectionRange(pos, pos);
		}
		else if (elem.createTextRange)
		{
			var range = elem.createTextRange();
			range.collapse(true);
			range.moveEnd('character', pos);
			range.moveStart('character', pos);
			range.select();
		}
	},
	checklistEditOnKeyPress : function(e, isTaskCreateMode, taskId, itemId)
	{
		if (!e)
			var e = window.event;

		var key=e.keyCode || e.which;

		if (key != 13)
			return;

		tasksDetailPartsNS.checklistRename(
			isTaskCreateMode,
			taskId,
			itemId,
			BX('task-detail-checklist-item-' + itemId + '-editInput').value
		);
	},
	checklistSaveItem : function(e, isTaskCreateMode, taskId)
	{
		var key=e.keyCode || e.which;

		if (key != 13)
			return;

		tasksDetailPartsNS.checklistAddItem(isTaskCreateMode, taskId);
	},
	ShowActionMenu : function(button, id, menu)
	{
		BX.PopupMenu.destroy(id);

		BX.PopupMenu.show(
			id,
			button,
			menu,
			{ /*bindOptions : {forceBindPosition : true } */}
		);

		return false;
	},
	loadUserSelectorViaAjax : function(selectorCodename, selectedUsersIds, params)
	{
		var groupId          = 0;
		var multiple         = 'N';
		var bindElement      = null;
		var callbackOnReady  = null;
		var callbackOnSelect = null;
		var loadingParams    = {};

		if (params)
		{
			if (params.callbackOnSelect)
				callbackOnSelect = params.callbackOnSelect;

			if (params.callbackOnReady)
				callbackOnReady = params.callbackOnReady;

			if (params.groupId)
				groupId = params.groupId;

			if (params.bindElement)
				bindElement = params.bindElement;

			if (params.multiple)
				multiple = params.multiple;
		}

		if ( ! tasksDetailPartsNS.loadedSelectors[selectorCodename] )
		{
			loadingParams = {
				requestedObject   : 'intranet.user.selector.new',
				selectedUsersIds  :  selectedUsersIds,
				anchorId          :  bindElement,
				multiple          :  multiple,
				GROUP_ID_FOR_SITE :  groupId,
				callbackOnSelect  : (function(callbackOnSelect){
					return function (arUser)
					{
						if (callbackOnSelect)
							callbackOnSelect(arUser);
					}
				})(callbackOnSelect),
				onReady : (function(callbackOnReady, selectorCodename){
					return function(selectorObject)
					{
						tasksDetailPartsNS.loadedSelectors[selectorCodename] = selectorObject;

						if (callbackOnReady)
							callbackOnReady(selectorObject);
					}
				})(callbackOnReady, selectorCodename)
			};

			if (params && params.callbackOnChange)
			{
				loadingParams.callbackOnChange = (function(callbackOnChange){
					return function (arUsers)
					{
						callbackOnChange(arUsers);
					}
				})(params.callbackOnChange);
			}

			BX.Tasks.lwPopup.__initSelectors([loadingParams]);
		}
		else
		{
			if (callbackOnReady)
				callbackOnReady(tasksDetailPartsNS.loadedSelectors[selectorCodename]);
		}
	},
	ShowDelegatePopup : function(bindElement, taskId, groupId)
	{
		tasksDetailPartsNS.loadUserSelectorViaAjax(
			'DELEGATE',
			null,
			{
				groupId         : groupId,
				bindElement     : bindElement,
				callbackOnReady : (function(bindElement, taskId){
					return function(selectorObject)
					{
						tasksDetailPartsNS.__ShowDelegatePopup(selectorObject.name, bindElement, taskId)
					}
				})(bindElement, taskId),
				callbackOnSelect : function(arUsers)
				{
					tasksDetailPartsNS.onDelegateChange(arUsers);
				}
			}
		);
	},
	__ShowDelegatePopup : function(selectorObjName, bindElement, taskId)
	{
		delegatePopup = BX.PopupWindowManager.create("delegate-employee-popup", bindElement, {
			offsetTop : 1,
			autoHide : true,
			closeByEsc : true,
			content : BX(selectorObjName + '_selector_content'),
			buttons : [
			new BX.PopupWindowButton({
				text : BX.message("TASKS_SELECT"),
				className : "popup-window-button-accept",
				events : {
					click : (function (e) {
						if(!e) e = window.event;

						return function(e) {
							var form = BX.create("form", {
								props : {
									method : "POST"
								},
								style : {
									display : "none"
								},
								children : [
								BX.create("input",{
									props : {
										name : "ACTION",
										value : "delegate"
									}
								}),
								BX.create("input",{
									props : {
										name : "sessid",
										value : BX.message("bitrix_sessid")
									}
								}),
								BX.create("input",{
									props : {
										name : "ID",
										value : taskId
									}
								}),
								BX.create("input",{
									props : {
										name : "USER_ID",
										value : delegateUser
									}
								})
								]
							});
							document.body.appendChild(form);
							BX.submit(form);

							this.popupWindow.close();
						}

					})()
					}
			}),

			new BX.PopupWindowButtonLink({
				text : BX.message("TASKS_CANCEL"),
				className : "popup-window-button-link-cancel",
				events : {
					click : function(e) {
						if(!e) e = window.event;

						this.popupWindow.close();

						BX.PreventDefault(e);
					}
				}
			})
		]
		});

		delegatePopup.show();
	},
	onDelegateChange : function(arUser)
	{
		if (arUser)
			delegateUser = arUser.id;
	},
	showResponsibleChangePopup : function(bindElement, taskId, groupId, selectedUserId)
	{
		tasksDetailPartsNS.loadUserSelectorViaAjax(
			'RESPONSIBLE',
			selectedUserId,
			{
				groupId         : groupId,
				bindElement     : bindElement,
				callbackOnReady : (function(bindElement, taskId){
					return function(selectorObject)
					{
						tasksDetailPartsNS.responsiblePopup = BX.PopupWindowManager.create("responsible-employee-popup", bindElement, {
							offsetTop  : 1,
							autoHide   : true,
							closeByEsc : true,
							content    : BX(selectorObject.name + '_selector_content')
						});

						tasksDetailPartsNS.responsiblePopup.show();

						BX.focus(bindElement);
					}
				})(bindElement, taskId),
				callbackOnSelect : function(arUser)
				{
					tasksDetailPartsNS.onResponsibleSelect(arUser)
				}
			}
		);
	},
	onDeleteClick : function(e, taskId)
	{
		if (!e) e = window.event;

		BX.PreventDefault(e);

		// BX.Tasks.confirmDelete(BX.message('TASKS_COMMON_TASK_ALT_A')).then(function(){

			if (window.top.BX.TasksIFrameInst && window.top.BX.TasksIFrameInst.isOpened())
			{
				var data = {
					mode : "delete",
					sessid : BX.message("bitrix_sessid"),
					id : taskId
				};

				BX.ajax({
					"method": "POST",
					"dataType": "json",
					"url": tasksDetailPartsNS.tasksListAjaxUrl,
					"data":  data,
					"processData" : true,
					"onsuccess": (function(taskId){
						return function(datum) {
							tasksDetailPartsNS.onDeleteClick_onSuccess(e, taskId, datum);

							BX.UI.Notification.Center.notify({
								content: BX.message('TASKS_DELETE_SUCCESS')
							});
						};
					})(taskId)
				});
			}

		// }.bind(this));
	},
	onDeleteClick_onSuccess : function(e, taskId, data)
	{
		if (data && data.length > 0)
		{
			// there is an error occured
		}
		else
		{
			window.top.BX.TasksIFrameInst.close();
			window.top.BX.TasksIFrameInst.onTaskDeleted(taskId);
		}
	},
	getMembersAddChangeFunction : function(memberType, bindElement, taskId, groupId, selectedUsersIds)
	{
		return function(e)
		{
			if(!e) e = window.event;

			var callbackOnReady  = null;
			var callbackOnChange = null;

			if (memberType === 'AUDITORS')
			{
				callbackOnReady  = tasksDetailPartsNS._auditorsAddChange;
				callbackOnChange = tasksDetailPartsNS.onAuditorsChange;
			}
			else if (memberType === 'ACCOMPLICES')
			{
				callbackOnReady  = tasksDetailPartsNS._accomplicesAddChange;
				callbackOnChange = tasksDetailPartsNS.onAccomplicesChange;
			}
			else
				throw 'Unknown memberType : ' + memberType;

			tasksDetailPartsNS.loadUserSelectorViaAjax(
				memberType,
				selectedUsersIds,
				{
					groupId         : groupId,
					multiple        : 'Y',
					bindElement     : bindElement,
					callbackOnReady : (function(bindElement, taskId, callbackOnReady){
						return function(selectorObject)
						{
							callbackOnReady(selectorObject, bindElement, taskId)
						}
					})(bindElement, taskId, callbackOnReady),
					callbackOnChange : (function(callbackOnChange){
						return function(arUsers)
						{
							callbackOnChange(arUsers);
						}
					})(callbackOnChange)
				}
			);
			
			BX.focus(bindElement);

			BX.PreventDefault(e);
		}
	},
	_auditorsAddChange : function(O_AUDITORS, bindElement, taskId)
	{
		tasksDetailPartsNS.arAuditors = O_AUDITORS.arSelected;

		tasksDetailPartsNS.auditorsPopup = BX.PopupWindowManager.create("auditors-employee-popup", bindElement, {
			autoHide : true,
			closeByEsc : true,
			content : BX(O_AUDITORS.name + '_selector_content'),
			buttons : [
			new BX.PopupWindowButton({
				text : BX.message("TASKS_SELECT"),
				className : "popup-window-button-accept",
				events : {
					click : function(e) {
						if(!e) e = window.event;

						var arUsersIds = tasksDetailPartsNS.renderMembersBlock('auditors', tasksDetailPartsNS.arAuditors, this.popupWindow);

						BX.ajax.post(tasksDetailPartsNS.tasksListAjaxUrl, {
							mode : "auditors",
							sessid : BX.message("bitrix_sessid"),
							id : detailTaksID,
							path_to_user: BX.message("TASKS_PATH_TO_USER_PROFILE"),
							path_to_task: BX.message("TASKS_PATH_TO_TASK"),
							path_to_user_tasks_task: BX.message("TASKS_PATH_TO_USER_TASKS_TASK"),
							auditors : arUsersIds
						}, function(){
							BX.Tasks.Util.fireGlobalTaskEvent('UPDATE', {ID: detailTaksID}, {STAY_AT_PAGE: true}, {id: detailTaksID});
						});

						this.popupWindow.close();
					}
				}
			}),

			new BX.PopupWindowButtonLink({
				text : BX.message("TASKS_CANCEL"),
				className : "popup-window-button-link-cancel",
				events : {
					click : function(e) {
						if(!e) e = window.event;

						this.popupWindow.close();

						BX.PreventDefault(e);
					}
				}
			})
			]
		});

		tasksDetailPartsNS.auditorsPopup.show();
	},
	_accomplicesAddChange : function(O_ACCOMPLICES, bindElement, taskId)
	{
		tasksDetailPartsNS.arAccomplices = O_ACCOMPLICES.arSelected;

		tasksDetailPartsNS.accomplicesPopup = BX.PopupWindowManager.create("assistants-employee-popup", bindElement, {
			autoHide : true,
			closeByEsc : true,
			content : BX(O_ACCOMPLICES.name + '_selector_content'),
			buttons : [
			new BX.PopupWindowButton({
				text : BX.message("TASKS_SELECT"),
				className : "popup-window-button-accept",
				events : {
					click : function(e) {
						if(!e) e = window.event;

						var arUsersIds = tasksDetailPartsNS.renderMembersBlock('accomplices', tasksDetailPartsNS.arAccomplices, this.popupWindow);

						var data = {
							mode : "accomplices",
							sessid : BX.message("bitrix_sessid"),
							id : detailTaksID,
							path_to_user: BX.message("TASKS_PATH_TO_USER_PROFILE"),
							path_to_task: BX.message("TASKS_PATH_TO_TASK"),
							accomplices : arUsersIds
						};
						BX.ajax.post(tasksDetailPartsNS.tasksListAjaxUrl, data);


						this.popupWindow.close();
					}
				}
			}),

			new BX.PopupWindowButtonLink({
				text : BX.message("TASKS_CANCEL"),
				className : "popup-window-button-link-cancel",
				events : {
					click : function(e) {
						if(!e) e = window.event;

						this.popupWindow.close();

						BX.PreventDefault(e);
					}
				}
			})
			]
		});

		tasksDetailPartsNS.accomplicesPopup.show();
	},
	stopWatch : function(taskId)
	{
		var oTask = new BX.CJSTask.Item(taskId);
		oTask.stopWatch({
			callbackOnSuccess: (function(taskId)
			{
				var renderredUserBlock = BX('task-detail-info-user-auditor-' + BX.message('USER_ID') + '-container');
				if (renderredUserBlock)
					renderredUserBlock.parentNode.removeChild(renderredUserBlock);

				if (BX('task-detail-info-stop-watch'))
					BX('task-detail-info-stop-watch').style.display = 'none';

				if (BX('task-detail-info-start-watch-block'))
					BX('task-detail-info-start-watch-block').style.display = '';

				// Remove user from auditors
				var index = tasksDetailPartsNS.arAuditors.indexOf(BX.message('USER_ID'));
				if (index != -1)
					tasksDetailPartsNS.arAuditors.splice(index, 1);

				// If there is no more auditors - hide title of auditors block
				if ( ! tasksDetailPartsNS.isAuditorsInAuditorsBlock() )
					BX.addClass(BX("task-detail-info-auditors"), "task-detail-info-users-empty");
			})(taskId)
		})
	},
	startWatch : function(taskId)
	{
		var oTask = new BX.CJSTask.Item(taskId);
		oTask.startWatch({
			callbackOnSuccess: (function(taskId)
			{
				tasksDetailPartsNS.addUserToAuditorsBlock({
					id       : BX.message('USER_ID'),
					name     : BX.message('TASKS_LOGGED_IN_USER_FORMATTED_NAME'),
					position : BX.message('TASKS_LOGGED_IN_USER_WORK_POSITION')
				});

				if (BX('task-detail-info-stop-watch'))
					BX('task-detail-info-stop-watch').style.display = '';

				if (BX('task-detail-info-start-watch-block'))
					BX('task-detail-info-start-watch-block').style.display = 'none';
			})(taskId)
		})
	},
	addUserToAuditorsBlock : function(arUser)
	{
		// Nothing to do, if user already in list
		if (BX("task-detail-info-user-auditor-" + arUser.id + "-container"))
			return;

		BX("task-detail-auditors").appendChild(tasksDetailPartsNS.RenderUser(arUser));
		BX.removeClass(BX("task-detail-info-auditors"), "task-detail-info-users-empty");		

		tasksDetailPartsNS.arAuditors.push(arUser.id);
	},
	isAuditorsInAuditorsBlock : function()
	{
		var block = BX('task-detail-auditors');
		var node = null;

		for (var i = 0; i < block.childNodes.length; i++)
		{
			node = block.childNodes[i];
			
			if (node && node.className && node.className == "task-detail-info-user")
				return (true);
		}

		return (false);
	},
	renderMembersBlock : function (blockType, arUsers, userSelectorPopup)
	{
		// There is strings 'auditors' or 'accomplices' expected in blockType
		var styleSuffix = '';
		var arUsersIds = [];

		if (blockType === 'auditors')
		{
			styleSuffix = 'auditors';
		}
		else if (blockType === 'accomplices')
		{
			styleSuffix = 'assistants'
		}

		div = BX("task-detail-" + styleSuffix);

		BX.cleanNode(div);
		for(i = 0; i < arUsers.length; i++)
		{
			if (arUsers[i])
			{
				div.appendChild(tasksDetailPartsNS.RenderUser(arUsers[i]));
				arUsersIds.push(arUsers[i].id);
			}
		}

		if (arUsersIds.length > 0)
		{
			BX.removeClass(BX("task-detail-info-" + styleSuffix), "task-detail-info-users-empty");
			BX("task-detail-info-" + styleSuffix + "-add").parentNode.style.display = "none";

			if (userSelectorPopup)
				userSelectorPopup.setBindElement(BX("task-detail-info-" + styleSuffix + "-change"));
		}
		else
		{
			BX.addClass(BX("task-detail-info-" + styleSuffix), "task-detail-info-users-empty");
			BX("task-detail-info-" + styleSuffix + "-add").parentNode.style.display = "block";

			if (userSelectorPopup)
				userSelectorPopup.setBindElement(BX("task-detail-info-" + styleSuffix + "-add"));
		}

		return (arUsersIds);
	},
	RenderUser : function(arUser, bAvatar)
	{
		var arChildren = [];
		if (bAvatar)
		{
			arChildren.push(BX.create("a", {
				props : {
					href : BX.message("TASKS_PATH_TO_USER_PROFILE").replace("#user_id#", arUser.id),
					className : "task-detail-info-user-avatar"
				},
				style : {
					background : arUser.photo ? "url('" + arUser.photo + "') no-repeat center center" : ""
				}
			}));
		}

		arChildren.push(BX.create("div", {
			props : {
				className : "task-detail-info-user-info"
			},
			children : [
				BX.create("div", {
					props : {
						className : "task-detail-info-user-name"
					},
					children : [
					BX.create("a", {
						props : {
							href : BX.message("TASKS_PATH_TO_USER_PROFILE").replace("#user_id#", arUser.id)
						},
						text : arUser.name
					})
					]
				}),
				BX.create("div", {
					props : {
						className : "task-detail-info-user-position"
					},
					text : arUser.position
				})
			]
		}));

		return BX.create("div", {
			props : {
				id : "task-detail-info-user-auditor-" + arUser.id + "-container",
				className : "task-detail-info-user"
			},
			children : arChildren
		});
	},
	ShowGradePopupDetail : function(taskId, bindElement, currentValues)
	{
		BX.TaskGradePopup.show(
			taskId,
			bindElement,
			currentValues,
			{
				events : {
					onPopupChange : tasksDetailPartsNS.__onGradePopupChangeDetail
				}
			}
			);

		return false;
	},
	__onGradePopupChangeDetail : function()
	{
		this.bindElement.className = "task-detail-grade task-detail-grade-" + this.listItem.className;
		this.bindElement.childNodes[1].innerHTML = this.listItem.name;
		var data = {
			mode : "mark",
			sessid : BX.message("bitrix_sessid"),
			id : this.id,
			mark : this.listValue
		};
		BX.ajax.post(tasksDetailPartsNS.tasksListAjaxUrl, data);
	},
	ShowPriorityPopupDetail : function(taskId, bindElement, currentPriority)
	{
		BX.TaskPriorityPopup.show(
			taskId,
			bindElement,
			currentPriority,
			{
				events : {
					onPopupChange : tasksDetailPartsNS.__onPriorityChangeDetail
				}
			}
		);

		return false;
	},
	__onPriorityChangeDetail : function()
	{
		this.bindElement.className = "task-detail-priority task-detail-priority-" + this.listValue;
		this.bindElement.childNodes[1].innerHTML = this.listItem.name;
		var data = {
			mode : "priority",
			sessid : BX.message("bitrix_sessid"),
			id : this.id,
			path_to_user: BX.message("TASKS_PATH_TO_USER_PROFILE"),
			path_to_task: BX.message("TASKS_PATH_TO_TASK"),
			path_to_user_tasks_task: BX.message("TASKS_PATH_TO_USER_TASKS_TASK"),
			priority : this.listValue
		};
		BX.ajax.post(tasksDetailPartsNS.tasksListAjaxUrl, data);

		if (taskData.priority != this.listValue && window.top.BX.TasksIFrameInst)
		{
			taskData.priority = this.listValue;
			window.top.BX.TasksIFrameInst.onTaskChanged(taskData);
		}
	},
	onResponsibleSelect : function(arUser)
	{
		var arFilter = '', columnsOrder = null;

		var div = BX.findNextSibling(tasksDetailPartsNS.responsiblePopup.bindElement, {
			tag : "div"
		});
		BX.cleanNode(div);

		div.appendChild(tasksDetailPartsNS.RenderUser(arUser, true));

		var data = {
			mode : "responsible",
			sessid : BX.message("bitrix_sessid"),
			id : detailTaksID,
			path_to_user: BX.message("TASKS_PATH_TO_USER_PROFILE"),
			path_to_task: BX.message("TASKS_PATH_TO_TASK"),
			path_to_user_tasks_task: BX.message("TASKS_PATH_TO_USER_TASKS_TASK"),
			responsible : arUser.id
		};

		if (window.top != window)
		{
			if (window.top.tasksListNS && window.top.tasksListNS.arFilter)
			{
				arFilter = window.top.tasksListNS.arFilter;
				columnsOrder = window.top.tasksListNS.getColumnsOrder();
			}
		}

		if (columnsOrder !== null)
			data['columnsOrder'] = columnsOrder;

		BX.ajax({
			'url' : tasksDetailPartsNS.tasksListAjaxUrl,
			'dataType': 'json',
			'method' : 'POST',
			'data' : data,
			'processData' : true,
			'onsuccess': function(reply){
				var taskData, legacyHtmlTaskItem;

				taskData = BX.parseJSON(reply.tasksRenderJSON);
				legacyHtmlTaskItem = taskData.html;

				window.top.BX.TasksIFrameInst.onTaskChanged(taskData, null, null, null, legacyHtmlTaskItem);
			}
		});

		if (taskData.responsibleId != arUser.id && window.top.BX.TasksIFrameInst)
		{
			taskData.responsibleId = arUser.id;
			taskData.responsible = arUser.name;
		}

		tasksDetailPartsNS.responsiblePopup.close();
	},
	onAuditorsChange : function(arUsers)
	{
		tasksDetailPartsNS.arAuditors = arUsers;
	},
	onAccomplicesChange : function (arUsers)
	{
		tasksDetailPartsNS.arAccomplices = arUsers;
	},
	ClearDeadline : function (taskId, deleteIcon)
	{
		deleteIcon.style.display = "none";
		var field = BX("task-deadline-hidden");
		field.value = "";

		BX.cleanNode (field.previousSibling);
		var newsubcont = document.createElement("span");
		newsubcont.innerHTML = BX.message("TASKS_SIDEBAR_DEADLINE_NO");
		field.previousSibling.appendChild(newsubcont);

		field.previousSibling.className = "webform-field-action-link";
		var data = {
			mode : "deadline",
			sessid : BX.message("bitrix_sessid"),
			id : taskId,
			deadline : ""
		};
		BX.ajax.post(tasksDetailPartsNS.tasksListAjaxUrl, data);
	},
	reloadRightSideBar : function(taskId, fireOnChangeEvent)
	{
		tasksDetailPartsNS.__reloadBlock(taskId, 'right_sidebar', fireOnChangeEvent);
	},
	reloadButtons : function(taskId)
	{
		var node = BX('task-detail-buttons-div');

		if (node)
			BX.addClass(node, 'task-buttons-to-be-reloaded');

		tasksDetailPartsNS.__reloadBlock(taskId, 'buttons', 'N');
	},
	__reloadBlock : function(taskId, blockName, fireOnChangeEvent)
	{
		var targetDomNode = null;
		if (blockName === 'buttons')
			targetDomNode = BX('task-detail-buttons-area');
		else if (blockName === 'right_sidebar')
			targetDomNode = BX('task-detail-right-sidebar');
		else
			throw 'Error [0x37479de4]';

		BX.ajax.post(
			tasksDetailPartsNS.detailsAjaxUrl + '&action=render_task_detail_part',
			{
				sessid                     :  BX.message('bitrix_sessid'),
				MODE                       :  BX.message('TASKS_CONTEXT_PARTS_MODE'),
				INNER_HTML                 : 'Y',
				BLOCK                      :  blockName,
				IS_IFRAME                  :  BX.message('TASKS_CONTEXT_IS_IFRAME'),
				PATH_TO_TEMPLATES_TEMPLATE :  BX.message('TASKS_CONTEXT_PATH_TO_TEMPLATES_TEMPLATE'),
				PATH_TO_TASKS_TASK         :  BX.message('TASKS_CONTEXT_PATH_TO_TASKS_TASK'),
				PATH_TO_USER_PROFILE       :  BX.message('TASKS_CONTEXT_PATH_TO_USER_PROFILE'),
				NAME_TEMPLATE              :  BX.message('TASKS_CONTEXT_NAME_TEMPLATE'),
				FIRE_ON_CHANGED_EVENT      : (fireOnChangeEvent === true ? 'Y' : 'N'),
				TASK_ID                    :  taskId
			},
			function(data)
			{
				targetDomNode.innerHTML = data;
			}
		);
	},
	doAction : function(taskId, actionName)
	{
		var operation = null, params, columnsOrder = null, arFilter = null;

		if (actionName === 'start_timer')
		{
			BX.TasksTimerManager.start(taskId);
			return;
		}
		else if (actionName === 'stop_timer')
		{
			BX.TasksTimerManager.stop(taskId);
			return;
		}

		switch (actionName)
		{
			case 'approve':
				operation = 'CTaskItem::approve()';
			break;

			case 'disapprove':
				operation = 'CTaskItem::disapprove()';
			break;

			case 'complete':
				operation = 'CTaskItem::complete()';
			break;

			case 'start':
				operation = 'CTaskItem::startExecution()';
			break;

			case 'pause':
				operation = 'CTaskItem::pauseExecution()';
			break;

			case 'renew':
				operation = 'CTaskItem::renew()';
			break;

			case 'defer':
				operation = 'CTaskItem::defer()';
			break;

			default:
				throw 'tasks module unexpected error: [0x15a49c7f] unknown actionName: ' + actionName;
				return (false);
			break;
		}

		if (window.top != window)
		{
			if (window.top.tasksListNS && window.top.tasksListNS.arFilter)
			{
				arFilter = window.top.tasksListNS.arFilter;
				columnsOrder = window.top.tasksListNS.getColumnsOrder();
			}
		}

		params = {
			operation : 'tasksRenderJSON() && tasksRenderListItem()',
			taskData  : {
				ID : '#RC#$arOperationsResults#-3#requestedTaskId'
			}
		};

		if (columnsOrder !== null)
			params['columnsIds'] = columnsOrder;

		if (arFilter !== null)
			params['arFilter'] = arFilter;

		BX.CJSTask.batchOperations(
			[
				{
					operation : operation,
					taskData  : {
						ID : taskId
					}
				},
				{
					operation : 'CTaskItem::getTaskData()',
					taskData  : {
						ID : '#RC#$arOperationsResults#-1#requestedTaskId'
					}
				},
				{
					operation : 'CTaskTimerManager::getLastTimer()'
				},
				params
			],
			{
				callbackOnSuccess : (function(taskId){
					return function(data)
					{
						var taskData, legacyHtmlTaskItem;
						var taskOnTimer = false;
						var fireOnChangeEvent = true;

						tasksDetailPartsNS.reloadButtons(taskId);
						tasksDetailPartsNS.reloadRightSideBar(taskId, fireOnChangeEvent);
						BX.TasksTimerManager.reLoadInitTimerDataFromServer();

						if (data.status === 'success')
						{
							if (
								data.rawReply.data[2].returnValue
								&& data.rawReply.data[2].returnValue.TASK_ID
								&& (data.rawReply.data[1].returnValue.ID == data.rawReply.data[2].returnValue.TASK_ID)
							)
							{
								taskOnTimer = data.rawReply.data[1].returnValue;
							}

							BX.TasksTimerManager.onDataRecieved({
								TASKS_TIMER   : data.rawReply.data[2].returnValue,
								TASK_ON_TIMER : taskOnTimer
							});

							if (
								data.rawReply.data[3].returnValue
								&& data.rawReply.data[3].returnValue.tasksRenderListItem
								&& data.rawReply.data[3].returnValue.tasksRenderJSON
							)
							{
								taskData = BX.parseJSON(data.rawReply.data[3].returnValue.tasksRenderJSON);
								legacyHtmlTaskItem = data.rawReply.data[3].returnValue.tasksRenderListItem;

								window.top.BX.TasksIFrameInst.onTaskChanged(taskData, null, null, null, legacyHtmlTaskItem);
							}
						}
					}
				})(taskId),
				callbackOnFailure : (function(taskId){
					return function(data)
					{
						var fireOnChangeEvent = true;
						tasksDetailPartsNS.reloadButtons(taskId);
						tasksDetailPartsNS.reloadRightSideBar(taskId, fireOnChangeEvent);
						BX.TasksTimerManager.reLoadInitTimerDataFromServer();
					}
				})(taskId)
			}
		);
	},
	renderSecondsToHHMMSS : function(totalSeconds, bShowSeconds)
	{
		var pad = '00';
		var hours = '' + Math.floor(totalSeconds / 3600);
		var minutes = '' + (Math.floor(totalSeconds / 60) % 60);
		var seconds = 0;
		var result = '';

		result = pad.substring(0, 2 - hours.length) + hours
			+ ':' + pad.substring(0, 2 - minutes.length) + minutes;

		if (bShowSeconds)
		{
			seconds = '' + totalSeconds % 60;
			result = result + ':' + pad.substring(0, 2 - seconds.length) + seconds;
		}

		return (result);
	},
	initTimer : function(selfTaskId, ynRunning)
	{
		if (tasksDetailPartsNS.timerCallback !== null)
		{
			BX.removeCustomEvent(
				window,
				'onTaskTimerChange',
				tasksDetailPartsNS.timerCallback
			);
			tasksDetailPartsNS.timerCallback = null;
		}

		var state = null;
		if (ynRunning === 'Y')
			state = 'playing';
		else if (ynRunning === 'N')
			state = 'paused';

		tasksDetailPartsNS.timerCallback = tasksDetailPartsNS.onTaskTimerChange(
			selfTaskId,
			state
		);

		BX.addCustomEvent(
			window,
			'onTaskTimerChange',
			tasksDetailPartsNS.timerCallback
		);
	},
	onTaskTimerChange : function(selfTaskId, stateIn)
	{
		var state                  = null;
		var timerSpentTimeBlock1   = 0;
		var timerSpentTimeBlock2   = 0;
		var timerEstimateTimeBlock = 0;

		if (stateIn)
			state = stateIn;

		return function(params)
		{
			var switchStateTo                        = null;
			var renderredTimeSpentText               = '';
			var renderredTimeEstimateTextWoSeconds   = '';
			var renderredTimeEstimateTextWithSeconds = '';

			if (params.action === 'refresh_daemon_event')
			{
				if (params.taskId !== selfTaskId)
					switchStateTo = 'paused';
				else
				{
					switchStateTo = 'playing';

					if (timerSpentTimeBlock1 === 0)
						timerSpentTimeBlock1 = BX('task_details_buttons_timer_' + selfTaskId + '_text');

					if (timerSpentTimeBlock2 === 0)
						timerSpentTimeBlock2 = BX('task-detail-spent-time-' + selfTaskId);

					if (timerEstimateTimeBlock === 0)
						timerEstimateTimeBlock = BX('task-detail-estimate-time-' + selfTaskId);

					if (timerSpentTimeBlock1 || timerSpentTimeBlock2 || timerEstimateTimeBlock)
					{
						renderredTimeSpentText = tasksDetailPartsNS.renderSecondsToHHMMSS(
							params.data.TASK.TIME_SPENT_IN_LOGS + params.data.TIMER.RUN_TIME,
							true	// show seconds
						);

						if (params.data.TASK.TIME_ESTIMATE > 0)
						{
							renderredTimeEstimateTextWoSeconds = tasksDetailPartsNS.renderSecondsToHHMMSS(
								params.data.TASK.TIME_ESTIMATE,
								false	// hide seconds
							);

							renderredTimeEstimateTextWithSeconds = tasksDetailPartsNS.renderSecondsToHHMMSS(
								params.data.TASK.TIME_ESTIMATE,
								true	// hide seconds
							);
						}

						if (timerSpentTimeBlock1)
						{
							if (renderredTimeEstimateTextWoSeconds !== '')
								timerSpentTimeBlock1.innerHTML = renderredTimeSpentText + ' / ' + renderredTimeEstimateTextWoSeconds;
							else
								timerSpentTimeBlock1.innerHTML = renderredTimeSpentText;
						}

						if (timerSpentTimeBlock2)
							timerSpentTimeBlock2.innerHTML = renderredTimeSpentText;

						if (timerEstimateTimeBlock)
						{
							if (renderredTimeEstimateTextWithSeconds !== '')
								timerEstimateTimeBlock.innerHTML = renderredTimeEstimateTextWithSeconds;
							else
								timerEstimateTimeBlock.innerHTML = BX.message('TASKS_SIDEBAR_NO_ESTIMATE_TIME');
						}
					}
				}
			}
			else if (params.action === 'start_timer')
			{
				if (
					(selfTaskId == params.taskId)
					&& params.timerData
					&& (selfTaskId == params.timerData.TASK_ID)
				)
				{
					switchStateTo = 'playing';
				}
				else
					switchStateTo = 'paused';	// other task timer started, so we need to be paused
			}
			else if (params.action === 'stop_timer')
			{
				if (selfTaskId == params.taskId)
					switchStateTo = 'paused';
			}
			else if (params.action === 'init_timer_data')
			{
				if (params.data.TIMER)
				{
					if (params.data.TIMER.TASK_ID == selfTaskId)
					{
						if (params.data.TIMER.TIMER_STARTED_AT > 0)
							switchStateTo = 'playing';
						else
							switchStateTo = 'paused';
					}
					else if (params.data.TIMER.TASK_ID > 0)
					{
						// our task is not playing now
						switchStateTo = 'paused';
					}
				}
			}

			if ((switchStateTo !== null) && (switchStateTo !== state))
			{
				timerBlock = BX('task_details_buttons_timer_' + selfTaskId);

				if (timerBlock)
				{
					if (switchStateTo === 'paused')
						BX.removeClass(timerBlock, 'task-timeman-link-green');
					else if (switchStateTo === 'playing')
					{
						if ( ! BX.hasClass(timerBlock, 'task-timeman-link-red') )
							BX.addClass(timerBlock, 'task-timeman-link-green');
					}
				}

				state = switchStateTo;

				if (
					(params.action === 'start_timer')
					|| (params.action === 'stop_timer')
				)
				{
					tasksDetailPartsNS.reloadButtons(selfTaskId);
					tasksDetailPartsNS.reloadRightSideBar(
						selfTaskId,
						true			// fireOnChangeEvent
					);
				}
			}
		}
	},
	getFullHeight : function (elem)
	{
		var elmHeight, elmMargin;
		//if(document.all && (BX.browser.DetectIeVersion < 10))
		if(BX.browser.IsIE())
		{ // IE
			elmHeight=elem.clientHeight;
			elmMargin=parseInt(elem.currentStyle.marginTop)+parseInt(elem.currentStyle.marginBottom)+"px";
		}
		else
		{ // Mozilla
			elmHeight=document.defaultView.getComputedStyle(elem, '').getPropertyValue('height');
			elmMargin=parseInt(document.defaultView.getComputedStyle(elem, '').getPropertyValue('margin-top')) + parseInt(document.defaultView.getComputedStyle(elem, '').getPropertyValue('margin-bottom'))+"px";
		}

		return (parseInt(elmHeight + elmMargin));
	}
};
