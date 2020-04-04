"use strict";

(function(){

	class BackgroundTasks
	{
		constructor()
		{
			this.tasks = [];
			this.delayedWorkerId = null;

			BX.addCustomEvent("chatbackground::task::add", (taskId, query, extra = null, delay = null) => this.addTask(taskId, query, extra, delay));
		}

		addTask(taskId, query, extra = false, delay = null)
		{
			console.info('ChatBackground.addTask', taskId, query, extra, delay);

			let date = delay? new Date(+new Date() + delay): null;

			this.tasks = this.tasks.filter(element => element.taskId !== taskId);
			this.tasks.push({taskId, query, extra, date});

			this.worker();
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

					if (response[taskId].error())
					{
						this.postFailureEvent(
							taskId,
							response[taskId].error().ex.error,
							response[taskId].error().ex.error_description,
							response[taskId].error().ex.status,
							extra
						);
					}
					else
					{
						this.postSuccessEvent(
							taskId,
							response[taskId].data(),
							extra
						);
					}
				}
			}, false, (xhr) => {
				requestTimeout = setTimeout(() => {
					xhr.abort();
				}, 60000)
			});
		}

		worker()
		{
			clearTimeout(this.delayedWorkerId);

			let query = [];
			let nextExecute = 0;

			this.tasks = this.tasks.filter(item =>
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
				console.log(`ChatBackground.worker: ${this.tasks.length} tasks delayed for ${nextExecute}ms`);
				this.delayedWorkerId = setTimeout(this.worker.bind(this), nextExecute);
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
			clearInterval(this.delayedWorkerId);
		}
	}

	this.ChatBackgroundTasks = new BackgroundTasks();

})();