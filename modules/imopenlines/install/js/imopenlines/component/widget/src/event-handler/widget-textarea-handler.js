import { TextareaHandler } from "im.event-handler";
import { EventEmitter } from "main.core.events";
import { DeviceType, EventType } from "im.const";
import { WidgetEventType, FormType } from "../const";
import { Utils } from "im.lib.utils";
import { md5 } from "main.md5";

export class WidgetTextareaHandler extends TextareaHandler
{
	application: Object = null;
	pullClient: Object = null;

	constructor($Bitrix)
	{
		super($Bitrix);
		this.application = $Bitrix.Application.get();
		this.pullClient = $Bitrix.PullClient.get();
	}

	onAppButtonClick({data: event})
	{
		if (event.appId === FormType.smile)
		{
			if (this.getWidgetModel().common.showForm === FormType.smile)
			{
				EventEmitter.emit(WidgetEventType.hideForm);
			}
			else
			{
				EventEmitter.emit(WidgetEventType.showForm, {
					type: FormType.smile
				});
			}
		}
		else
		{
			EventEmitter.emit(EventType.textarea.setFocus);
		}
	}

	onFocus()
	{
		if (
			this.getWidgetModel().common.copyright
			&& this.getApplicationModel().device.type === DeviceType.mobile
		)
		{
			this.getWidgetModel().common.copyright = false;
		}

		if (Utils.device.isMobile())
		{
			clearTimeout(this.onFocusScrollTimeout);
			this.onScrollHandler = this.onScroll.bind(this);
			this.onFocusScrollTimeout = setTimeout(() => {
				document.addEventListener('scroll', this.onScrollHandler);
			}, 1000);
		}
	}

	onBlur()
	{
		if (
			!this.getWidgetModel().common.copyright
			&& this.getWidgetModel().common.copyright !== this.application.copyright
		)
		{
			this.getWidgetModel().common.copyright = this.application.copyright;
			setTimeout(() => {
				EventEmitter.emit(EventType.dialog.scrollToBottom, {
					chatId: this.getChatId(),
					force: true
				});
			}, 100);
		}

		if (Utils.device.isMobile())
		{
			clearTimeout(this.onFocusScrollTimeout);
			document.removeEventListener('scroll', this.onScrollHandler);
		}
	}

	// send typed client message to operator
	onKeyUp({data: event})
	{
		if (this.canSendTypedText())
		{
			const {sessionId} = this.getWidgetModel().dialog;
			const chatId = this.getChatId();
			const userId = this.getWidgetModel().user.id;
			const infoString = md5(`${sessionId}/${chatId}/${userId}`);

			const operatorId = this.getWidgetModel().dialog.operator.id;
			const {operatorChatId} = this.getWidgetModel().dialog;
			this.pullClient.sendMessage([operatorId], 'imopenlines', 'linesMessageWrite', {
					text: event.text,
					infoString,
					operatorChatId: operatorChatId
			});
		}
	}

	canSendTypedText()
	{
		return this.getWidgetModel().common.watchTyping
			&& this.getWidgetModel().dialog.sessionId
			&& !this.getWidgetModel().dialog.sessionClose
			&& this.getWidgetModel().dialog.operator.id
			&& this.getWidgetModel().dialog.operatorChatId
			&& this.pullClient.isPublishingEnabled();
	}

	onScroll()
	{
		clearTimeout(this.onScrollTimeout);
		this.onScrollTimeout = setTimeout(() => {
			EventEmitter.emit(EventType.textarea.setBlur, true);
		}, 50);
	}

	getWidgetModel()
	{
		return this.store.state.widget;
	}

	getApplicationModel()
	{
		return this.store.state.application;
	}
}