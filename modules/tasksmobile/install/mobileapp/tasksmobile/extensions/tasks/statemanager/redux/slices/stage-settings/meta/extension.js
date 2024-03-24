/**
 * @module tasks/statemanager/redux/slices/stage-settings/meta
 */
jn.define('tasks/statemanager/redux/slices/stage-settings/meta', (require, exports, module) => {
	const { createEntityAdapter } = require('statemanager/redux/toolkit');

	const sliceName = 'tasks:stage';
	const adapter = createEntityAdapter({});

	module.exports = {
		sliceName,
		adapter,
	};
});
