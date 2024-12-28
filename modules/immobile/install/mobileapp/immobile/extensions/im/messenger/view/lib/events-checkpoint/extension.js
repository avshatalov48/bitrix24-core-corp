/**
 * @module im/messenger/view/lib/events-checkpoint
 */
jn.define('im/messenger/view/lib/events-checkpoint', (require, exports, module) => {
	const { EventType, EventsCheckpointType, ViewName } = require('im/messenger/const');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('view--events-checkpoint');

	/**
	 * @class EventsCheckpoint
	 */
	class EventsCheckpoint
	{
		constructor()
		{
			this.enabledEvents = {
				selectMessagesMode: {
					[ViewName.dialog]: [
						EventType.dialog.topReached,
						EventType.dialog.bottomReached,
						EventType.dialog.loadBottomPage,
						EventType.dialog.loadTopPage,
						EventType.dialog.messageRead,
						EventType.dialog.visibleMessagesChanged,
						EventType.dialog.scrollToNewMessages,
					],
					[ViewName.dialogActionPanel]: [
						EventType.dialog.actionPanel.buttonTap,
					],
					[ViewName.dialogSelector]: [
						EventType.dialog.multiSelect.selected,
						EventType.dialog.multiSelect.unselected,
						EventType.dialog.multiSelect.maxCountExceeded,
					],
				},
			};

			this.checkpoints = {
				[EventsCheckpointType.selectMessagesMode]: false,
			};
			/** @type {Map<Function, Function>} */
			this.handlerMapCollection = new Map();
		}

		/**
		 * @param {string} checkpoint
		 */
		activateCheckpoint(checkpoint)
		{
			logger.log(`${this.constructor.name} activateCheckpoint:`, checkpoint);
			this.checkpoints[checkpoint] = true;
		}

		/**
		 * @param {string} checkpoint
		 */
		deactivateCheckpoint(checkpoint)
		{
			logger.log(`${this.constructor.name} deactivateCheckpoint:`, checkpoint);
			this.checkpoints[checkpoint] = false;
		}

		/**
		 * @return {Array<string>}
		 */
		getActiveCheckpoints()
		{
			return Object.keys(this.checkpoints).filter((checkpointKey) => this.checkpoints[checkpointKey] === true) || [];
		}

		/**
		 * @param {string} viewName
		 * @param {string} eventName
		 * @param {() => any} eventHandler
		 */
		add(viewName, eventName, eventHandler)
		{
			const wrappedHandler = (...args) => {
				const activeCheckpoints = this.getActiveCheckpoints();
				if (activeCheckpoints.length > 0)
				{
					activeCheckpoints.forEach((checkpoint) => {
						if (this.enabledEvents[checkpoint][viewName]?.includes(eventName))
						{
							eventHandler(...args);
						}
						else
						{
							logger.info(`${this.constructor.name} handler - ${eventName} is not called for checkpoint - ${checkpoint} - viewName - ${viewName}`);
						}
					});
				}
				else
				{
					eventHandler(...args);
				}
			};

			this.handlerMapCollection.set(eventHandler, wrappedHandler);

			return wrappedHandler;
		}

		/**
		 * @param {() => any} eventHandler
		 * @return {() => any}
		 */
		getWrappedHandler(eventHandler)
		{
			return this.handlerMapCollection.get(eventHandler);
		}

		/**
		 * @param {JNBaseClass} view
		 * @param {string} viewName
		 * @return {() => any}
		 */
		addByView(view, viewName)
		{
			const onMethod = view.on;

			return (eventName, eventHandler) => {
				const wrappedHandler = this.add(viewName, eventName, eventHandler);
				onMethod.apply(view, [eventName, wrappedHandler]);
			};
		}

		/**
		 * @param {JNBaseClass} view
		 * @return {() => any}
		 */
		removeByView(view)
		{
			const offMethod = view.off;

			return (eventName, eventHandler) => {
				const wrappedHandler = this.getWrappedHandler(eventHandler);
				offMethod.apply(view, [eventName, wrappedHandler]);
			};
		}
	}

	module.exports = {
		EventsCheckpoint,
	};
});
