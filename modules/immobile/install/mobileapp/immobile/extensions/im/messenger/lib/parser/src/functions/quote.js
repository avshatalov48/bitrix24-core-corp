/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */
/* eslint-disable  no-param-reassign */
/**
 * @module im/messenger/lib/parser/functions/quote
 */
jn.define('im/messenger/lib/parser/functions/quote', (require, exports, module) => {

	const { Loc } = require('im/messenger/loc');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { parsedElements, PLACEHOLDER } = require('im/messenger/lib/parser/utils/parsed-elements');
	const { MessageText } = require('im/messenger/lib/parser/elements/dialog/message/text');
	const { QuoteActive } = require('im/messenger/lib/parser/elements/dialog/message/quote-active');
	const { QuoteInactive } = require('im/messenger/lib/parser/elements/dialog/message/quote-inactive');
	const { Code } = require('im/messenger/lib/parser/elements/dialog/message/code');
	const { parserUrl } = require('im/messenger/lib/parser/functions/url');
	const { parserImage } = require('im/messenger/lib/parser/functions/image');
	const { getLogger } = require('im/messenger/lib/logger');
	const logger = getLogger('parser');

	const QUOTE_SIGN = '>>';

	const parserQuote = {
		patterns: {
			DECODE_QUOTE_CONTENT_PATTERN: /-{54}[\r\n\\n]*([\s\S]*?)[\r\n\\n]*-{54}/g,
		},

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

				quoteText = parserImage.simplifyIcon(quoteText);
				quoteText = parserImage.simplifyImage(quoteText);
				const quoteEndIndex = i - 1;

				textLines.splice(quoteStartIndex, quoteEndIndex - quoteStartIndex);
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

		/**
		 * @param {string} text
		 * @param {object} options
		 * @return {string}
		 */
		decodeQuote(text, options)
		{
			logger.log('Parser.decodeQuote text:', text, options);
			text = text.replace(this.patterns.DECODE_QUOTE_CONTENT_PATTERN, (whole, content) => {
				const textWithoutTags = parsedElements.cutTags(content);
				logger.log('Parser.decodeQuote textWithoutTags:', textWithoutTags);

				const userNameMatch = textWithoutTags.match(/^(.*?)\[/s);
				const userName = userNameMatch ? userNameMatch[1].trim() : '';
				logger.log('Parser.decodeQuote userName:', userName);

				const timeTagMatch = textWithoutTags.match(/\[([^\]]*?)]/s);
				const timeTag = timeTagMatch ? timeTagMatch[1].trim() : '';
				logger.log('Parser.decodeQuote timeTag:', timeTag);

				const contextTagMatch = textWithoutTags.match(/(\s+#(?:chat\d+|\d+:\d+)\/\d+)/);
				let contextTag = contextTagMatch ? contextTagMatch[1].trim() : '';
				logger.log('Parser.decodeQuote contextTag:', contextTag);

				let userNameWithData = '';
				if (userName && timeTag)
				{
					userNameWithData = `${userName} ${timeTag}`;
				}
				else
				{
					userNameWithData = textWithoutTags.split(/\s+#(?:chat\d+|\d+:\d+)\/\d+/s)[0]?.trim() || '';
				}
				logger.log('Parser.decodeQuote userNameWithData:', userNameWithData);

				let quoteText = textWithoutTags;
				if (contextTag)
				{
					const lines = textWithoutTags
						.split(/\r?\n/)
						.map((line) => line.trim())
						.filter(Boolean);

					quoteText = lines.length > 1 ? lines.slice(1).join('\n') : '';
				}

				const hasUserBlock = userName && timeTag;
				if (!hasUserBlock && !quoteText)
				{
					// the case, when inside the quote we have only some string in square brackets
					quoteText = timeTag;
				}

				let title = '';
				if (userName)
				{
					title = userName;
				}

				if (userName && !contextTag)
				{
					// the case, for compatibility with web behavior
					title = '';
				}

				if (!userName && userNameWithData && contextTag)
				{
					// the case, for compatibility with web behavior
					title += `${userNameWithData} ${contextTag}`;
					contextTag = '';
				}

				if (!userName && userNameWithData === `[${timeTag}]`)
				{
					/*
					* the case, for compatibility with web behavior (pin own pinned system message in group chat)
					* removes this case, when backend will be sending quote with username on the start string
					*/
					title = '';
				}

				logger.log('Parser.decodeQuote title and quoteText:', { title, quoteText });

				return this.createQuote(title, contextTag, quoteText, userNameWithData, options);
			});

			parsedElements.cleanTags();

			return text;
		},

		/**
		 * @param {string} contextTag
		 * @param {object} options
		 * @return {object}
		 */
		getQuoteContextData(contextTag, options)
		{
			let contextDialogId = '';
			let contextMessageId = '';
			let isActiveQuote = false;

			if (contextTag)
			{
				contextTag = contextTag.trim().slice(1);

				// eslint-disable-next-line prefer-const
				let [, dialogId, user1, user2, messageId] = contextTag
					.match(/((?:chat)?\d+|(\d+):(\d+))\/(\d+)/i);

				contextDialogId = dialogId;
				contextMessageId = messageId;
				const currentDialogId = options.dialogId || serviceLocator.get('core').getStore().getters['applicationModel/getCurrentOpenedDialogId']();
				const isCurrenDialog = [dialogId, String(user1), String(user2)].includes(String(currentDialogId));

				if (isCurrenDialog)
				{
					isActiveQuote = true;
				}

				if (!dialogId.toString().startsWith('chat'))
				{
					user1 = Number.parseInt(user1, 10);
					user2 = Number.parseInt(user2, 10);

					if (MessengerParams.getUserId() === user1)
					{
						contextDialogId = user2;
					}
					else if (MessengerParams.getUserId() === user2)
					{
						contextDialogId = user1;
					}
				}
			}

			return { isActiveQuote, contextDialogId, contextMessageId };
		},

		/**
		 * @param {string} title
		 * @param {string} contextTag
		 * @param {string} quoteText
		 * @param {string} userNameWithData
		 * @param {object} options
		 * @return {string}
		 */
		createQuote(title, contextTag, quoteText, userNameWithData, options)
		{
			const { isActiveQuote, contextDialogId, contextMessageId } = this.getQuoteContextData(contextTag, options);

			if (!isActiveQuote && contextTag)
			{
				title = userNameWithData;
			}

			/**
				Replacement below bb code is needed because native does not support clicking on a mention
				in a quote title yet.
				And there is no product development.
				As needed, remove.
			 */
			let restoredTagTitle = parsedElements.restoreTags(title);
			if (restoredTagTitle)
			{
				restoredTagTitle = restoredTagTitle.replaceAll(/\[USER=\d+](.*?)\[\/USER]/g, '$1').trim();
			}

			let restoredTagQuoteText = parsedElements.restoreTags(quoteText);
			restoredTagQuoteText = parserImage.decodeIcon(restoredTagQuoteText);
			restoredTagQuoteText = parserImage.simplifyImage(restoredTagQuoteText);

			let quoteMark = '';
			if (isActiveQuote)
			{
				restoredTagQuoteText = parserUrl.simplify(restoredTagQuoteText);
				const activeQuote = new QuoteActive(
					restoredTagTitle,
					restoredTagQuoteText,
					contextDialogId,
					contextMessageId,
				);
				const activeQuoteId = parsedElements.add(activeQuote);
				quoteMark = `${PLACEHOLDER}${activeQuoteId}`;
			}
			else
			{
				const inactiveQuote = new QuoteInactive(restoredTagTitle, restoredTagQuoteText);
				const inactiveQuoteId = parsedElements.add(inactiveQuote);
				quoteMark = `${PLACEHOLDER}${inactiveQuoteId}`;
			}

			return quoteMark;
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

		decodeCode(text)
		{
			let result = text;
			let prevPhraseFirstIndex = 0;

			result = result.replaceAll(
				/\[code](.*?)\[\/code]?/gis,
				(textWithTag, context, index) => {
					const textBeforeTag = text.slice(prevPhraseFirstIndex, index).replaceAll(' ', '');
					const textAfterTag = text.slice(index + textWithTag.length).replaceAll(' ', '');

					const startTag = /((.*\n)|^)$/gi.test(textBeforeTag) ? '' : '\n';
					const endTag = /^((\n.*)|$)/gi.test(textAfterTag) ? '' : '\n';
					const formattedContext = context.replace(/(^\n\s*)|(\n\s*$)/, '');
					const code = new Code(formattedContext);
					const codeId = parsedElements.add(code);

					prevPhraseFirstIndex = index + textWithTag.length;

					return `${startTag}${PLACEHOLDER}${codeId}${endTag}`;
				},
			);

			return result;
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

		truncateDoubleLineBreak(text)
		{
			return text.replaceAll(/\n[\t ]*\n/g, '\n');
		},
	};

	module.exports = {
		parserQuote,
	};
});
