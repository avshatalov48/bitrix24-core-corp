/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/lib/parser/functions/action
 */
jn.define('im/messenger/lib/parser/functions/action', (require, exports, module) => {
	const parserAction = {
		simplifyPut(text)
		{
			return text.replaceAll(/\[put(?:=.+?)?](?:.+?)?\[\/put]/gi, (match) => {
				return match.replaceAll(/\[put(?:=(.+))?](.+?)?\[\/put]/gi, (whole, command, tagText) => {
					return tagText || command;
				});
			});
		},

		simplifySend(text)
		{
			return text.replaceAll(/\[send(?:=.+?)?](?:.+?)?\[\/send]/gi, (match) => {
				return match.replaceAll(/\[send(?:=(.+))?](.+?)?\[\/send]/gi, (whole, command, tagText) => {
					return tagText || command;
				});
			});
		},

		/**
		 * @param {string} text
		 * @return {string}
		 */
		decodePut(text)
		{
			return text.replaceAll(/\[put=([^\]]+)]\[\/put]/gi, (match, command) => {
				return `[put=${command}]${command}[/put]`;
			});
		},

		/**
		 * @param {string} text
		 * @return {string}
		 */
		decodeSend(text)
		{
			return text.replaceAll(/\[send=([^\]]+)]\[\/send]/gi, (match, command) => {
				return `[send=${command}]${command}[/send]`;
			});
		},
	};

	module.exports = {
		parserAction,
	};
});
