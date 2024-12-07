/**
 * @module im/messenger/controller/sidebar/channel/profile-info
 */
jn.define('im/messenger/controller/sidebar/channel/profile-info', (require, exports, module) => {
	const { SidebarProfileInfo } = require('im/messenger/controller/sidebar/chat/sidebar-profile-info');

	/**
	 * @class ChannelProfileInfo
	 * @typedef {LayoutComponent<SidebarProfileInfoProps, SidebarProfileInfoState>} ChannelProfileInfo
	 */
	class ChannelProfileInfo extends SidebarProfileInfo
	{
		renderStatusImage()
		{
			return null;
		}

		getStyleDescText()
		{
			const style = super.getStyleDescText();
			delete style.marginLeft;

			return style;
		}

		renderShevronImage()
		{
			return null;
		}

		renderDepartment()
		{
			return null;
		}
	}

	module.exports = { ChannelProfileInfo };
});
