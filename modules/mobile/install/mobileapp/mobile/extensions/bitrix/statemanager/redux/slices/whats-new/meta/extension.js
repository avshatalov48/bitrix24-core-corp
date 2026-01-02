/**
 * @module statemanager/redux/slices/whats-new/meta
 */
jn.define('statemanager/redux/slices/whats-new/meta', (require, exports, module) => {
	const { createEntityAdapter } = require('statemanager/redux/toolkit');

	const sliceName = 'mobile:whats-new';
	const whatsNewAdapter = createEntityAdapter();

	const WHATS_NEW_FETCH_ACTIONS = {
		INIT_ACTION: 'getInitialData',
		LOAD_MORE_ACTION: 'getNews',
	};

	const STATUS = {
		idle: 'idle',
		pending: 'pending',
		success: 'success',
		failed: 'failed',
	};

	const REACTION_ACTIONS = {
		MAKE_REACTION: 'MAKE_REACTION',
		REMOVE_REACTION: 'REMOVE_REACTION',
	};

	const ACTION_TYPE = {
		HELPDESK_LINK: 'helpdeskLink',
		DEMO_LINK: 'demoLink',
	};

	const REACTIONS_NAMES = {
		FIRE: 'fire',
		LIKE: 'like',
		DISLIKE: 'dislike',
	};

	const BOTTOM_DESCRIPTION_TYPE = {
		lottie: 'lottie',
		image: 'image',
		video: 'video',
	};

	module.exports = {
		sliceName,
		whatsNewAdapter,
		WHATS_NEW_FETCH_ACTIONS,
		STATUS,
		REACTION_ACTIONS,
		REACTIONS_NAMES,
		ACTION_TYPE,
		BOTTOM_DESCRIPTION_TYPE,
	};
});
