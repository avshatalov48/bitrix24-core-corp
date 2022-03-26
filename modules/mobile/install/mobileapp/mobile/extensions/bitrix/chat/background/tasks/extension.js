"use strict";

(function(){

	class BackgroundTasks
	{
		constructor()
		{
			this.tasks = [];
			this.delayedWorkerIds = [];

			this.userId = BX.componentParameters.get("USER_ID", 0);

			this.storage = Application.sharedStorage(`chatBackgroundQueue_${this.userId}`);
			this.start();

			BX.addCustomEvent("chatbackground::task::add", (taskId, query, extra = null, delay = null) => this.addTask(taskId, query, extra, delay));
			BX.addCustomEvent("chatbackground::task::restart", () => this.start());
		}

		addTask(taskId, query, extra = false, delay = null)
		{
			console.info('ChatBackground.addTask', taskId, query, extra, delay);

			this.enqueue({taskId, query, extra, delay});
			const queueType = this.getTaskQueueType(extra);
			this.worker(queueType);
		}

		moveToTheEnd(task)
		{
			// 5 min delay for the next try
			task.delay = 5 * 60 * 1000;

			console.info('ChatBackground.moveToTheEnd', task);

			// remove it from the first position and add to the end of queue
			this.enqueue(task);
			const queueType = this.getTaskQueueType(task.extra);
			this.worker(queueType);
		}

		stopQueue(queueType)
		{
			console.log('ChatBackground.stopQueue', queueType);

			// we stop the first task and other tasks in this queue type will wait the first one.
			if (this.tasks[queueType].length > 0)
			{
				this.tasks[queueType][0].sending = false;
			}
		}

		postSuccessEvent(taskId, result = {}, extra = false)
		{
			console.log('ChatBackground.postEvent', taskId, result, extra);

			BX.postComponentEvent("chatbackground::task::status::success", [taskId, result, extra]);
			BX.postWebEvent("chatbackground::task::status::success", {taskId, result, extra});
		}

		postFailureEvent(taskId, code, text = '', status = 200, extra = false)
		{
			console.log('ChatBackground.postFailureEvent', taskId, code, text, status, extra);

			BX.postComponentEvent("chatbackground::task::status::failure", [taskId, code, text, status, extra]);
			BX.postWebEvent("chatbackground::task::status::failure", {taskId, code, text, status, extra});
		}

		executeRest(queries, extra = false)
		{
			let requestTimeout;
			BX.rest.callBatch(queries, (response) =>
			{
				clearTimeout(requestTimeout);

				if (!response)
				{
					queries.forEach(query => {
						let taskId; for (let key in query) { if (query.hasOwnProperty(key)) { taskId = key; }}
						this.postFailureEvent(taskId, 'EMPTY_RESPONSE', 'Server returned an empty response.', 204, extra);
					});

					return false;
				}

				for (let taskId in response)
				{
					if (!response.hasOwnProperty(taskId))
					{
						continue;
					}

					const queueType = this.getTaskQueueType(extra);
					if (response[taskId].error())
					{
						const failedTask = this.getTaskByUid(taskId);
						if (response[taskId].error().status && response[taskId].error().status === -2)
						{
							// if no internet connection - then only delay the first task
							if (failedTask)
							{
								this.stopQueue(queueType);
							}
						}
						else if (response[taskId].error().status === 200)
						{
							// if we have API error and no server error, then only delete task
							this.deleteTask(taskId);
							this.worker(queueType);
						}
						else
						{
							// if we have server error (code !== 200), then delay task and move it to the end of queue
							if (failedTask)
							{
								this.moveToTheEnd(failedTask);
							}
							this.worker(queueType);
						}

						this.postFailureEvent(
							taskId,
							response[taskId].error().ex.error,
							response[taskId].error().ex.error_description,
							response[taskId].error().status,
							extra
						);
					}
					else
					{
						this.deleteTask(taskId);
						this.postSuccessEvent(
							taskId,
							response[taskId].data(),
							extra
						);
						this.worker(queueType);
					}
				}
			}, false, (xhr) => {
				requestTimeout = setTimeout(() => {
					xhr.abort();
				}, 60000)
			});
		}

		worker(queueType)
		{
			if (this.delayedWorkerIds.hasOwnProperty(queueType))
			{
				clearTimeout(this.delayedWorkerIds[queueType]);
			}

			const currentTask = this.peek(queueType);
			if (!currentTask || this.isTaskAlreadySending(currentTask))
			{
				return;
			}

			const query = [];
			if (this.checkExecutionDate(currentTask))
			{
				currentTask.sending = true;
				query.push(currentTask);
			}

			const nextExecute = currentTask.date - new Date();
			if (nextExecute > 0)
			{
				console.log(`ChatBackground.worker: ${this.tasks[queueType].length} tasks delayed for ${nextExecute}ms`);
				this.delayedWorkerIds[queueType] = setTimeout(this.worker.bind(this, queueType), nextExecute);

				return;
			}

			if (query.length > 0)
			{
				console.info(`ChatBackground.worker: ${query.length} tasks start execute`, query);


				let restQueriesEmpty = true;
				let restQueries = {};
				let queryBatch = [];

				query.forEach(element => {

					if (element.extra)
					{
						if (queryBatch.length > 0)
						{
							queryBatch.push({queries: restQueries, extra: false});
							restQueries = {};
						}

						restQueries[element.taskId] = element.query;

						queryBatch.push({queries: restQueries, extra: element.extra});

						restQueriesEmpty = true;
						restQueries = {};

						return true;
					}

					restQueriesEmpty = false;
					restQueries[element.taskId] = element.query;
				});

				if (!restQueriesEmpty)
				{
					queryBatch.push({queries: restQueries, extra: false});
				}

				queryBatch.forEach(element => {
					this.executeRest(element.queries, element.extra);
				})
			}
		};

		destroy()
		{
			for (const queueType in this.tasks)
			{
				clearInterval(this.delayedWorkerIds[queueType]);
			}
		}

		getTasksFromStorage()
		{
			const rawTasks = this.storage.get('tasks');

			return rawTasks ? JSON.parse(rawTasks) : {};
		}

		removeTaskFromStorage(taskId)
		{
			let tasks = this.getTasksFromStorage();
			if (tasks.length === 0)
			{
				return;
			}

			for (const queueType in tasks)
			{
				tasks[queueType] = tasks[queueType].filter(task => task.taskId !== taskId);
			}

			this.storage.set('tasks', JSON.stringify(tasks));
		}

		getTaskByUid(taskId)
		{
			let foundTask;
			for (const queueType in this.tasks)
			{
				foundTask = this.tasks[queueType].find(task => task.taskId === taskId);
				if (foundTask)
				{
					return foundTask;
				}
			}
		}

		saveTaskToStorage(queueType, taskToSave)
		{
			let tasks = this.getTasksFromStorage();
			if (!tasks[queueType])
			{
				tasks[queueType] = [];
			}

			tasks[queueType] = tasks[queueType].filter(task => task.taskId !== taskToSave.taskId);
			tasks[queueType].push(taskToSave);

			this.storage.set('tasks', JSON.stringify(tasks));
		}

		start()
		{
			this.tasks = this.getTasksFromStorage();

			for (const queueType in this.tasks)
			{
				this.tasks[queueType] = this.tasks[queueType].filter(task => {
					if (task.dateCreateTS)
					{
						// delete all the tasks older than 30 days
						const dateExpire = new Date(Date.now() - (60 * 60 * 24 * 30 * 1000)).getTime();

						return task.dateCreateTS > dateExpire;
					}

					return true;
				});

				this.tasks[queueType] = this.tasks[queueType].map(task => {
					// clean the execution date before start, because we trying to resend it again.
					task.date = null;

					return task
				});

				if (this.tasks[queueType].length > 0)
				{
					this.worker(queueType);
				}
			}
		}

		deleteTask(taskId)
		{
			for (const queueType in this.tasks)
			{
				this.tasks[queueType] = this.tasks[queueType].filter(task => task.taskId !== taskId);
			}

			this.removeTaskFromStorage(taskId);
		}

		getTaskQueueType(extra)
		{
			let type = 'default';
			if (!extra)
			{
				return type;
			}

			if (extra.params && extra.params.dialogId)
			{
				type = extra.params.dialogId;
			}
			else if(extra.dialogId)
			{
				type = extra.dialogId;
			}

			return type.toString();
		}

		enqueue(task)
		{
			console.info('ChatBackground.enqueue', task);

			if (!task.dateCreateTS)
			{
				task.dateCreateTS = Date.now();
			}

			const {taskId, query, extra, delay, dateCreateTS} = task;

			const date = delay ? new Date(+new Date() + delay) : null;
			const queueType = this.getTaskQueueType(extra);
			if (!this.tasks[queueType])
			{
				this.tasks[queueType] = [];
			}
			this.tasks[queueType] = this.tasks[queueType].filter(element => element.taskId !== taskId);
			this.tasks[queueType].push({taskId, query, extra, date, dateCreateTS, sending: false});

			this.saveTaskToStorage(queueType, {taskId, query, extra, date, dateCreateTS, sending: false});
		}

		peek(queueType)
		{
			if (this.tasks[queueType].length === 0)
			{
				return null;
			}

			return this.tasks[queueType][0];
		}

		isTaskAlreadySending(task)
		{
			return task.hasOwnProperty('sending') && task.sending === true;
		}

		checkExecutionDate(task)
		{
			if (task.date && typeof task.date === 'string')
			{
				task.date = new Date(task.date);
			}

			if (!task.date || task.date && task.date <= new Date())
			{
				return true;
			}

			return false;
		}
	}

	this.ChatBackgroundTasks = new BackgroundTasks();

})();