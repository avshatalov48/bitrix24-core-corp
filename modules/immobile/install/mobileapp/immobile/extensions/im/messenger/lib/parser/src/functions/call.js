/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module im/messenger/lib/parser/functions/call
 */
jn.define('im/messenger/lib/parser/functions/call', (require, exports, module) => {

	const parserCall = {
		simplify(text)
		{
			text = text.replace(
				/\[CALL(?:=([-+\d()./# ]+))?](.+?)\[\/CALL]/ig,
				(whole, number, text) => text ? text : number
			);

			text = text.replace(
				/\[PCH=([0-9]+)](.*?)\[\/PCH]/ig,
				(whole, historyId, text) => text
			);

			return text;
		},
	};

	module.exports = {
		parserCall,
	};
});
