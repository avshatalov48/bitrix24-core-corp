/**
 * @module im/messenger/controller/sidebar/comment/profile-info
 */
jn.define('im/messenger/controller/sidebar/comment/profile-info', (require, exports, module) => {
	const { SidebarProfileInfo } = require('im/messenger/controller/sidebar/chat/sidebar-profile-info');
	const { Theme } = require('im/lib/theme');

	/**
	 * @class CommentProfileInfo
	 * @typedef {LayoutComponent<SidebarProfileInfoProps, SidebarProfileInfoState>} CommentProfileInfo
	 */
	class CommentProfileInfo extends SidebarProfileInfo
	{
		renderStatusImage()
		{
			return null;
		}

		renderDescription()
		{
			return View(
				{
					style: {
						justifyContent: 'flex-start',
						flexDirection: 'row',
					},
					onClick: () => this.props.callbacks.onClickInfoBLock(),
				},
				Text({
					style: {
						color: Theme.colors.base4,
						fontSize: 14,
						fontWeight: 400,
						textStyle: 'normal',
						textAlign: 'start',
					},
					numberOfLines: 1,
					text: this.props.headData.desc,
					testId: 'SIDEBAR_DESCRIPTION',
				}),
				Text({
					style: {
						paddingLeft: 2,
						color: Theme.colors.base2,
						fontSize: 14,
						fontWeight: 400,
						textStyle: 'normal',
						textAlign: 'start',
					},
					numberOfLines: 1,
					ellipsize: 'end',
					text: this.props.headData.subDesc,
					testId: 'SIDEBAR_SUB_DESCRIPTION',
				}),
			);
		}

		renderDepartment()
		{
			return null;
		}
	}

	module.exports = { CommentProfileInfo };
});
