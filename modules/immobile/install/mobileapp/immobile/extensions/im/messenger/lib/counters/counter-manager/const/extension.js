/**
 * @module im/messenger/lib/counters/counter-manager/const
 */
jn.define('im/messenger/lib/counters/counter-manager/const', (require, exports, module) => {

	const STORAGE_NAME = 'immobile::counter-storage';

	const STATE_NAME = 'countersCollection';

	const Event = {
		recentPageLoaded: 'immobile::counter-manager:recent-page-loaded',
		initialCountersLoaded: 'immobile::counter-manager:initial-counters-loaded',
		readAll: 'immobile::counter-manager:read-all',
		readChannelComments: 'immobile::counter-manager:read-channel-comments',
		deleteChats: 'immobile::counter-manager:delete-chats',
	};

	module.exports = {
		STORAGE_NAME,
		STATE_NAME,
		Event,
	};
});
