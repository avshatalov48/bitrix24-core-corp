/**
 * @module layout/ui/user/enums/src/selector-entity-type
 */
jn.define('layout/ui/user/enums/src/selector-entity-type', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class UserSelectorEntityType
	 * @template TUserSelectorEntityType
	 * @extends {BaseEnum<UserSelectorEntityType>}
	 */
	class UserSelectorEntityType extends BaseEnum
	{
		static COLLABER = new UserSelectorEntityType('COLLAB', 'collaber');

		static EXTRANET = new UserSelectorEntityType('EXTRANET', 'extranet');

		/**
		 * @param {string} entityType
		 * @returns {boolean}
		 */
		static isCollaber(entityType)
		{
			return entityType === UserSelectorEntityType.COLLABER.getValue();
		}

		/**
		 * @param {string} entityType
		 * @returns {boolean}
		 */
		static isExtranet(entityType)
		{
			return entityType === UserSelectorEntityType.EXTRANET.getValue();
		}
	}

	module.exports = { UserSelectorEntityType };
});
