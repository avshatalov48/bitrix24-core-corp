/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/lib/core/runtime
 */
jn.define('im/messenger/lib/core/runtime', (require, exports, module) => {

	const { Type } = jn.require('im/messenger/lib/core/type');

	class Runtime
	{
		debounce(func, wait = 0, context = null)
		{
			let timeoutId;

			return function debounced(...args)
			{
				if (Type.isNumber(timeoutId))
				{
					clearTimeout(timeoutId);
				}

				timeoutId = setTimeout(() => {
					func.apply((context || this), args);
				}, wait);
			};
		}

		throttle(func, wait = 0, context = null)
		{
			let timer = 0;
			let invoke;

			return function wrapper(...args)
			{
				invoke = true;

				if (!timer)
				{
					const q = function q()
					{
						if (invoke)
						{
							func.apply((context || this), args);
							invoke = false;
							timer = setTimeout(q, wait);
						}
						else
						{
							timer = null;
						}
					};
					q();
				}
			};
		}
	}

	module.exports = {
		Runtime: new Runtime(),
	};
});
