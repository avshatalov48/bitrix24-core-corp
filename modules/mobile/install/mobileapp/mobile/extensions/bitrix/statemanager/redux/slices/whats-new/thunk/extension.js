/**
 * @module statemanager/redux/slices/whats-new/thunk
 */
jn.define('statemanager/redux/slices/whats-new/thunk', (require, exports, module) => {
	const { createAsyncThunk } = require('statemanager/redux/toolkit');
	const {
		sliceName,
		WHATS_NEW_FETCH_ACTIONS,
		STATUS,
	} = require('statemanager/redux/slices/whats-new/meta');
	const { getUrlParamsFromCache, loadUrlParamsFromServer } = require('statemanager/redux/slices/whats-new/thunk/data-provider');
	const {
		selectUrlParams,
		selectEventService,
		selectItemsByIds,
		selectLastNewsCheckTime,
		selectNewCount,
	} = require('statemanager/redux/slices/whats-new/selector');
	const { RunActionExecutor } = require('rest/run-action-executor');
	const isTest = Application.isBeta() ? 'Y' : 'N';

	const runActionPromise = ({ action, options }) => new Promise((resolve) => {
		(new RunActionExecutor(action, options)).setHandler(resolve).call(false);
	});

	const { Uri } = require('utils/uri');
	const INITIAL_BLOCK_PAGE = 0;

	const fetchWhatsNewThunk = createAsyncThunk(
		`${sliceName}/fetchWhatsNew`,
		async ({ blockPage = 0 }, { getState, rejectWithValue }) => {
			try
			{
				const state = getState();
				const urlParams = selectUrlParams(state);

				let params = null;
				if (blockPage > INITIAL_BLOCK_PAGE)
				{
					params = {
						action: WHATS_NEW_FETCH_ACTIONS.LOAD_MORE_ACTION,
						category: null,
						pageNumber: blockPage,
					};
				}
				else
				{
					params = {
						action: WHATS_NEW_FETCH_ACTIONS.INIT_ACTION,
						placeStart: 'licenseWidget',
					};
				}

				const response = await BX.ajax({
					url: prepareUrl({
						userId: env.userId,
						userDateRegister: urlParams.userDateRegister,
						portalDateRegister: urlParams.portalDateRegister,
						url: urlParams.url,
					}),
					method: 'POST',
					mode: 'cors',
					data: params,
					dataType: 'json',
				});

				return {
					isSuccess: true,
					data: {
						...response,
						...response.content,
					},
				};
			}
			catch (response)
			{
				let status = null;

				if (response && Number.isInteger(response?.xhr?.status))
				{
					status = response?.xhr?.status;
				}

				return rejectWithValue({
					error: 'load error',
					errorStatus: status,
				});
			}
		},
		{},
	);

	const fetchUrlParamsThunk = createAsyncThunk(
		`${sliceName}/fetchUrlParams`,
		async (params, { rejectWithValue }) => {
			try
			{
				const response = await loadUrlParamsFromServer();

				if (response.status === STATUS.success)
				{
					return response.data;
				}

				return rejectWithValue(response);
			}
			catch (error)
			{
				console.error('fetchUrlParamsThunk error', error);

				return rejectWithValue({
					error: 'load error',
				});
			}
		},
		{},
	);

	/**
	 * @param {string} url
	 * @param {number} userId
	 * @param {string} userDateRegister
	 * @param {string} portalDateRegister
	 * @return {`${string}&userId=${number}&userDateRegister=${string}&portalDateRegister=${string}`}
	 */
	const prepareUrl = ({ url, userId, userDateRegister, portalDateRegister }) => {
		return `${url}&userId=${userId}&userDateRegister=${userDateRegister}&portalDateRegister=${portalDateRegister}&isMobile=Y&isTest=${isTest}`;
	};

	const sendReactionThunk = createAsyncThunk(
		`${sliceName}/sendReaction`,
		async (
			{
				itemId,
				auth,
				notificationAction,
				notificationCode,
			},
			{ getState, rejectWithValue },
		) => {
			try
			{
				const state = getState();
				const urlParams = selectUrlParams(state);
				const eventService = selectEventService(state);

				validateReactionParams(urlParams, eventService);

				const uri = new Uri(urlParams.url);
				uri.setPath(eventService.path);
				uri.setQueryParam('isMobile', 'Y');

				const url = uri.toString();
				const data = prepareReactionData(itemId, auth, notificationAction, notificationCode, eventService);

				const response = await BX.ajax({
					url,
					method: 'POST',
					mode: 'cors',
					data,
					dataType: 'json',
				});

				if (response?.removeReaction && !response?.removeReaction?.isSuccess)
				{
					return rejectWithValue({
						errors: response?.removeReaction?.errors,
					});
				}

				if (response?.saveReaction && !response?.saveReaction?.isSuccess)
				{
					return rejectWithValue({
						errors: response?.saveReaction?.errors,
					});
				}

				return {
					isSuccess: true,
					data: {
						...response,
					},
				};
			}
			catch (error)
			{
				console.error('sendReactionThunk error', error);

				return rejectWithValue({
					error: 'make reaction error',
				});
			}
		},
		{},
	);

	const updateReadNewsThunk = createAsyncThunk(
		`${sliceName}/updateReadNews`,
		async ({ newsIds }, { getState, rejectWithValue }) => {
			try
			{
				const state = getState();
				const eventService = selectEventService(state);
				const urlParams = selectUrlParams(state);

				const uri = new Uri(urlParams.url);
				uri.setPath(eventService.path);
				uri.setQueryParam('isMobile', 'Y');
				const url = uri.toString();

				const now = new Date();

				const items = selectItemsByIds(state, newsIds);

				if (!eventService || !eventService?.authParamName || !eventService?.notificationIdParamName)
				{
					throw new Error('eventService is not defined');
				}

				const newsList = items
					.filter((item) => !item.isRead)
					.map((item) => {
						return {
							[eventService.authParamName]: item.auth,
							[eventService.notificationIdParamName]: item.id,
							timestamp: now.getTime(),
						};
					});

				const data = {
					newsList,
					offset: now.getTimezoneOffset(),
				};

				const response = await BX.ajax({
					url,
					method: 'POST',
					mode: 'cors',
					data,
					dataType: 'json',
				});

				if (!Array.isArray(response))
				{
					return rejectWithValue({
						error: 'read news error',
					});
				}

				const markedAsReadNewsIds = response
					.filter((item) => item.notificationId && item.isSuccess)
					.map((item) => item.notificationId);

				return {
					isSuccess: true,
					data: {
						markedAsReadNewsIds,
					},
				};
			}
			catch (error)
			{
				console.error('updateReadNewsThunk error', error);

				return rejectWithValue({
					error: 'read news error',
				});
			}
		},
		{},
	);

	const updateLocalWhatsNewsParamsThunk = createAsyncThunk(
		`${sliceName}/updateLocalWhatsNewsParams`,
		// eslint-disable-next-line no-empty-pattern
		async ({}, { getState, rejectWithValue }) => {
			const state = getState();
			const count = selectNewCount(state);

			const response = await runActionPromise({
				action: 'mobile.WhatsNew.updateLocalParams',
				options: {
					count,
					lastChecked: selectLastNewsCheckTime(state),
				},
			});

			if (response.status !== STATUS.success)
			{
				return rejectWithValue(response);
			}

			return {
				isSuccess: true,
			};
		},
		{},
	);

	const validateReactionParams = (urlParams, eventService) => {
		if (!urlParams?.url || !eventService?.path)
		{
			throw new Error('url or path is not defined');
		}

		const requiredParams = [
			'authParamName',
			'notificationIdParamName',
			'typeParamName',
			'eventsHolderParamName',
		];

		requiredParams.forEach((param) => {
			if (!eventService[param])
			{
				throw new Error(`${param} is not defined`);
			}
		});
	};

	const prepareReactionData = (itemId, auth, notificationAction, notificationCode, eventService) => {
		const now = new Date();
		const offset = now.getTimezoneOffset();
		const timestamp = now.getTime();

		return {
			[eventService.notificationIdParamName]: itemId,
			[eventService.authParamName]: auth,
			[eventService.eventsHolderParamName]: [
				{
					[eventService.typeParamName]: notificationAction,
					notificationCode,
					offset,
					timestamp,
				},
			],
		};
	};

	module.exports = {
		fetchWhatsNewThunk,
		getUrlParamsFromCache,
		fetchUrlParamsThunk,
		sendReactionThunk,
		updateReadNewsThunk,
		updateLocalWhatsNewsParamsThunk,
		INITIAL_BLOCK_PAGE,
	};
});
