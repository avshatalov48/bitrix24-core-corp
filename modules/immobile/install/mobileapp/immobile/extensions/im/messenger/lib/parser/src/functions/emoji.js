/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module im/messenger/lib/parser/functions/emoji
 */
jn.define('im/messenger/lib/parser/functions/emoji', (require, exports, module) => {

	const { Loc } = require('loc');
	const { Type } = require('type');
	const {
		FileType,
		FileEmojiType
	} = require('im/messenger/const');

	const parserEmoji = {
		addIconToShortText(config)
		{
			let {
				text
			} = config;
			const {
				attach,
				files,
			} = config;

			if (Type.isArray(files) && files.length > 0)
			{
				text = this.getTextForFile(text, files);
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
				//text = this.getTextForAttach(text, attach);
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

		getTextForFile(text, files)
		{
			if (Type.isArray(files) && files.length > 0)
			{
				const [ firstFile ] = files;
				text = this.getEmojiTextForFile(text, firstFile);
			}
			else if (files === true)
			{
				text = this.getEmojiTextForFileType(text, FileEmojiType.file);
			}

			return text;
		},

		getEmojiTextForFile(text, file)
		{
			const withText = text.replace(/(\s|\n)/gi, '').length > 0;

			// todo: remove this hack after fix receiving messages with files on P&P
			if (!file || !file.type)
			{
				return text;
			}

			if (file.type === FileType.image)
			{
				return this.getEmojiTextForFileType(text, FileEmojiType.image);
			}
			else if (file.type === FileType.audio)
			{
				return this.getEmojiTextForFileType(text, FileEmojiType.audio);
			}
			else if (file.type === FileType.video)
			{
				return this.getEmojiTextForFileType(text, FileEmojiType.video);
			}
			else
			{
				const emoji = false; //Utils.text.getEmoji(FileEmojiType.file);
				if (emoji)
				{
					const textDescription = withText? text: '';
					text = `${emoji} ${file.name} ${textDescription}`;
				}
				else
				{
					text = `${Loc.getMessage('IMMOBILE_PARSER_EMOJI_TYPE_FILE')}: ${file.name} ${text}`;
				}

				return text.trim();
			}
		},

		getEmojiTextForFileType(text, type = FileEmojiType.file)
		{
			let result = text;
			const emoji = false;//Utils.text.getEmoji(type);
			const iconText = Loc.getMessage(`IMMOBILE_PARSER_EMOJI_TYPE_${type.toUpperCase()}`);
			if (emoji)
			{
				const withText = text.replace(/(\s|\n)/gi, '').length > 0;
				const textDescription = withText ? text : iconText;
				result = `${emoji} ${textDescription}`;
			}
			else
			{
				result = `${Loc.getMessage('IMMOBILE_PARSER_EMOJI_TYPE_FILE')}: ${iconText} ${text}`;
			}

			return result.trim();
		},
	};

	module.exports = {
		parserEmoji,
	};
});
