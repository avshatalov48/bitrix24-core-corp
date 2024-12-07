/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/lib/parser/parser
 */
jn.define('im/messenger/lib/parser/parser', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Feature } = require('im/messenger/lib/feature');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Logger } = require('im/messenger/lib/logger');
	const { parserUrl } = require('im/messenger/lib/parser/functions/url');
	const { parserQuote } = require('im/messenger/lib/parser/functions/quote');
	const { parserCall } = require('im/messenger/lib/parser/functions/call');
	const { parserCommon } = require('im/messenger/lib/parser/functions/common');
	const { parserEmoji } = require('im/messenger/lib/parser/functions/emoji');
	const { parserLines } = require('im/messenger/lib/parser/functions/lines');
	const { parserMention } = require('im/messenger/lib/parser/functions/mention');
	const { parserSlashCommand } = require('im/messenger/lib/parser/functions/slash-command');
	const { parserAction } = require('im/messenger/lib/parser/functions/action');
	const { parserFont } = require('im/messenger/lib/parser/functions/font');
	const { parserImage } = require('im/messenger/lib/parser/functions/image');
	const { parserDisk } = require('im/messenger/lib/parser/functions/disk');
	const { parsedElements } = require('im/messenger/lib/parser/utils/parsed-elements');
	const { parserSmile } = require('im/messenger/lib/parser/functions/smile');
	const { parserContext } = require('im/messenger/lib/parser/functions/context');

	const parser = {
		decodeMessageFromText(text, options = {})
		{
			if (!Type.isStringFilled(text))
			{
				return [];
			}

			// TODO: support bb code [context]
			text = text.replace(
				/\[context=(chat\d+|\d+:\d+)\/(\d+)](.*?)\[\/context]/gi,
				(whole, dialogId, messageId, message) => message,
			);

			// TODO: support bb code [chat]
			text = text.replace(/\[chat=(imol\|)?(\d+)](.*?)\[\/chat]/gi, (whole, imol, chatId, text) => {
				if (imol)
				{
					return text;
				}

				return whole;
			});

			text = parserCommon.decodeNewLine(text);
			text = parserUrl.prepareGifUrl(text);
			text = parserSmile.decodeSmile(text, options);
			text = parserMention.decode(text);
			text = parserAction.decodePut(text);
			text = parserAction.decodeSend(text);

			if (Feature.isChatDialogWidgetSupportsSendPutCallBbCodes)
			{
				text = parserCall.simplifyPch(text);
				text = parserCall.decode(text);
			}
			else
			{
				text = parserCall.simplify(text);
			}

			text = parserQuote.decodeArrowQuote(text);
			text = parserQuote.decodeQuote(text, options);
			text = parserQuote.decodeCode(text);
			text = parserQuote.decodeTextAroundQuotes(text);

			const elementList = parsedElements.getOrderedList(text);
			parsedElements.clean();

			return elementList;
		},

		decodeTextForAttachBlock(text)
		{
			if (!Type.isStringFilled(text))
			{
				return [];
			}

			let blockText = text;
			blockText = parserCommon.decodeNewLine(blockText);
			blockText = parserMention.decode(blockText);
			blockText = parserAction.decodePut(blockText);
			blockText = parserAction.decodeSend(blockText);
			blockText = parserCall.simplifyPch(blockText);
			blockText = parserCall.decode(blockText);

			return blockText;
		},

		simplifyMessage(modelMessage, messageFiles = null)
		{
			if (!messageFiles)
			{
				messageFiles = serviceLocator.get('core').getStore().getters['messagesModel/getMessageFiles'](modelMessage.id);
			}

			return this.simplify({
				text: modelMessage.text,
				attach: modelMessage.params && modelMessage.params.ATTACH ? modelMessage.params.ATTACH : false,
				files: messageFiles,
			});
		},

		simplify(config)
		{
			if (!Type.isPlainObject(config))
			{
				Logger.error('parser.simplify: the first parameter must be a object', config);

				return 'parser.simplify: the first parameter must be a parameter object';
			}

			let {
				text,
			} = config;
			const {
				attach = false,
				files = false,
				replaces = [],
				showIconIfEmptyText = true,
				showPhraseMessageWasDeleted = true,
				showFilePrefix = false,
			} = config;

			if (!Type.isString(text))
			{
				text = Type.isNumber(text) ? text.toString() : '';
			}

			if (!text)
			{
				text = parserEmoji.addIconToShortText({ text, attach, files, showFilePrefix });

				return text.trim();
			}

			text = text.trim();

			text = parserCommon.simplifyNewLine(text, '\n');
			text = parserSlashCommand.simplify(text);
			text = parserQuote.simplifyArrowQuote(text);
			text = parserQuote.simplifyQuote(text);
			text = parserQuote.simplifyCode(text);
			text = parserAction.simplifyPut(text);
			text = parserAction.simplifySend(text);
			text = parserMention.simplify(text);
			text = parserFont.simplify(text);
			text = parserLines.simplify(text);
			text = parserCall.simplify(text);
			text = parserImage.simplifyLink(text);
			text = parserImage.simplifyIcon(text);
			text = parserUrl.simplify(text);
			text = parserDisk.simplify(text);
			text = parserCommon.simplifyNewLine(text);
			text = parserEmoji.addIconToShortText({
				text,
				attach,
				files,
				showFilePrefix,
			});

			text = parserContext.simplify(text);

			if (text.length === 0 && showPhraseMessageWasDeleted)
			{
				text = Loc.getMessage('IMMOBILE_PARSER_MESSAGE_DELETED');
			}

			return text.trim();
		},

		prepareCopy(modelMessage)
		{
			let text = modelMessage.text;
			text = parserUrl.simplify(text);
			text = parserUrl.removeBR(text);

			return text;
		},

		prepareQuote(modelMessage)
		{
			const {
				id,
				params,
			} = modelMessage;
			let {
				text,
			} = modelMessage;

			const attach = params.ATTACH || false;
			const files = serviceLocator.get('core').getStore().getters['messagesModel/getMessageFiles'](id);

			text = text.trim();

			text = parserMention.simplify(text);
			text = parserCall.simplify(text);
			text = parserLines.simplify(text);
			text = parserCommon.simplifyBreakLine(text, '\n');
			text = parserCommon.simplifyNbsp(text);
			text = parserUrl.removeSimpleUrlTag(text);
			text = parserQuote.simplifyCode(text, ' ');
			text = parserQuote.simplifyQuote(text, ' ');
			text = parserQuote.simplifyArrowQuote(text, ' ');
			text = parserEmoji.addIconToShortText({
				text,
				attach,
				files,
				showFilePrefix: false,
			});

			return text.trim();
		},
	};

	module.exports = {
		parser,
	};
});
