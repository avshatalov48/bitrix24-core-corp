/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module im/messenger/lib/parser/functions/url
 */
jn.define('im/messenger/lib/parser/functions/url', (require, exports, module) => {

	const parserUrl = {
		simplify(text)
		{
			text = text.replace(/\[url(?:=([^\[\]]+))?](.*?)\[\/url]/ig, (whole, link, text) => {
				return text ? text : link;
			});

			text = text.replace(/\[url(?:=(.+))?](.*?)\[\/url]/ig, (whole, link, text) => {
				return text ? text : link;
			});

			return text;
		},

		removeSimpleUrlTag(text)
		{
			text = text.replace(/\[url](.*?)\[\/url]/ig, (whole, link) => link);

			return text;
		},
	};

	module.exports = {
		parserUrl,
	};
});
