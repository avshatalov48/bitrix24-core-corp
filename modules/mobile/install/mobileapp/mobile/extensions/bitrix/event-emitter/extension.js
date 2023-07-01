/**
 * @module event-emitter
 */
jn.define('event-emitter', (require, exports, module) => {

	const suppressWarnings = true;

	const callbacksByEventName = new Map();

	/**
	 * @class EventEmitter
	 */
	class EventEmitter
	{
		static create()
		{
			return new this();
		}

		/**
		 * @param {String} uid
		 * @return {EventEmitter}
		 */
		static createWithUid(uid)
		{
			if (typeof uid !== 'string' || uid === '')
			{
				throw new Error('EventEmitter.createWithUid: uid must be a non-empty string.');
			}

			return this.create().setUid(uid);
		}

		/**
		 * @public
		 * @param {String} uid
		 * @return {EventEmitter}
		 */
		setUid(uid)
		{
			/**
			 * @private
			 * @type {String}
			 */
			this.uid = uid;

			return this;
		}

		/**
		 * @public
		 * @return {String}
		 */
		getUid()
		{
			return this.uid;
		}

		/**
		 * Triggers global level event.
		 *
		 * @public
		 * @param {String} eventName
		 * @param {any[]?} args
		 */
		emit(eventName, args = [])
		{
			args = Array.isArray(args) ? args : [args];

			if (this.getUid())
			{
				args = [this.getUid(), ...args];
			}
			else
			{
				if (!suppressWarnings)
				{
					console.warn(`EventEmitter: event "${eventName}" will be handled globally because of empty uid. This may implicitly affect other contexts. Please, use createWithUid() instead.`);
				}
			}

			BX.postComponentEvent(eventName, args);
		}

		/**
		 * @private
		 * @param {String} eventName
		 * @param {Function} callback
		 * @return {Function}
		 */
		getWrappedCallback(eventName, callback)
		{
			if (!callbacksByEventName.has(eventName))
			{
				callbacksByEventName.set(eventName, new WeakMap());
			}

			const callbackWeakMap = callbacksByEventName.get(eventName);
			if (!callbackWeakMap.has(callback))
			{
				callbackWeakMap.set(callback, this.handleCustomEvent.bind(this, eventName, callback));
			}

			return callbackWeakMap.get(callback);
		}

		/**
		 * @private
		 * @param {String} eventName
		 * @param {Function} callback
		 */
		clearWrappedCallback(eventName, callback)
		{
			if (this.hasWrapperCallback(eventName, callback))
			{
				callbacksByEventName.get(eventName).delete(callback);
			}
		}

		/**
		 * @private
		 * @param {String} eventName
		 * @param {Function} callback
		 */
		hasWrapperCallback(eventName, callback)
		{
			if (!callbacksByEventName.has(eventName))
			{
				return false;
			}

			const callbackWeakMap = callbacksByEventName.get(eventName);

			return callbackWeakMap.has(callback);
		}

		/**
		 * Binds handler {callback} to global {eventName} and checks the uid if it exists.
		 *
		 * @public
		 * @param {String} eventName
		 * @param {Function} callback
		 * @return {EventEmitter}
		 */
		on(eventName, callback)
		{
			BX.addCustomEvent(eventName, this.getWrappedCallback(eventName, callback));

			return this;
		}

		/**
		 * Binds handler {callback} to global {eventName} at most once and checks the uid if it exists.
		 *
		 * @public
		 * @param {String} eventName
		 * @param {Function} callback
		 * @return {EventEmitter}
		 */
		once(eventName, callback)
		{
			const wrappedCallback = this.getWrappedCallback(eventName, callback);
			const onceCallback = (...args) => {
				BX.removeCustomEvent(eventName, onceCallback);
				wrappedCallback(...args);
				this.clearWrappedCallback(eventName, callback);
			};

			BX.addCustomEvent(eventName, onceCallback);

			return this;
		}

		off(eventName, callback)
		{
			if (this.hasWrapperCallback(eventName, callback))
			{
				BX.removeCustomEvent(eventName, this.getWrappedCallback(eventName, callback));
				this.clearWrappedCallback(eventName, callback);
			}

			return this;
		}

		/**
		 * @private
		 */
		handleCustomEvent(eventName, callback, uid, ...passThroughArgs)
		{
			const emitterUid = this.getUid();

			if (emitterUid)
			{
				if (emitterUid === uid)
				{
					callback(...passThroughArgs);
				}
				else
				{
					if (!suppressWarnings)
					{
						console.info(`EventEmitter: event "${eventName}" was not handled because of uid mismatch.`, this.getUid(), uid, passThroughArgs);
					}
				}
			}
			else
			{
				callback(uid, ...passThroughArgs);
				if (!suppressWarnings)
				{
					console.warn(`EventEmitter: event "${eventName}" was handled globally without matching uid. This may implicitly affect other contexts. Please, use createWithUid() instead.`, this.getUid(), uid, passThroughArgs);
				}
			}
		}
	}

	module.exports = { EventEmitter };
});
