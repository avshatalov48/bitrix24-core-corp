import {SendMessageHandler} from 'im.event-handler';
import {FormType, WidgetEventType, RestMethod as WidgetRestMethod} from '../const';
import {EventEmitter} from 'main.core.events';
import {DeviceType, EventType, RestMethod as ImRestMethod, RestMethodHandler} from 'im.const';
import {Utils} from 'im.lib.utils';
import {Logger} from 'im.lib.logger';

export class WidgetSendMessageHandler extends SendMessageHandler
{
	application: Object = null;
	storedMessage: string = null;

	constructor($Bitrix)
	{
		super($Bitrix);

		this.application = $Bitrix.Application.get();

		this.onProcessQueueHandler = this.processQueue.bind(this);
		this.onConsentAcceptedHandler = this.onConsentAccepted.bind(this);
		this.onConsentDeclinedHandler = this.onConsentDeclined.bind(this);

		EventEmitter.subscribe(WidgetEventType.processMessagesToSendQueue, this.onProcessQueueHandler);
		EventEmitter.subscribe(WidgetEventType.consentAccepted, this.onConsentAcceptedHandler);
		EventEmitter.subscribe(WidgetEventType.consentDeclined, this.onConsentDeclinedHandler);
	}

	destroy(): void
	{
		super.destroy();
		EventEmitter.unsubscribe(WidgetEventType.processMessagesToSendQueue, this.onProcessQueueHandler);
		EventEmitter.unsubscribe(WidgetEventType.consentAccepted, this.onConsentAcceptedHandler);
		EventEmitter.unsubscribe(WidgetEventType.consentDeclined, this.onConsentDeclinedHandler);
	}

	onSendMessage({data: event})
	{
		event.focus = event.focus !== false;

		//hide smiles
		if (this.getWidgetData().common.showForm === FormType.smile)
		{
			EventEmitter.emit(WidgetEventType.hideForm);
		}

		//show consent window if needed
		if (!this.getWidgetData().dialog.userConsent && this.getWidgetData().common.consentUrl)
		{
			if (event.text)
			{
				this.storedMessage = event.text;
			}

			EventEmitter.emit(WidgetEventType.showConsent);

			return false;
		}

		event.text = event.text ? event.text : this.storedMessage;
		if (!event.text && !event.file)
		{
			return false;
		}

		EventEmitter.emit(WidgetEventType.hideForm);

		if (this.isCreateSessionMode())
		{
			EventEmitter.emit(WidgetEventType.hideForm);
			EventEmitter.emit(EventType.textarea.stopWriting);
			EventEmitter.emitAsync(WidgetEventType.createSession).then(() => {
				this.store.commit('widget/common', {isCreateSessionMode: false});
				this.sendMessage(event.text, event.file);
			});
		}
		else
		{
			this.sendMessage(event.text, event.file);
		}

		if (event.focus)
		{
			EventEmitter.emit(EventType.textarea.setFocus);
		}

		return true;
	}

	sendMessage(text = '', file = null)
	{
		if (!text && !file)
		{
			return false;
		}

		const quoteId = this.store.getters['dialogues/getQuoteId'](this.getDialogId());
		if (quoteId)
		{
			const quoteMessage = this.store.getters['messages/getMessage'](this.getChatId(), quoteId);

			if (quoteMessage)
			{
				text = this.getMessageTextWithQuote(quoteMessage, text);
				EventEmitter.emit(EventType.dialog.quotePanelClose);
			}
		}

		if (!this.controller.application.isUnreadMessagesLoaded())
		{
			this.sendMessageToServer({
				id: 0,
				chatId: this.getChatId(),
				dialogId: this.getDialogId(),
				text,
				file
			});
			this.processQueue();

			return true;
		}

		const params = {};
		if (file)
		{
			params.FILE_ID = [file.id];
		}

		this.addMessageToModel({
			text,
			params,
			sending: !file
		}).then(messageId => {
			if (!this.isDialogStart())
			{
				this.store.commit('widget/common', {dialogStart: true});
			}

			EventEmitter.emit(EventType.dialog.scrollToBottom, {
				chatId: this.getChatId(),
				cancelIfScrollChange: true
			});

			this.addMessageToQueue({messageId, text, file});

			if (this.getChatId())
			{
				this.processQueue();
			}
			else
			{
				EventEmitter.emit(WidgetEventType.requestData);
			}
		});

		return true;
	}

