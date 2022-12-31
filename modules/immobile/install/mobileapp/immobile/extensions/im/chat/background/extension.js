"use strict";


(function(){

	class ChatBackgroundAction
	{
		constructor()
		{
			this.delayed = [];
			this.delayedWorkerId = null;

			BX.addCustomEvent("chatbackground::task::action", (type, taskId, params = {}, extra = false, delay = null) => this.executeAction(type, taskId, params, extra,  delay));
		}

		executeAction(type, taskId, params, extra = false, delay = null)
		{
			console.info('ChatBackgroundAction.executeAction', type, taskId, params, extra, delay);

			if (type === 'readMessage')
			{
				this.actionReadMessage(taskId, params, extra, delay);
			}
			else if (type === 'reactMessage')
			{
				this.actionReactMessage(taskId, params, extra, delay);
			}
			else if (type === 'readNotification')
			{
				this.actionReadNotification(taskId, params, extra, delay);
			}
			else if (type === 'readNotificationList')
			{
				this.actionReadNotificationList(taskId, params, extra, delay);
			}
		}

		actionReadMessage(taskId, params, extra = false, delay = null)
		{
			if (delay)
			{
				let action = this.delayed.find(action => action.taskId === taskId);
				if (action && action.params.lastId > params.lastId)
				{
					params = action.params;
				}

				this.delayAction('actionReadMessage', taskId, params, extra, delay);

				return true;
			}

			ChatBackgroundTasks.addTask(taskId, ['im.dialog.read', {
				'DIALOG_ID': params.dialogId,
				'MESSAGE_ID': params.lastId
			}], extra);
		}

		actionReadNotification(taskId, params, extra = false)
		{
			ChatBackgroundTasks.addTask(taskId, ['im.notify.read', {
				'ID': params.id,
				'ACTION': params.action
			}], extra);
		}

		actionReadNotificationList(taskId, params, extra = false)
		{
			ChatBackgroundTasks.addTask(taskId, ['im.notify.read.list', {
				'IDS': params.ids,
				'ACTION': params.action
			}], extra);
		}

		actionReactMessage(taskId, params, extra = false, delay = null)
		{
			if (delay)
			{
				let action = this.delayed.find(action => action.taskId === taskId);
				if (action && action.params.lastId > params.lastId)
				{
					params = action.params;
				}

				this.delayAction('actionReactMessage', taskId, params, extra, delay);

				return true;
			}

			ChatBackgroundTasks.addTask(taskId, ['im.message.like', {
				'MESSAGE_ID': params.messageId,
				'ACTION': params.action,
			}], extra);
		}

		delayAction(action, taskId, params, extra, delay)
		{
			this.delayed = this.delayed.filter(action => action.taskId !== taskId);
			this.delayed.push({action, taskId, params, extra, date: new Date(+new Date() + delay)});
			this.worker();

			return true;
		}

		worker()
		{
			clearTimeout(this.delayedWorkerId);

			let query = [];
			let nextExecute = 0;

			this.delayed = this.delayed.filter(item =>
			{
				if (!item.date || item.date && item.date <= new Date())
				{
					query.push(item);
					return false;
				}

				let nextExecuteTest = item.date - new Date();
				if (nextExecute === 0 || nextExecute > nextExecuteTest)
				{
					nextExecute = nextExecuteTest;
				}

				return true;
			});

			if (nextExecute)
			{
				console.log(`ChatBackgroundAction.worker: ${this.delayed.length} actions delayed for ${nextExecute}ms`);
				this.delayedWorkerId = setTimeout(this.worker.bind(this), nextExecute);
			}

			if (query.length > 0)
			{
				console.info(`ChatBackgroundAction.worker: ${query.length} actions start execute`, query);
				query.forEach(item => this[item.action](item.taskId, item.params, item.extra));
			}
		};

		destroy()
		{
			clearInterval(this.delayedWorkerId);
		}
	}

	this.ChatBackgroundAction = new ChatBackgroundAction();

})();