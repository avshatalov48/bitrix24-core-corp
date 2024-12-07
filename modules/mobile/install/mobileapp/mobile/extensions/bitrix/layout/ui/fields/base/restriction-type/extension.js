/**
 * @module layout/ui/fields/base/restriction-type
 */
jn.define('layout/ui/fields/base/restriction-type', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class RestrictionType
	 */
	class RestrictionType extends BaseEnum
	{
		static ADD = new RestrictionType('ADD', 0b0001);
		static UPDATE = new RestrictionType('UPDATE', 0b0010);
		static REMOVE = new RestrictionType('REMOVE', 0b0100);
		static NONE = new RestrictionType('NONE', 0b0000);
		static FULL = new RestrictionType(
			'FULL',
			RestrictionType.ADD | RestrictionType.UPDATE | RestrictionType.REMOVE,
		);
	}

	/**
	 * @param {RestrictionType|number} restrictionPolicy
	 * @param {RestrictionType} restrictionType
	 * @return {boolean}
	 */
	const hasRestriction = (restrictionPolicy, restrictionType) => {
		return (restrictionPolicy & restrictionType.getValue()) === restrictionType.getValue();
	};

	module.exports = { RestrictionType, hasRestriction };
});