	onClickOnKeyboard({data: event})
	{
		if (event.action === 'ACTION' && event.params.action === 'LIVECHAT')
		{
			const {dialogId, messageId, value} = event.params;
			const values = JSON.parse(value);

			const sessionId = Number.parseInt(values.SESSION_ID, 10);
			if (sessionId !== this.getSessionId() || this.isSessionClose())
			{
				console.error('WidgetSendMessageHandler', this.loc['BX_LIVECHAT_ACTION_EXPIRED']);
				return false;
			}

			this.restClient.callMethod(WidgetRestMethod.widgetActionSend, {
				'MESSAGE_ID': messageId,
				'DIALOG_ID': dialogId,
				'ACTION_VALUE': value,
			});
		}

		if (event.action === 'COMMAND')
		{
			const {dialogId, messageId, botId, command, params} = event.params;

			this.restClient.callMethod(ImRestMethod.imMessageCommand, {
				'MESSAGE_ID': messageId,
				'DIALOG_ID': dialogId,
				'BOT_ID': botId,
				'COMMAND': command,
				'COMMAND_PARAMS': params,
			}).catch(error => console.error('WidgetSendMessageHandler: command processing error', error));
		}
	}

	getWidgetData(): Object
	{
		return this.store.state.widget;
	}

	getChatId(): number
	{
		return this.store.state.application.dialog.chatId;
	}

	getDialogId(): number | string
	{
		return this.store.state.application.dialog.dialogId;
	}

	getUserId(): number
	{
		return this.store.state.widget.user.id;
	}

	getMessageTextWithQuote(quoteMessage, text): string
	{
		let user = null;
		if (quoteMessage.authorId)
		{
			user = this.store.getters['users/get'](quoteMessage.authorId);
		}

		const files = this.store.getters['files/getList'](this.getChatId());

		const quoteDelimiter = '-'.repeat(54);
		const quoteTitle = (user && user.name) ? user.name : this.loc['BX_LIVECHAT_SYSTEM_MESSAGE'];
		const quoteDate = Utils.date.format(quoteMessage.date, null, this.loc);
		const quoteContent = Utils.text.quote(quoteMessage.text, quoteMessage.params, files, this.loc);

		const message = [];
		message.push(quoteDelimiter);
		message.push(`${quoteTitle} [${quoteDate}]`);
		message.push(quoteContent);
		message.push(quoteDelimiter);
		message.push(text);

		return message.join("\n");
	}

	addMessageToQueue({messageId, text, file}): void
	{
		this.messagesToSend.push({
			id: messageId,
			chatId: this.getChatId(),
			dialogId: this.getDialogId(),
			text,
			file,
			sending: false
		});
	}

	sendMessageToServer(message)
	{
		EventEmitter.emit(EventType.textarea.stopWriting);

		// first message, when we didn't have chat
		if (message.chatId === 0)
		{
			message.chatId = this.getChatId();
		}

		this.restClient.callMethod(ImRestMethod.imMessageAdd, {
			'TEMPLATE_ID': message.id,
			'CHAT_ID': message.chatId,
			'MESSAGE': message.text
		}, null, null, Utils.getLogTrackingParams({
			name: ImRestMethod.imMessageAdd,
			data: {timMessageType: 'text'},
			dialog: this.getDialogData()
		})).then(response => {
			this.controller.executeRestAnswer(RestMethodHandler.imMessageAdd, response, message);
		}).catch(error => {
			this.controller.executeRestAnswer(RestMethodHandler.imMessageAdd, error, message);
			Logger.warn('Error during sending message', error);
		});

		return true;
	}

	isDialogStart(): boolean
	{
		return this.store.state.widget.common.dialogStart;
	}

	isCreateSessionMode(): boolean
	{
		return this.store.state.widget.common.isCreateSessionMode;
	}

	getDialogData(): Object
	{
		const dialogId = this.getDialogId();

		return this.store.state.dialogues.collection[dialogId];
	}

	getApplicationModel()
	{
		return this.store.state.application;
	}

	getSessionId()
	{
		return this.store.state.widget.dialog.sessionId;
	}

	isSessionClose()
	{
		return this.store.state.widget.dialog.sessionClose;
	}

	processQueue(): void
	{
		if (this.application.offline)
		{
			return false;
		}

		this.messagesToSend.filter(element => !element.sending).forEach(element => {
			this.deleteFromQueue(element.id);
			element.sending = true;
			if (element.file)
			{
				EventEmitter.emit(EventType.textarea.stopWriting);
				EventEmitter.emit(EventType.uploader.addMessageWithFile, element);
			}
			else
			{
				this.sendMessageToServer(element);
			}
		});
	}

	onConsentAccepted()
	{
		if (!this.storedMessage)
		{
			return;
		}

		const isFocusNeeded = this.getApplicationModel().device.type !== DeviceType.mobile;
		this.onSendMessage({
			data: {focus: isFocusNeeded}
		});
		this.storedMessage = '';
	}

	onConsentDeclined()
	{
		if (!this.storedMessage)
		{
			return;
		}

		EventEmitter.emit(EventType.textarea.insertText, {
			text: this.storedMessage,
			focus: this.getApplicationModel().device.type !== DeviceType.mobile
		});
		this.storedMessage = '';
	}
}