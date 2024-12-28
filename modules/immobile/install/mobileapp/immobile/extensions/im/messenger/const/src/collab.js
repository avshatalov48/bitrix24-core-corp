/**
 * @module im/messenger/const/collab
 */
jn.define('im/messenger/const/collab', (require, exports, module) => {
	const CollabEntity = Object.freeze({
		tasks: 'tasks',
		files: 'files',
		calendar: 'calendar',
	});

	module.exports = {
		CollabEntity,
	};
});
