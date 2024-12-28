/* eslint-disable es/no-nullish-coalescing-operators */

/**
 * @module im/messenger/controller/dialog/copilot/dialog
 */
jn.define('im/messenger/controller/dialog/copilot/dialog', (require, exports, module) => {
	const {
		CopilotButtonType,
		EventType,
		BotCode,
		Analytics,
		DialogWidgetType,
		OpenDialogContextType,
	} = require('im/messenger/const');
	const { MessageService } = require('im/messenger/provider/service');
	const { LoggerManager, Logger } = require('im/messenger/lib/logger');

	const { Dialog } = require('im/messenger/controller/dialog/chat');
	const { DialogTextHelper } = require('im/messenger/controller/dialog/lib/helper/text');

	const { CopilotMessageMenu } = require('im/messenger/controller/dialog/copilot/component/message-menu');
	const { CopilotMentionManager } = require('im/messenger/controller/dialog/copilot/component/mention/manager');

	const logger = LoggerManager.getInstance().getLogger('dialog--dialog');
	const { AnalyticsEvent } = require('analytics');

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
				messageId,
				withMessageHighlight,
				dialogTitleParams,
			} = options;

			this.dialogId = dialogId;
			this.contextMessageId = messageId ?? null;
			this.withMessageHighlight = withMessageHighlight ?? false;
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
				this.view.textField.showKeyboard?.();

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

			const userCounter = this.getDialog().userCounter;

			const element = this.openingContext === OpenDialogContextType.push
				? Analytics.Element.push
				: null
			;
			const p3type = userCounter > 2 ? Analytics.CopilotChatType.multiuser : Analytics.CopilotChatType.private;
			const analytics = new AnalyticsEvent()
				.setTool(Analytics.Tool.ai)
				.setCategory(Analytics.Category.chatOperations)
				.setEvent(Analytics.Event.openChat)
				.setType(Analytics.Type.ai)
				.setSection(Analytics.Section.copilotTab)
				.setElement(element)
				.setP3(p3type)
				.setP5(`chatId_${this.getDialog()?.chatId}`);

			try
			{
				analytics.send();
			}
			catch (err)
			{
				Logger.error(`${this.constructor.name}.sendAnalyticsOpenDialog.send.catch:`, err);
			}
		}

		manageTextField(isFirstCall = false)
		{}
	}

	module.exports = { CopilotDialog };
});
