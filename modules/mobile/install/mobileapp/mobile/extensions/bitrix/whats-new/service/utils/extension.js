/**
 * @module whats-new/service/utils
 */
jn.define('whats-new/service/utils', (require, exports, module) => {
	const DAY_IN_SECONDS = 86400;

	/**
	 * @param {number} checkTime
	 * @return {boolean}
	 */
	const isCheckTimeExpired = (checkTime) => {
		return (Date.now() - checkTime) > DAY_IN_SECONDS * 1000;
	};

	module.exports = {
		isCheckTimeExpired,
	};
});
