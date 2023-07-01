/**
 * @module im/messenger/view/base
 */
jn.define('im/messenger/view/base', (require, exports, module) => {

	class View
	{
		constructor(options = {})
		{
			if (!options.ui)
			{
				throw new Error('View: options.ui is required');
			}

			this.ui = options.ui;

			this.customUiEventEmitter = new JNEventEmitter();
			this.customUiEventList = new Set();
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

			this.customUiEventEmitter.emit(eventName, [ eventData ]);
		}

		on(eventName, eventHandler)
		{
			if (this.customUiEventList.has(eventName))
			{
				this.customUiEventEmitter.on(eventName, eventHandler);

				return this;
			}

			this.ui.on(eventName, eventHandler);

			return this;
		}

		off(eventName, eventHandler)
		{
			if (this.customUiEventList.has(eventName))
			{
				this.customUiEventEmitter.off(eventName, eventHandler);

				return this;
			}

			this.ui.off(eventName, eventHandler);

			return this;
		}

		once(eventName, eventHandler)
		{
			if (this.customUiEventList.has(eventName))
			{
				this.customUiEventEmitter.once(eventName, eventHandler);

				return this;
			}

			this.ui.once(eventName, eventHandler);

			return this;
		}

		removeAll()
		{
			this.customUiEventEmitter.removeAll();
			this.ui.removeAll();
		}
	}

	module.exports = {
		View,
	};
});
