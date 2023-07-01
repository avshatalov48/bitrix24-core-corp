/**
 * @module crm/storage/base
 */
jn.define('crm/storage/base', (require, exports, module) => {
	const { get, set, isEqual } = require('utils/object');

	const Event = {
		OnChange: 'onChange',
		OnLoading: 'onLoading',
		OnReady: 'onReady',
		NotifyAnotherContexts: 'notifyAnotherContexts',
	};
	const DATA_KEY = 'data';
	const TTL_KEY = 'ttl';

	/**
	 * @class BaseStorage
	 * @abstract
	 */
	class BaseStorage
	{
		constructor()
		{
			this.ready = false;
			this.fetchingSet = new Set();

			/** Subscribers in current js-context. */
			this.subscribers = new Map();

			this.eventId = null;

			/** Subscription to other js-contexts. */
			BX.addCustomEvent(
				`${this.getEventNamespace()}::${Event.NotifyAnotherContexts}`,
				this.publish.bind(this),
			);
		}

		markReady()
		{
			if (this.ready)
			{
				return;
			}

			this.ready = true;
			this.publish(Event.OnReady);

			return this;
		}

		subscribeOnChange(handler = null)
		{
			const eventId = (this.eventId ? `${this.eventId}.onChange` : null);

			return this.subscribe(Event.OnChange, handler, eventId);
		}

		subscribeOnLoading(handler = null)
		{
			const eventId = (this.eventId ? `${this.eventId}.onLoading` : null);

			return this.subscribe(Event.OnLoading, handler, eventId);
		}

		subscribe(event, handler, eventId = null)
		{
			eventId = eventId || Symbol('eventId');
			this.subscribers.set(eventId, { event, handler });

			return this;
		}

		unsubscribe(eventId)
		{
			this.subscribers.delete(eventId);

			return this;
		}

		/**
		 * @param {String|Event} event
		 * @param {Object|null} eventArgs
		 * @param {Boolean|null} fromCurrentContext
		 */
		publish(event, eventArgs = {}, fromCurrentContext = true)
		{
			[...this.subscribers.values()]
				.filter((sub) => sub.event === event)
				.forEach(({ handler }) => handler(eventArgs))
			;

			if (event === Event.OnChange && fromCurrentContext)
			{
				BX.postComponentEvent(
					`${this.getEventNamespace()}::${Event.NotifyAnotherContexts}`,
					[event, {}, false],
				);
			}
		}

		publishOnChange(eventArgs = {}, fromCurrentContext = true)
		{
			this.publish(Event.OnChange, eventArgs, fromCurrentContext);
		}

		/**
		 * Time to live for cache in seconds.
		 * @return {Number}
		 */
		getDefaultTtl()
		{
			return 600;
		}

		getCurrentTimeInSeconds()
		{
			return Math.floor(Date.now() / 1000);
		}

		/**
		 * @param {String} pathTo
		 * @return {boolean}
		 */
		cacheExpired(pathTo)
		{
			const cacheTime = this.getTtlValue(pathTo);
			const currentTime = this.getCurrentTimeInSeconds();

			return currentTime > cacheTime + this.getDefaultTtl();
		}

		/**
		 * @return {BaseAjax|null}
		 */
		getAjax()
		{
			return null;
		}

		/**
		 * @abstract
		 * @return {String}
		 */
		getEventNamespace()
		{
			throw new Error('Abstract method must be implemented in child class');
		}

		/**
		 * @abstract
		 * @return {String}
		 */
		getStorageKey()
		{
			throw new Error('Abstract method must be implemented in child class');
		}

		getStorageId()
		{
			return `crm/storage/${this.getStorageKey()}`;
		}

		/**
		 * @return {KeyValueStorage}
		 */
		getStorage()
		{
			return Application.storageById(this.getStorageId());
		}

		/**
		 * @return {string}
		 */
		getPathTo(...args)
		{
			return args.join('.');
		}

		/**
		 * @internal
		 * @return {Object|null}
		 */
		getTtlObject()
		{
			return this.getStorage().getObject(TTL_KEY, {});
		}

		/**
		 * @param {String} pathTo
		 * @return {*}
		 */
		getTtlValue(pathTo)
		{
			pathTo += `.${TTL_KEY}`;

			return get(this.getTtlObject(), pathTo, 0);
		}

		/**
		 * @param {String} pathTo
		 * @param {Number} ttl
		 * @return void
		 */
		setTtlValue(pathTo, ttl)
		{
			pathTo += `.${TTL_KEY}`;
			const mutatedTtl = set(this.getTtlObject(), pathTo, ttl);
			this.getStorage().setObject(TTL_KEY, mutatedTtl);
		}

		/**
		 * @param {String} pathTo
		 * @return void
		 */
		clearTtlValue(pathTo)
		{
			this.setTtlValue(pathTo, 0);
		}

		/**
		 * @internal
		 * @return {Object|null}
		 */
		getDataObject()
		{
			return this.getStorage().getObject(DATA_KEY, {});
		}

		/**
		 * @param {String} pathTo
		 * @param defaultValue
		 * @return {*}
		 */
		getDataValue(pathTo, defaultValue = null)
		{
			return get(this.getDataObject(), pathTo, defaultValue);
		}

		/**
		 * @param {String} pathTo
		 * @param {*} value
		 * @return void
		 */
		setDataValue(pathTo, value)
		{
			const mutatedData = set(this.getDataObject(), pathTo, value);
			this.getStorage().setObject(DATA_KEY, mutatedData);
		}

		/**
		 * @param {String} pathTo
		 * @param {Boolean} status
		 */
		emitLoading(pathTo, status)
		{
			this.publish(Event.OnLoading, { status });
		}

		markRequestIsFetching(pathTo, status)
		{
			if (status)
			{
				this.fetchingSet.add(pathTo);
			}
			else
			{
				this.fetchingSet.delete(pathTo);
			}
		}

		/**
		 * @internal
		 * @param {String} pathTo
		 * @param {Object} response
		 */
		handleAjaxResponse(pathTo, response)
		{
			if (response.error || (response.errors && response.errors.length > 0))
			{
				console.warn(`Fetch error for ${this.getStorageId()} {${pathTo}}.`, response);

				const notFoundOrAccessDenied = Array.isArray(response.errors) && response.errors.some((error) => {
					return error.code === 'NOT_FOUND' || error.code === 'ACCESS_DENIED';
				});
				if (!notFoundOrAccessDenied)
				{
					// cancel storage update, it seems like some internal error or internet connection
					return;
				}
			}

			this.setTtlValue(pathTo, this.getCurrentTimeInSeconds());
			this.updateDataInStorage(pathTo, response.data);
		}

		/**
		 * @internal
		 * @param {String} pathTo
		 * @param {Object} data
		 * @param {Boolean} skipEvents
		 */
		updateDataInStorage(pathTo, data, skipEvents = false)
		{
			const currentData = this.getDataValue(pathTo);

			if (!isEqual(currentData, data) || data === null)
			{
				this.setDataValue(pathTo, data);
			}

			if (!skipEvents)
			{
				this.publishOnChange();
			}
		}

		/**
		 * @param {String} pathTo
		 * @return {Boolean}
		 */
		isFetching(pathTo)
		{
			return this.fetchingSet.has(pathTo);
		}

		/**
		 * @param {String} pathTo
		 * @param {String} action
		 * @param {Object|null} ajaxParams
		 * @return void
		 */
		fetch(pathTo, action, ajaxParams = null)
		{
			if (!this.getAjax() || this.isFetching(pathTo))
			{
				return;
			}

			this.markRequestIsFetching(pathTo, true);

			if (this.ready)
			{
				this.fetchInternal(pathTo, action, ajaxParams);
			}
			else
			{
				this.subscribe(Event.OnReady, () => this.fetchInternal(pathTo, action, ajaxParams));
			}
		}

		fetchInternal(pathTo, action, ajaxParams)
		{
			this.emitLoading(pathTo, true);

			this
				.getAjax()
				.fetch(action, ajaxParams)
				.then((result) => this.handleAjaxResponse(pathTo, result))
				.finally(() => {
					this.emitLoading(pathTo, false);
					this.markRequestIsFetching(pathTo, false);
				})
			;
		}

		setEventId(eventId)
		{
			this.eventId = eventId;

			return this;
		}
	}

	module.exports = { BaseStorage };
});
