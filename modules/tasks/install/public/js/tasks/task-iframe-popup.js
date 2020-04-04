(function (window) {
	var resizeInterval, lastSrc;
	var lastheight = 0;

	BX.TasksIFramePopup = {
		create : function(params)
		{
			if (!window.top.BX.TasksIFrameInst)
				window.top.BX.TasksIFrameInst = new TasksIFramePopup(params);

			if (params.events)
			{
				for (var eventName in params.events)
				{
					BX.removeCustomEvent(window.top.BX.TasksIFrameInst, eventName, params.events[eventName]);
					BX.addCustomEvent(window.top.BX.TasksIFrameInst, eventName, params.events[eventName]);
				}
			}

			return window.top.BX.TasksIFrameInst;
		}
	};

	var TasksIFramePopup = function(params) {

		this.inited = false;
		this.pathToEdit = "";
		this.pathToView = "";
		this.iframeWidth = 900;
		this.iframeHeight = 400;
		this.topBottomMargin = 15;
		this.leftRightMargin = 50;
		this.tasksList = [];
		this.currentURL = window.location.href;
		this.currentTaskId = 0;
		this.lastAction = null;
		this.loading = false;
		this.isEditMode = false;
		this.prevIframeSrc = '';
		this.descriptionBuffered = null;

		if (params)
		{
			if (params.pathToEdit)
			{
				this.pathToEdit = params.pathToEdit + (params.pathToEdit.indexOf("?") == -1 ? "?" : "&") + "IFRAME=Y";
			}
			if (params.pathToView)
			{
				this.pathToView = params.pathToView + (params.pathToView.indexOf("?") == -1 ? "?" : "&") + "IFRAME=Y";
			}
			if (params.tasksList)
			{
				for(var i = 0, count = params.tasksList.length; i < count; i++)
				{
					this.tasksList[i] = parseInt(params.tasksList[i]);
				}
			}
		}
	};


	TasksIFramePopup.prototype.init = function() {

		if (this.inited)
			return;

		this.inited = true;

		this.header = BX.create("div", {
			props : {className : "popup-window-titlebar"},
			html : '<table width="877" border="0" cellspacing="0" cellpadding="0"><tbody><tr><td align="left">&nbsp;</td><td width="13" style="padding-top: 2px;"><div class="tasks-iframe-close-icon">&nbsp;</div></td></tr></tbody></table>',
			style : {
				background : "#e8e8e8",
				height : "20px",
				padding : "5px 0px 5px 15px",
				borderRadius : "4px 4px 0px 0px"
			}
		});
		this.title = this.header.firstChild.tBodies[0].rows[0].cells[0];
		this.closeIcon = this.header.firstChild.tBodies[0].rows[0].cells[1].firstChild;
		this.closeIcon.onclick = BX.proxy(this.close, this);
		this.iframe = BX.create("iframe", {
			props : {
				scrolling : "no",
				frameBorder : "0"
			},
			style : {
				width : this.iframeWidth + "px",
				height : this.iframeHeight + "px",
				overflow : "hidden",
				border : "1px solid #fff",
				borderTop : "0px",
				borderRadius : "4px"
			}
		});
		this.prevTaskLink = BX.create("a", {props : {href : "javascript: void(0)", className : "tasks-popup-prev-slide"}, html : "<span></span>"});
		this.closeLink = BX.create("a", {props : {href : "javascript: void(0)", className : "tasks-popup-close"}, html : "<span></span>"});
		this.nextTaskLink = BX.create("a", {props : {href : "javascript: void(0)", className : "tasks-popup-next-slide"}, html : "<span></span>"});

		// Set nav
		this.prevTaskLink.onclick = BX.proxy(this.previous, this);
		this.nextTaskLink.onclick = BX.proxy(this.next, this);
		this.closeLink.onclick = BX.proxy(this.close, this);

		this.table = BX.create("table", {
			props : {className : "tasks-popup-main-table"},
			style : {
				top : this.topBottomMargin + "px"
			},
			children : [
				BX.create("tbody", {
					children : [
						BX.create("tr", {
							children : [
								this.prevTaskArea = BX.create("td", {
									props : {className : "tasks-popup-prev-slide-wrap"},
									children : [this.prevTaskLink]
								}),
								BX.create("td", {
									props : {
										id : 'tasks-crazy-heavy-cpu-usage-item',
										className : "tasks-popup-main-block-wrap tasks-popup-main-block-wrap-bg"
									},
									children : [
										BX.create("div", {
											props : {className : "tasks-popup-main-block-inner"},
											children : [this.header, this.iframe]
										})
									]
								}),
								this.nextTaskArea = BX.create("td", {
									props : {className : "tasks-popup-next-slide-wrap"},
									children : [this.closeLink, this.nextTaskLink]
								})
							]
						})
					]
				})
			]
		});

		this.overlay = document.body.appendChild(BX.create("div", {
			props : {
				className : "tasks-fixed-overlay"
			},
			children : [
				BX.create("div", {props : {className : "bx-task-dialog-overlay"}}),
				this.table
			]
		}));

		this.__adjustControls();

		BX.bind(window.top, "resize", BX.proxy(this.__onWindowResize, this));
	};

	TasksIFramePopup.prototype.view = function(taskId, tasksList) {
		this.init();
		if (tasksList)
		{
			this.currentList = [];
			for(var i = 0, count = tasksList.length; i < count; i++)
			{
				this.currentList[i] = parseInt(tasksList[i]);
			}
		}
		else
		{
			this.currentList = null;
		}
		BX.adjust(this.title, {text: BX.message("TASKS_TASK_NUM").replace("#TASK_NUM#", taskId)});
		this.currentTaskId = taskId;
		this.lastAction = "view";
		var isViewMode = true;
		this.load(this.pathToView.replace("#task_id#", taskId), isViewMode);
		this.show();
	};

	TasksIFramePopup.prototype.edit = function(taskId) {
		this.init();
		BX.adjust(this.title, {text: BX.message("TASKS_TITLE_EDIT_TASK").replace("#TASK_ID#", taskId)});
		this.currentTaskId = taskId;
		this.lastAction = "edit";
		this.load(this.pathToEdit.replace("#task_id#", taskId));
		this.show();
	};

	TasksIFramePopup.prototype.add = function(params) {
		this.init();
		BX.adjust(this.title, {text: BX.message("TASKS_TITLE_CREATE_TASK")});
		this.currentTaskId = 0;
		this.lastAction = "add";
		var url = this.pathToEdit.replace("#task_id#", 0) + '&UTF8encoded=1';
		this.descriptionBuffered = null;
		for(var name in params)
		{
			if ((name === 'DESCRIPTION') && (params[name].length > 1000))
				this.descriptionBuffered = params[name];
			else
				url += "&" + name + "=" + encodeURIComponent(params[name]);
		}

		this.load(url);
		this.show();
	};

	TasksIFramePopup.prototype.show = function() {
		BX.onCustomEvent(this, "onBeforeShow", []);
		this.overlay.style.display = "block";
		BX.addClass(document.body, "tasks-body-overlay");
		this.closeLink.style.display = 'none';		// This is first part of hack for Chrome due to bug http://jabber.bx/view.php?id=39643
		this.__onWindowResize();
		this.closeLink.style.display = 'block';		// This is last part of hack, I don't know how is it works, but it is.
		BX.bind(this.iframe.contentDocument ? this.iframe.contentDocument : this.iframe.contentWindow.document, "keypress", BX.proxy(this.__onKeyPress, this));
		BX.onCustomEvent(this, "onAfterShow", []);
	};

	TasksIFramePopup.prototype.close = function() {
		BX.onCustomEvent(this, "onBeforeHide", []);
		this.overlay.style.display = "none";
		BX.removeClass(document.body, "tasks-body-overlay");
		BX.unbind(this.iframe.contentDocument ? this.iframe.contentDocument : this.iframe.contentWindow.document, "keypress", BX.proxy(this.__onKeyPress, this));
		BX('tasks-crazy-heavy-cpu-usage-item').className = 'tasks-popup-main-block-wrap tasks-popup-main-block-wrap-bg';
		BX.onCustomEvent(this, "onAfterHide", []);
		/*if(history.replaceState)
		{
			history.replaceState({}, '', this.currentURL);
		}*/
	};

	TasksIFramePopup.prototype.previous = function() {
		var list = this.currentList ? this.currentList : this.tasksList;
		if (this.currentTaskId && list.length > 1)
		{
			var currentIndex = this.__indexOf(this.currentTaskId, list);
			if (currentIndex != -1)
			{
				if (currentIndex == 0)
				{
					var previousIndex = list.length - 1;
				}
				else
				{
					var previousIndex = currentIndex - 1;
				}

				this.view(list[previousIndex], list);
			}
		}
	};

	TasksIFramePopup.prototype.next = function() {
		var list = this.currentList ? this.currentList : this.tasksList;
		if (this.currentTaskId && list.length > 1)
		{
			var currentIndex = this.__indexOf(this.currentTaskId, list);
			if (currentIndex != -1)
			{
				if (currentIndex == list.length - 1)
				{
					var nextIndex = 0;
				}
				else
				{
					var nextIndex = currentIndex + 1;
				}

				this.view(list[nextIndex], list);
			}
		}
	};

	TasksIFramePopup.prototype.load = function(url, isViewMode)
	{
		this.isEditMode = true;
		if (isViewMode === true)
			this.isEditMode = false;

		var loc = this.iframe.contentWindow ? this.iframe.contentWindow.location : "";
		/*if(history.replaceState)
		{
			history.replaceState({}, '', url.replace("?IFRAME=Y", "").replace("&IFRAME=Y", ""))
		}*/

		this.__onUnload();
		this.iframe.src = url;
	};

	TasksIFramePopup.prototype.isOpened = function() {
		this.init();
		return this.overlay.style.display == "block";
	};

	TasksIFramePopup.prototype.isEmpty = function() {
		this.init();
		return this.iframe.contentWindow.location == "about:blank";
	};

	TasksIFramePopup.prototype.isLeftClick = function(event) {
		if (!event.which && event.button !== undefined)
		{
			if (event.button & 1)
				event.which = 1;
			else if (event.button & 4)
				event.which = 2;
			else if (event.button & 2)
				event.which = 3;
			else
				event.which = 0;
		}

		return event.which == 1 || (event.which == 0 && BX.browser.IsIE());
	};

	TasksIFramePopup.prototype.onTaskLoaded = function() {
		this.__onLoad();
	};

	TasksIFramePopup.prototype.onTaskAdded = function(task, action, params, newDataPack, legacyHtmlTaskItem) {
		this.tasksList.push(task.id);
		BX.onCustomEvent(this, "onTaskAdded", [task, action, params, newDataPack, legacyHtmlTaskItem]);
	};

	TasksIFramePopup.prototype.onTaskChanged = function(task, action, params, newDataPack, legacyHtmlTaskItem) {
		BX.onCustomEvent(this, "onTaskChanged", [task, action, params, newDataPack, legacyHtmlTaskItem]);
	};

	TasksIFramePopup.prototype.onTaskDeleted = function(taskId) {
		BX.onCustomEvent(this, "onTaskDeleted", [taskId]);
	};

	TasksIFramePopup.prototype.__onKeyPress = function(e) {
		if (!e) e = window.event;
		if(e.keyCode == 27)
		{
			// var params = {
			// 	canClose : true
			// };

			// BX.onCustomEvent(this, "onBeforeCloseByEscape", [params]);


			//if (params.canClose)

			if (
				(this.lastAction === 'view')
				|| confirm(BX.message('TASKS_CONFIRM_CLOSE_CREATE_DIALOG'))
			)
			{
				this.close();
			}
		}
	};

	TasksIFramePopup.prototype.__indexOf = function(needle, haystack) {
		for(var i = 0, count = haystack.length; i < count; i++) {
			if (needle == haystack[i])
			{
				return i;
			}
		}

		return -1;
	};

	TasksIFramePopup.prototype.__onMouseMove = function(e)
	{
		if (!e)
			e = this.iframe.contentWindow.event;

		var innerDoc = (this.iframe.contentDocument) ? this.iframe.contentDocument : this.iframe.contentWindow.document;

		if (innerDoc && innerDoc.body)
		{
			innerDoc.body.onbeforeunload = BX.proxy(this.__onUnload, this);

			if (this.iframe.contentDocument)
				this.iframe.contentDocument.body.onbeforeunload = BX.proxy(this.__onBeforeUnload, this);

			innerDoc.body.onunload = BX.proxy(this.__onUnload, this);

			var eTarget = e.target || e.srcElement;
			if (eTarget)
			{
				eTargetA = false;
				if (eTarget && eTarget.tagName == "SPAN")
				{
					var oTmp = BX.findParent(eTarget);
					if ((oTmp !== null) && (oTmp.tagName == 'A'))
						eTargetA = oTmp;
				}
				else
					eTargetA = eTarget;

				if (eTargetA.tagName == "A" && eTargetA.href)
				{
					if (eTargetA.href.substr(0, 11) == "javascript:")
					{
						innerDoc.body.onbeforeunload = null;
						innerDoc.body.onunload = null;
					}
					else if (
						(eTargetA.href.indexOf("IFRAME=Y") == -1) 
						&& (eTargetA.href.indexOf("/show_file.php?fid=") == -1)
						&& (eTargetA.target !== '_blank')
					)
					{
						eTargetA.target = "_top";
					}
				}
			}
		}
	};

	TasksIFramePopup.prototype.__onLoad = function() {
		if (!this.isEmpty())
		{
			var innerDoc = (this.iframe.contentDocument) ? this.iframe.contentDocument : this.iframe.contentWindow.document;

			if (innerDoc && innerDoc.body)
			{
				if (BX('tasks-crazy-heavy-cpu-usage-item'))
					BX('tasks-crazy-heavy-cpu-usage-item').className = 'tasks-popup-main-block-wrap';

				this.loading = false;

				innerDoc.body.onmousemove = BX.proxy(this.__onMouseMove, this);

				if (!innerDoc.getElementById("task-reminder-link"))
				{
					window.top.location = innerDoc.location.href.replace("?IFRAME=Y", "").replace("&IFRAME=Y", "").replace("&CALLBACK=CHANGED", "").replace("&CALLBACK=ADDED", "");
				}
				lastSrc = this.iframe.contentWindow.location.href;
				BX.bind(innerDoc, "keyup", BX.proxy(this.__onKeyPress, this));
				this.iframe.style.height = innerDoc.getElementById("tasks-content-outer").offsetHeight + "px";
				this.iframe.style.visibility = "visible";
				this.iframe.contentWindow.focus();

				this.__onWindowResize();
			}

			if (resizeInterval)
				clearInterval(resizeInterval);

			resizeInterval = setInterval(BX.proxy(this.__onContentResize, this), 300);
		}
	};

	TasksIFramePopup.prototype.__onBeforeUnload = function(e)
	{
	};

	TasksIFramePopup.prototype.__onUnload = function(e) {
		if (!e) e = window.event;
		if (!this.loading)
		{
			this.loading = true;
			this.iframe.style.visibility = "hidden";
			clearInterval(resizeInterval);
		}
	};

	TasksIFramePopup.prototype.__onContentResize = function(){
		if (this.isOpened())
		{
			var innerDoc = (this.iframe.contentDocument) ? this.iframe.contentDocument : this.iframe.contentWindow.document;
			if (innerDoc && innerDoc.body)
			{
				var mainContainerHeight = innerDoc.getElementById("tasks-content-outer");
				if (mainContainerHeight)
				{
					var iframeScrollHeight = this.__getWindowScrollHeight(innerDoc);
					var innerSize = BX.GetWindowInnerSize(innerDoc);

					var realHeight = 0;
					if (iframeScrollHeight > innerSize.innerHeight)
						realHeight = iframeScrollHeight - 1;
					else
						realHeight = mainContainerHeight.offsetHeight;//innerDoc.documentElement.scrollHeight;//this.heightDiv ? this.heightDiv.scrollTop + 15 : 0;

					var loc = this.iframe.contentWindow ? this.iframe.contentWindow.location : '';

					if (loc.toString)
						loc = loc.toString();

					if (
						(realHeight != lastheight)
						|| (this.prevIframeSrc != loc)
					)
					{
						lastheight = realHeight;
						this.prevIframeSrc = loc;
						this.iframe.style.height = realHeight + "px";
						this.__onWindowResize();
					}
				}
			}
		}
	};

	TasksIFramePopup.prototype.__getWindowScrollHeight = function(pDoc)
	{
		var height;
		if (!pDoc)
			pDoc = document;

		if ( (pDoc.compatMode && pDoc.compatMode == "CSS1Compat") && !BX.browser.IsSafari())
		{
			height = pDoc.documentElement.scrollHeight;
		}
		else
		{
			if (pDoc.body.scrollHeight > pDoc.body.offsetHeight)
				height = pDoc.body.scrollHeight;
			else
				height = pDoc.body.offsetHeight;
		}
		return height;
	};

	TasksIFramePopup.prototype.__onWindowResize = function(){
		var size = BX.GetWindowInnerSize();
		this.overlay.style.height = size.innerHeight + "px";
		this.overlay.style.width = size.innerWidth + "px";
		var scroll = BX.GetWindowScrollPos();
		this.overlay.style.top = scroll.scrollTop + "px";
		if (BX.browser.IsIE() && !BX.browser.IsIE9())
		{
			this.table.style.width = (size.innerWidth - 20) + "px";
		}
		this.overlay.firstChild.style.height = Math.max(this.iframe.offsetHeight + this.topBottomMargin * 2 + 31, this.overlay.clientHeight) + "px";
		this.overlay.firstChild.style.width = Math.max(1024, this.overlay.clientWidth) + "px";

		this.prevTaskArea.style.width = Math.max(0, Math.max(1024, this.overlay.clientWidth) / 2) + "px";
		this.nextTaskArea.style.width = this.prevTaskArea.style.width;

		this.__adjustControls();
	};

	TasksIFramePopup.prototype.__adjustControls = function(){
		if (this.lastAction != "view" || ((!this.currentList || this.currentList.length <= 1 || this.__indexOf(this.currentTaskId, this.currentList) == -1) && (this.tasksList.length <= 1 || this.__indexOf(this.currentTaskId, this.tasksList) == -1)))
		{
			this.nextTaskLink.style.display = this.prevTaskLink.style.display = "none";
		}
		else
		{
			if(!BX.browser.IsDoctype() && BX.browser.IsIE())
			{
				this.nextTaskLink.style.height = this.prevTaskLink.style.height = document.documentElement.offsetHeight + "px";
				this.prevTaskLink.style.width = (this.prevTaskLink.parentNode.clientWidth - 1) + 'px';
				this.nextTaskLink.style.width = (this.nextTaskLink.parentNode.clientWidth - 1) + 'px';
			}
			else
			{
				this.nextTaskLink.style.height = this.prevTaskLink.style.height = document.documentElement.clientHeight + "px";
				this.prevTaskLink.style.width = this.prevTaskLink.parentNode.clientWidth + 'px';
				this.nextTaskLink.style.width = this.nextTaskLink.parentNode.clientWidth + 'px';
			}
			this.prevTaskLink.firstChild.style.left = (this.prevTaskLink.parentNode.clientWidth * 4 / 10) + 'px';
			this.nextTaskLink.firstChild.style.right = (this.nextTaskLink.parentNode.clientWidth * 4 / 10) + 'px';
			this.nextTaskLink.style.display = this.prevTaskLink.style.display = "";
		}
		this.closeLink.style.width = this.closeLink.parentNode.clientWidth + 'px';
	};
})(window);


