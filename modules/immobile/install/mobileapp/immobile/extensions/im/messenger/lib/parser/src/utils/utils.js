/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module im/messenger/lib/parser/utils/utils
 */
jn.define('im/messenger/lib/parser/utils/utils', (require, exports, module) => {

	const { Type } = require('type');

	const RECURSIVE_LIMIT = 10;

	const parserUtils = {
		recursiveReplace(text, pattern, replacement)
		{
			if (!Type.isStringFilled(text))
			{
				return text;
			}

			let count = 0;
			let deep = true;
			do
			{
				deep = false;
				count++;
				text = text.replace(pattern, (...params) => {
					deep = true;
					return replacement(...params);
				});
			}
			while (deep && count <= RECURSIVE_LIMIT);

			return text;
		}
	};

	module.exports = {
		parserUtils,
	};
});
