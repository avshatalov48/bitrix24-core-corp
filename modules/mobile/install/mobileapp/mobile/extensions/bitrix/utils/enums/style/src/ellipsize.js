/**
 * @module utils/enums/style/src/ellipsize
 */
jn.define('utils/enums/style/src/ellipsize', (require, exports, module) => {
	const { isNil } = require('utils/type');
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class Ellipsize
	 * @template TEllipsize
	 * @extends {BaseEnum<Ellipsize>}
	 */
	class Ellipsize extends BaseEnum
	{
		static START = new Ellipsize('START', 'start');

		static MIDDLE = new Ellipsize('MIDDLE', 'middle');

		static END = new Ellipsize('END', 'end');

		static resolveValue(value, defaultValue)
		{
			const ellipsizeValue = isNil(value) ? defaultValue : value;

			return Ellipsize.isDefined(ellipsizeValue) ? ellipsizeValue : Ellipsize.END.toString();
		}
	}

	module.exports = { Ellipsize };
});
