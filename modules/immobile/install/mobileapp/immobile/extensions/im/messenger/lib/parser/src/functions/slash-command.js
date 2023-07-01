/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module im/messenger/lib/parser/functions/slash-command
 */
jn.define('im/messenger/lib/parser/functions/slash-command', (require, exports, module) => {

	const parserSlashCommand = {
		simplify(text)
		{
			if (text.startsWith('/me'))
			{
				return  text.substr(4);
			}

			if (text.startsWith('/loud'))
			{
				return  text.substr(6);
			}

			return text;
		},
	};

	module.exports = {
		parserSlashCommand,
	};
});
