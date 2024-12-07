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
			text = text.replace(/\[url(?:=([^[\]]+))?](.*?)\[\/url]/gi, (whole, link, text) => {
				return text || link;
			});

			text = text.replace(/\[url(?:=(.+))?](.*?)\[\/url]/gi, (whole, link, text) => {
				return text || link;
			});

			return text;
		},

		removeBR(text)
		{
			text = text.replace(/\[\/?br]/gim, '');

			return text;
		},

		removeSimpleUrlTag(text)
		{
			text = text.replace(/\[url](.*?)\[\/url]/gi, (whole, link) => link);

			return text;
		},

		prepareGifUrl(text)
		{
			text = text.replace(/(\[url=|\[url])?http.*?\.(gif|webp)(\[\/url])?/gim, (match, p1, p2) => {
				if (p1 && p2)
				{
					return match.replace(/\[\/url]/gim, '[/IMG]').replace(/\[url=|(\[url])/gim, '[IMG]');
				}

				if (p1 === undefined || p2 === undefined)
				{
					return match;
				}

				return `[IMG]${match}[/IMG]`;
			});

			text = text.replace(/(.)(\[img)/gim, '$1\n$2');
			text = text.replace(/(\/img])(.)/gim, '$1\n$2');

			return text;
		},
	};

	module.exports = {
		parserUrl,
	};
});
