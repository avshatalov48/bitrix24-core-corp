/**
 * @module statemanager/redux/slices/whats-new/selector
 */
jn.define('statemanager/redux/slices/whats-new/selector', (require, exports, module) => {
	const { createDraftSafeSelector } = require('statemanager/redux/toolkit');
	const { whatsNewAdapter, sliceName, STATUS } = require('statemanager/redux/slices/whats-new/meta');

	const {
		selectAll,
		selectById,
	} = whatsNewAdapter.getSelectors((state) => state[sliceName]);

	const selectArticlesWithMinimalParams = (state) => selectAll(state).map((item) => ({
		id: item.id,
		type: item.type,
		isNew: item.isNew,
		isUnsupportedFeature: item.isUnsupportedFeature,
	}));

	const selectCategories = (state) => state[sliceName].categories;
	const selectAllCategoriesTitle = (state) => state[sliceName].allCategoriesTitle;
	const selectIsIdleStatus = (state) => state[sliceName].status === STATUS.idle;
	const selectIsLoadingStatus = (state) => state[sliceName].status === STATUS.pending;
	const selectIsSuccessStatus = (state) => state[sliceName].status === STATUS.success;
	const selectIsFailedStatus = (state) => state[sliceName].status === STATUS.failed;
	const selectIsLast = (state) => state[sliceName].isLast;
	const selectNewCheckTime = (state) => state[sliceName].newCheckTime;
	const selectUrlParams = (state) => state[sliceName].urlParams;
	const selectNewCount = (state) => state[sliceName].newCount;
	const selectEventService = (state) => state[sliceName].eventService;
	const selectReactionByItemIdAndReactionName = createDraftSafeSelector(
		[
			selectById,
			(state, itemId, reactionName) => reactionName,
		],
		(item, reactionName) => {
			return {
				count: item?.[`${reactionName}Count`] ?? 0,
				select: item?.[`${reactionName}Select`] ?? false,
			};
		},
	);

	const selectItemsByIds = createDraftSafeSelector(
		(state, ids) => ids,
		(state) => state,
		(ids, state) => ids.map((id) => selectById(state, id)).filter(Boolean),
	);

	const selectHasReadNews = (state) => state[sliceName].hasReadNews;
	const selectLastNewsCheckTime = (state) => state[sliceName].lastNewsCheckTime;

	const selectHasUnsupportedFeatures = createDraftSafeSelector(
		(state, ids) => ids,
		(state) => state,
		(ids, state) => ids.some((id) => {
			const item = selectById(state, id);

			return item && item.isUnsupportedFeature;
		}),
	);

	const selectErrorStatus = (state) => state[sliceName].errorStatus;

	module.exports = {
		selectAll,
		selectById,
		selectItemsByIds,
		selectArticlesWithMinimalParams,
		selectCategories,
		selectAllCategoriesTitle,
		selectIsIdleStatus,
		selectIsLoadingStatus,
		selectIsSuccessStatus,
		selectIsFailedStatus,
		selectIsLast,
		selectNewCheckTime,
		selectEventService,
		selectReactionByItemIdAndReactionName,
		selectUrlParams,
		selectNewCount,
		selectHasReadNews,
		selectLastNewsCheckTime,
		selectHasUnsupportedFeatures,
		selectErrorStatus,
	};
});
