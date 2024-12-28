/**
 * @module layout/ui/stateful-list/pull
 */
jn.define('layout/ui/stateful-list/pull', (require, exports, module) => {
	const { Loc } = require('loc');
	const { PushProcessor } = require('layout/ui/stateful-list/pull/src/push-processor');
	const { command: pullCommands } = require('layout/ui/stateful-list/pull/src/command');

	class Pull
	{
		static get command()
		{
			return pullCommands;
		}

		constructor(data)
		{
			this.moduleId = data.moduleId;
			this.callback = data.callback;
			this.notificationAddText = data.notificationAddText;
			this.notificationUpdateText = data.notificationUpdateText;
			this.context = data.context;
			this.pushProcessor = new PushProcessor({
				eventCallbacks: data.eventCallbacks,
			});
		}

		subscribe()
		{
			if (this.moduleId)
			{
				this.unsubscribeCallback = BX.PULL.subscribe({
					moduleId: this.moduleId,
					callback: this.processPullEvent.bind(this),
				});
			}

			return null;
		}

		unsubscribe()
		{
			if (this.unsubscribeCallback)
			{
				this.unsubscribeCallback();
			}
		}

		processPullEvent(data = {})
		{
			if (!this.callback)
			{
				return;
			}

			this.callback(data, this.context)
				.then((response) => {
					if (response.isBatchMode)
					{
						Object.keys(response.data).forEach((eventName) => {
							this.processPullItems(eventName, response.data[eventName]);
						});
					}
					else
					{
						this.processPullItems(response.params.eventName, response.params.items);
					}
				}, (reason) => {
					console.log('processPullEvent callback rejected', reason);
				})
				.catch((err) => {
					console.error(err);
				});
		}

		processPullItems(eventName, items)
		{
			this.pushProcessor.addToQueue(eventName, items);
		}

		getNotificationText(countOfNewElementsFromPull)
		{
			if (countOfNewElementsFromPull > 0)
			{
				if (this.notificationAddText)
				{
					return this.notificationAddText.replaceAll(/(%COUNT%)|(#COUNT#)/g, countOfNewElementsFromPull);
				}

				return Loc.getMessage(
					'MOBILE_STATEFUL_LIST_PULL_NOTIFICATION_ADD',
					{ '%COUNT%': countOfNewElementsFromPull },
				);
			}

			return this.notificationUpdateText ?? Loc.getMessage('MOBILE_STATEFUL_LIST_PULL_NOTIFICATION_UPDATE');
		}
	}

	module.exports = { Pull };
});
