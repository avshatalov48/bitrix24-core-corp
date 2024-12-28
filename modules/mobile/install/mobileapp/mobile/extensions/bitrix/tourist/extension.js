/**
 * @module tourist
 */
jn.define('tourist', (require, exports, module) => {
	const { MemoryStorage } = require('native/memorystore');

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
		}

		async #init()
		{
			const inited = await this.storage.get('_inited');

			if (inited)
			{
				return Promise.resolve();
			}

			return BX.ajax.runAction('mobile.tourist.getEvents', { json: {} })
				.then((response) => {
					const operations = [];
					operations.push(this.storage.set('_inited', true));
					for (const eventId of Object.keys(response.data))
					{
						if (isKeyAllowed(eventId))
						{
							operations.push(this.storage.set(eventId, response.data[eventId]));
						}
					}

					return Promise.allSettled(operations);
				})
				.catch((err) => {
					console.error('Cannot fetch tourist events from server', err);

					return this.storage.set('_inited', true);
				});
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
		 * @return {Promise}
		 */
		remember(event)
		{
			assertKeyAllowed(event);

			const cnt = this.storage.getSync(event)?.cnt ?? 0;
			const model = {
				ts: Math.round(Date.now() / 1000),
				cnt: Math.min(cnt + 1, MAX_TIMES_TO_REMEMBER),
			};

			BX.ajax.runAction('mobile.tourist.remember', { json: { event } })
				.then((response) => {
					this.storage.set(event, {
						...model,
						...this.storage.getSync(event),
						...response.data,
					});
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

			return this.storage.set(event, null);
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
	}

	module.exports = {
		Tourist: new Tourist(env.userId),
	};
});
