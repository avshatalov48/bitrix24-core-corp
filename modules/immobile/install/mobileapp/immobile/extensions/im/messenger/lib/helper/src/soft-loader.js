/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module im/messenger/lib/helper/soft-loader
 */
jn.define('im/messenger/lib/helper/soft-loader', (require, exports, module) => {

	const { Type } = require('type');

	/**
	 * @class SoftLoader
	 *
	 * Designed to solve the problem of loader animation blinking.
	 *
	 * @property {Number} safeDisplayTime - minimum animation display time
	 * @property {Number} showDelay - animation start delay
	 * @property {Function} onShow - animation start callback
	 * @property {Function} onHide - animation start callback
	 *
	 * @property {Boolean} _isDisplayed
	 * @property {Number} _displayStartTime
	 * @property {Number} [_showTimeoutId]
	 * @property {Number} [_hideTimeoutId]
	 */
	class SoftLoader
	{
		/**
		 * @param {Object} options
		 * @param {Number} options.safeDisplayTime - minimum animation display time
		 * @param {Number} options.showDelay - animation start delay
		 * @param {Function} options.onShow - animation start callback
		 * @param {Function} options.onHide - animation start callback
		 */
		constructor(options = {})
		{
			if (Type.isNumber(options.safeDisplayTime) && options.safeDisplayTime > 0)
			{
				this.safeDisplayTime = options.safeDisplayTime;
			}
			else
			{
				throw new Error('SoftLoader: options.safeDisplayTime must be a positive number.');
			}

			if (Type.isNumber(options.showDelay) && options.showDelay > 0)
			{
				this.showDelay = options.showDelay;
			}
			else
			{
				this.showDelay = 0;
			}

			this.onShow = Type.isFunction(options.onShow) ? options.onShow : () => {};
			this.onHide = Type.isFunction(options.onHide) ? options.onHide : () => {};

			this._reset();
		}

		_reset()
		{
			this._isDisplayed = false;
			this._displayStartTime = null;

			this._showTimeoutId = null;
			this._hideTimeoutId = null;
		}

		show()
		{
			clearTimeout(this._hideTimeoutId);
			if (this._isDisplayed)
			{
				return;
			}

			if (this.showDelay === 0)
			{
				this._startDisplay();

				return;
			}

			this._showTimeoutId = setTimeout(this._startDisplay.bind(this), this.showDelay);
		}

		hide()
		{
			if (!this._isDisplayed)
			{
				clearTimeout(this._showTimeoutId);
				return;
			}

			const displayTime = Date.now() - this._displayStartTime;
			if (displayTime >= this.safeDisplayTime)
			{
				this._stopDisplay();
				return;
			}

			const remainingDisplayTime = this.safeDisplayTime - displayTime;
			this._hideTimeoutId = setTimeout(this._stopDisplay.bind(this), remainingDisplayTime);
		}

		_startDisplay()
		{
			this._isDisplayed = true;
			this.onShow();
			this._displayStartTime = Date.now();
		}

		_stopDisplay()
		{
			this.onHide();
			this._reset();
		}
	}

	module.exports = {
		SoftLoader,
	};
});
