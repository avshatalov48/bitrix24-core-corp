/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module im/messenger/lib/parser/functions/common
 */
jn.define('im/messenger/lib/parser/functions/common', (require, exports, module) => {

	const parserCommon = {
		simplifyBreakLine(text, replaceLetter = ' ')
		{
			text = text.replace(/<br><br \/>/ig, '<br />');
			text = text.replace(/<br \/><br>/ig, '<br />');
			text = text.replace(/\[BR]/ig, '<br />');
			text = text.replace(/<br \/>/ig, replaceLetter);

			return text;
		},

		simplifyNbsp(text)
		{
			text = text.replace(/&nbsp;/ig, " ");

			return text;
		},

		simplifyNewLine(text, replaceSymbol = ' ')
		{
			if (replaceSymbol !== '\n')
			{
				text = text.replace(/\n/gi, replaceSymbol);
			}
			text = text.replace(/\[BR]/ig, replaceSymbol);

			return text;
		},
	};

	module.exports = {
		parserCommon,
	};
});
