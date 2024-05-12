/**
 * @module im/messenger/controller/dialog/copilot/dialog
 */
jn.define('im/messenger/controller/dialog/copilot/dialog', (require, exports, module) => {
	const { Uuid } = require('utils/uuid');

	const {
		CopilotButtonType,
		EventType,
		BotCode,
	} = require('im/messenger/const');
	const { MessageService } = require('im/messenger/provider/service');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { MessengerParams } = require('im/messenger/lib/params');

	const { Dialog } = require('im/messenger/controller/dialog/chat');
	const { DialogTextHelper } = require('im/messenger/controller/dialog/lib/helper/text');

	const { CopilotMessageMenu } = require('im/messenger/controller/dialog/copilot/component/message-menu');
	const { CopilotMentionManager } = require('im/messenger/controller/dialog/copilot/component/mention/manager');

	const logger = LoggerManager.getInstance().getLogger('dialog--dialog');

	/**
	 * @class CopilotDialog
	 */
	class CopilotDialog extends Dialog
	{
		constructor()
		{
			super();

			this.messageButtonTapHandler = this.onMessageButtonTap.bind(this);
			this.onCopilotFootnoteTap = this.onCopilotFootnoteTap.bind(this);
		}

		getDialogType()
		{
			return 'copilot';
		}

		checkCanHaveAttachments()
		{
			return false;
		}

		initComponents()
		{
			super.initComponents();

			this.messageMenuComponent = new CopilotMessageMenu({
				serviceLocator: this.locator,
				dialogId: this.getDialogId(),
			});
		}

		subscribeViewEvents()
		{
			super.subscribeViewEvents();
			this.disableParentClassViewEvents();
			this.subscribeCustomViewEvents();
		}

		disableParentClassViewEvents()
		{
			this.view
				.off(EventType.dialog.statusFieldTap, this.statusFieldTapHandler)
			;
		}

		subscribeCustomViewEvents()
		{
			this.view
				.on(EventType.dialog.messageButtonTap, this.messageButtonTapHandler)
			;
			this.view
				.on(EventType.dialog.copilotFootnoteTap, this.onCopilotFootnoteTap)
			;
		}

		initManagers()
		{
			super.initManagers();
		}

		initMentionManager()
		{
			const dialogModelState = this.store.getters['dialoguesModel/getById'](this.dialogId);
			if (dialogModelState && dialogModelState.userCounter > 2)
			{
				this.mentionManager = new CopilotMentionManager({
					view: this.view,
					dialogId: this.dialogId,
				});
			}
		}

		async open(options)
		{
			const {
				dialogId,
				dialogTitleParams,
			} = options;

			this.dialogId = dialogId;

			void this.store.dispatch('applicationModel/openDialogId', dialogId);

			const hasDialog = await this.loadDialogFromDb();
			if (hasDialog)
			{
				this.messageService = new MessageService({
					store: this.store,
					chatId: this.getChatId(),
				});
			}

			this.firstDbPagePromise = this.loadHistoryMessagesFromDb();

			let titleParams = null;
			if (dialogTitleParams)
			{
				titleParams = {
					text: dialogTitleParams.name,
					detailText: dialogTitleParams.description,
					imageUrl: dialogTitleParams.avatar,
					useLetterImage: true,
				};

				if (!dialogTitleParams.avatar || dialogTitleParams.avatar === '')
				{
					titleParams.imageColor = dialogTitleParams.color;
				}
			}

			this.createWidget(titleParams);
		}

		/**
		 *
		 * @param index
		 * @param {Message} message
		 */
		onMessageAvatarLongTap(index, message)
		{
			const messageModel = this.store.getters['messagesModel/getById'](message.id);
			const dialogModel = this.store.getters['usersModel/getById'](messageModel.authorId);

			if (dialogModel.botData?.code === BotCode.copilot)
			{
				return;
			}

			super.onMessageAvatarLongTap(index, message);
		}

		/**
		 *
		 * @param messageId
		 * @param {CopilotButton} button
		 */
		onMessageButtonTap(messageId, button)
		{
			logger.log('Dialog.onMessageButtonTap', messageId, button);

			if (button.id === CopilotButtonType.copy)
			{
				const modelMessage = this.store.getters['messagesModel/getById'](messageId);
				DialogTextHelper.copyToClipboard(modelMessage);

				return;
			}

			if (button.id === CopilotButtonType.promtEdit)
			{
				const currentText = this.view.textField.getText();
				const text = (currentText.endsWith(' ') ? button.text : ` ${button.text}`)
					.replace('...', ' ')
				;
				this.view.textField.replaceText(currentText.length, currentText.length, text);
				this.view.textField.showKeyboard?.();

				return;
			}

			if (button.id === CopilotButtonType.promtSend)
			{
				this.sendMessage(button.text);
			}
		}

		onCopilotFootnoteTap()
		{
			const articleCode = '20418172';
			logger.log('Dialog.onCopilotFootnoteTap, articleCode:', articleCode);
			helpdesk.openHelpArticle(articleCode, 'helpdesk');
		}

		/**
		 * @desc Create new status field by current dialog data and draw it in view
		 * @param {boolean} [isCheckBottom=true]
		 * @override
		 */
		drawStatusField(isCheckBottom = true)
		{
			// TODO if need show the status field then remove this override
		}

		/**
		 * @override
		 * @param {Object} mutation
		 * @void
		 */
		checkAvailableMention(mutation)
		{
			if (!mutation.type.includes('dialoguesModel'))
			{
				return;
			}

			// eslint-disable-next-line es/no-optional-chaining
			if (mutation.payload.data?.fields?.userCounter > 2)
			{
				this.mentionManager ??= new CopilotMentionManager({
					view: this.view,
					dialogId: this.dialogId,
				});
			}
			else
			{
				this.mentionManager?.unsubscribeEvents();
				this.mentionManager = null;
			}
		}
	}

	module.exports = { CopilotDialog };
});
