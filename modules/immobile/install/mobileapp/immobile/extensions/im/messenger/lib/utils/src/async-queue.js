/**
 * @module im/messenger/lib/utils/src/async-queue
 */
jn.define('im/messenger/lib/utils/src/async-queue', (require, exports, module) => {
	const { createPromiseWithResolvers } = require('im/messenger/lib/utils/promise');

	/**
	 * @class AsyncQueue
	 */
	class AsyncQueue
	{
		#queue = Promise.resolve();

		/**
		 * @param {() => void | Promise} callback
		 * @param {() => void | Promise} fallback
		 * @return {Promise<void>}
		 */
		async enqueue(callback, fallback = () => {})
		{
			const { resolve, promise } = createPromiseWithResolvers();

			this.#queue = this.#queue
				// eslint-disable-next-line promise/no-callback-in-promise
				.then(() => callback())
				.catch((error) => {
					console.error('AsyncQueue error in callback. Run fallback', error);

					return fallback(error);
				})
				.catch((error) => {
					console.error('AsyncQueue error in fallback!', error);
				})
				.finally(resolve)
			;

			await promise;
		}
	}

	module.exports = {
		AsyncQueue,
	};
});
