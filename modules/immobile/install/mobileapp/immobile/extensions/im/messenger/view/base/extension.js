/**
 * @module im/messenger/view/base
 */
jn.define('im/messenger/view/base', (require, exports, module) => {
	const { EventsCheckpoint } = require('im/messenger/view/lib/events-checkpoint');

	class View
	{
		#viewName;

		constructor(options = {})
		{
			if (!options.ui)
			{
				throw new Error('View: options.ui is required');
			}

			this.ui = options.ui;
			this.#viewName = options.viewName ?? '';
			this.customUiEventEmitter = new JNEventEmitter();
			this.customUiEventList = new Set();
			this.eventsCheckpoint = new EventsCheckpoint();
		}

		get viewName()
		{
			return this.#viewName;
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

			this.customUiEventEmitter.emit(eventName, [eventData]);
		}

		on(eventName, eventHandler)
		{
			const wrappedHandler = this.eventsCheckpoint.add(this.viewName, eventName, eventHandler);
			if (this.customUiEventList.has(eventName))
			{
				this.customUiEventEmitter.on(eventName, wrappedHandler);

				return this;
			}

			this.ui.on(eventName, wrappedHandler);

			return this;
		}

		off(eventName, eventHandler)
		{
			const wrappedHandler = this.eventsCheckpoint.getWrappedHandler(eventHandler);
			if (this.customUiEventList.has(eventName))
			{
				this.customUiEventEmitter.off(eventName, wrappedHandler);

				return this;
			}

			this.ui.off(eventName, wrappedHandler);

			return this;
		}

		once(eventName, eventHandler)
		{
			const wrappedHandler = this.eventsCheckpoint.add(eventName, eventHandler);
			if (this.customUiEventList.has(eventName))
			{
				this.customUiEventEmitter.once(eventName, wrappedHandler);

				return this;
			}

			this.ui.once(eventName, wrappedHandler);

			return this;
		}

		removeAll()
		{
			this.customUiEventEmitter.removeAll();
			this.ui.removeAll();
			// removeAll - delete only own events, the first nesting (ui.eventName)
			// the export object event will not be deleted (ui.textField.eventName)
		}
	}

	module.exports = {
		View,
	};
});
