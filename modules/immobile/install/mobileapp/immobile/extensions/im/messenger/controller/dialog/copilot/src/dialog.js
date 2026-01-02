/* eslint-disable es/no-nullish-coalescing-operators */

/**
 * @module im/messenger/controller/dialog/copilot/dialog
 */
jn.define('im/messenger/controller/dialog/copilot/dialog', (require, exports, module) => {
	const { Uuid } = require('utils/uuid');

	const {
		CopilotButtonType,
		EventType,
		BotCode,
		DialogWidgetType,
		OpenDialogContextType,
	} = require('im/messenger/const');
	const { MessageService } = require('im/messenger/provider/services/message');
	const { AnalyticsService } = require('im/messenger/provider/services/analytics');

	const { getLogger } = require('im/messenger/lib/logger');
	const { MessageUiConverter } = require('im/messenger/lib/converter/ui/message');

	const { Dialog } = require('im/messenger/controller/dialog/chat');
	const { DialogTextHelper } = require('im/messenger/controller/dialog/lib/helper/text');
	const { DialogConfigurator } = require('im/messenger/controller/dialog/lib/configurator');

	const { CopilotMessageMenu } = require('im/messenger/controller/dialog/copilot/component/message-menu');
	const { CopilotMentionManager } = require('im/messenger/controller/dialog/copilot/component/mention/manager');

	const logger = getLogger('dialog--dialog');

	/**
	 * @class CopilotDialog
	 */
	class CopilotDialog extends Dialog
	{
		constructor()
		{
			super();

			this.messageButtonTapHandler = this.messageButtonTapHandler.bind(this);
			this.copilotFootnoteTapHandler = this.copilotFootnoteTapHandler.bind(this);
		}

		getDialogType()
		{
			return DialogWidgetType.copilot;
		}

		checkCanHaveAttachments()
		{
			return false;
		}

		/**
		 * @return {MessageMenuController}
		 */
		createMessageMenu()
		{
			return new CopilotMessageMenu(this.getMessageMenuParams());
		}

		subscribeViewEvents()
		{
			super.subscribeViewEvents();
			this.disableParentClassViewEvents();
			this.subscribeCustomViewEvents();
		}

		disableParentClassViewEvents()
		{}

		subscribeCustomViewEvents()
		{
			this.view
				.on(EventType.dialog.messageButtonTap, this.messageButtonTapHandler)
			;
			this.view
				.on(EventType.dialog.copilotFootnoteTap, this.copilotFootnoteTapHandler)
			;
		}

		subscribeStoreEvents()
		{
			super.subscribeStoreEvents();
			this.subscribeCopilotStoreEvents();
		}

		subscribeCopilotStoreEvents()
		{
			this.storeManager
				.on('dialoguesModel/copilotModel/update', this.dialogUpdateHandlerRouter);
		}

		unsubscribeStoreEvents()
		{
			super.unsubscribeStoreEvents();
			this.unsubscribeCopilotStoreEvents();
		}

		unsubscribeCopilotStoreEvents()
		{
			this.storeManager
				.off('dialoguesModel/copilotModel/update', this.dialogUpdateHandlerRouter);
		}

		async initManagers()
		{
			await super.initManagers();
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
				messageId,
				withMessageHighlight,
				dialogTitleParams,
				integrationSettings,
			} = options;

			this.configurator = new DialogConfigurator(integrationSettings);
			this.locator.add('configurator', this.configurator);
			this.headerTitleControllerClassLoadPromise = this.configurator.getHeaderTitleControllerClass();
			this.headerButtonsControllerClassLoadPromise = this.configurator.getHeaderButtonsControllerClass();

			this.dialogId = dialogId;
			this.dialogCode = `im.dialog-${this.getDialogId()}-${Uuid.getV4()}`;
			this.locator.add('dialogId', this.dialogId);
			this.messageUiConverter = new MessageUiConverter({ dialogId, dialogCode: this.dialogCode });
			this.locator.add('message-ui-converter', this.messageUiConverter);
			this.contextMessageId = messageId ?? null;
			this.withMessageHighlight = withMessageHighlight ?? false;
			void this.store.dispatch('applicationModel/openDialogId', dialogId);

			const hasDialog = await this.loadDialogFromDb();
			if (hasDialog)
			{
				this.messageService = new MessageService({
					store: this.store,
					chatId: this.getChatId(),
					dialogId: this.getDialogId(),
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

			this.createWidget(titleParams)
				.catch((error) => {
					logger.error(`${this.constructor.name}.createWidget error:`, error);
				})
			;
		}

		/**
		 *
		 * @param index
		 * @param {Message} message
		 */
		messageAvatarLongTapHandler(index, message)
		{
			const messageModel = this.store.getters['messagesModel/getById'](message.id);
			const dialogModel = this.store.getters['usersModel/getById'](messageModel.authorId);

			if (dialogModel.botData?.code === BotCode.copilot)
			{
				return;
			}

			super.messageAvatarLongTapHandler(index, message);
		}

		/**
		 *
		 * @param messageId
		 * @param {MessageButton} button
		 */
		async messageButtonTapHandler(messageId, button)
		{
			logger.log('Dialog.messageButtonTapHandler', messageId, button);

			if (button.id === CopilotButtonType.copy)
			{
				const modelMessage = this.store.getters['messagesModel/getById'](messageId);
				DialogTextHelper.copyToClipboard(
					modelMessage.text,
					{
						parentWidget: this.view.ui,
					},
				);

				return true;
			}

			if (button.id === CopilotButtonType.promptEdit)
			{
				const currentText = this.view.textField.getText();
				const text = (currentText.endsWith(' ') ? button.text : ` ${button.text}`)
					.replace('...', ' ')
				;
				this.view.textField.replaceText(currentText.length, currentText.length, text);
				this.view.textField.showKeyboard();

				return true;
			}

			if (button.id === CopilotButtonType.promptSend)
			{
				await this.sendMessage(button.text, button.code);
			}
		}

		copilotFootnoteTapHandler()
		{
			const articleCode = '20418172';
			logger.log('Dialog.copilotFootnoteTapHandler, articleCode:', articleCode);
			helpdesk.openHelpArticle(articleCode, 'helpdesk');
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

		sendAnalyticsOpenDialog()
		{
			super.sendAnalyticsOpenDialog();
			if (this.openingContext === OpenDialogContextType.chatCreation)
			{
				return;
			}

			AnalyticsService.getInstance().sendOpenCopilotDialog({
				dialogId: this.dialogId,
				context: this.openingContext,
			});
		}
	}

	module.exports = { CopilotDialog };
});
