/**
 * @module im/messenger/lib/element/dialog/message/copilot-prompt
 */

jn.define('im/messenger/lib/element/dialog/message/copilot-prompt', (require, exports, module) => {
	const { Loc } = require('loc');

	const { MessageType } = require('im/messenger/const');
	const { TextMessage } = require('im/messenger/lib/element/dialog/message/text');
	const { CopilotButtonType, CopilotPromptType } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Logger } = require('im/messenger/lib/logger');

	class CopilotPromptMessage extends TextMessage
	{
		constructor(modelMessage = {}, options = {})
		{
			super(modelMessage, options);

			this.dialogId = `chat${modelMessage.chatId}`;
			/**
			 *
			 * @type {{promt: {title: string, text: string}, buttons: Array<CopilotButton>}}
			 */
			this.copilot = {
				promt: { title: '', text: '' },
				buttons: [],
			};
			/**
			 * @type {Array<{code: string, promptType: string, title: string, text: string}>}
			 */
			this.initialPrompt = [];

			/**
			 * @type CopilotRoleData
			 */
			const copilotRoleData = serviceLocator.get('core')
				.getStore().getters['dialoguesModel/copilotModel/getRoleByMessageId'](this.dialogId, modelMessage.id);

			this.getInitialPrompts(copilotRoleData, modelMessage.params)
				.setCopilotButtons()
				.setCopilotPromptText()
				.setShowAvatarForce(false)
				.setCanBeQuoted(false)
				.setCanBeChecked(false);
			this.setCopilotPromptAvatar(copilotRoleData);
		}

		/**
		 * @param {CopilotRoleData} copilotRoleData
		 * @param {object} messageParams
		 * @return this
		 */
		getInitialPrompts(copilotRoleData, messageParams)
		{
			try
			{
				this.initialPrompt = copilotRoleData ? copilotRoleData.prompts : [];

				this.setCopilotPromptName(copilotRoleData?.name || copilotRoleData?.desc, messageParams);
			}
			catch (e)
			{
				Logger.error('CopilotPromptMessage.getInitialPrompts.catch:', e);
			}

			return this;
		}

		getType()
		{
			return MessageType.copilotPrompt;
		}

		/**
		 * @desc blocking the value setting
		 * @param modelMessage
		 * @param shouldShowAvatar
		 * @return {CopilotPromptMessage}
		 */
		setShowAvatar(modelMessage, shouldShowAvatar)
		{
			this.showAvatar = false;

			return this;
		}

		setCopilotButtons()
		{
			let basicButtons = this.getRoleButtons();
			if (this.initialPrompt.length === 0)
			{
				basicButtons = this.getBasicButtons();
			}

			this.copilot.buttons = [
				...basicButtons,
			];

			return this;
		}

		getRoleButtons()
		{
			const btns = [];
			this.initialPrompt.forEach((prompt) => {
				if (prompt.promptType === CopilotPromptType.default)
				{
					btns.push({
						id: CopilotButtonType.promptSend,
						text: prompt.title,
						code: prompt.code,
						editable: false,
						leftIcon: null,
					});
				}
			});

			if (btns.length === 0)
			{
				return this.getBasicButtons();
			}

			return btns;
		}

		setCopilotPromptText()
		{
			this.copilot.promt.text = Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_COPILOT_PROMT_TEXT_MSGVER_1');

			return this;
		}

		/**
		 * @param {CopilotRoleData} copilotRoleData
		 * @return this
		 */
		setCopilotPromptAvatar(copilotRoleData)
		{
			const avatarUrl = copilotRoleData?.avatar?.medium || copilotRoleData?.avatar?.small;
			this.copilot.avatarUrl = avatarUrl ? encodeURI(avatarUrl) : null;

			return this;
		}

		/**
		 * @param {string} name
		 * @param {object} messageParams
		 * @return this
		 */
		setCopilotPromptName(name, messageParams)
		{
			let title = name || '';
			if (messageParams?.COMPONENT_PARAMS?.copilotRoleUpdated === true)
			{
				title = Loc.getMessage(
					'IMMOBILE_ELEMENT_DIALOG_MESSAGE_COPILOT_PROMT_TITLE_NOW',
					{
						'#ROLE_NAME#': name,
					},
				);
			}

			if (messageParams?.COMPONENT_PARAMS?.copilotRoleUpdated === false)
			{
				title = Loc.getMessage(
					'IMMOBILE_ELEMENT_DIALOG_MESSAGE_COPILOT_PROMT_TITLE_HELLO',
					{
						'#ROLE_NAME#': name,
					},
				);
			}

			this.copilot.promt.title = title || '';

			return this;
		}

		getBasicButtons()
		{
			return [
				{
					id: CopilotButtonType.promptSend,
					text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_COPILOT_PROMT_ACTION_1'),
					editable: false,
					leftIcon: null,
					code: 'ability',
				},
				{
					id: CopilotButtonType.promptSend,
					text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_COPILOT_PROMT_ACTION_2'),
					editable: false,
					leftIcon: null,
					code: 'greeting',
				},
				{
					id: CopilotButtonType.promptSend,
					text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_COPILOT_PROMT_ACTION_3'),
					editable: false,
					leftIcon: null,
					code: 'congratulation',
				},
			];
		}
	}

	module.exports = { CopilotPromptMessage };
});
