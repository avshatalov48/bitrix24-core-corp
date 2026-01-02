/**
 * @module tourist
 */
jn.define('tourist', (require, exports, module) => {
	const { MemoryStorage } = require('native/memorystore');
	const { TouristEventsFetcher } = require('tourist/src/events-fetcher');
	const { Type } = require('type');

	const MAX_TIMES_TO_REMEMBER = 1000;

	const RESERVED_KEYS = new Set(['_inited']);
	const isKeyAllowed = (key) => !RESERVED_KEYS.has(key);
	const assertKeyAllowed = (key) => {
		if (!isKeyAllowed(key))
		{
			throw new Error(`Tourist: unable to use reserved key ${key}`);
		}
	};

	class Tourist
	{
		constructor(userId)
		{
			this.userId = userId;
			this.storage = new MemoryStorage(`tourist${this.userId}`);
			this.onInit = this.#init();
			this.eventsFetcher = new TouristEventsFetcher((response) => this.#handleResponse(response));
		}

		async #init()
		{
			const inited = await this.storage.get('_inited');

			if (inited)
			{
				return Promise.resolve();
			}

			return this.#loadEvents();
		}

		/**
		 * @private
		 * @param {function(): Promise} fn
		 * @param {number} maxRetries
		 * @param {number} delayMs
		 * @return {Promise}
		 */
		async #retry(fn, maxRetries = 3, delayMs = 1000)
		{
			for (let attempt = 1; attempt <= maxRetries; attempt++)
			{
				try
				{
					// eslint-disable-next-line no-await-in-loop
					return await fn();
				}
				catch (error)
				{
					const isNetworkError = error?.errors?.some((err) => err.code === 'NETWORK_ERROR');
					if (!isNetworkError || attempt === maxRetries)
					{
						return error;
					}
					const delay = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
					// eslint-disable-next-line no-await-in-loop
					await delay(delayMs);
				}
			}

			throw new Error('Retry failed: should not reach here');
		}

		/**
		 * @private
		 * @return {Promise}
		 */
		async #loadEvents()
		{
			try
			{
				return await this.#retry(() => this.eventsFetcher.call());
			}
			catch (error)
			{
				return this.#handleError(error);
			}
		}

		#handleResponse(response)
		{
			if (response?.status !== 'success')
			{
				return this.#handleError(response);
			}

			const operations = [];
			operations.push(this.storage.set('_inited', true));
			for (const eventId of Object.keys(response.data))
			{
				if (isKeyAllowed(eventId))
				{
					const preparedEventData = response.data[eventId];
					preparedEventData.context = this.#convertContextToObjectIfNeeded(preparedEventData.context);
					operations.push(this.storage.set(eventId, preparedEventData));
				}
			}

			return Promise.allSettled(operations);
		}

		#convertContextToObjectIfNeeded(context)
		{
			if (context && typeof context === 'string')
			{
				return JSON.parse(context);
			}

			return context;
		}

		#handleError(error)
		{
			console.error('Cannot fetch tourist events from server', error);

			return this.storage.set('_inited', false);
		}

		/**
		 * @public
		 * @return {Promise}
		 */
		ready()
		{
			return this.onInit;
		}

		/**
		 * @public
		 * @param {string} event
		 * @return {boolean}
		 */
		firstTime(event)
		{
			return !this.storage.getSync(event);
		}

		/**
		 * Alias for firstTime()
		 * @public
		 * @param {string} event
		 * @return {boolean}
		 */
		never(event)
		{
			return this.firstTime(event);
		}

		/**
		 * @public
		 * @param {string} event
		 * @return {number}
		 */
		numberOfTimes(event)
		{
			return Number(this.storage.getSync(event)?.cnt ?? 0);
		}

		/**
		 * @public
		 * @param {string} event
		 * @return {object|null}
		 */
		getContext(event)
		{
			return this.storage.getSync(event)?.context ?? null;
		}

		/**
		 * @public
		 * @param {string} event
		 * @return {Date|undefined}
		 */
		lastTime(event)
		{
			const ts = this.storage.getSync(event)?.ts;

			return ts ? new Date(ts * 1000) : undefined;
		}

		/**
		 * @public
		 * @param {string} event
		 * @param {object} [options = {}]
		 * @param {object} [options.context = null]
		 * @param {Date} [options.time = null]
		 * @param {?number} [options.count = null]
		 * @return {Promise}
		 */
		remember(event, { context = null, time = null, count = null } = {})
		{
			assertKeyAllowed(event);

			const currentEventData = this.storage.getSync(event) ?? {};
			const currentCount = Number.isFinite(currentEventData?.cnt) ? currentEventData.cnt : 0;
			const counter = count ?? currentCount + 1;
			const newCount = this.#clampEventCount(counter);
			const timestamp = this.#getEventTimestamp(time);

			const model = {
				ts: timestamp,
				cnt: newCount,
				context,
			};

			BX.ajax.runAction('mobile.tourist.remember', {
				json: {
					event,
					context: context ? JSON.stringify(context) : null,
					timestamp: time ? timestamp : null,
					count: count ?? null,
				},
			})
				.then((response) => {
					const data = {
						...model,
						...this.storage.getSync(event),
						...response.data,
					};

					if (Type.isStringFilled(data.context))
					{
						try
						{
							data.context = JSON.parse(response.data.context);
						}
						catch (error)
						{
							console.error('Failed to parse context JSON', error);
							data.context = {};
						}
					}
					else
					{
						data.context = {};
					}

					this.#updateStorage(event, data);
				})
				.catch((err) => {
					console.error('Cannot remember tourist event on server', event, err);
				});

			return this.storage.set(event, model);
		}

		/**
		 * @public
		 * @param {string} event
		 * @return {Promise}
		 */
		forget(event)
		{
			assertKeyAllowed(event);

			void BX.ajax.runAction('mobile.tourist.forget', { json: { event } });

			return this.#updateStorage(event, null);
		}

		#updateStorage(event, eventData)
		{
			this.eventsFetcher.updateCache(event, eventData);

			return this.storage.set(event, eventData);
		}

		/**
		 * Shorthand for firstTime() + remember()
		 * @public
		 * @param {string} event
		 * @return {boolean}
		 */
		rememberFirstTime(event)
		{
			assertKeyAllowed(event);

			if (this.firstTime(event))
			{
				void this.remember(event);

				return true;
			}

			return false;
		}

		#clampEventCount(count = 0)
		{
			return Math.min(count, MAX_TIMES_TO_REMEMBER);
		}

		#getEventTimestamp(time)
		{
			return Math.round((time ?? Date.now()) / 1000);
		}
	}

	module.exports = {
		Tourist: new Tourist(env.userId),
	};
});
