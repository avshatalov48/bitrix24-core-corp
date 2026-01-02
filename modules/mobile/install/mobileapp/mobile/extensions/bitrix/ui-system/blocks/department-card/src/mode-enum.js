/**
 * @module ui-system/blocks/department-card/src/mode-enum
 */
jn.define('ui-system/blocks/department-card/src/mode-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class DepartmentCardMode
	 * @extends {BaseEnum<DepartmentCardMode>}
	 */
	class DepartmentCardMode extends BaseEnum
	{
		static UPPER = new DepartmentCardMode('UPPER', 'upper');

		static CURRENT = new DepartmentCardMode('CURRENT', 'current');
	}

	module.exports = { DepartmentCardMode };
});
