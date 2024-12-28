/**
 * @module im/messenger/controller/sidebar/channel/profile-btn-view
 */
jn.define('im/messenger/controller/sidebar/channel/profile-btn-view', (require, exports, module) => {
	const { SidebarProfileBtn } = require('im/messenger/controller/sidebar/chat/sidebar-profile-btn');

	/**
	 * @class ChannelProfileBtn
	 * @typedef {LayoutComponent<SidebarProfileBtnProps, SidebarProfileBtnState>} SidebarProfileBtn
	 */
	class ChannelProfileBtn extends SidebarProfileBtn
	{
		/**
		 * @desc Handler update mute btn
		 * @param {LayoutComponent} button
		 * @void
		 */
		onChangeMuteButton(button)
		{
			const oldState = this.state.buttonElements;
			const newState = [...oldState];
			newState[0] = button;
			this.updateStateView({ buttonElements: newState });
		}
	}

	module.exports = { ChannelProfileBtn };
});
