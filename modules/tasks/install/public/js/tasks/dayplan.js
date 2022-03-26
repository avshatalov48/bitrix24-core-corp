BX.namespace('BX.Tasks');

/**
 * This class provides front-end integration with 'timeman' module and
 * the time management widget (currently BX.CTasksPlannerHandler) and\or allows
 * to perform start/stop task timer.
 *
 * If you intend to refactor BX.CTasksPlannerHandler, please do smth like the following:
 *
 *      BX.Tasks.PlannerHandler = BX.Tasks.DayPlan.extend(...);
 *
 * and re-use an existing code there. There are lots of.
 *
 */

BX.Tasks.DayPlan = BX.Tasks.Util.Base.extend({
	options: {
		data: []
	},
	methods: {

		construct: function()
		{
			this.callConstruct(BX.Tasks.Util.Base);

			if(typeof this.vars == 'undefined')
			{
				this.vars = {};
			}
			this.vars.state = {};
			this.vars.timeOffsets = {};
			this.vars.taskData = BX.clone(this.option('data'));

			this.onPlannerUpdate = BX.debounce(this.onPlannerUpdate, 100, this);
			this.onTimerUpdate = this.onTimerUpdate.bind(this);
			this.handleTimeManDataReceived = this.handleTimeManDataReceived.bind(this);
			this.handlePlannerDataReceived = this.handlePlannerDataReceived.bind(this);
			this.handleSliderDestroy = this.handleSliderDestroy.bind(this);

			// we could be in iframe
			if (window !== window.top)
			{
				this.bindWidgetEvents(window.top);
				BX.addCustomEvent("SidePanel.Slider:onDestroy", this.handleSliderDestroy);
			}
			else
			{
				this.bindWidgetEvents(window);
			}

			BX.ready(BX.delegate(function(){
				if (!this.hasPlanner())
				{
					// on pages with no planner included we have to use fake tick-tack event :(
					setInterval(BX.delegate(this.timerTickEmulation, this), 1000);

					// and also, bind global task event to be able to start\stop timer when required
					BX.addCustomEvent(window, 'tasksTaskEvent', BX.delegate(this.onTaskGlobalEvent, this));
				}
			}, this));
		},

		bindWidgetEvents: function(windowObj)
		{
			windowObj.BX.addCustomEvent(windowObj, 'onTimeManDataRecieved', this.handleTimeManDataReceived);
			windowObj.BX.addCustomEvent(windowObj, 'onPlannerDataRecieved', this.handlePlannerDataReceived);
			windowObj.BX.addCustomEvent(windowObj, 'onTaskTimerChange', this.onTimerUpdate);
		},

		unbindWidgetEvents: function(windowObj)
		{
			windowObj.BX.removeCustomEvent(windowObj, 'onTimeManDataRecieved', this.handleTimeManDataReceived);
			windowObj.BX.removeCustomEvent(windowObj, 'onPlannerDataRecieved', this.handlePlannerDataReceived);
			windowObj.BX.removeCustomEvent(windowObj, 'onTaskTimerChange', this.onTimerUpdate);
		},

		handlePlannerDataReceived: function(obPlanner, data)
		{
			this.onPlannerUpdate(data);
		},

		handleTimeManDataReceived: function(data)
		{
			this.onPlannerUpdate(data.PLANNER);
		},

		handleSliderDestroy: function(event)
		{
			this.unbindWidgetEvents(window.top);
			BX.removeCustomEvent("SidePanel.Slider:onDestroy", this.handleSliderDestroy);
		},

		// any changes considering planner task set
		onPlannerUpdate: function(data)
		{
			data = data || {};
			data.TASKS = data.TASKS || [];
			data.TASK_ON_TIMER = data.TASK_ON_TIMER || {};

			var taskId;
			var k;
			var planCame = {};

			for(k = 0; k < data.TASKS.length; k++)
			{
				taskId = parseInt(data.TASKS[k].ID);
				if(isNaN(taskId))
				{
					continue;
				}
				planCame[taskId] = true;

				if(typeof this.vars.state[taskId] == 'undefined')
				{
					this.vars.state[taskId] = {timer: false, plan: false};
				}
				this.vars.state[taskId].plan = true;
			}

			for(k in this.vars.state)
			{
				if(typeof planCame[k] == 'undefined')
				{
					this.fireEvent('task-plan-toggle', [k, false]);
					this.vars.state[k].plan = false;
				}
			}

			taskId = parseInt(data.TASK_ON_TIMER.ID);

			if(!isNaN(taskId))
			{
				if(typeof this.vars.state[taskId] == 'undefined')
				{
					this.vars.state[taskId] = {timer: false, plan: false};
				}

				for(k in this.vars.state)
				{
					if(this.vars.state[k].timer && k != taskId)
					{
						this.fireEvent('task-timer-toggle', [false, k]);
					}

					this.vars.state[k].timer = false;
				}
				this.vars.state[taskId].timer = true;
			}
		},

		// spikes from the previous version ...
		updatePlanner: function()
		{
			if(!this.hasPlanner())
			{
				return false;
			}

			var updated = true;

			// This will run onTimeManDataRecieved/onPlannerDataRecieved
			// and after that, init_timer_data event
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
		},

		hasPlanner: function()
		{
			return !!(window.top.BXPLANNER || window.top.BXTIMEMAN);
		},

		// any changes considering task timing
		onTimerUpdate: function(data)
		{
			data = data || {};
			data.taskData = data.taskData || {};

			if(data.action == 'refresh_daemon_event') // timer tick actually
			{
				var inLog = parseInt(data.data.TASK.TIME_SPENT_IN_LOGS);
				if(isNaN(inLog))
				{
					inLog = 0;
				}
				var inTimer = parseInt(data.data.TIMER.RUN_TIME);
				if(isNaN(inTimer))
				{
					inTimer = 0;
				}

				this.fireEvent('task-timer-tick', [data.taskId, inLog + inTimer, data.data.TASK]);
			}
			else if(data.action == 'stop_timer')
			{
				data.taskData.TIMER_IS_RUNNING_FOR_CURRENT_USER = false;
				this.fireEvent('task-timer-toggle', [data.taskId, false, data.taskData]);
			}
			else if(data.action == 'start_timer')
			{
				data.taskData.TIMER_IS_RUNNING_FOR_CURRENT_USER = true;
				this.fireEvent('task-timer-toggle', [data.taskId, true, data.taskData]);
			}
		},

		onTaskGlobalEvent: function(type, data)
		{
			if(type == 'UPDATE' && typeof data.task != 'undefined' && data.task.ID)
			{
				this.vars.taskData[data.task.ID] = BX.clone(data.task);
				this.setTaskTimeOffset(data.task.ID, data.task.TIME_ELAPSED);
			}
		},

		setTaskTimeOffset: function(taskId, value)
		{
			this.vars.timeOffsets[taskId] = {inLog: parseInt(value), current: 0};
		},

		// nasty logic
		timerTickEmulation: function()
		{
			var tasks = this.vars.taskData;

			for(var k in tasks)
			{
				var data = tasks[k];

				if(data.ID && data.TIMER_IS_RUNNING_FOR_CURRENT_USER)
				{
					if(typeof this.vars.timeOffsets[data.ID] == 'undefined')
					{
						this.setTaskTimeOffset(data.ID, data.TIME_ELAPSED);
					}
					var time = this.vars.timeOffsets[data.ID];

					this.fireEvent('task-timer-tick', [data.ID, time.inLog + time.current, data]);
					time.current++;
				}
			}
		},

		addToPlan: function(taskId)
		{
			if(BX.addTaskToPlanner)
			{
				BX.addTaskToPlanner(taskId);
				return true;
			}
			else if(window.top.BX.addTaskToPlanner)
			{
				window.top.BX.addTaskToPlanner(taskId);
				return true;
			}
			return false;
		},
		startTimer: function(taskId, sync, stopPrevious)
		{
			if(!taskId)
			{
				return;
			}

			if(sync)
			{
				BX.ajax.runComponentAction('bitrix:tasks.task', 'startTimer', {
					mode: 'class',
					data: {
						taskId: taskId,
						stopPrevious: stopPrevious || false
					}
				}).then(
					function(response)
					{
						if (
							!response.status
							|| response.status !== 'success'
						)
						{
							this.fireEvent('other-task-on-timer', [taskId, []]);
							return;
						}
						this.fireEvent('task-timer-toggle', [taskId, true]);
					}.bind(this),
					function(response)
					{
						var respData = [];
						if (response.errors[0].data.TASK)
						{
							respData = response.errors[0].data.TASK;
						}
						this.fireEvent('other-task-on-timer', [taskId, respData]);
					}.bind(this)
				);
			}
			else
			{
				this.fireEvent('task-timer-toggle', [taskId, true]);
			}
		},
		stopTimer: function(taskId, sync)
		{
			if(!taskId)
			{
				return;
			}

			if(sync)
			{
				BX.ajax.runComponentAction('bitrix:tasks.task', 'stopTimer', {
					mode: 'class',
					data: {
						taskId: taskId
					}
				}).then(
					function(response)
					{
						if (
							!response.status
							|| response.status !== 'success'
						)
						{
							return;
						}

						this.fireEvent('task-timer-toggle', [taskId, false]);
					}.bind(this),
					function(response)
					{

					}.bind(this)
				);
			}
			else
			{
				this.fireEvent('task-timer-toggle', [taskId, false]);
			}
		}
	}
});