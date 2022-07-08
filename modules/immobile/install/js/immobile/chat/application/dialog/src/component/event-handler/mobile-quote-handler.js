import { QuoteHandler } from 'im.event-handler';
import { EventEmitter } from 'main.core.events';
import { EventType } from 'im.const';

export class MobileQuoteHandler extends QuoteHandler
{
	quoteMessage(messageId)
	{
		this.store.dispatch('dialogues/update', {
			dialogId: this.getDialogId(),
			fields: {
				quoteId: messageId
			}
		}).then(() => {
			if (this.store.state.application.mobile.keyboardShow)
			{
				return;
			}

			EventEmitter.emit(EventType.mobile.textarea.setFocus);
			setTimeout(() => {
				EventEmitter.emit(EventType.dialog.scrollToBottom, {
					chatId: this.getChatId(),
					duration: 300,
					cancelIfScrollChange: false,
					force: true
				});
			}, 300);
		});
	}

	clearQuote()
	{
		EventEmitter.emit(EventType.mobile.textarea.setText, {text: ''});

		this.store.dispatch('dialogues/update', {
			dialogId: this.getDialogId(),
			fields: {
				quoteId: 0,
				editId: 0
			}
		});
	}

	getChatId()
	{
		return this.store.state.application.dialog.chatId;
	}
}