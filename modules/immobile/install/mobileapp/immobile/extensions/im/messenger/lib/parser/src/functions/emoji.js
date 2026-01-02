/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/lib/parser/functions/emoji
 */
jn.define('im/messenger/lib/parser/functions/emoji', (require, exports, module) => {

	const { Loc } = require('im/messenger/loc');
	const { Type } = require('type');
	const {
		FileType,
		FileEmojiType,
	} = require('im/messenger/const');

	const parserEmoji = {
		addIconToShortText(config)
		{
			let {
				text,
			} = config;
			const {
				attach,
				files,
				showFilePrefix = true,
			} = config;

			if (Type.isArray(files) && files.length > 0)
			{
				if (files.length === 1)
				{
					text = this.getTextForFile(text, files, showFilePrefix);
				}
				else
				{
					text = this.getTextForFiles(text, files);
				}
			}
			else if (
				attach === true
				|| (
					Type.isArray(attach)
					&& attach.length > 0
				)
				|| Type.isStringFilled(attach)
			)
			{
				text = this.getTextForAttach(text, attach);
			}

			return text.trim();
		},

		getImageBlock()
		{
			// const emoji = Utils.text.getEmoji(FileEmojiType.image);
			// if (emoji)
			// {
			// 	return emoji;
			// }

			return `[${Loc.getMessage('IMMOBILE_PARSER_EMOJI_TYPE_IMAGE')}]`;
		},

		getTextForFile(text, files, showFilePrefix)
		{
			let textResult = text;
			if (Type.isArray(files) && files.length > 0)
			{
				const [firstFile] = files;
				textResult = this.getEmojiTextForFile(textResult, firstFile, showFilePrefix);
			}
			else if (files === true)
			{
				textResult = this.getEmojiTextForFileType(textResult, FileEmojiType.file, showFilePrefix);
			}

			return textResult;
		},

		getEmojiTextForFile(text, file, showFilePrefix = true)
		{
			let textResult = text;
			const withText = textResult.replace(/(\s|\n)/gi, '').length > 0;

			// todo: remove this hack after fix receiving messages with files on P&P
			if (!file || !file.type)
			{
				return textResult;
			}

			if (file.type === FileType.image)
			{
				return this.getEmojiTextForFileType(textResult, FileEmojiType.image, showFilePrefix);
			}
			else if (file.type === FileType.audio)
			{
				return this.getEmojiTextForFileType(textResult, FileEmojiType.audio, showFilePrefix);
			}
			else if (file.type === FileType.video)
			{
				return this.getEmojiTextForFileType(textResult, FileEmojiType.video, showFilePrefix);
			}
			else
			{
				const emoji = false; // Utils.text.getEmoji(FileEmojiType.file);
				if (emoji)
				{
					const textDescription = withText ? textResult : '';
					textResult = `${emoji} ${file.name} ${textDescription}`;
				}
				else
				{
					textResult = `${Loc.getMessage('IMMOBILE_PARSER_EMOJI_TYPE_FILE')}: ${file.name} ${textResult}`;
				}

				return textResult.trim();
			}
		},

		getTextForFiles(text, files)
		{
			const media = files.filter((file) => file?.type === FileType.image || file?.type === FileType.video);
			const isOnlyMedia = media.length === files.length;
			const emojiTypeKey = isOnlyMedia
				? 'IMMOBILE_PARSER_EMOJI_TYPE_GALLERY'
				: 'IMMOBILE_PARSER_EMOJI_TYPE_FILES';
			const emojiType = Loc.getMessage(emojiTypeKey);
			const messageWithText = Type.isStringFilled(text) && text.replaceAll(/(\s|\n)/gi, '').length > 0;
			if (messageWithText)
			{
				return `[${emojiType}] ${text}`.trim();
			}

			return `[${emojiType}]`;
		},

		getEmojiTextForFileType(text, type = FileEmojiType.file, showFilePrefix = true)
		{
			let textResult = text;
			const emoji = false; // Utils.text.getEmoji(type);
			const iconText = Loc.getMessage(`IMMOBILE_PARSER_EMOJI_TYPE_${type.toUpperCase()}`);
			if (emoji)
			{
				const withText = textResult.replace(/(\s|\n)/gi, '').length > 0;
				const textDescription = withText ? textResult : iconText;
				textResult = `${emoji} ${textDescription}`;
			}
			else
			{
				textResult = showFilePrefix
					? `${Loc.getMessage('IMMOBILE_PARSER_EMOJI_TYPE_FILE')}: ${iconText} ${textResult}`
					: `[${iconText}] ${textResult}`
				;
			}

			return textResult.trim();
		},

		getTextForAttach(text, attach)
		{
			let attachDescription = '';
			if (Type.isArray(attach) && attach.length > 0)
			{
				attachDescription = `[${Loc.getMessage('IMMOBILE_PARSER_EMOJI_TYPE_ATTACH')}]`;
			}

			return `${text} ${attachDescription}`.trim();
		},
	};

	module.exports = {
		parserEmoji,
	};
});
