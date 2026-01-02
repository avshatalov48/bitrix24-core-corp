/**
 * @module im/messenger/controller/sidebar-v2/search/src/utils
 */
jn.define('im/messenger/controller/sidebar-v2/search/src/utils', (require, exports, module) => {
	const { Feature } = require('im/messenger/lib/feature');
	const { Color } = require('tokens');
	const { BBCodeParser } = require('bbcode/parser');
	const ELLIPSIS = '...';

	/**
	 * @param {string} text
	 * @param {string} word
	 * @param {number} length
	 * @param {boolean} shouldEllipsis
	 * @returns {string}
	 */
	function cropTextByCenterWord(text, word, length = 30, shouldEllipsis = true)
	{
		const preparedText = normalizeText(text);

		const wordIndex = preparedText.toLowerCase().indexOf(word.toLowerCase());
		if (wordIndex === -1 || text.length <= length)
		{
			return preparedText;
		}

		let start = Math.max(0, wordIndex - Math.floor((length - word.length) / 2));
		let end = start + length;

		if (end > preparedText.length)
		{
			end = preparedText.length;
			start = Math.max(0, end - length);
		}

		let croppedText = preparedText.slice(start, end);
		if (start > 0)
		{
			croppedText = `${shouldEllipsis ? ELLIPSIS : ''}${croppedText}`;
		}

		if (end < preparedText.length)
		{
			croppedText = `${croppedText}${shouldEllipsis ? ELLIPSIS : ''}`;
		}

		return croppedText;
	}

	/**
	 * Remove bb-codes, empty lines and line breaks.
	 * @param {string} text
	 * @return {string}
	 */
	function normalizeText(text)
	{
		const parser = new BBCodeParser();
		const ast = parser.parse(text);

		const preparedText = ast.toPlainText();

		return preparedText.split('\n')
			.filter((line) => line.trim() !== '')
			.map((line) => line.trim())
			.join(' ');
	}

	/**
	 * @param {string} text
	 * @param {string} word
	 * @param {string} color
	 * @returns {string}
	 */
	function highlightWord(text, word, color = Color.accentMainPrimaryalt.toHex())
	{
		const regex = new RegExp(word, 'gi');

		return text.replace(regex, `[color="${color}"]$&[/color]`);
	}

	/**
	 * @param text
	 * @param color
	 * @returns {*|string}
	 */
	function withColor(text, color)
	{
		if (!Feature.isChatDialogListSupportsSubtitleBbCodes || !color)
		{
			return text;
		}

		return `[color="${color}"]${text}[/color]`;
	}

	/**
	 * @param {string} text
	 * @param {string} word
	 * @param {string} [color]
	 * @param {number} [maxLength]
	 * @param {boolean} shouldEllipsis
	 * @returns {string}
	 */
	function prepareTextToItem(text, word, color, maxLength = 30, shouldEllipsis = true)
	{
		const croppedText = cropTextByCenterWord(text, word, maxLength, shouldEllipsis);

		if (!Feature.isChatDialogListSupportsSubtitleBbCodes)
		{
			return croppedText;
		}

		return highlightWord(croppedText, word, color);
	}

	module.exports = { cropTextByCenterWord, highlightWord, prepareTextToItem, withColor };
});
