/* eslint-disable no-param-reassign */
/**
 * @module statemanager/redux/slices/whats-new
 */
jn.define('statemanager/redux/slices/whats-new', (require, exports, module) => {
	const { StateCache } = require('statemanager/redux/state-cache');
	const { createSlice } = require('statemanager/redux/toolkit');
	const {
		whatsNewAdapter,
		sliceName,
		STATUS,
		REACTION_ACTIONS,
		REACTIONS_NAMES,
	} = require('statemanager/redux/slices/whats-new/meta');
	const { ReducerRegistry } = require('statemanager/redux/reducer-registry');
	const {
		fetchWhatsNewThunk,
		fetchUrlParamsThunk,
		updateReadNewsThunk,
		sendReactionThunk,
		getUrlParamsFromCache,
		updateLocalWhatsNewsParamsThunk,
		INITIAL_BLOCK_PAGE,
	} = require('statemanager/redux/slices/whats-new/thunk');
	const { mapArticlesToViewModel } = require('statemanager/redux/slices/whats-new/mapper');
	const {
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
	} = require('statemanager/redux/slices/whats-new/selector');
	const { createOptimisticUiSliceReducer } = require('statemanager/redux/optimistic-ui');

	const {
		count,
		lastNewsCheckTime,
		...urlParams
	} = getUrlParamsFromCache();

	const initialState = StateCache.getReducerState(sliceName, whatsNewAdapter.getInitialState({
		allCategoriesTitle: null,
		newCount: count ?? 0,
		newCheckTime: 0,
		lastNewsCheckTime: lastNewsCheckTime ?? 0,
		lastContent: null,
		categories: [],
		blockPage: 0,
		status: STATUS.idle,
		urlParams: urlParams ?? null,
		hasReadNews: false,
	}));

	const whatsNewSlice = createSlice({
		name: sliceName,
		initialState,
		reducers: {
			setHasReadNews: (state) => {
				state.hasReadNews = true;
			},
		},
		extraReducers: (builder) => {
			builder
				.addCase(fetchWhatsNewThunk.pending, (state) => {
					state.status = STATUS.pending;
				})
				.addCase(fetchWhatsNewThunk.fulfilled, (state, action) => {
					state.status = STATUS.success;
					state.blockPage = action.meta.arg.blockPage;
					if (state.blockPage === 0)
					{
						state.hasReadNews = false;
					}

					const data = action.payload.data;

					state.allCategoriesTitle = data.allCategoriesTitle ?? state.allCategoriesTitle;
					state.newCount = (typeof data.new === 'number') ? data.new : state.newCount;
					state.newCheckTime = (typeof data.newCheckTime === 'number') ? data.newCheckTime * 1000 : state.newCheckTime;
					state.lastNewsCheckTime = (typeof data.time === 'number') ? data.time * 1000 : state.lastNewsCheckTime;
					state.lastContent = data.last ?? state.lastContent;
					state.categories = data.categories ?? state.categories;
					state.isLast = data.last ?? state.isLast;
					state.eventService = data.eventService || state.eventService;

					const mappedArticles = mapArticlesToViewModel(data.articles);

					whatsNewAdapter.upsertMany(state, mappedArticles);
				})
				.addCase(fetchWhatsNewThunk.rejected, (state, action) => {
					state.status = STATUS.failed;
					state.errorStatus = action.payload.errorStatus;
				})
				.addCase(fetchUrlParamsThunk.fulfilled, (state, action) => {
					const {
						count: newCount = 0,
						url = null,
						userDateRegister = null,
						portalDateRegister = null,
						lastNewsCheckTime: time = 0,
					} = action.payload;

					state.newCount = newCount;
					state.lastNewsCheckTime = time;

					state.urlParams = {
						url,
						userDateRegister,
						portalDateRegister,
					};
				})
				.addCase(sendReactionThunk.pending, (state, action) => {
					const {
						itemId,
						notificationCode,
						notificationAction,
					} = action.meta.arg;

					const item = state.entities[itemId];

					if (!item)
					{
						return;
					}

					const reactionCount = item[`${notificationCode}Count`];
					if (notificationAction === REACTION_ACTIONS.MAKE_REACTION)
					{
						item[`${notificationCode}Count`] = reactionCount + 1;
						item[`${notificationCode}Select`] = true;

						Object.values(REACTIONS_NAMES)
							.filter((reactionName) => reactionName !== notificationCode)
							.forEach((reactionName) => {
								if (item[`${reactionName}Select`])
								{
									item[`${reactionName}Select`] = false;
									item[`${reactionName}Count`] = Math.max(0, item[`${reactionName}Count`] - 1);
								}
							});
					}
					else
					{
						item[`${notificationCode}Count`] = Math.max(0, reactionCount - 1);
						item[`${notificationCode}Select`] = false;
					}

					whatsNewAdapter.upsertOne(state, item);
				})
				.addCase(updateReadNewsThunk.fulfilled, (state, action) => {
					const {
						markedAsReadNewsIds = [],
					} = action.payload.data;

					const counterValue = Math.max(0, state.newCount - markedAsReadNewsIds.length);

					if (counterValue === state.newCount)
					{
						return;
					}

					const updatedItems = markedAsReadNewsIds.map((id) => {
						const item = state.entities[id];
						if (item)
						{
							item.isRead = true;
						}

						return item;
					}).filter(Boolean);

					state.newCount = counterValue;
					whatsNewAdapter.upsertMany(state, updatedItems);
				});
		},
	});

	const { reducer, actions } = whatsNewSlice;

	const {
		setHasReadNews,
	} = actions;

	ReducerRegistry.register(sliceName, createOptimisticUiSliceReducer(sliceName, reducer));

	module.exports = {
		sliceName,
		whatsNewSlice,
		REACTION_ACTIONS,
		REACTIONS_NAMES,

		fetchWhatsNewThunk,
		fetchUrlParamsThunk,
		sendReactionThunk,
		updateReadNewsThunk,
		updateLocalWhatsNewsParamsThunk,

		setHasReadNews,

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

		INITIAL_BLOCK_PAGE,
	};
});
