/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module im/messenger/lib/parser/functions/disk
 */
jn.define('im/messenger/lib/parser/functions/disk', (require, exports, module) => {

	const { Loc } = require('loc');

	const parserDisk = {
		decode(text)
		{
			const emoji = false;//TODO: Utils.text.getEmoji(FileEmojiType.file);

			let diskText;
			if (emoji)
			{
				diskText = `${emoji} ${Loc.getMessage('IMMOBILE_PARSER_EMOJI_TYPE_FILE')}`;
			}
			else
			{
				diskText = `[${Loc.getMessage('IMMOBILE_PARSER_EMOJI_TYPE_FILE')}]`;
			}

			text = text.replace(/\[disk=\d+]/gi, diskText);

			return text;
		},

		simplify(text)
		{
			return this.decode(text);
		},
	};

	module.exports = {
		parserDisk,
	};
});
