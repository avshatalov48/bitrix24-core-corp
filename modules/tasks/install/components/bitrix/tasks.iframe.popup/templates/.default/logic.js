BX.namespace('Tasks.Component');

/**
 * This template only used when we open task in the classic old-style centered popup
 */

// no widget inheritance here, make it as fast as possible
BX.Tasks.Component.IframePopup = function(opts)
{
	this.opts = BX.merge({
	}, opts);
	this.vars = {
		skip: true,
		callbacks: {},
		resizeInterval: false,
		resizeLock: true,
		lastHeight: false
	};
	this.sys = {
		scope: null
	};
	this.instances = {
		win: false
	};
	this.ctrls = {
		iframe: null,
		wrap: null,
		close: null
	};

	this.setCallbacks(opts.callbacks);
	this.bindEvents();
};

BX.mergeEx(BX.Tasks.Component.IframePopup.prototype, {
	add: function(params)
	{
		this.edit(0, params);
	},
	view: function(taskId)
	{
		this.open('view', taskId);
	},
	edit: function(taskId, params)
	{
		this.open('edit', taskId, {urlParams: params});
	},
	open: function(action, taskId, parameters)
	{
		taskId = parseInt(taskId);
		if(isNaN(taskId) || taskId < 0)
		{
			return;
		}

		parameters = parameters || {};

		var path = this.getPath(action, taskId, parameters.urlParams);

		if(BX.Bitrix24 && 'PageSlider' in BX.Bitrix24)
		{
			BX.Bitrix24.PageSlider.open(path);
		}
		else
		{
			this.toggleLoading(true);
			this.getWindow().show();

			this.getWindow().setBindElement(this.getWindowCoords());
			this.getWindow().adjustPosition();

			this.getIframe().src = path;
		}
	},
	close: function()
	{
		this.getWindow().close();
	},
	bindEvents: function()
	{
		// track resize
		BX.bind(window, 'resize', BX.throttle(this.onWindowResize, 100, this));

		// track content loading\unloading, see "wrap" template
		BX.addCustomEvent(window, 'tasksIframeLoad', this.onContentLoaded.bind(this));
		BX.addCustomEvent(window, 'tasksIframeUnload', this.onContentUnLoaded.bind(this));
	},
	bindInnerDocumentEvents: function()
	{
		var innerDoc = this.getContentDocument();
		if(innerDoc)
		{
			BX.bind(innerDoc, 'keydown', this.onInnerDocumentKeyDown.bind(this));
		}
	},
	getIframe: function()
	{
		if (this.ctrls.iframe === null)
		{
			this.ctrls.iframe = BX.create('iframe', {
				attrs: { scrolling: 'no', frameBorder: '0'}
			});
		}

		return this.ctrls.iframe;
	},

	getWindow: function()
	{
		if(this.instances.win === false)
		{
			this.instances.win = new BX.PopupWindow('tasks-iframe-popup', {top: 0, left: 0}, {
				autoHide : false,
				closeByEsc : true,
				content : this.getIframeContainer(),
				overlay: true,
				lightShadow: false, // rounded corners = off
				closeIcon: true,
				contentNoPaddings: true,
				draggable: false, // when mouse is over iframe, onmouseover doesnt fire, so it causes drag error
				titleBar: true,
				events: {
					onPopupClose: BX.delegate(this.onPopupClose, this),
					onPopupShow: BX.delegate(this.onPopupOpen, this)
				}
			});

			this.ctrls.close = BX.create('div', {
				props: {
					className: "hidden"
				},
				attrs: {
					id: "tasks-popup-close",
					title: BX.message("TASKS_TIP_COMPONENT_TEMPLATE_CLOSE_WINDOW")
				},
				events: {
					click: BX.delegate(this.onCloseClicked, this)
				},
				children: [
					BX.create("span")
				]
			});

			BX.insertAfter(this.ctrls.close, BX('popup-window-overlay-tasks-iframe-popup'));
		}

		return this.instances.win;
	},
	setTitle: function(action, taskId)
	{
		var title = '';

		if(action != false)
		{
			action = action == 'view' ? 'VIEW' : 'EDIT';

			taskId = parseInt(taskId);
			if(isNaN(taskId) || taskId <= 0)
			{
				taskId = 0;
			}

			if(action == 'EDIT' && taskId == 0)
			{
				action = 'NEW';
			}

			title = BX.message('TASKS_TIP_COMPONENT_TEMPLATE_'+action+'_TASK_TITLE');

			if(taskId > 0)
			{
				title = title.replace('#TASK_ID#', taskId);
			}
		}

		this.getWindow().setTitleBar(title);
	},
	getPath: function(action, taskId, urlParams)
	{
		action = action == 'view' ? 'view' : 'edit';
		taskId = parseInt(taskId);

		var path = this.opts.pathToTasks.replace('#task_id#', taskId);
		path = path + (path.indexOf("?") == -1 ? "?" : "&") + "IFRAME=Y";

		if(BX.type.isPlainObject(urlParams))
		{
			for(var k in urlParams)
			{
				path += '&'+k+'='+encodeURIComponent(urlParams[k]);
			}
		}

		path = path.replace('#action#', action);

		return path;
	},

	getWindowCoords: function()
	{
		var popupWidth = BX.pos(this.getIframeContainer()).width;

		var windowWidth = BX.GetWindowSize().innerWidth;
		var windowScrollTop = BX.GetWindowScrollPos().scrollTop;

		return {left: Math.floor((windowWidth - popupWidth) / 2), top: (30 + windowScrollTop)};
	},

	// get document inside iframe
	getContentDocument: function()
	{
		var iFrame = this.getIframe();

		var innerDoc = null;
		if(iFrame.contentDocument)
		{
			innerDoc = iFrame.contentDocument;
		}
		if(iFrame.contentWindow)
		{
			innerDoc = iFrame.contentWindow.document;
		}

		return innerDoc && innerDoc.body ? innerDoc : null;
	},

	// get container that wraps iframe
	getIframeContainer: function()
	{
		if (this.ctrls.wrap === null)
		{
			this.ctrls.wrap = this.ctrls.wrap = BX.create('div', {
				props: { className: 'tasks-iframe-wrap loading fixedHeight'},
				attrs: { id: 'tasks-iframe-wrap' },
				children: [ this.getIframe() ]
			});
		}

		return this.ctrls.wrap;
	},

	// get direct content container inside iframe document
	getContentContainer: function()
	{
		var innerDoc = this.getContentDocument();

		if(innerDoc)
		{
			return innerDoc.getElementById("tasks-content-outer"); // see tasks.iframe.popup "wrap" template for "tasks-content-outer" node
		}

		return null;
	},

	onCloseClicked: function()
	{
		this.getWindow().close();
	},

	onTaskGlobalEvent: function(eventType, params)
	{
		if(BX.type.isNotEmptyString(eventType))
		{
			var cbAction = eventType.toString().toUpperCase();

			params = params || {};
			params.task = params.task || {};
			params.options = params.options || {};

			var args = [];
			var taskId = parseInt(params.task.ID);

			if (cbAction == 'DELETE' && !isNaN(taskId) && taskId)
			{
				args.push(params.task.ID);
			}
			else if(cbAction == 'ADD' || cbAction == 'UPDATE')
			{
				// todo: normal task should go here, but still unsure what it should look like: just PhpToJsObject() result, or
				// some kind of upper-level abstraction that supports both normal format and legacy-ugly
				// currently only legacy backward-compatibility object here

				if(params.taskUgly)
				{
					args.push(params.taskUgly);
				}
				else
				{
					// dont fire on incorrect arguments, because most of event handlers DOES NOT check event data
					return;
				}
			}

			if (!params.options.STAY_AT_PAGE)
			{
				this.close();
			}

			if(typeof this.vars.callbacks[cbAction] != 'undefined' && this.vars.callbacks[cbAction] !== false)
			{
				var fn = this.vars.callbacks[cbAction];
				if(BX.type.isString(fn))
				{
					// if fn is string, it must be a callback name. so lets dereference it relative to the window object
					fn = BX.Tasks.deReference(fn, window);
				}

				if(BX.type.isFunction(fn))
				{
					fn.apply(window, args);
				}
			}
		}
	},

	onContentLoaded: function()
	{
		var doc = this.getContentDocument();
		if(doc)
		{
			var pair = this.parseUrl(doc.location.pathname);
			if(pair)
			{
				this.setTitle(pair.action, pair.taskId);
			}
		}

		this.toggleLoading(false);
		this.startMonitorContent();

		this.bindInnerDocumentEvents();
	},

	onContentUnLoaded: function()
	{
		this.setTitle(false);
		this.stopMonitorContent();
	},

	onPopupOpen: function()
	{
		BX.toggleClass(this.ctrls.close, 'hidden');
		this.toggleLoading(true);
	},
	onPopupClose: function()
	{
		BX.toggleClass(this.ctrls.close, 'hidden');

		this.lockHeight();
		this.stopMonitorContent();
		this.toggleLoading(true);
		this.vars.lastHeight = false;
		this.getIframe().src = "about:blank";
	},

	onWindowResize: function()
	{
		if(this.getWindow().isShown())
		{
			this.getWindow().setBindElement(this.getWindowCoords());
		}
	},

	onContentResize: function()
	{
		if(this.getWindow().isShown() && !this.vars.resizeLock)
		{
			var innerDoc = this.getContentDocument();
			if (innerDoc)
			{
				var content = this.getContentContainer();
				if (content)
				{
					//var scrollHeight = Math.max(
					//	innerDoc.body.scrollHeight, innerDoc.documentElement.scrollHeight,
					//	innerDoc.body.offsetHeight, innerDoc.documentElement.offsetHeight,
					//	innerDoc.body.clientHeight, innerDoc.documentElement.clientHeight
					//);
                    //
					//var innerSize = BX.GetWindowInnerSize(innerDoc);
					//var realHeight = 0;
                    //
					//if (scrollHeight > innerSize.innerHeight)
					//{
					//	realHeight = scrollHeight - 1;
					//}
					//else
					//{
					//	realHeight = content.offsetHeight;
					//}

					var realHeight = content.offsetHeight;

					if (realHeight != this.vars.lastHeight)
					{
						this.getIframeContainer().style.height = realHeight + "px";
						this.vars.lastHeight = realHeight;

						this.unLockHeight();
					}

					this.getWindow().popupContainer.style.marginBottom = "40px";
					this.getWindow().resizeOverlay();
				}
			}
		}
	},

	onInnerDocumentKeyDown: function(e)
	{
		if(BX.Tasks.Util.isEsc(e))
		{
			this.close();
		}
	},
	
	lockHeight: function()
	{
		this.toggleFixedHeight(true);
	},

	unLockHeight: function()
	{
		this.toggleFixedHeight(false);
	},

	toggleFixedHeight: function(way)
	{
		BX[way ? 'addClass' : 'removeClass'](this.getIframeContainer(), 'fixedHeight');
	},

	toggleLoading: function(way)
	{
		BX[way ? 'addClass' : 'removeClass'](this.getIframeContainer(), 'loading');
	},

	stopMonitorContent: function()
	{
		this.vars.resizeLock = true;
	},

	startMonitorContent: function()
	{
		this.vars.resizeLock = false;
		if(this.vars.resizeInterval === false)
		{
			this.vars.resizeInterval = setInterval(BX.proxy(this.onContentResize, this), 300);
		}
	},

	setCallbacks: function(callbacks)
	{
		if(BX.type.isPlainObject(callbacks))
		{
			BX.Tasks.each(callbacks, function(callback, k){

				if(callback == '#SHOW_ADDED_TASK_DETAIL#')
				{
					return;
				}

				/*
				if(false && k == 'ADD' && callback == '#SHOW_ADDED_TASK_DETAIL#')
				{
					callback = function(task, action, params)
					{
						var skipShow = false;

						if (
							params
							&& (params.multipleTasksAdded === true)
							&& (params.firstTask === false)
						)
						{
							skipShow = true;
						}

						if ( ! skipShow )
						{
							BX.Tasks.Singletons.iframePopup.view(task.id);
						}
					}
				}
				*/

				if(callback !== false && (BX.type.isFunction(callback) || BX.type.isNotEmptyString(callback)))
				{
					this.vars.callbacks[k] = callback;
				}

			}.bind(this));
		}
	},

	showCreateForm: function()
	{
		this.add();
	},

	parseUrl: function(url)
	{
		var path = this.opts.pathToTasks;
		if(path)
		{
			path = path.toLowerCase().replace('#action#', '(view|edit){1}').replace('#task_id#', '(\\d+)');
			var found = url.match(new RegExp(path));
			if(found && BX.type.isArray(found))
			{
				var action = found[1] || false;
				var taskId = found[2] || false;

				if(action && taskId)
				{
					return {action: action, taskId: parseInt(taskId)};
				}
			}
		}

		return null;
	},

	// these methods are only for compatibility with the gantt
	onTaskAdded: function(task, action, params, newDataPack, legacyHtmlTaskItem) {
		BX.onCustomEvent(this, "onTaskAdded", [task, action, params, newDataPack, legacyHtmlTaskItem]);
	},

	onTaskChanged: function(task, action, params, newDataPack, legacyHtmlTaskItem) {
		BX.onCustomEvent(this, "onTaskChanged", [task, action, params, newDataPack, legacyHtmlTaskItem]);
	},

	onTaskDeleted: function(taskId) {
		BX.onCustomEvent(this, "onTaskDeleted", [taskId]);
	}
});

BX.Tasks.Component.IframePopup.create = function(opts)
{
	// dont allow to init iframe.popup inside another iframe.popup
	if(window.top != window)
	{
		return;
	}

	if(typeof BX.Tasks.Singletons == 'undefined')
	{
		BX.Tasks.Singletons = {};
	}

	if(typeof BX.Tasks.Singletons.iframePopup == 'undefined')
	{
		BX.Tasks.Singletons.iframePopup = new BX.Tasks.Component.IframePopup(opts);

		// it has many names... (just for compatibility)
		window.taskIFramePopup = BX.Tasks.Singletons.iframePopup;
		window.BX.TasksIFrameInst = BX.Tasks.Singletons.iframePopup;
	}
	else
	{
		// update callback set
		BX.Tasks.Singletons.iframePopup.setCallbacks(opts.callbacks);
	}

	return BX.Tasks.Singletons.iframePopup;
};