/**
 * @module im/messenger/view/lib/proxy-view
 */
jn.define('im/messenger/view/lib/proxy-view', (require, exports, module) => {
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('view--proxy-view');

	/**
	 * @class ProxyView
	 */
	class ProxyView
	{
		/** @type {EventFilter} */
		#eventFilter;
		/** @type {EventHandlerCollection} */
		#eventHandlerCollection = {};
		/** @type {EventWrappedHandlerCollection} */
		#eventWrappedHandlerCollection = {};

		/**
		 * @constructor
		 * @param {JNBaseClassInterface} ui
		 * @param {EventFilter} eventFilter
		 */
		constructor(ui, eventFilter)
		{
			/** @type {JNBaseClassInterface} */
			this.ui = ui;
			this.#eventFilter = eventFilter;
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
		 * @param {()=>*} handler
		 * @return {object}
		 */
		on(eventName, handler)
		{
			if (!this.isUiAvailable())
			{
				return this;
			}

			return this.#on(eventName, handler);
		}

		/**
		 * @param {string} eventName
		 * @param {()=>*} handler
		 * @return {object}
		 */
		#on(eventName, handler)
		{
			if (!this.#eventHandlerCollection[eventName])
			{
				this.#eventHandlerCollection[eventName] = [];
				const wrappedHandler = this.createHandlerWrapper(eventName);
				this.#eventWrappedHandlerCollection[eventName] = wrappedHandler;
				this.ui.on(eventName, wrappedHandler);
			}

			this.#eventHandlerCollection[eventName].push(handler);

			return this;
		}

		/**
		 * @param {string} eventName
		 * @param {()=>*} handler
		 * @return {object}
		 */
		once(eventName, handler)
		{
			if (!this.isUiAvailable())
			{
				return this;
			}

			const wrappedHandler = (...args) => {
				if (!this.#eventFilter.canEmitEvent(eventName, this.getAvailableEvents()))
				{
					return;
				}

				handler(...args);
			};

			this.ui.once(eventName, wrappedHandler);

			return this;
		}

		/**
		 * @param {string} eventName
		 * @param {()=>*} handler
		 * @return {object}
		 */
		off(eventName, handler)
		{
			if (!this.isUiAvailable())
			{
				return this;
			}

			return this.#off(eventName, handler);
		}

		/**
		 * @param {string} eventName
		 * @param {()=>*} handler
		 * @return {object}
		 */
		#off(eventName, handler)
		{
			if (!this.#eventHandlerCollection[eventName])
			{
				return this;
			}

			this.#eventHandlerCollection[eventName] = this.#eventHandlerCollection[eventName].filter(
				(savedHandler) => savedHandler !== handler,
			);

			if (this.#eventHandlerCollection[eventName].length === 0 && this.#eventWrappedHandlerCollection[eventName])
			{
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
				if (!this.#eventFilter.canEmitEvent(eventName, this.getAvailableEvents()))
				{
					return;
				}

				this.#eventHandlerCollection[eventName].forEach((eventHandler) => {
					eventHandler(...args);
				});
			};
		}

		/**
		 * @return {boolean}
		 */
		isUiAvailable()
		{
			const isUiAvailable = Boolean(this.ui);
			if (isUiAvailable)
			{
				return true;
			}

			logger.error(`${this.constructor.name}.isUiAvailable - this ui does not exist`);

			return false;
		}
	}

	module.exports = {
		ProxyView,
	};
});
