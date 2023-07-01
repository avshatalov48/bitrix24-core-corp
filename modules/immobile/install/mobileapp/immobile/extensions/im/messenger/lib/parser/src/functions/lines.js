/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module im/messenger/lib/parser/functions/lines
 */
jn.define('im/messenger/lib/parser/functions/lines', (require, exports, module) => {

	const { Loc } = require('loc');

	const parserLines = {
		simplify(text)
		{
			text = text.replace(
				/\[LIKE]/ig,
				Loc.getMessage('IMMOBILE_PARSER_LINES_RATING_LIKE')
			);

			text = text.replace(
				/\[DISLIKE]/ig,
				Loc.getMessage('IMMOBILE_PARSER_LINES_RATING_DISLIKE')
			);

			text = text.replace(/\[RATING=([1-5])]/ig, () => {
				return '[' + Loc.getMessage('IMMOBILE_PARSER_LINES_RATING') + '] ';
			});

			return text;
		},
	};

	module.exports = {
		parserLines,
	};
});
