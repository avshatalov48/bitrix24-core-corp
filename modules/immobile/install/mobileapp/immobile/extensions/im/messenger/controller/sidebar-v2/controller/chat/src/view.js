/**
 * @module im/messenger/controller/sidebar-v2/controller/chat/src/view
 */
jn.define('im/messenger/controller/sidebar-v2/controller/chat/src/view', (require, exports, module) => {
	const { Indent, Color } = require('tokens');
	const { BBCodeText, Text4, Text5 } = require('ui-system/typography');
	const { IconView, Icon } = require('ui-system/blocks/icon');

	const { SidebarBaseView } = require('im/messenger/controller/sidebar-v2/controller/base');
	const { UserUtils } = require('im/messenger/lib/utils');
	const { SidebarWorkPosition } = require('im/messenger/controller/sidebar-v2/ui/layout/work-position');

	class ChatSidebarView extends SidebarBaseView
	{
		renderChatInfo()
		{
			const titleParams = this.chatTitle.getTitleParams();
			const descriptionText = this.chatTitle.description;
			const detailText = titleParams.detailText;

			const user = this.store.getters['usersModel/getById'](this.dialogId);
			const userStatus = (new UserUtils()).getLastDateText(user);
			const isBot = Boolean(user.bot);

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
					Text4({
						text: descriptionText,
						testId: 'chat-info-description-value',
						color: Color.base1,
					}),
					!isBot && IconView({
						icon: Icon.CHEVRON_TO_THE_RIGHT,
						size: 20,
						color: Color.base4,
						style: {
							marginLeft: Indent.XS.toNumber(),
						},
					}),
				),

				!isBot && View(
					{
						style: {
							marginTop: Indent.XS.toNumber(),
							flexDirection: 'row',
						},
					},
					SidebarWorkPosition({
						userId: this.dialogId,
						testId: 'chat-info-department',
					}),
				),

				(detailText !== descriptionText) && BBCodeText({
					style: {
						marginTop: Indent.XS.toNumber(),
					},
					value: detailText,
					testId: 'chat-info-detail-text-value',
					size: 5,
					color: Color.base4,
				}),

				userStatus && Text5({
					style: {
						marginTop: Indent.XS.toNumber(),
					},
					text: userStatus,
					testId: 'chat-info-user-status-value',
					color: Color.base4,
				}),
			);
		}
	}

	module.exports = { ChatSidebarView };
});