(function(){
	if (BX.TasksTimerManager)
		return;

	BX.TasksTimerManager = {
		popup : null,
		onTimeManDataRecievedEventDetected : false
	};


	BX.TasksTimerManager.reLoadInitTimerDataFromServer = function()
	{
		var updated = true;

		// This will run onTimeManDataRecieved/onPlannerDataRecieved 
		// and after it init_timer_data event
		if (window.BXTIMEMAN)
			window.BXTIMEMAN.Update(true);
		else if (window.BXPLANNER && window.BXPLANNER.update)
			window.BXPLANNER.update();
		else
			updated = false;

		if (window.top !== window)
		{
			if (window.top.BXTIMEMAN)
				window.top.BXTIMEMAN.Update(true);
			else if (window.top.BXPLANNER && window.top.BXPLANNER.update)
				window.top.BXPLANNER.update();
		}

		return (updated);
	};


	BX.TasksTimerManager.start = function(taskId)
	{
		BX.CJSTask.batchOperations(
			[{
				operation : 'CTaskTimerManager::getLastTimer()'
			}],
			{
				callbackOnSuccess : (function(taskId){
					return function(data)
					{
						// some other task on timer?
						if (
							(data.rawReply.data[0].returnValue)
							&& (data.rawReply.data[0].returnValue.TASK_ID > 0)
							&& (data.rawReply.data[0].returnValue.TIMER_STARTED_AT > 0)
							&& (taskId != data.rawReply.data[0].returnValue.TASK_ID)
						)
						{
							BX.CJSTask.batchOperations(
								[{
									operation : 'CTaskItem::getTaskData()',
									taskData  : {
										ID : data.rawReply.data[0].returnValue.TASK_ID
									}
								}],
								{
									callbackOnSuccess : (function(taskId){
										return function(data)
										{
											if (
												(data.rawReply.data[0].returnValue.ID)
												&& (taskId != data.rawReply.data[0].returnValue.ID)
											)
											{
												BX.TasksTimerManager.__showConfirmPopup(
													data.rawReply.data[0].returnValue.ID,
													data.rawReply.data[0].returnValue.TITLE,
													(function(taskId){
														return function(bConfirmed)
														{
															if (bConfirmed)
																BX.TasksTimerManager.__doStart(taskId);
														}
													})(taskId)
												);
											}
										};
									})(taskId),
									callbackOnFailure : (function(taskId){
										return function(data)
										{
											// probably task not exists or not accessible
											BX.TasksTimerManager.__doStart(taskId);
										};
									})(taskId)
								},
								true	// sync
							);
						}
						else
							BX.TasksTimerManager.__doStart(taskId);
					}
				})(taskId)
			},
			true	// sync
		);
	};


	BX.TasksTimerManager.stop = function(taskId)
	{
		var oTaskTimer = new BX.CJSTask.TimerManager(taskId);

		oTaskTimer.stop({
			callbackOnSuccess : function(data)
			{
				if (data.status === 'success')
				{
					BX.onCustomEvent(
						window,
						'onTaskTimerChange',
						[{
							module           : 'tasks',
							action           : 'stop_timer',
							taskId           :  data.rawReply.data[0].requestedTaskId,
							taskData         :  data.rawReply.data[1].returnValue,
							timerData        :  data.rawReply.data[2].returnValue
						}]
					);
				}
			}
		});
	};


	BX.TasksTimerManager.__doStart = function(taskId)
	{
		var oTaskTimer = new BX.CJSTask.TimerManager(taskId);
		oTaskTimer.start({
			callbackOnSuccess : function(data)
			{
				if (data.status === 'success')
				{
					BX.onCustomEvent(
						window,
						'onTaskTimerChange',
						[{
							module    : 'tasks',
							action    : 'start_timer',
							taskId    :  data.rawReply.data[0].requestedTaskId,
							taskData  :  data.rawReply.data[1].returnValue,
							timerData :  data.rawReply.data[2].returnValue
						}]
					);
				}
			}
		});
	};


	BX.TasksTimerManager.__showConfirmPopup = function(taskId, taskName, callback)
	{
		if (this.popup)
		{
			this.popup.close();
			this.popup.destroy();
		}

		var message = BX.message('TASKS_TASK_CONFIRM_START_TIMER');
		message = message.replace('{{TITLE}}', BX.util.htmlspecialchars(taskName));

		var winTitle = BX.create(
			'span',
			{ html : BX.message('TASKS_TASK_CONFIRM_START_TIMER_TITLE') }
		);

		BX.Tasks.confirm(message, BX.delegate(function(result){
			callback(result);
		}, this), {title: winTitle});
	};


	BX.TasksTimerManager.refreshDaemon = new function()
	{
		this.data = null;


		this.onTick = function()
		{
			if (this.data !== null)
			{
				var JS_UNIX_TIMESTAMP = Math.round((new Date()).getTime() / 1000);
				this.data.TIMER.RUN_TIME = JS_UNIX_TIMESTAMP - this.data.TIMER.TIMER_STARTED_AT - this.data.UNIX_TIMESTAMP_DELTA;

				BX.onCustomEvent(
					window,
					'onTaskTimerChange',
					[{
						action : 'refresh_daemon_event',
						taskId : this.data.TIMER.TASK_ID,
						data   : this.data
					}]
				);
			}
		};

		BX.ready(
			(function(self){
				return function(){
					BX.CJSTask.setTimerCallback(
						'tasks_timer_refresh_daemon_event',
						(function(self){
							return function(){
								self.onTick();
							}
						})(self),
						1024
					);
				}
			})(this)
		);

		this.catchTimerChange = function(params)
		{
			if (params.module !== 'tasks')
				return;

			if (params.action === 'refresh_daemon_event')
			{
				return;
			}
			else if (params.action === 'stop_timer')
			{
				this.data = null;

				// This will transfer data through browsers tabs
				BX.TasksTimerManager.reLoadInitTimerDataFromServer();
			}
			else if (params.action === 'start_timer')
			{
				if (
					( ! (params.timerData && params.timerData.USER_ID) )
					|| (params.timerData.TASK_ID != params.taskData.ID)
				)
				{
					// We cannot work with this data
					this.data = null;
					return;
				}

				if (params.timerData.TIMER_STARTED_AT == 0)
				{
					// Task on pause
					this.data = null;
					return;
				}

				var UNIX_TIMESTAMP_DELTA = 0;
				var JS_UNIX_TIMESTAMP    = Math.round((new Date()).getTime() / 1000);
				var RUN_TIME             = parseInt(params.timerData.RUN_TIME);
				var TIME_SPENT_IN_LOGS   = parseInt(params.taskData.TIME_SPENT_IN_LOGS);
				var TIMER_STARTED_AT     = parseInt(params.timerData.TIMER_STARTED_AT);

				if (isNaN(RUN_TIME))
					RUN_TIME = 0;

				if (isNaN(TIME_SPENT_IN_LOGS))
					TIME_SPENT_IN_LOGS = 0;

				if (TIMER_STARTED_AT > 0)
					UNIX_TIMESTAMP_DELTA = JS_UNIX_TIMESTAMP - TIMER_STARTED_AT - RUN_TIME;

				this.data = {
					TIMER : {
						TASK_ID          : parseInt(params.timerData.TASK_ID),
						USER_ID          : parseInt(params.timerData.USER_ID),
						TIMER_STARTED_AT : TIMER_STARTED_AT,
						RUN_TIME         : RUN_TIME
					},
					TASK : {
						ID                  : params.taskData.ID,
						TITLE               : params.taskData.TITLE,
						TIME_SPENT_IN_LOGS  : TIME_SPENT_IN_LOGS,
						TIME_ESTIMATE       : parseInt(params.taskData.TIME_ESTIMATE),
						ALLOW_TIME_TRACKING : params.taskData.ALLOW_TIME_TRACKING
					},
					UNIX_TIMESTAMP_DELTA : UNIX_TIMESTAMP_DELTA
				};

				// This will transfer data through browsers tabs
				BX.TasksTimerManager.reLoadInitTimerDataFromServer();
			}
			else if (params.action === 'init_timer_data')
			{
				if (
					( ! (params.data.TIMER && params.data.TIMER.USER_ID) )
					|| (params.data.TIMER.TASK_ID != params.data.TASK.ID)
				)
				{
					// We cannot work with this data
					this.data = null;
					return;
				}

				if (params.data.TIMER.TIMER_STARTED_AT == 0)
				{
					// Task on pause
					this.data = null;
					return;
				}

				var UNIX_TIMESTAMP_DELTA = 0;
				var JS_UNIX_TIMESTAMP    = Math.round((new Date()).getTime() / 1000);
				var RUN_TIME             = parseInt(params.data.TIMER.RUN_TIME);
				var TIME_SPENT_IN_LOGS   = parseInt(params.data.TASK.TIME_SPENT_IN_LOGS);
				var TIMER_STARTED_AT     = parseInt(params.data.TIMER.TIMER_STARTED_AT);

				if (isNaN(RUN_TIME))
					RUN_TIME = 0;

				if (isNaN(TIME_SPENT_IN_LOGS))
					TIME_SPENT_IN_LOGS = 0;

				if (TIMER_STARTED_AT > 0)
					UNIX_TIMESTAMP_DELTA = JS_UNIX_TIMESTAMP - TIMER_STARTED_AT - RUN_TIME;

				this.data = {
					TIMER : {
						TASK_ID          : parseInt(params.data.TIMER.TASK_ID),
						USER_ID          : parseInt(params.data.TIMER.USER_ID),
						TIMER_STARTED_AT : TIMER_STARTED_AT,
						RUN_TIME         : RUN_TIME
					},
					TASK : {
						ID                  : params.data.TASK.ID,
						TITLE               : params.data.TASK.TITLE,
						TIME_SPENT_IN_LOGS  : TIME_SPENT_IN_LOGS,
						TIME_ESTIMATE       : parseInt(params.data.TASK.TIME_ESTIMATE),
						ALLOW_TIME_TRACKING : params.data.TASK.ALLOW_TIME_TRACKING
					},
					UNIX_TIMESTAMP_DELTA : UNIX_TIMESTAMP_DELTA
				};
			}
		};

		BX.addCustomEvent(
			window,
			'onTaskTimerChange',
			(function(self){
				return function(params){
					self.catchTimerChange(params);
				};
			})(this)
		);
	};

	BX.TasksTimerManager.onDataRecieved = function(PLANNER)
	{
		var RUN_TIME = 0;
		var reply = { TIMER : false, TASK : false };

		if ( ! PLANNER )
			return;

		if (PLANNER.TASKS_TIMER)
		{
			if (parseInt(PLANNER.TASKS_TIMER.TIMER_STARTED_AT) > 0)
				RUN_TIME = Math.round((new Date()).getTime() / 1000) - parseInt(PLANNER.TASKS_TIMER.TIMER_STARTED_AT);

			if (RUN_TIME < 0)
				RUN_TIME = 0;

			reply.TIMER = {
				TASK_ID          : PLANNER.TASKS_TIMER.TASK_ID,
				USER_ID          : PLANNER.TASKS_TIMER.USER_ID,
				TIMER_STARTED_AT : PLANNER.TASKS_TIMER.TIMER_STARTED_AT,
				RUN_TIME         : RUN_TIME
			};
		}

		if (PLANNER.TASK_ON_TIMER)
		{
			reply.TASK = {
				ID                  : PLANNER.TASK_ON_TIMER.ID,
				TITLE               : PLANNER.TASK_ON_TIMER.TITLE,
				STATUS              : PLANNER.TASK_ON_TIMER.STATUS,
				TIME_SPENT_IN_LOGS  : PLANNER.TASK_ON_TIMER.TIME_SPENT_IN_LOGS,
				TIME_ESTIMATE       : PLANNER.TASK_ON_TIMER.TIME_ESTIMATE,
				ALLOW_TIME_TRACKING : PLANNER.TASK_ON_TIMER.ALLOW_TIME_TRACKING
			};
		}

		BX.onCustomEvent(
			window,
			'onTaskTimerChange',
			[{
				action : 'init_timer_data',
				module : 'tasks',
				data   :  reply
			}]
		);
	};

	BX.addCustomEvent(
		window,
		'onTimeManDataRecieved',
		function(data){
			BX.TasksTimerManager.onTimeManDataRecievedEventDetected = true;
			if (data.PLANNER)
				BX.TasksTimerManager.onDataRecieved(data.PLANNER);
		}
	);

	BX.addCustomEvent(
		window,
		'onPlannerDataRecieved',
		function(obPlanner, data){
			if (BX.TasksTimerManager.onTimeManDataRecievedEventDetected === false)
				BX.TasksTimerManager.onDataRecieved(data);
		}
	);
})();

