/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module im/messenger/lib/parser/functions/quote
 */
jn.define('im/messenger/lib/parser/functions/quote', (require, exports, module) => {

	const { Loc } = require('loc');
	const { clone } = require('utils/object');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { parsedElements, PLACEHOLDER } = require('im/messenger/lib/parser/utils/parsed-elements');
	const { MessageText } = require('im/messenger/lib/parser/elements/dialog/message/text');
	const { QuoteActive } = require('im/messenger/lib/parser/elements/dialog/message/quote-active');
	const { QuoteInactive } = require('im/messenger/lib/parser/elements/dialog/message/quote-inactive');
	const { parserUrl } = require('im/messenger/lib/parser/functions/url');

	const QUOTE_SIGN = '>>';

	const parserQuote = {
		decodeArrowQuote(text)
		{
			if (!text.includes(QUOTE_SIGN))
			{
				return text;
			}

			let isProcessed = false;

			let textLines = text.split('\n');
			for (let i = 0; i < textLines.length; i++)
			{
				if (!textLines[i].startsWith(QUOTE_SIGN))
				{
					continue;
				}

				const quoteStartIndex = i;
				let quoteText = textLines[quoteStartIndex].replace(QUOTE_SIGN, '');
				// remove >> from all next lines
				while (++i < textLines.length && textLines[i].startsWith(QUOTE_SIGN))
				{
					textLines[i] = textLines[i].replace(QUOTE_SIGN, '');
					quoteText += '\n' + textLines[i];
				}
				const quoteEndIndex = i - 1;

				textLines.splice(quoteStartIndex, quoteEndIndex - quoteStartIndex);
				quoteText = parserUrl.simplify(quoteText);
				const inactiveQuoteId = parsedElements.add(new QuoteInactive('', quoteText));
				textLines[quoteStartIndex] = `${PLACEHOLDER}${inactiveQuoteId}`;

				isProcessed = true;
			}

			if (!isProcessed)
			{
				return text;
			}

			return textLines.join('\n');
		},

		decodeQuote(text)
		{
			text = text.replace(
				/-{54}((.*?)\[(.*?)]( #(?:(?:chat)?\d+|\d+:\d+)\/\d+)?)?(.*?)-{54}?/gs,
				(whole, userBlock, userName, timeTag, contextTag, text) => {
					const skipTitle = !userName;
					if (skipTitle && !text) // greedy date detector :(
					{
						text = `${timeTag}`;
					}

					text = text.trim();

					let title = '';
					if (!skipTitle)
					{
						title = userName.trim();
					}

					let contextDialogId = '';
					let contextMessageId = '';
					if (contextTag)
					{
						contextTag = contextTag.trim().slice(1);

						let [, dialogId, user1, user2, messageId] =
							contextTag
								.match(/((?:chat)?\d+|(\d+):(\d+))\/(\d+)/i)
						;

						contextDialogId = dialogId;
						contextMessageId = messageId;
						if (!dialogId.toString().startsWith('chat'))
						{
							user1 = Number.parseInt(user1, 10);
							user2 = Number.parseInt(user2, 10);
							contextMessageId = messageId;
							if (MessengerParams.getUserId() === user1)
							{
								contextDialogId = user2;
							}
							else if (MessengerParams.getUserId() === user2)
							{
								contextDialogId = user1;
							}
							else
							{
								contextTag = '';
							}
						}
					}

					text = parserUrl.simplify(text);
					let quoteMark;
					if (contextTag)
					{
						const activeQuote = new QuoteActive(title, text, contextDialogId, contextMessageId);
						const activeQuoteId = parsedElements.add(activeQuote);
						quoteMark = `${PLACEHOLDER}${activeQuoteId}`;
					}
					else
					{
						const inactiveQuote = new QuoteInactive(title, text);
						const inActiveQuoteId = parsedElements.add(inactiveQuote);
						quoteMark = `${PLACEHOLDER}${inActiveQuoteId}`;
					}

					return quoteMark;
				}
			);

			return text;
		},

		decodeTextAroundQuotes(text)
		{
			let textLines = text.split('\n');

			textLines.forEach((line, index, lines) => {
				if (index === lines.length - 1)
				{
					return;
				}

				lines[index] += '\n';
			});

			text = '';
			let currentTextId = -1;
			for (let i = 0; i < textLines.length; i++)
			{
				if (textLines[i].startsWith(PLACEHOLDER))
				{
					currentTextId = -1;
					text += textLines[i] + '\n';
					continue;
				}

				let endOfLine = '';
				if (textLines[i] === '')
				{
					endOfLine = '\n';
				}

				if (currentTextId === -1)
				{
					const line = textLines[i] + endOfLine;
					const messageText = new MessageText(line);
					currentTextId = parsedElements.add(messageText);
					text += `${PLACEHOLDER}${currentTextId}` + '\n';

					continue;
				}

				parsedElements._list[currentTextId].text += textLines[i] + endOfLine;
			}

			return text;
		},

		simplifyCode(text, spaceLetter = ' ')
		{
			return text.replace(
				/\[code](<br \/>)?([\0-\uFFFF]*?)\[\/code]/gi,
				`[${Loc.getMessage('IMMOBILE_PARSER_EMOJI_TYPE_CODE')}]` + spaceLetter
			);
		},

		simplifyQuote(text, spaceLetter = ' ')
		{
			return text.replace(
				/-{54}(.*?)-{54}/gims,
				`[${Loc.getMessage('IMMOBILE_PARSER_EMOJI_TYPE_QUOTE')}]` + spaceLetter
			);
		},

		simplifyArrowQuote(text, spaceLetter = ' ')
		{
			text = text.replace(
				new RegExp(`^(${QUOTE_SIGN}(.*))`, 'gim'),
				`[${Loc.getMessage('IMMOBILE_PARSER_EMOJI_TYPE_QUOTE')}]` + spaceLetter
			);

			return text;
		},
	};

	module.exports = {
		parserQuote,
	};
});
