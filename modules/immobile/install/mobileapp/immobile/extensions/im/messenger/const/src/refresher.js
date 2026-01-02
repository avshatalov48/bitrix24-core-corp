/**
 * @module im/messenger/const/refresher
 */
jn.define('im/messenger/const/refresher', (require, exports, module) => {

	const RefreshMode = {
		startUp: 'startUp',
		resume: 'resume',
		scrollUp: 'scrollUp',
		restoreConnection: 'restoreConnection',
	};

	module.exports = { RefreshMode };
});
