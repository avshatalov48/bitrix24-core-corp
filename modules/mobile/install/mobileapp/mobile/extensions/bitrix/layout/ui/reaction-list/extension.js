/**
 * @module layout/ui/reaction-list
 */
jn.define('layout/ui/reaction-list', (require, exports, module) => {
	const { ReactionListView } = require('layout/ui/reaction-list/src/view');
	const { ReactionListController } = require('layout/ui/reaction-list/src/controller');

	module.exports = {
		ReactionListView,
		ReactionListController,
	};
});
