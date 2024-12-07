/**
 * @module im/messenger/controller/sidebar/chat/friendly-date
 */
jn.define('im/messenger/controller/sidebar/chat/friendly-date', (require, exports, module) => {
	const { FriendlyDate } = require('layout/ui/friendly-date');
	const { UserUtils } = require('im/messenger/lib/utils');
	class SidebarFriendlyDate extends FriendlyDate
	{
		makeText(moment)
		{
			/** {UsersModelState} */
			const userData = BX.prop.getObject(this.props, 'userData', null);

			if (!userData)
			{
				return '';
			}

			const userUtils = new UserUtils();

			return userUtils.getLastDateText(userData, true, true);
		}
	}

	module.exports = { SidebarFriendlyDate };
});
