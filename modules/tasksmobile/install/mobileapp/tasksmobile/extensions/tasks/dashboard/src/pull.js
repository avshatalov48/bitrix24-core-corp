/**
 * @module tasks/dashboard/src/pull
 */
jn.define('tasks/dashboard/src/pull', (require, exports, module) => {
	class Pull
	{
		static get events()
		{
			return {
				USER_COUNTER: 'user_counter',
				TASK_REMOVE: 'task_remove',
			};
		}

		constructor(data)
		{
			this.eventCallbacks = data.eventCallbacks;
			this.onPullCallback = data.onPullCallback;

			this.shouldReloadDynamically = data.shouldReloadDynamically ?? true;
			this.isTabsMode = data.isTabsMode;

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

			if (this.isTabsMode)
			{
				BX.postComponentEvent('tasks.dashboard:pullSubscribed', [], 'tasks.tabs');
			}
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
