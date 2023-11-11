/**
 * @module im/messenger/controller/search/experimental/get-words-from-text
 */
jn.define('im/messenger/controller/search/experimental/get-words-from-text', (require, exports, module) => {
	/**
	 * @private
	 * @param {string} text
	 * @return {Array<string>}
	 */
	function getWordsFromText(text)
	{
		const clearedText = text
			.replaceAll('(', ' ')
			.replaceAll(')', ' ')
			.replaceAll('[', ' ')
			.replaceAll(']', ' ')
			.replaceAll('{', ' ')
			.replaceAll('}', ' ')
			.replaceAll('<', ' ')
			.replaceAll('>', ' ')
			.replaceAll('-', ' ')
			.replaceAll('#', ' ')
			.replaceAll('"', ' ')
			.replaceAll('\'', ' ')
			.replaceAll(/\s\s+/g, ' ')
		;

		return clearedText.split(' ').filter((word) => word !== '');
	}

	module.exports = { getWordsFromText };
});