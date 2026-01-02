/**
 * @module im/messenger/controller/sidebar-v2/controller/notes/src/view
 */
jn.define('im/messenger/controller/sidebar-v2/controller/notes/src/view', (require, exports, module) => {
	const { SidebarBaseView } = require('im/messenger/controller/sidebar-v2/controller/base');
	const { ChatDescription } = require('im/messenger/controller/sidebar-v2/ui/chat-description');
	const { SidebarAvatar } = require('im/messenger/controller/sidebar-v2/ui/sidebar-avatar');
	const { Loc } = require('im/messenger/controller/sidebar-v2/loc');
	const { Text5 } = require('ui-system/typography');
	const { Indent, Color } = require('tokens');

	class NotesSidebarView extends SidebarBaseView
	{
		/**
		 * @protected
		 * @return {SidebarViewTheme}
		 */
		getTheme()
		{
			return {
				titleGap: Indent.XS.toNumber(),
			};
		}

		renderAvatar()
		{
			return SidebarAvatar({
				dialogId: this.dialogId,
				size: 72,
				testId: 'avatar',
				isNotes: true,
				style: {
					marginRight: Indent.XL3.toNumber(),
				},
			});
		}

		renderChatInfo()
		{
			return View(
				{
					testId: 'chat-info-container',
				},
				View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'center',
						},
					},
					Text5({
						text: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_NOTES_PRIVACY_NOTE'),
						color: Color.base4,
						testId: 'chat-info-detail-text-value',
					}),
				),
			);
		}

		renderDescription()
		{
			const text = Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_NOTES_DESCRIPTION');

			return ChatDescription({
				text,
				testId: 'chat-description',
				parentWidget: this.widget,
			});
		}
	}

	module.exports = { NotesSidebarView };
});
