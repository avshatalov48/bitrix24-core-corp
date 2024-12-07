/**
 * @module im/messenger/controller/sidebar/channel/profile-user-counter-view
 */
jn.define('im/messenger/controller/sidebar/channel/profile-user-counter-view', (require, exports, module) => {
	const { Loc } = require('loc');
	const { SidebarProfileUserCounter } = require('im/messenger/controller/sidebar/chat/sidebar-profile-user-counter');

	/**
	 * @class ChannelProfileUserCounter
	 * @typedef {LayoutComponent<SidebarProfileCounterProps, SidebarProfileCounterState>} SidebarProfileUserCounter
	 */
	class ChannelProfileUserCounter extends SidebarProfileUserCounter
	{
		/**
		 * @desc Create string for label user counter by number
		 * @param {number} userCounter
		 * @return {string}
		 */
		createUserCounterLabel(userCounter)
		{
			return Loc.getMessagePlural(
				'IMMOBILE_DIALOG_SIDEBAR_CHANNEL_USER_COUNTER',
				userCounter,
				{
					'#COUNT#': userCounter,
				},
			);
		}
	}

	module.exports = { ChannelProfileUserCounter };
});
