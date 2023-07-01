/**
 * @module utils/svg
 */
jn.define('utils/svg', (require, exports, module) => {
	/**
	 * Replaces the fill color of the given svg content
	 *
	 * @param {string} svgContent original svg content
	 * @param {string} hexColor with leading #-sign
	 * @returns {string} svg content with changed fill color
	 */
	function changeFillColor(svgContent, hexColor)
	{
		return svgContent.replace(/fill="#[0-9a-f]{6}"/gi, `fill="${hexColor}"`);
	}

	module.exports = {
		changeFillColor,
	};
});