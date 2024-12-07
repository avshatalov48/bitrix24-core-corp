/**
 * @module utils/enums/style/src/align
 */
jn.define('utils/enums/style/src/align', (require, exports, module) => {
	const { isNil } = require('utils/type');
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class Align
	 * @template TAlign
	 * @extends {BaseEnum<Align>}
	 */
	class Align extends BaseEnum
	{
		static TOP = new Align('TOP', 'flex-start');

		static CENTER = new Align('CENTER', 'center');

		static BOTTOM = new Align('BOTTOM', 'flex-end');

		static STRETCH = new Align('STRETCH', 'stretch');

		static BASELINE = new Align('BASELINE', 'baseline');

		static resolveValue(value, defaultValue)
		{
			const AlignValue = isNil(value) ? defaultValue : value;

			return Align.isDefined(AlignValue) ? AlignValue : Align.TOP.toString();
		}
	}

	module.exports = { Align };
});
