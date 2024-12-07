/**
 * @module ui-system/form/inputs/input/src/enums/mode-enum
 */
jn.define('ui-system/form/inputs/input/src/enums/mode-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class InputMode
	 * @template TInputMode
	 * @extends {BaseEnum<InputMode>}
	 */
	class InputMode extends BaseEnum
	{
		static STROKE = new InputMode('STROKE', 'stroke');

		static NAKED = new InputMode('NAKED', 'naked');
	}

	module.exports = { InputMode };
});
