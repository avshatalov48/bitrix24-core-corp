/**
 * @module im/messenger/const/messenger-init-rest
 */
jn.define('im/messenger/const/messenger-init-rest', (require, exports, module) => {
	const MessengerInitRestMethod = Object.freeze({
		recentList: 'recentList',
		userData: 'userData',
		portalCounters: 'portalCounters',
		imCounters: 'imCounters',
		mobileRevision: 'mobileRevision',
		serverTime: 'serverTime',
		desktopStatus: 'desktopStatus',
		promotion: 'promotion',
		departmentColleagues: 'departmentColleagues',
		tariffRestriction: 'tariffRestriction',
		activeCalls: 'activeCalls',
		anchors: 'anchors',

		chatsList: 'chatsList',
		copilotList: 'copilotList',
		channelList: 'channelList',
		collabList: 'collabList',
		taskList: 'taskList',
	});

	module.exports = {
		MessengerInitRestMethod,
	};
});
