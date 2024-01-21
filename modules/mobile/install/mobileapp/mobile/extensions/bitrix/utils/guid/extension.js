/**
 * @module utils/guid
 */
jn.define('utils/guid', (require, exports, module) => {
	function s4()
	{
		return Math.floor((1 + Math.random()) * 0x10000)
			.toString(16)
			.slice(1);
	}

	/**
	 * @return {string}
	 */
	function guid()
	{
		return `${s4()}${s4()}-${s4()}-${s4()}-${s4()}-${s4()}${s4()}${s4()}`;
	}

	module.exports = { guid };
});
