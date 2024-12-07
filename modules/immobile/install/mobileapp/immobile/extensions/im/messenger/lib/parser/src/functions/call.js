/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/lib/parser/functions/call
 */
jn.define('im/messenger/lib/parser/functions/call', (require, exports, module) => {
	const parserCall = {
		simplify(text)
		{
			let simplifiedText = text.replaceAll(
				/\[call(?:=([\d #()+./-]+))?](.+?)\[\/call]/gi,
				(whole, number, tagText) => tagText || number,
			);

			simplifiedText = this.simplifyPch(simplifiedText);

			return simplifiedText;
		},

		simplifyPch(text)
		{
			return text.replaceAll(
				/\[pch=(\d+)](.*?)\[\/pch]/gi,
				(whole, historyId, tagText) => '',
			);
		},

		decode(text)
		{
			return text.replaceAll(/\[call=([^\]]+)]\[\/call]/gi, (match, phoneNumber) => {
				return `[call=${phoneNumber}]${phoneNumber}[/call]`;
			});
		},
	};

	module.exports = {
		parserCall,
	};
});
