/**
 * @module im/messenger/lib/counters/tab-counters/state
 */
jn.define('im/messenger/lib/counters/tab-counters/state', (require, exports, module) => {
	const { Type } = require('type');

	/**
	 * @class TabCounterState
	 */
	class TabCounterState
	{
		constructor()
		{
			this._value = 0;
			this._detail = {};
		}

		get value()
		{
			return this._value;
		}

		set value(count)
		{
			count = Number(count);
			if (!Type.isNumber(count))
			{
				throw new TypeError('Counter: count is not a number');
			}

			this._value = count;
		}

		get detail()
		{
			return this._detail;
		}

		set detail(value)
		{
			this._detail = value;
		}

		reset()
		{
			this.value = 0;
		}

		clearAll()
		{
			Object.keys(this.detail).forEach((dialogId) => {
				this.detail[dialogId] = 0;
			});
		}

		update()
		{
			Object.keys(this.detail).forEach((dialogId) => {
				this.value += this.detail[dialogId];
			});
		}
	}

	module.exports = {
		TabCounterState,
	};
});
