/**
 * @module im/messenger/controller/dialog/lib/emitter
 */
jn.define('im/messenger/controller/dialog/lib/emitter', (require, exports, module) => {
	const { Type } = require('type');
	const { LoggerManager } = require('im/messenger/lib/logger');

	const logger = LoggerManager.getInstance().getLogger('dialog--emitter');

	/**
	 * @implements {IDialogEmitter}
	 */
	class DialogEmitter
	{
		#listeners;

		constructor()
		{
			this.#listeners = new Map();
		}

		on(event, handler)
		{
			if (!Type.isFunction(handler))
			{
				logger.error(`${this.constructor.name} listener must be a function`, handler);

				return this;
			}

			if (!this.#listeners.has(event))
			{
				this.#listeners.set(event, []);
			}

			this.#listeners.get(event).push(handler);

			return this;
		}

		off(event, listener)
		{
			if (!this.#listeners.has(event))
			{
				return this;
			}

			const listeners = this.#listeners.get(event);
			const index = listeners.indexOf(listener);

			if (index !== -1)
			{
				listeners.splice(index, 1);
			}

			return this;
		}

		async emit(event, args)
		{
			if (!this.#listeners.has(event))
			{
				return;
			}

			const listeners = this.#listeners.get(event);

			for await (const listener of listeners)
			{
				const result = listener(...args);

				if (result instanceof Promise)
				{
					await result;
				}
			}
		}
	}

	module.exports = { DialogEmitter };
});
