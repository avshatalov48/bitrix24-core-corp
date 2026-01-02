/**
 * @module im/messenger-v2/provider/pull/lib/input-action-listener
 */
jn.define('im/messenger-v2/provider/pull/lib/input-action-listener', (require, exports, module) => {
	const { Worker } = require('im/messenger/lib/helper');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	const DEFAULT_INPUT_ACTION_DURATION = 25000;

	class InputActionListener
	{
		static #instance;

		#inputActionWorkers = {};

		/**
		 * @return {InputActionListener}
		 */
		static getInstance()
		{
			if (!this.#instance)
			{
				this.#instance = new this();
			}

			return this.#instance;
		}

		getStore()
		{
			return serviceLocator.get('core').getStore();
		}

		/**
		 * @desc Start input action worker by timerId
		 * @param {InputActionListenerActionData} actionData
		 */
		startInputAction(actionData)
		{
			const timerId = this.#buildTimerId(actionData);
			const { duration } = actionData;

			if (this.hasInputAction(timerId))
			{
				this.stopInputAction(timerId);
			}

			this.#inputActionWorkers[timerId] = new Worker({
				frequency: duration || DEFAULT_INPUT_ACTION_DURATION,
				callback: () => {
					this.getStore().dispatch('dialoguesModel/removeInputAction', { ...actionData });
					delete this.#inputActionWorkers[timerId];
				},
			});

			this.#inputActionWorkers[timerId].startOnce();
		}

		/**
		 * @desc Stop input action worker by timerId
		 * @param {string} timerId
		 * @param {boolean} [skipCallback=false]
		 * @return {boolean}
		 */
		stopInputAction(timerId, skipCallback = false)
		{
			if (!this.#inputActionWorkers[timerId])
			{
				return false;
			}

			this.#inputActionWorkers[timerId].stop({ skipCallback });
			delete this.#inputActionWorkers[timerId];

			return true;
		}

		clear()
		{
			Object.keys(this.#inputActionWorkers).forEach((timerId) => {
				this.stopInputAction(timerId, true);
			});
			this.#inputActionWorkers = {};
		}

		/**
		 * @param {object} actionData
		 * @return {string}
		 */
		#buildTimerId(actionData)
		{
			const { dialogId, userId, type } = actionData;

			return `${dialogId}|${userId}|${type}`;
		}

		/**
		 * @desc Returns check is having worker by timerId
		 * @param {string} timerId
		 * @return {boolean}
		 */
		hasInputAction(timerId)
		{
			return Boolean(this.#inputActionWorkers[timerId]?.isHasOnce());
		}

		/**
		 * @param {object} actionData
		 * @return {boolean}
		 */
		checkAndStopInputAction(actionData)
		{
			const timerId = this.#buildTimerId(actionData);

			if (this.hasInputAction(timerId))
			{
				return this.stopInputAction(timerId);
			}

			return false;
		}
	}

	module.exports = { InputActionListener };
});
