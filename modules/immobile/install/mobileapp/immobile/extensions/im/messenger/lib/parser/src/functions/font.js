/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module im/messenger/lib/parser/functions/font
 */
jn.define('im/messenger/lib/parser/functions/font', (require, exports, module) => {

	const { parserUtils } = require('im/messenger/lib/parser/utils/utils');

	const parserFont = {
		simplify(text, removeStrike = true)
		{
			if (removeStrike)
			{
				text = parserUtils.recursiveReplace(text, /\[s]([^[]*(?:\[(?!s]|\/s])[^[]*)*)\[\/s]/ig, () => ' ');
			}

			text = parserUtils.recursiveReplace(text, /\[b]([^[]*(?:\[(?!b]|\/b])[^[]*)*)\[\/b]/ig, (whole, text) => text);
			text = parserUtils.recursiveReplace(text, /\[u]([^[]*(?:\[(?!u]|\/u])[^[]*)*)\[\/u]/ig, (whole, text) => text);
			text = parserUtils.recursiveReplace(text, /\[i]([^[]*(?:\[(?!i]|\/i])[^[]*)*)\[\/i]/ig, (whole, text) => text);
			text = parserUtils.recursiveReplace(text, /\[s]([^[]*(?:\[(?!s]|\/s])[^[]*)*)\[\/s]/ig, (whole, text) => text);
			text = parserUtils.recursiveReplace(text, /\[size=(\d+)](.*?)\[\/size]/ig, (whole, number, text) => text);
			text = parserUtils.recursiveReplace(text, /\[color=#([0-9a-f]{3}|[0-9a-f]{6})](.*?)\[\/color]/ig, (whole, hex, text) => text);

			return text;
		}
	};

	module.exports = {
		parserFont,
	};
});
