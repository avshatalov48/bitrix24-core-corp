/**
 * @module im/messenger-v2/controller/recent/const/event-type
 */
jn.define('im/messenger-v2/controller/recent/const/event-type', (require, exports, module) => {
	const RecentEventType = {
		ui: {
			onScroll: 'onScroll',
			itemWillDisplay: 'itemWillDisplay',
			onItemSelected: 'onItemSelected',
		},
		onInit: 'onInit',
		pagination: {},
		render: {
			updateUIByRecentCollectionSizeIfNeeded: 'updateUIByRecentCollectionSizeIfNeeded',
		},
	};

	module.exports = { RecentEventType };
});
