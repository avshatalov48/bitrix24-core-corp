/**
 * @module calendar/state
 */
jn.define('calendar/state', (require, exports, module) => {
	const { BaseState } = require('calendar/state/base-state');
	const { observeState } = require('calendar/state/observe-state');

	module.exports = { BaseState, observeState };
});
