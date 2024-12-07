/**
 * @module tasks/flow-list/src/pull
 */
jn.define('tasks/flow-list/src/pull', (require, exports, module) => {
	const { RunActionExecutor } = require('rest/run-action-executor');

	class Pull
	{
		static get events()
		{
			return {
				USER_COUNTER: 'user_counter',
			};
		}

		constructor(data)
		{
			this.eventCallbacks = data.eventCallbacks;
			this.onPullCallback = data.onPullCallback;

			this.shouldReloadDynamically = data.shouldReloadDynamically ?? true;

			this.getPullConfig = this.getPullConfig.bind(this);
		}

		unsubscribe()
		{
			if (this.unsubscribeCallback)
			{
				this.unsubscribeCallback();
			}
		}

		subscribe()
		{
			this.unsubscribeCallback = BX.PULL.subscribe({
				moduleId: 'tasks',
				callback: this.processPullEvent.bind(this),
			});

			void this.subscribeUserToFlowsPull();
		}

		subscribeUserToFlowsPull()
		{
			return new Promise((resolve) => {
				(new RunActionExecutor('tasksmobile.Flow.subscribeUserToPull'))
					.setHandler((response) => {
						if (response.errors && response.errors.length > 0)
						{
							console.error(response.errors);
						}
						resolve(response);
					})
					.call(false)
				;
			});
		}

		processPullEvent(data)
		{
			const { command, params } = data;

			if (this.eventCallbacks[command])
			{
				this.eventCallbacks[command](params);
			}
		}

		getPullConfig()
		{
			return {
				moduleId: 'tasks',
				callback: this.onPullCallback,
				shouldReloadDynamically: this.shouldReloadDynamically,
			};
		}
	}

	module.exports = { Pull };
});
