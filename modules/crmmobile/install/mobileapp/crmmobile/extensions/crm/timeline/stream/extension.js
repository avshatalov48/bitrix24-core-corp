/**
 * @module crm/timeline/stream
 */
jn.define('crm/timeline/stream', (require, exports, module) => {
	const { TimelineStreamPinned } = require('crm/timeline/stream/pinned');
	const { TimelineStreamScheduled } = require('crm/timeline/stream/scheduled');
	const { TimelineStreamHistory } = require('crm/timeline/stream/history');

	module.exports = {
		TimelineStreamPinned,
		TimelineStreamScheduled,
		TimelineStreamHistory,
	};
});
