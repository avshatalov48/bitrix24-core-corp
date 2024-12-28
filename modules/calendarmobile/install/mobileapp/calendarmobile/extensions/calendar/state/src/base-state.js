/**
 * @module calendar/state/base-state
 */
jn.define('calendar/state/base-state', (require, exports, module) => {
	const setterPrefix = 'set';

	/**
	 * @class BaseState
	 */
	class BaseState
	{
		constructor()
		{
			this.subscribers = [];

			return this.asProxy();
		}

		subscribe(callback)
		{
			this.subscribers.push(callback);
		}

		unsubscribe(callback)
		{
			this.subscribers = this.subscribers.filter((it) => it !== callback);
		}

		emit()
		{
			this.subscribers.forEach((it) => it());
		}

		/**
		 * @private
		 */
		asProxy()
		{
			const properties = Object.getOwnPropertyNames(Object.getPrototypeOf(this));
			const setters = properties.filter((property) => this.isSetter(property));

			return new Proxy(this, {
				get: (target, property) => {
					if (this.isSetter(property))
					{
						const field = this.getFieldName(property);

						return (...args) => {
							if (setters.includes(property))
							{
								target[property](...args);
							}
							else
							{
								target[field] = args[0];
							}

							this.emit();
						};
					}

					return target[property];
				},
			});
		}

		/**
		 * @private
		 */
		isSetter(property)
		{
			const fourthLetter = property[3];

			return fourthLetter && fourthLetter === fourthLetter.toUpperCase() && property.startsWith(setterPrefix);
		}

		/**
		 * @private
		 */
		getFieldName(property)
		{
			const fourthLetter = property[3];

			return fourthLetter.toLowerCase() + property.slice(4);
		}
	}

	module.exports = { BaseState };
});
