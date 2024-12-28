/**
 * @module layout/ui/user/user-name/src/enums/type-enum
 */
jn.define('layout/ui/user/user-name/src/enums/type-enum', (require, exports, module) => {
	const { Color } = require('tokens');
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class UserType
	 * @template TUserType
	 * @extends {BaseEnum<UserType>}
	 */
	class UserType extends BaseEnum
	{
		static COLLAB = new UserType('COLLAB', {
			color: Color.collabAccentPrimaryAlt,
		});

		static USER = new UserType('USER', {
			color: Color.base1,
		});

		static EXTRANET = new UserType('EXTRANET', {
			color: Color.accentExtraOrange,
		});

		/**
		 * @returns {Color}
		 */
		getColor()
		{
			const { color } = this.getValue();

			return color;
		}

		/**
		 * @returns {string}
		 */
		toHex()
		{
			return this.getColor().toHex();
		}
	}

	module.exports = { UserType };
});
