/**
 * @module im/messenger/view/base
 */
jn.define('im/messenger/view/base', (require, exports, module) => {
	const { EventFilter } = require('im/messenger/view/lib/event-filter');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('view--base');

	class View
	{
		/** @type {EventHandlerCollection} */
		#eventHandlerCollection = {};
		/** @type {EventWrappedHandlerCollection} */
		#eventWrappedHandlerCollection = {};

		constructor(options = {})
		{
			if (!options.ui)
			{
				throw new Error('View: options.ui is required');
			}

			this.ui = options.ui;
			this.customUiEventEmitter = new JNEventEmitter();
			this.customUiEventList = new Set();
			this.eventFilter = new EventFilter();
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

		/**
		 * @abstract
		 * @return {AvailableEventCollection}
		 */
		getAvailableEvents()
		{
			logger.warn(`${this.constructor.name}.getAvailableEvents is not implemented`);
		}

		/**
		 * @param {string} eventName
		 * @param {function(string, string, string=): void} eventHandler
		 * @return {object}
		 */
		on(eventName, eventHandler)
		{
			if (!this.#eventHandlerCollection[eventName])
			{
				this.#eventHandlerCollection[eventName] = [];

				const wrappedHandler = this.createHandlerWrapper(eventName);
				this.#eventWrappedHandlerCollection[eventName] = wrappedHandler;
				if (this.customUiEventList.has(eventName))
				{
					this.customUiEventEmitter.on(eventName, wrappedHandler);
					this.#eventHandlerCollection[eventName].push(eventHandler);

					return this;
				}

				this.ui.on(eventName, wrappedHandler);
			}

			this.#eventHandlerCollection[eventName].push(eventHandler);

			return this;
		}

		/**
		 * @param {string} eventName
		 * @param {()=>*} eventHandler
		 * @return {object}
		 */
		off(eventName, eventHandler)
		{
			if (!this.#eventHandlerCollection[eventName])
			{
				return this;
			}
			this.#eventHandlerCollection[eventName] = this.#eventHandlerCollection[eventName].filter(
				(savedHandler) => savedHandler !== eventHandler,
			);

			if (this.#eventHandlerCollection[eventName].length === 0 && this.#eventWrappedHandlerCollection[eventName])
			{
				if (this.customUiEventList.has(eventName))
				{
					this.customUiEventEmitter.off(eventName, this.#eventWrappedHandlerCollection[eventName]);

					return this;
				}

				this.ui.off(eventName, this.#eventWrappedHandlerCollection[eventName]);
				delete this.#eventWrappedHandlerCollection[eventName];
				delete this.#eventHandlerCollection[eventName];
			}

			return this;
		}

		/**
		 * @param {string} eventName
		 * @return {()=>*}
		 */
		createHandlerWrapper(eventName)
		{
			return (...args) => {
				if (!this.eventFilter.canEmitEvent(eventName, this.getAvailableEvents()))
				{
					return;
				}

				this.#eventHandlerCollection[eventName].forEach((eventHandler) => {
					eventHandler(...args);
				});
			};
		}

		/**
		 * @param {string} eventName
		 * @param {()=>*} eventHandler
		 * @return {object}
		 */
		once(eventName, eventHandler)
		{
			const wrappedHandler = (...args) => {
				if (!this.eventFilter.canEmitEvent(eventName, this.getAvailableEvents()))
				{
					return;
				}

				eventHandler(...args);
			};

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
