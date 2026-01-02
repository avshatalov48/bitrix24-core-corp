/**
 * @module im/messenger/controller/sidebar-v2/controller/comment/src/view
 */
jn.define('im/messenger/controller/sidebar-v2/controller/comment/src/view', (require, exports, module) => {
	const { SidebarBaseView } = require('im/messenger/controller/sidebar-v2/controller/base');
	const { Loc } = require('im/messenger/controller/sidebar-v2/loc');
	const { SidebarAvatar } = require('im/messenger/controller/sidebar-v2/ui/sidebar-avatar');
	const { BBCodeText } = require('ui-system/typography');
	const { Color, Indent } = require('tokens');

	class CommentSidebarView extends SidebarBaseView
	{
		/**
		 * @return {?DialoguesModelState}
		 */
		getParentDialog()
		{
			const currentDialog = this.store.getters['dialoguesModel/getById'](this.dialogId);

			return (currentDialog && currentDialog.parentChatId)
				? this.store.getters['dialoguesModel/getByChatId'](currentDialog.parentChatId)
				: undefined;
		}

		renderAvatar()
		{
			const dialogId = this.getParentDialog()?.dialogId ?? this.dialogId;

			return SidebarAvatar({
				dialogId,
				size: 72,
				testId: 'avatar',
				style: {
					marginRight: Indent.XL3.toNumber(),
				},
			});
		}

		renderChatInfo()
		{
			const parentDialog = this.getParentDialog();

			if (parentDialog?.name)
			{
				const value = Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_CHAT_INFO_PARENT_CHANNEL', {
					'#NAME#': parentDialog.name,
				});

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
						BBCodeText({
							size: 4,
							ellipsize: 'end',
							numberOfLines: 1,
							value: value?.replace('[color]', `[color=${Color.base1.toHex()}]`) ?? '',
							testId: 'chat-info-description-value',
							color: Color.base4,
						}),
					),
				);
			}

			return super.renderChatInfo();
		}
	}

	module.exports = { CommentSidebarView };
});
