;(function(){

if(!!window.BX.CTasksPlannerHandler)
	return;

var
	BX = window.BX,
	TASK_SUFFIXES = {"-1": "overdue", "-2": "new", 1: "new", 2: "accepted", 3: "in-progress", 4: "waiting", 5: "completed", 6: "delayed", 7: "declined"},
	PLANNER_HANDLER = null;

BX.addTaskToPlanner = function(taskId)
{
	PLANNER_HANDLER.addTask({id:taskId});
}

BX.CTasksPlannerHandler = function()
{
	this.TASKS = null;
	this.TASKS_LIST = null;
	this.ADDITIONAL = {};
	this.MANDATORY_UFS = null;

	this.TASK_CHANGES = {add: [], remove: []};
	this.TASK_CHANGES_TIMEOUT = null;

	this.TASKS_WND = null;

	this.DATA_TASKS = null;

	this.PLANNER = null;

	this.taskTimerSwitch = false;
	this.timerTaskId = 0;

	this.onTimeManDataRecievedEventDetected = false;

	BX.addCustomEvent('onPlannerDataRecieved', BX.proxy(this.draw, this));
	BX.addCustomEvent("onTaskTimerChange", BX.proxy(this.onTaskTimerChange, this));
};

BX.CTasksPlannerHandler.prototype.formatTime = function(ts, bSec)
{
	var pad = '00';
	var hours = '';
	var minutes = '';
	var seconds = '' + ts % 60;

	var partSign = ['', '', ''];

	if (ts >= 0)
	{
		hours += Math.floor(ts / 3600);
		minutes += Math.floor(ts / 60) % 60;

		var timeParts = [hours, minutes, seconds];
	}
	else
	{
		hours += Math.ceil(ts / 3600);
		minutes += Math.ceil(ts / 60) % 60;

		timeParts = [hours, minutes, seconds];

		Object.keys(timeParts).forEach(function(key)
		{
			timeParts[key] = timeParts[key].replace('-', '');
			if (timeParts[key] !== '0')
			{
				partSign[key] = '-';
			}
		});
	}

	var time = partSign[0] + pad.substring(0, 2 - timeParts[0].length) + timeParts[0] +
		':' + partSign[1] + pad.substring(0, 2 - timeParts[1].length) + timeParts[1];

	if (bSec)
	{
		time += ':' + partSign[2] + pad.substring(0, 2 - timeParts[2].length) + timeParts[2];
	}

	return (time);
};

BX.CTasksPlannerHandler.prototype.draw = function(obPlanner, DATA)
{
	if (typeof DATA.MANDATORY_UFS !== 'undefined')
	{
		this.MANDATORY_UFS = DATA.MANDATORY_UFS;
	}
	if (typeof DATA.TASK_ADD_URL !== 'undefined')
	{
		this.TASK_ADD_URL = DATA.TASK_ADD_URL;
	}

	if (!DATA.TASKS_ENABLED)
	{
		return;
	}

	this.PLANNER = obPlanner;

	if (null == this.TASKS)
	{
		this.TASKS = BX.create('DIV');

		this.TASKS.appendChild(BX.create('DIV', {
			props: {className: 'tm-popup-section tm-popup-section-tasks'},
			children: [
				BX.create('SPAN', {
					props: {className: 'tm-popup-section-text'},
					text: BX.message('JS_CORE_PL_TASKS')
				}),
				BX.create('span', {
					props: {className: 'tm-popup-section-right-link'},
					events: {click: BX.proxy(this.showTasks, this)},
					text: BX.message('JS_CORE_PL_TASKS_CHOOSE')
				})
			]
		}));

		this.TASKS.appendChild(BX.create('DIV', {
			props: {className: 'tm-popup-tasks'},
			children: [
			(this.TASKS_LIST = BX.create('div', {
				props: {
					className: 'tm-task-list'
				}
			})),
			this.drawTasksForm(BX.proxy(this.addTask, this))
		]}));
	}
	else
	{
		BX.cleanNode(this.TASKS_LIST);
	}

	if (DATA.TASKS && DATA.TASKS.length > 0)
	{
		var LAST_TASK = null;
		var clsName   = '';
		var children  = [];
		var timeSpent = 0;
		var timeEstim = 0;
		var strTimer  = '';
		var isComplete = null;

		BX.removeClass(this.TASKS, 'tm-popup-tasks-empty');

		for (var i=0,l=DATA.TASKS.length; i<l; i++)
		{
			isComplete = (DATA.TASKS[i].STATUS == 4) || (DATA.TASKS[i].STATUS == 5);

			if (isComplete)
				clsName = ' tm-task-item-done';
			else
				clsName = '';

			children = [];
			children.push(BX.create('input', {
				props: {className: 'tm-task-checkbox', type: 'checkbox', checked : isComplete},
				events: {
					click: (function(taskData){
						return function(){
							var oTask = new BX.CJSTask.Item(taskData.ID);

							if (this.checked)
							{
								oTask.complete({
									callbackOnSuccess : function(){
										if (BX.TasksTimerManager)
											BX.TasksTimerManager.reLoadInitTimerDataFromServer();
									}
								});
							}
							else
							{
								oTask.startExecutionOrRenewAndStart({
									callbackOnSuccess : function(){
										if (BX.TasksTimerManager)
											BX.TasksTimerManager.reLoadInitTimerDataFromServer();
									}
								});
							}
						}
					})(DATA.TASKS[i])
				}
			}));

			var task = DATA.TASKS[i];

			if (
				task.ALLOW_TIME_TRACKING == 'Y' &&
				(
					(DATA.TASKS[i].TIME_SPENT_IN_LOGS > 0)
					|| (DATA.TASKS[i].TIME_ESTIMATE > 0)
				)
			)
			{
				timeSpent = parseInt(DATA.TASKS[i].TIME_SPENT_IN_LOGS);
				timeEstim = parseInt(DATA.TASKS[i].TIME_ESTIMATE);

				if (isNaN(timeSpent))
				{
					timeSpent = 0;
				}

				if (isNaN(timeEstim))
				{
					timeEstim = 0;
				}

				strTimer = (this.formatTime(timeSpent, true));
				if (timeEstim > 0)
				{
					strTimer = strTimer + ' / ' + this.formatTime(timeEstim);
				}
			}
			else
			{
				strTimer = '';
			}

			children.push(BX.create('a', {
				attrs: {href: DATA.TASKS[i].URL},
				props: {
					className: 'tm-task-name' + ((strTimer === '') ? ' tm-task-no-timer' : '')
				},
				text: DATA.TASKS[i].TITLE
			}));

			if (strTimer !== '')
			{
				children.push(BX.create('SPAN', {
					props: {className: 'tm-task-time', id : 'tm-task-time-' + DATA.TASKS[i].ID},
					text : strTimer
				}));
			}

			children.push(BX.create('SPAN', {
				props: {className: 'tm-task-item-menu'},
				events : {
					click : (function(taskData, timerData, self){
						return function(){
							var menuItems = [];

							var menuId = 'task-tm-item-entry-menu-' + taskData.ID;

							if (timerData
								&& (timerData.TASK_ID == taskData.ID)
								&& (timerData.TIMER_STARTED_AT > 0)
							)
							{
								menuItems.push({
									text      : BX.message('JS_CORE_PL_TASKS_STOP_TIMER'),
									className : 'menu-popup-item-hold',
									onclick   : function(e)
									{
										BX.TasksTimerManager.stop(taskData.ID);
										this.popupWindow.close();
									}
								});
							}
							else
							{
								if (taskData.ALLOW_TIME_TRACKING === 'Y')
								{
									menuItems.push({
										text      : BX.message('JS_CORE_PL_TASKS_START_TIMER'),
										className : 'menu-popup-item-begin',
										onclick   : function(e)
										{
											BX.TasksTimerManager.start(taskData.ID);
											this.popupWindow.close();
										}
									});
								}
							}

							menuItems.push({
								text      : BX.message('JS_CORE_PL_TASKS_MENU_REMOVE_FROM_PLAN'),
								className : 'menu-popup-item-decline',
								onclick   : function(e)
								{
									self.removeTask(e, taskData.ID);
									this.popupWindow.close();
								}
							});

							var menu = BX.PopupMenu.getMenuById(menuId);
							if(menu !== null)
							{
								BX.PopupMenu.destroy(menuId);
							}
							else
							{
								menu = BX.PopupMenu.show(
									'task-tm-item-entry-menu-' + taskData.ID,
									this,
									menuItems,
									{
										autoHide : true,
										//"offsetLeft": -1 * anchorPos["width"],
										"offsetTop": 4,
										"events":
										{
											"onPopupClose" : function(ind){
											}
										}
									}
								);
							}
						};
					})(DATA.TASKS[i], DATA.TASKS_TIMER, this)
				}
			}));

			var q = this.TASKS_LIST.appendChild(BX.create('div', {
				props: {
					id         : 'tm-task-item-' + DATA.TASKS[i].ID,
					className  : 'tm-task-item ' + clsName,
					bx_task_id : DATA.TASKS[i].ID
				},
				children: children
			}));

			if (DATA.TASK_LAST_ID && DATA.TASKS[i].ID == DATA.TASK_LAST_ID)
			{
				LAST_TASK = q;
			}
		}

		if (LAST_TASK)
		{
			setTimeout(BX.delegate(function()
			{
				if (LAST_TASK.offsetTop < this.TASKS_LIST.scrollTop || LAST_TASK.offsetTop + LAST_TASK.offsetHeight > this.TASKS_LIST.scrollTop + this.TASKS_LIST.offsetHeight)
				{
					this.TASKS_LIST.scrollTop = LAST_TASK.offsetTop - parseInt(this.TASKS_LIST.offsetHeight/2);
				}
			}, this), 10);
		}
	}
	else
	{
		BX.addClass(this.TASKS, 'tm-popup-tasks-empty');
	}

	this.DATA_TASKS = BX.clone(DATA.TASKS);

	obPlanner.addBlock(this.TASKS, 200);
	obPlanner.addAdditional(this.drawAdditional());
};

BX.CTasksPlannerHandler.prototype.drawAdditional = function()
{
	if(!this.TASK_ADDITIONAL)
	{
		this.ADDITIONAL.TASK_TEXT = BX.create('SPAN', {props: {className: 'tm-info-bar-text-inner'}});
		this.ADDITIONAL.TASK_TIMER = BX.create('SPAN', {props: {className: 'tm-info-bar-time'}});

		this.TASK_ADDITIONAL = BX.create('DIV', {
			props: {className: 'tm-info-bar'},
			children: [
				BX.create('SPAN', {
					props: {
						title    : BX.message('JS_CORE_PL_TASKS_START_TIMER'),
						className: 'tm-info-bar-btn tm-info-bar-btn-play'
					},
					events: {
						click: BX.proxy(this.timerStart, this)
					}
				}),
				BX.create('SPAN', {
					props: {
						title    : BX.message('JS_CORE_PL_TASKS_STOP_TIMER'),
						className: 'tm-info-bar-btn tm-info-bar-btn-pause'
					},
					events: {
						click: BX.proxy(this.timerStop, this)
					}
				}),
				BX.create('SPAN', {
					props: {
						title    : BX.message('JS_CORE_PL_TASKS_FINISH'),
						className: 'tm-info-bar-btn tm-info-bar-btn-flag'
					},
					events: {
						click: BX.proxy(this.timerFinish, this)
					}
				}),
				this.ADDITIONAL.TASK_TIMER,
				BX.create('SPAN', {
					props: {className: 'tm-info-bar-text'},
					children: [
						this.ADDITIONAL.TASK_TEXT
					]
				})
			]
		});
		BX.hide(this.TASK_ADDITIONAL);
	}

	return this.TASK_ADDITIONAL;
};

BX.CTasksPlannerHandler.prototype.timerStart = function()
{
	if(this.timerTaskId > 0)
	{
		BX.TasksTimerManager.start(this.timerTaskId);
	}
};

BX.CTasksPlannerHandler.prototype.timerStop = function()
{
	if(this.timerTaskId > 0)
	{
		BX.TasksTimerManager.stop(this.timerTaskId);
	}
};

BX.CTasksPlannerHandler.prototype.timerFinish = function()
{
	if(this.timerTaskId > 0)
	{
		var oTask = new BX.CJSTask.Item(this.timerTaskId);
		oTask.complete({
			callbackOnSuccess : function(){
				if (BX.TasksTimerManager)
					BX.TasksTimerManager.reLoadInitTimerDataFromServer();
			}
		});
	}
};

BX.CTasksPlannerHandler.prototype.onTaskTimerChange = function(params)
{
	if (params.action === 'refresh_daemon_event')
	{
		this.timerTaskId = params.taskId;
		if(this.PLANNER && !!this.PLANNER.WND && this.PLANNER.WND.isShown() && params.taskId > 0)
		{
			var d = this.drawAdditional();

			if(!!this.taskTimerSwitch)
			{
				d.style.display = '';
				this.taskTimerSwitch = false;
			}

			var curTime = parseInt(params.data.TIMER.RUN_TIME||0) + parseInt(params.data.TASK.TIME_SPENT_IN_LOGS||0),
				planTime = parseInt(params.data.TASK.TIME_ESTIMATE||0);

			if(planTime > 0 && curTime > planTime)
			{
				BX.addClass(d, 'tm-info-bar-overdue');
			}
			else
			{
				BX.removeClass(d, 'tm-info-bar-overdue');
			}

			var s = '';
			s += this.formatTime(curTime, true);

			if(planTime > 0)
			{
				s += ' / ' + this.formatTime(planTime);
			}

			this.ADDITIONAL.TASK_TIMER.innerHTML = s;
			this.ADDITIONAL.TASK_TEXT.innerHTML = BX.util.htmlspecialchars(params.data.TASK.TITLE);

			var tmListTaskEntry = BX('tm-task-time-' + this.timerTaskId);
			if (tmListTaskEntry)
				tmListTaskEntry.innerHTML = s;
		}
	}
	else if(params.action === 'start_timer')
	{
		if (this.isClosed(params.taskData))
		{
			BX.addClass(this.drawAdditional(), 'tm-info-bar-closed');
		}
		else
		{
			BX.removeClass(this.drawAdditional(), 'tm-info-bar-closed');
		}

		this.timerTaskId = params.taskData.ID;
		this.taskTimerSwitch = true;
		BX.addClass(this.drawAdditional(), 'tm-info-bar-active');
		BX.removeClass(this.drawAdditional(), 'tm-info-bar-pause');
	}
	else if(params.action === 'stop_timer')
	{
		this.timerTaskId = params.taskData.ID;
		if (this.isClosed(params.taskData))
		{
			BX.hide(this.drawAdditional());
		}
		else
		{
			BX.addClass(this.drawAdditional(), 'tm-info-bar-pause');
			BX.removeClass(this.drawAdditional(), 'tm-info-bar-active');
		}
	}
	else if(params.action === 'init_timer_data')
	{
		if (params.data.TIMER && params.data.TASK.ID > 0 && (params.data.TIMER.TASK_ID == params.data.TASK.ID))
		{
			this.timerTaskId = params.data.TASK.ID;

			if (this.isClosed(params.data.TASK))
			{
				BX.addClass(this.drawAdditional(), 'tm-info-bar-closed');
			}
			else
			{
				BX.removeClass(this.drawAdditional(), 'tm-info-bar-closed');
			}

			if (params.data.TIMER.TIMER_STARTED_AT == 0)
			{
				if (this.isClosed(params.data.TASK))
				{
					BX.hide(this.drawAdditional());
				}
				else
				{
					this.taskTimerSwitch = true;
					BX.addClass(this.drawAdditional(), 'tm-info-bar-pause');
					BX.removeClass(this.drawAdditional(), 'tm-info-bar-active');
				}
			}
			else
			{
				this.taskTimerSwitch = true;
				BX.addClass(this.drawAdditional(), 'tm-info-bar-active');
				BX.removeClass(this.drawAdditional(), 'tm-info-bar-pause');
			}
		}
		else
		{
			BX.hide(this.drawAdditional());
		}

		this.onTaskTimerChange({action:'refresh_daemon_event',taskId:+params.data.TASK.ID,data:params.data});
	}
};

BX.CTasksPlannerHandler.prototype.isClosed = function(task)
{
	return task.STATUS == 5||task.STATUS == 4;
};

BX.CTasksPlannerHandler.prototype.addTask = function(task_data)
{
	if(!!this.TASKS_LIST)
	{
		this.TASKS_LIST.appendChild(BX.create('LI', {
			props: {className: 'tm-popup-task'},
			text: task_data.name
		}));

		BX.removeClass(this.TASKS, 'tm-popup-tasks-empty');
	}

	var data = {action: 'add'};

	if(typeof task_data.id != 'undefined')
		data.id = task_data.id;
	if(typeof task_data.name != 'undefined')
		data.name = task_data.name;

	this.query(data);
};

BX.CTasksPlannerHandler.prototype.removeTask = function(e, taskId)
{
	this.query({action: 'remove', id: taskId});
	BX.cleanNode(BX('tm-task-item-' + taskId), true);

	if(!this.TASKS_LIST.firstChild)
	{
		BX.addClass(this.TASKS, 'tm-popup-tasks-empty');
	}
};

BX.CTasksPlannerHandler.prototype.showTasks = function()
{
	if (!this.TASKS_WND)
	{
		this.TASKS_WND = new BX.CTasksPlannerSelector({
			node: BX.proxy_context,
			onselect: BX.proxy(this.addTask, this)
		});
	}
	else
	{
		this.TASKS_WND.setNode(BX.proxy_context);
	}

	this.TASKS_WND.Show();
};

BX.CTasksPlannerHandler.prototype.showTask = function(e)
{
	var taskId = BX.proxy_context.parentNode.bx_task_id;
	var tasks = this.DATA_TASKS;

	if (tasks.length > 0)
	{
		var taskViewUrl = '';
		tasks.forEach(function(task) {
			if (Number(task.ID) === Number(taskId))
			{
				taskViewUrl = task.URL;
			}
		});
		if (taskViewUrl !== '')
		{
			BX.SidePanel.Instance.open(taskViewUrl);
		}
	}

	return false;
};

BX.CTasksPlannerHandler.prototype.drawTasksForm = function(cb)
{
	var handler  = null;
	var inp_Task = null;
	var children = null;

	if (this.MANDATORY_UFS !== 'Y')
	{
		handler = BX.delegate(function(e, bEnterPressed) {
			inp_Task.value = BX.util.trim(inp_Task.value);
			if (inp_Task.value && inp_Task.value!=BX.message('JS_CORE_PL_TASKS_ADD'))
			{
				cb({
					name: inp_Task.value
				});

				if (!bEnterPressed)
				{
					BX.addClass(inp_Task.parentNode, 'tm-popup-task-form-disabled')
					inp_Task.value = BX.message('JS_CORE_PL_TASKS_ADD');
				}
				else
				{
					inp_Task.value = '';
				}
			}

			return BX.PreventDefault(e);
		}, this);

		var inp_Task = BX.create('INPUT', {
			props: {type: 'text', className: 'tm-popup-task-form-textbox', value: BX.message('JS_CORE_PL_TASKS_ADD')},
			events: {
				keypress: function(e) {
					return (e.keyCode == 13) ? handler(e, true) : true;
				},
				blur: function() {
					if (this.value == '')
					{
						BX.addClass(this.parentNode, 'tm-popup-task-form-disabled');
						this.value = BX.message('JS_CORE_PL_TASKS_ADD');
					}
				},
				focus: function() {
					BX.removeClass(this.parentNode, 'tm-popup-task-form-disabled');
					if (this.value == BX.message('JS_CORE_PL_TASKS_ADD'))
						this.value = '';
				}
			}
		});

		BX.focusEvents(inp_Task);

		children = [
			inp_Task,
			BX.create('SPAN', {
				props: {className: 'tm-popup-task-form-submit'},
				events: {click: handler}
			})
		];
	}
	else
	{
		children = [
			BX.create('A', {
				text : BX.message('JS_CORE_PL_TASKS_CREATE'),
				attrs: {href: this.TASK_ADD_URL}
			})
		];
	}

	return BX.create('DIV', {
		props: {
			className: 'tm-popup-task-form tm-popup-task-form-disabled'
		},
		children: children
	});
};

BX.CTasksPlannerHandler.prototype.query = function(entry, callback)
{
	if (this.TASK_CHANGES_TIMEOUT)
	{
		clearTimeout(this.TASK_CHANGES_TIMEOUT);
	}

	if (typeof entry == 'object')
	{
		if(!!entry.id)
		{
			this.TASK_CHANGES[entry.action].push(entry.id);
		}

		if (entry.action == 'add')
		{
			if(!entry.id)
			{
				this.TASK_CHANGES.name = entry.name;
			}

			this.query();
		}
		else
		{
			this.TASK_CHANGES_TIMEOUT = setTimeout(
				BX.proxy(this.query, this), 1000
			);
		}
	}
	else
	{
		if(!!this.PLANNER)
		{
			this.DATA_TASKS = [];
			this.PLANNER.query('task', this.TASK_CHANGES);
		}
		else
		{
			window.top.BX.CPlanner.query('task', this.TASK_CHANGES);
		}
		this.TASK_CHANGES = {add: [], remove: []};
	}
};

BX.CTasksPlannerSelector = function(params)
{
	this.params = params;

	this.isReady = false;
	this.WND = BX.PopupWindowManager.create(
		'planner_tasks_selector_' + parseInt(Math.random() * 10000), this.params.node,
		{
			autoHide: true,
			closeByEsc: true,
			content: (this.content = BX.create('DIV')),
			buttons: [
				new BX.PopupWindowButtonLink({
					text : BX.message('JS_CORE_WINDOW_CLOSE'),
					className : "popup-window-button-link-cancel",
					events : {click : function(e) {this.popupWindow.close();return BX.PreventDefault(e);}}
				})
			]
		}
	);
};

BX.CTasksPlannerSelector.prototype.Show = function()
{
	if (!this.isReady)
	{
		var suffix = parseInt(Math.random() * 10000);
		window['PLANNER_ADD_TASK_' + suffix] = BX.proxy(this.setValue, this);

		return BX.ajax.get('/bitrix/tools/tasks_planner.php', {action:'list', suffix: suffix, sessid: BX.bitrix_sessid(), site_id: BX.message('SITE_ID')}, BX.proxy(this.Ready, this));
	}

	return this.WND.show();
};

BX.CTasksPlannerSelector.prototype.Hide = function()
{
	this.WND.close();
};

BX.CTasksPlannerSelector.prototype.Ready = function(data)
{
	this.content.innerHTML = data;

	this.isReady = true;
	this.Show();
};

BX.CTasksPlannerSelector.prototype.setValue = function(task)
{
	this.params.onselect(task)
	this.WND.close();
};

BX.CTasksPlannerSelector.prototype.setNode = function(node)
{
	this.WND.setBindElement(node);
};

PLANNER_HANDLER = new BX.CTasksPlannerHandler();
})();