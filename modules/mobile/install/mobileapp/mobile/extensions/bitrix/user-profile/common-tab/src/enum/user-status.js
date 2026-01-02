/**
 * @module user-profile/common-tab/enum/user-status
 */
jn.define('user-profile/common-tab/enum/user-status', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');
	const { Icon } = require('ui-system/blocks/icon');
	const { Color } = require('tokens');

	class UserStatus extends BaseEnum
	{
		static ONLINE = new UserStatus('ONLINE', {
			icon: Icon.EARTH_WITH_CHECK,
			iconColor: Color.accentMainSuccess,
		});

		static FIRED = new UserStatus('FIRED', {
			icon: Icon.EARTH_WITH_CROSS,
			iconColor: Color.base4,
		});

		static DND = new UserStatus('DND', {
			icon: Icon.EARTH_WITH_STOP,
			iconColor: Color.accentMainAlert,
		});

		static ON_VACATION = new UserStatus('ON_VACATION', {
			icon: Icon.EARTH_WITH_TREE,
			iconColor: Color.accentExtraAqua,
		});

		static OFFLINE = new UserStatus('OFFLINE', {
			icon: Icon.EARTH_WITH_CLOCK,
			iconColor: Color.accentMainWarning,
		});

		getIcon()
		{
			return this.getValue().icon;
		}

		getIconColor()
		{
			return this.getValue().iconColor;
		}
	}

	module.exports = {
		UserStatus,
	};
});
