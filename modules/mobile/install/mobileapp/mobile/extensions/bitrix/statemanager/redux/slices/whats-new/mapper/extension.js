/**
 * @module statemanager/redux/slices/whats-new/mapper
 */
jn.define('statemanager/redux/slices/whats-new/mapper', (require, exports, module) => {
	const { ACTION_TYPE } = require('statemanager/redux/slices/whats-new/meta');

	const API_VERSION = Application.getApiVersion();

	const mapArticleToViewModel = (article) => {
		const {
			action = {},
			auth = null,
			bottomDescription = null,
			bottomDescriptionType = null,
			categoryCode = null,
			description = null,
			id = '',
			isNew = false,
			name = null,
			reactions = {},
			time = 0,
			sort = '0',
			type = null,
			apiVersion = null,
			isRead = false,
		} = article;

		const {
			fire = { count: 0, select: false },
			like = { count: 0, select: false },
			dislike = { count: 0, select: false },
		} = reactions || {};

		const {
			name: actionName = '',
			type: actionType = '',
			articleId: actionArticleId = '',
			path: actionPath = '',
		} = action;

		return {
			id,
			name,
			description,
			bottomDescription,
			bottomDescriptionType,
			categoryCode,
			time,
			isNew,
			isRead,
			auth,
			sort,
			type,
			fireCount: Number(fire.count),
			fireSelect: fire.select,
			likeCount: Number(like.count),
			likeSelect: like.select,
			dislikeCount: Number(dislike.count),
			dislikeSelect: dislike.select,
			actionName,
			actionType,
			actionArticleId: actionType === ACTION_TYPE.HELPDESK_LINK ? actionArticleId : '',
			actionPath: actionType === ACTION_TYPE.LINK ? actionPath : '',
			isUnsupportedFeature: apiVersion && apiVersion > API_VERSION,
		};
	};

	const mapArticlesToViewModel = (articles) => articles.map((element) => mapArticleToViewModel(element));

	module.exports = {
		mapArticleToViewModel,
		mapArticlesToViewModel,
	};
});
