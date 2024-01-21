/**
 * @module layout/ui/stateful-list/pull/src/command
 */
jn.define('layout/ui/stateful-list/pull/src/command', (require, exports, module) => {
	const command = {
		ADDED: 'ADDED',
		UPDATED: 'UPDATED',
		DELETED: 'DELETED',
		RELOAD: 'RELOAD',
		VIEW: 'VIEW',
	};

	module.exports = { command };
});
