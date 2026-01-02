/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module im/messenger/lib/parser/functions/image
 */
jn.define('im/messenger/lib/parser/functions/image', (require, exports, module) => {

	const { Loc } = require('im/messenger/loc');
	const { parserEmoji } = require('im/messenger/lib/parser/functions/emoji');

	const parserImage = {
		decodeIcon(text)
		{
			const isSingleIcon = /^\[icon=([^\s\]]+)(?:\s+[^\]]*)?]$/i.test(text.trim());
			const size = isSingleIcon ? 40 : 25;

			return text.replaceAll(
				/\[icon=([^\s\]]+)(?:\s+[^\]]*)?]/gi,
				(match, url) => `[IMG width="${size}" height="${size}"]${url}[/IMG]`,
			);
		},

		decodeImageWithSize(text)
		{
			const patternImgWithUrl = /(\[img(?:\s\S+)?])\[url(?:=\S+)?]([^[]+)(?:\[\/url])?(\s?\[\/img])(?:\[\/url])?/gi;
			const patternImageWithSize = /\[img(?:\ssize=(?:small|medium|large))?](\S+)\s\[\/img]/gi;

			return text
				.replaceAll(patternImgWithUrl, '$1$2$3')
				.replaceAll(
					patternImageWithSize,
					(match, url) => `[IMG radius="10"]${url}[/IMG]`,
				);
		},

		simplifyImage(text)
		{
			const pattern = /\[img(?:\ssize=small|medium|large)?](\S+)\s\[\/img]/gi;

			return text.replaceAll(pattern, parserEmoji.getImageBlock());
		},

		simplifyLink(text)
		{
			text = text.replace(/(.)?((https|http):\/\/(\S+)\.(jpg|jpeg|png|gif|webp)(\?\S+)?)/ig, function(whole, letter, url)
			{
				if(
					letter && !(['>', ']', ' '].includes(letter))
					|| !url.match(/(\.(jpg|jpeg|png|gif|webp)\?|\.(jpg|jpeg|png|gif|webp)$)/i)
					|| url.toLowerCase().indexOf('/docs/pub/') > 0
					|| url.toLowerCase().indexOf('logout=yes') > 0
				)
				{
					return whole;
				}
				else
				{
					return (letter ? letter: '') + parserEmoji.getImageBlock();
				}
			});

			return text;
		},

		simplifyIcon(text)
		{
			text = text.replace(/\[icon=([^\]]*)]/ig, (whole) =>
			{
				let title = whole.match(/title=(.*[^\s\]])/i);
				if (title && title[1])
				{
					title = title[1];
					if (title.indexOf('width=') > -1)
					{
						title = title.substr(0, title.indexOf('width='))
					}
					if (title.indexOf('height=') > -1)
					{
						title = title.substr(0, title.indexOf('height='))
					}
					if (title.indexOf('size=') > -1)
					{
						title = title.substr(0, title.indexOf('size='))
					}
					if (title)
					{
						title = '('+title.trim()+')';
					}
				}
				else
				{
					title = '('+Loc.getMessage('IMMOBILE_PARSER_IMAGE_ICON')+')';
				}
				return title;
			});

			return text;
		},
	};

	module.exports = {
		parserImage,
	};
});
