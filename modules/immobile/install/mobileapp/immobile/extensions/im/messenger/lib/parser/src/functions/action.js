/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module im/messenger/lib/parser/functions/action
 */
jn.define('im/messenger/lib/parser/functions/action', (require, exports, module) => {

	const parserAction = {
		simplifyPut(text)
		{
			text = text.replace(/\[PUT(?:=(?:.+?))?](?:.+?)?\[\/PUT]/ig, (match) =>
			{
				return match.replace(/\[PUT(?:=(.+))?](.+?)?\[\/PUT]/ig, (whole, command, text) => {
					return text ? text : command;
				});
			});

			return text;
		},

		simplifySend(text)
		{
			text = text.replace(/\[SEND(?:=(?:.+?))?](?:.+?)?\[\/SEND]/ig, (match) =>
			{
				return match.replace(/\[SEND(?:=(.+))?](.+?)?\[\/SEND]/ig, (whole, command, text) => {
					return text ? text : command;
				});
			});

			return text;
		},
	};

	module.exports = {
		parserAction,
	};
});
