/**
 * @module im/messenger/lib/dev/action-timer
 */
jn.define('im/messenger/lib/dev/action-timer', (require, exports, module) => {
	/**
	 * @class ActionTimer
	 */
	class ActionTimer
	{
		constructor()
		{
			this.actionsCollection = {};
		}

		/**
		 * @param {string} actionName
		 */
		start(actionName)
		{
			this.actionsCollection[actionName] = {};
			this.actionsCollection[actionName].start = Date.now();
		}

		/**
		 * @param {string} actionName
		 * @return {boolean}
		 */
		finish(actionName)
		{
			if (
				!this.actionsCollection[actionName]
				|| !this.actionsCollection[actionName].start
			)
			{
				return false;
			}

			this.actionsCollection[actionName].finish = Date.now();

			return true;
		}

		/**
		 * @param {string} actionName
		 * @param {?string} text
		 */
		logDuration(actionName, text = '')
		{
			const previousFinish = this.actionsCollection[actionName]?.finish;
			const isFinished = this.finish(actionName);
			if (!isFinished)
			{
				return;
			}

			let fromLastFinish = '';
			if (previousFinish)
			{
				const timeFromLastFinishInSeconds = (this.actionsCollection[actionName].finish - previousFinish) / 1000;
				fromLastFinish += `\nDIFF: ${timeFromLastFinishInSeconds.toFixed(2)}s`;
			}

			const additionalText = (typeof text === 'string' && text !== '') ? ` to ${text}` : '';
			console.warn(this.#getDuration(actionName) + additionalText + fromLastFinish);
		}

		/**
		 * @param {string} actionName
		 * @return {string|null}
		 */
		#getDuration(actionName)
		{
			if (
				!this.actionsCollection[actionName]
				|| !this.actionsCollection[actionName].start
				|| !this.actionsCollection[actionName].finish
			)
			{
				return null;
			}

			const timeFinish = this.actionsCollection[actionName].finish;
			const timeStart = this.actionsCollection[actionName].start;
			const timeInSeconds = (timeFinish - timeStart) / 1000;

			return `ACTION: ${actionName}\nTIME: ${timeInSeconds.toFixed(2)}s`;
		}
	}

	const actionTimer = new ActionTimer();
	/**
	 * @type {ActionTimer}
	 */
	window.imMessengerActionTimer = actionTimer;

	module.exports = {
		ActionTimer,
		actionTimer,
	};
});
