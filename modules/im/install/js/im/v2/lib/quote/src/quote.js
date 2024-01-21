import { Loc } from 'main.core';
import { EventEmitter } from 'main.core.events';

import { Core } from 'im.v2.application.core';
import { EventType, ChatType } from 'im.v2.const';
import { DateFormatter, DateTemplate } from 'im.v2.lib.date-formatter';
import { Parser } from 'im.v2.lib.parser';

import type { ImModelMessage, ImModelUser, ImModelChat } from 'im.v2.model';

const QUOTE_DELIMITER = '-'.repeat(54);

export const Quote = {
	sendQuoteEvent(message: ImModelMessage, text: string)
	{
		EventEmitter.emit(EventType.textarea.insertText, {
			text: this.prepareQuoteText(message, text),
			withNewLine: true,
			replace: false,
		});
	},
	prepareQuoteText(message: ImModelMessage, text: string): string
	{
		let quoteTitle = Loc.getMessage('IM_DIALOG_CHAT_QUOTE_DEFAULT_TITLE');
		if (message.authorId)
		{
			const user: ImModelUser = Core.getStore().getters['users/get'](message.authorId);
			quoteTitle = user.name;
		}

		const quoteDate = DateFormatter.formatByTemplate(message.date, DateTemplate.notification);

		const quoteText = Parser.prepareQuote(message, text);

		let quoteContext = '';
		const dialog: ImModelChat = Core.getStore().getters['chats/getByChatId'](message.chatId);
		if (dialog && dialog.type === ChatType.user)
		{
			quoteContext = `#${dialog.dialogId}:${Core.getUserId()}/${message.id}`;
		}
		else
		{
			quoteContext = `#${dialog.dialogId}/${message.id}`;
		}

		return `${QUOTE_DELIMITER}\n`
			+ `${quoteTitle} [${quoteDate}] ${quoteContext}\n`
			+ `${quoteText}\n`
			+ `${QUOTE_DELIMITER}\n`
		;
	},
};
