/**
 * @module im/messenger/view/base
 */
jn.define('im/messenger/view/base', (require, exports, module) => {

	const { EventManager } = jn.require('im/messenger/lib/event-manager');

	class View
	{
		constructor(options = {})
		{
			if (!options.ui)
			{
				throw new Error('View: options.ui is required');
			}

			this.ui = options.ui;

			this.customUiEventManager = new EventManager();
			this.customUiEventList = null;
		}

		setCustomEvents(eventList = [])
		{
			this.customUiEventList = new Set(eventList);
		}

		emitCustomEvent(eventName, eventData)
		{
			if (!this.customUiEventList.has(eventName))
			{
				throw new Error('View: You cannot send an unregistered event, use setCustomEvents(eventList).');
			}

			this.customUiEventManager.emit(eventName, eventData);
		}

		on(eventName, eventHandler)
		{
			if (this.customUiEventList.has(eventName))
			{
				this.customUiEventManager.on(eventName, eventHandler);

				return this;
			}

			this.ui.on(eventName, eventHandler);

			return this;
		}

		off(eventName, eventHandler)
		{
			if (this.customUiEventList.has(eventName))
			{
				this.customUiEventManager.off(eventName, eventHandler);

				return this;
			}

			this.ui.off(eventName, eventHandler);

			return this;
		}

		once(eventName, eventHandler)
		{
			this.ui.once(eventName, eventHandler);

			return this;
		}
	}

	module.exports = {
		View,
	};
});
