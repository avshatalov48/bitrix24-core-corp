/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/lib/helper/worker
 */
jn.define('im/messenger/lib/helper/worker', (require, exports, module) => {

	/**
	 * @class Worker
	 *
	 * Applies the function once in a certain interval.
	 *
	 * @property {Number} frequency
	 * @property {Function} callback
	 * @property {?String} tickIntervalId
	 */
	class Worker
	{
		/**
		 * @param {Object} options
		 * @param {Number} [options.frequency]
		 * @param {Function} [options.callback]
		 */
		constructor(options = {})
		{
			this.frequency = options.frequency ? options.frequency : 1000;
			this.callback = options.callback ? options.callback : () => {};
			this.isStarted = false;

			this.tickIntervalId = null;
		}

		start()
		{
			this.tickIntervalId = setInterval(this.callback, this.frequency);

			this.isStarted = true;
		}

		stop()
		{
			clearInterval(this.tickIntervalId);

			this.isStarted = false;
		}

		startOnce()
		{
			this.tickIntervalId = setInterval(() => {
				this.stop();

				this.callback();
			}, this.frequency);
		}
	}

	module.exports = {
		Worker,
	};
});
