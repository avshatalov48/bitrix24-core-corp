/**
 * @module whats-new/service
 */
jn.define('whats-new/service', (require, exports, module) => {
	const store = require('statemanager/redux/store');
	const { dispatch } = store;

	const {
		fetchWhatsNewThunk,
		fetchUrlParamsThunk,
		updateLocalWhatsNewsParamsThunk,
		selectLastNewsCheckTime,
		selectNewCount,
		selectUrlParams,
		INITIAL_BLOCK_PAGE,
	} = require('statemanager/redux/slices/whats-new');
	const { isCheckTimeExpired } = require('whats-new/service/utils');

	/**
	 * @class WhatsNewService
	 */
	class WhatsNewService
	{
		/**
			@return {Object}
			@return {string|null} return.url
			@return {string|null} return.userDateRegister
			@return {string|null} return.portalDateRegister
		 */
		static async fetchUrlParams()
		{
			try
			{
				const response = await dispatch(fetchUrlParamsThunk());

				return response.payload;
			}
			catch (error)
			{
				console.error('Failed to dispatch fetchUrlParamsThunk', error);

				throw new Error('Failed to dispatch fetchUrlParamsThunk');
			}
		}

		/**
		 * @return {boolean}
		 */
		static isDayPassedSinceLastFetch()
		{
			return isCheckTimeExpired(selectLastNewsCheckTime(store.getState()));
		}

		/**
		 * @return {string}
		 */
		static getNewCount()
		{
			return selectNewCount(store.getState());
		}

		static async fetchWhatsNewList(userId, urlParams, blockPage = INITIAL_BLOCK_PAGE)
		{
			try
			{
				return await dispatch(fetchWhatsNewThunk({
					urlParams: {
						userId,
						...urlParams,
					},
					blockPage,
				}));
			}
			catch (error)
			{
				console.error('Failed to fetch WhatsNew list', error);

				throw new Error('Failed to fetch WhatsNew list');
			}
		}

		static async fetchInitialData()
		{
			try
			{
				const urlParams = selectUrlParams(store.getState());

				await WhatsNewService.fetchWhatsNewList(env.userId, urlParams, INITIAL_BLOCK_PAGE);
			}
			catch (error)
			{
				console.error('Error fetching initial WhatsNew data', error);
			}
		}

		static subscribeToNewCountChange(callback)
		{
			let lastNewCount = WhatsNewService.getNewCount();

			return store.subscribe(() => {
				const currentNewCount = WhatsNewService.getNewCount();
				if (currentNewCount !== lastNewCount)
				{
					lastNewCount = currentNewCount;
					callback(currentNewCount);
				}
			});
		}

		static async updateLocalWhatsNewsParams()
		{
			try
			{
				const response = await dispatch(updateLocalWhatsNewsParamsThunk({}));

				return response.payload;
			}
			catch (error)
			{
				console.error('Failed to dispatch updateLocalWhatsNewsParamsThunk', error);

				throw new Error('Failed to dispatch updateLocalWhatsNewsParamsThunk');
			}
		}
	}

	module.exports = {
		WhatsNewService,
	};
});
