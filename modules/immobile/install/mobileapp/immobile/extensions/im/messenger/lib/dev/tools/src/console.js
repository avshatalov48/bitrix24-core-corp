/**
 * @module im/messenger/lib/dev/tools/console
 */
jn.define('im/messenger/lib/dev/tools/console', (require, exports, module) => {
	const { Haptics } = require('haptics');
	const { Color } = require('tokens');
	const { Icon } = require('assets/icons');
	const AppTheme = require('apptheme');
	const { merge } = require('utils/object');

	const {
		EventType,
		RestMethod,
	} = require('im/messenger/const');
	const { AfterScrollMessagePosition } = require('im/messenger/view/dialog');
	const { Notification } = require('im/messenger/lib/ui/notification');
	const { runAction } = require('im/messenger/lib/rest');
	const {
		LoggerManager,
	} = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('dev--console');

	/** @type MessageContextMenuButton */
	const CopyAction = {
		id: 'copy',
		type: 'button',
		text: 'Copy',
		iconName: Icon.COPY.getIconName(),
		style: {
			fontColor: AppTheme.colors.base1,
		},
	};

	/**
	 * @class Console
	 */
	class Console
	{
		static open()
		{
			const instance = new this();
			instance.show();
		}

		constructor()
		{
			this.lastId = 0;
			/**
			 * @type {Array<Message>}
			 */
			this.messageList = [];
			this.bindMethods();
		}

		getMessageId()
		{
			this.lastId++;

			return this.lastId.toString();
		}

		async show()
		{
			logger.enable('log');

			const titleParams = {
				text: 'Console',
			};

			const componentCode = BX.componentParameters.get('COMPONENT_CODE', '');
			titleParams.text += `: ${componentCode}`;

			this.widget = await PageManager.openWidget(
				'chat.dialog',
				{
					titleParams,
				},
			);

			LoggerManager.getInstance().setExternalConsole(this);
			this.initTopMenu();
			this.widget.textField.setPlaceholder('>');

			this.subscribeEvents();
		}

		bindMethods()
		{
			this.textFieldSubmitHandler = this.textFieldSubmitHandler.bind(this);
			this.viewAreaMessagesChangedHandler = this.viewAreaMessagesChangedHandler.bind(this);
			this.scrollToNewMessagesHandler = this.scrollToNewMessagesHandler.bind(this);
			this.messageLongTapHandler = this.messageLongTapHandler.bind(this);
			this.messageMenuActionTapHandler = this.messageMenuActionTapHandler.bind(this);
		}

		subscribeEvents()
		{
			this.widget.textField.on(EventType.dialog.textField.submit, this.textFieldSubmitHandler);
			this.widget.on(EventType.dialog.viewAreaMessagesChanged, this.viewAreaMessagesChangedHandler);
			this.widget.on(EventType.dialog.scrollToNewMessages, this.scrollToNewMessagesHandler);
			this.widget.on(EventType.dialog.messageLongTap, this.messageLongTapHandler);
			this.widget.on(EventType.dialog.messageMenuActionTap, this.messageMenuActionTapHandler);
		}

		initTopMenu()
		{
			const topMenuPopup = dialogs.createPopupMenu();
			const topMenuButtons = [];

			topMenuButtons.push(
				{
					id: 'clear',
					title: 'Clear',
					sectionCode: 'general',
					iconName: 'action_delete',
				},
				{
					id: 'send-error-log',
					title: 'Send error log',
					sectionCode: 'general',
					iconName: 'send',
				},
			);

			const topMenuButtonHandler = async (event, item) => {
				if (event !== 'onItemSelected')
				{
					return;
				}

				if (item.id === 'clear')
				{
					await this.widget.setMessages([]);
				}

				if (item.id === 'send-error-log')
				{
					await this.openSendErrorLogDialog();
				}
			};

			const buttons = [];
			if (topMenuButtons.length > 0)
			{
				topMenuPopup.setData(topMenuButtons, [{ id: 'general' }], topMenuButtonHandler);
				buttons.push(
					{
						type: 'more',
						callback: () => topMenuPopup.show(),
					},
				);
			}

			this.widget.setRightButtons(buttons);
		}

		getBaseMessageFields(args)
		{
			return {
				id: this.getMessageId(),
				type: 'text',
				avatarUrl: null,
				avatar: {
					uri: null,
				},
				showAvatar: false,
				message: [{
					type: 'text',
					text: JSON.stringify(args),
				}],
				time: (new Date()).toTimeString().split(' ')[0],
				style: {
					backgroundColor: Color.chatOtherMessage1.toHex(),
					isBackgroundOn: true,
				},
			};
		}

		createLogMessage(args)
		{
			const logMessage = {};

			return merge(this.getBaseMessageFields(args), logMessage);
		}

		createInfoMessage(args)
		{
			const infoMessage = {
				title: {
					text: 'ℹ️',
				},
			};

			return merge(this.getBaseMessageFields(args), infoMessage);
		}

		createWarnMessage(args)
		{
			const warnMessage = {
				title: {
					text: '⚠️',
				},
				style: {
					backgroundColor: Color.accentMainWarning.toHex(),
				},
			};

			return merge(this.getBaseMessageFields(args), warnMessage);
		}

		createErrorMessage(args)
		{
			const errorMessage = {
				title: {
					text: '🚨',
				},
				style: {
					backgroundColor: Color.accentMainAlert.toHex(),
				},
			};

			return merge(this.getBaseMessageFields(args), errorMessage);
		}

		createTraceMessage(args)
		{
			return this.createLogMessage(args);
		}

		async printMessage(message)
		{
			if (this.lastId === 1)
			{
				return this.setMessageList([message]);
			}

			return this.addMessageList([message]);
		}

		async log(args)
		{
			return this.printMessage(this.createLogMessage(args));
		}

		async info(args)
		{
			return this.printMessage(this.createInfoMessage(args));
		}

		async warn(args)
		{
			return this.printMessage(this.createWarnMessage(args));
		}

		async error(args)
		{
			return this.printMessage(this.createErrorMessage(args));
		}

		async trace(args)
		{
			return this.printMessage(this.createLogMessage(args));
		}

		/**
		 * @param {array} messageList
		 */
		setMessageList(messageList)
		{
			this.messageList = messageList.length > 0 ? messageList : [];

			return this.widget.setMessages(messageList);
		}

		/**
		 * @param {array} messageList
		 */
		addMessageList(messageList)
		{
			this.messageList.push(...messageList);

			return this.widget.addMessages(messageList);
		}

		/**
		 * @param {string} id
		 * @return {Message|undefined}
		 */
		getMessageById(id)
		{
			return this.messageList.find((message) => {
				return message.id === String(id);
			});
		}

		textFieldSubmitHandler(inputText)
		{
			logger.log(inputText);
			try
			{
				const result = eval(inputText);
				logger.log(result);
			}
			catch (error)
			{
				logger.error(`${error.name}: ${error.message}`);
			}

			this.widget.textField.clear();
			this.scrollToNewMessagesHandler();
		}

		viewAreaMessagesChangedHandler(indexList, messageList)
		{
			if (indexList.includes(0))
			{
				this.widget.hideScrollToNewMessagesButton();
			}
			else
			{
				this.widget.showScrollToNewMessagesButton();
			}
		}

		async scrollToNewMessagesHandler()
		{
			return this.widget.scrollToMessageByIndex(0, true, () => {}, AfterScrollMessagePosition.bottom);
		}

		messageLongTapHandler(index, message)
		{
			const menu = {
				actionList: [
					CopyAction,
				],
			};

			this.widget.showMenuForMessage(message, menu);
			Haptics.impactMedium();
		}

		messageMenuActionTapHandler(actionId, message)
		{
			if (actionId === CopyAction.id)
			{
				const messageData = this.getMessageById(message?.id);

				Application.copyToClipboard(messageData?.message?.[0]?.text);

				Notification.showToastWithParams({
					message: 'Log copied',
					icon: Icon.COPY,
				});
			}
		}

		async openSendErrorLogDialog()
		{
			const { openDialogSelector } = require('im/messenger/controller/selector/dialog/opener');
			openDialogSelector({
				onItemSelected: ({ item }) => {
					const debugInfoText = JSON.stringify(LoggerManager.getInstance().getDebugInfo().error.reverse());

					const data = {
						dialogId: item.id,
						fields: {
							message: debugInfoText,
						},
					};

					return runAction(RestMethod.imV2ChatMessageSend, { data });
				},
				onClose: () => {
					Notification.showToastWithParams({
						message: 'Error log sent',
						icon: Icon.SENDED,
					});
				},
			});
		}
	}

	module.exports = {
		Console,
	};
});
