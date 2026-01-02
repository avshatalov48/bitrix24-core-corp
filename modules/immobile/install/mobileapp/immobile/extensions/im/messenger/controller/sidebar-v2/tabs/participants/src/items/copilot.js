/**
 * @module im/messenger/controller/sidebar-v2/tabs/participants/src/items/copilot
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/participants/src/items/copilot', (require, exports, module) => {
	const { ParticipantUserItem } = require('im/messenger/controller/sidebar-v2/tabs/participants/src/items/user');

	/**
	 * @class ParticipantCopilotItem
	 */
	class ParticipantCopilotItem extends ParticipantUserItem
	{
		constructor(props)
		{
			super(props);

			this.dialogChatTitle = this.createChatTitle(this.getDialogId());
		}

		createAvatar()
		{
			return {
				testId: 'copilot-item',
				dialogId: this.getDialogId(),
			};
		}

		createSubtitle()
		{
			const copilotRole = this.#getCopilotRole();
			const dialogModelState = this.#getCopilotDialogModelStateById(this.getDialogId());

			const text = (copilotRole?.default
				? this.chatTitle.getDescription()
				: this.dialogChatTitle.getDescription()) || dialogModelState?.aiProvider;

			return { text };
		}

		createTitle()
		{
			return {
				text: this.chatTitle.getTitle(),
				style: {
					color: this.chatTitle.getTitleColor(),
				},
			};
		}

		getTestId()
		{
			return 'sidebar-tab-copilot-item';
		}

		handleOnClick()
		{
			return null;
		}

		getUserId()
		{
			const { userId } = this.props;

			return userId;
		}

		/**
		 * @returns {?CopilotModelState}
		 */
		#getCopilotDialogModelStateById(id)
		{
			return this.store.getters['dialoguesModel/copilotModel/getByDialogId'](id);
		}

		#getCopilotRole()
		{
			const { copilotRole } = this.props;

			return copilotRole;
		}
	}

	module.exports = {
		ParticipantCopilotItem,
	};
});
