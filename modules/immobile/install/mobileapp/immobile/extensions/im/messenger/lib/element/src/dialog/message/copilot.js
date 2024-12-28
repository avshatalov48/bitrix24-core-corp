/**
 * @module im/messenger/lib/element/dialog/message/copilot
 */
jn.define('im/messenger/lib/element/dialog/message/copilot', (require, exports, module) => {
	const { Type } = require('type');
	const { Loc } = require('loc');

	const { MessageType } = require('im/messenger/const');
	const { TextMessage } = require('im/messenger/lib/element/dialog/message/text');
	const { CopilotButtonType } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Logger } = require('im/messenger/lib/logger');

	class CopilotMessage extends TextMessage
	{
		constructor(modelMessage = {}, options = {})
		{
			super(modelMessage, options);

			this.copilot = {};

			this
				.setCopilotButtons()
				.setFootNote()
				.setCanBeQuoted(false)
				.setCanBeChecked(false)
			;
		}

		getType()
		{
			return MessageType.copilot;
		}

		setAvatar(authorId, chatId, messageId)
		{
			const user = serviceLocator.get('core').getStore().getters['usersModel/getById'](authorId);
			const copilotRoleData = serviceLocator.get('core')
				.getStore().getters['dialoguesModel/copilotModel/getRoleByMessageId'](`chat${chatId}`, messageId);
			const copilotAvatar = encodeURI(copilotRoleData?.avatar?.small);
			this.avatarUrl = copilotAvatar || user.avatar;

			this.setUsername(copilotRoleData, user.name);
			this.setAvatarDetail({ ...user, avatar: this.avatarUrl, name: this.username });

			return this;
		}

		/**
		 * @param {UsersModelState|null} user
		 * @void
		 */
		setAvatarDetail(user)
		{
			super.setAvatarDetail(user);

			// TODO: switch to ChatAvatar for CoPilot
			if (Type.isObject(this.avatar) && this.avatar.uri)
			{
				this.avatar.uri = this.avatarUrl;
			}
		}

		/**
		 * @param {?CopilotRoleData} copilotRoleData
		 * @param {string} userName
		 * @return this
		 */
		setUsername(copilotRoleData, userName)
		{
			try
			{
				if (copilotRoleData?.name)
				{
					this.username = userName;
				}

				this.username = `${userName} (${copilotRoleData?.name})`;
				const COPILOT_UNIVERSAL_ROLE = 'copilot_assistant';
				if (copilotRoleData?.code === COPILOT_UNIVERSAL_ROLE)
				{
					this.username = userName;
				}
			}
			catch (error)
			{
				Logger.error(`${this.constructor.name}.setUsername.catch:`, error);
			}

			return this;
		}

		setCopilotButtons()
		{
			this.copilot.buttons = [
				{
					id: CopilotButtonType.copy,
					text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_COPILOT_BUTTON_COPY'),
					editable: false,
					leftIcon: `${currentDomain}/bitrix/mobileapp/immobile/extensions/im/messenger/assets/common/svg/copy.svg`,
				},
			];

			return this;
		}

		setFootNote()
		{
			this.copilot.footnote = `${Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_COPILOT_FOOT_NOTE_BASIC')} [U]${Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_COPILOT_FOOT_NOTE_UNDERLINE')}[/U]`;

			return this;
		}
	}

	module.exports = { CopilotMessage };
});
