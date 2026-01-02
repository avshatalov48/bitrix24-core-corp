/* eslint-disable no-param-reassign */

/**
 * @module im/messenger/model/sidebar/src/links/model
 */
jn.define('im/messenger/model/sidebar/src/links/model', (require, exports, module) => {
	const { Type } = require('type');
	const { Moment } = require('utils/date');

	const { validate } = require('im/messenger/model/sidebar/src/links/validator');
	const { linkItem, linkDefaultElement } = require('im/messenger/model/sidebar/src/links/default-element');
	const { MessengerParams } = require('im/messenger/lib/params');

	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('model--sidebar-links');

	/** @type {SidebarLinksModel} */
	const sidebarLinksModel = {
		namespaced: true,
		state: () => ({
			collection: {},
		}),
		getters: {
			/**
			 * @function sidebarModel/sidebarLinksModel/get
			 * @param state
			 * @return {SidebarLinkItem}
			 */
			get: (state) => (chatId) => {
				if (!state.collection[chatId])
				{
					return {};
				}

				if (!state.collection[chatId]?.isHistoryLimitExceeded)
				{
					return state.collection[chatId];
				}

				const defaultLimitDays = 30;
				const limitDays = MessengerParams.getPlanLimits()?.fullChatHistory?.limitDays || defaultLimitDays;

				const links = state.collection[chatId].links;
				const filteredLinks = new Map();
				for (const [key, value] of links)
				{
					if (new Moment(value.dateCreate).daysFromNow < limitDays)
					{
						filteredLinks.set(key, value);
					}
				}

				return {
					...state.collection[chatId],
					links: filteredLinks,
				};
			},
			/** @function sidebarModel/sidebarLinksModel/getSize
			 * @param state
			 * @return {number}
			 */
			getSize: (state) => (chatId) => {
				return state.collection[chatId]?.links?.size ?? 0;
			},
			/**
			 * @function sidebarModel/sidebarLinksModel/hasNextPage
			 * @param state
			 * @return {boolean}
			 */
			hasNextPage: (state) => (chatId) => {
				return Boolean(state.collection[chatId]?.hasNextPage);
			},
			/**
			 * @function sidebarModel/sidebarLinksModel/isHistoryLimitExceeded
			 * @param state
			 * @param getters
			 * @return {boolean}
			 */
			isHistoryLimitExceeded: (state, getters) => (chatId) => {
				if (MessengerParams.isFullChatHistoryAvailable())
				{
					return false;
				}

				if (state.collection[chatId]?.isHistoryLimitExceeded)
				{
					return true;
				}

				const linksCollectionSize = state.collection[chatId]?.links.size;
				const linksWithHistoryLimitExceeded = getters.get(chatId)?.links?.size;
				if (linksCollectionSize && linksWithHistoryLimitExceeded)
				{
					return linksWithHistoryLimitExceeded !== linksCollectionSize;
				}

				return false;
			},
		},
		actions: {
			/**
			 * @function sidebarModel/sidebarLinksModel/set
			 */
			set: (store, payload) => {
				const { chatId, links } = payload;

				if (!Type.isArrayFilled(links) || !Type.isNumber(chatId))
				{
					return;
				}

				set(store, payload, 'set');
			},
			/**
			 * @function sidebarModel/sidebarLinksModel/setFromPagination
			 */
			setFromPagination: (store, payload) => {
				const { chatId, links, hasNextPage, isHistoryLimitExceeded } = payload;

				if (!Type.isArray(links) || !Type.isNumber(chatId))
				{
					return;
				}

				if (Type.isBoolean(hasNextPage))
				{
					store.commit('setHasNextPage', {
						actionName: 'setHasNextPage',
						data: {
							chatId,
							hasNextPage,
						},
					});
				}

				store.dispatch('setHistoryLimitExceeded', {
					chatId,
					isHistoryLimitExceeded,
				});

				set(store, payload, 'setFromPagination');
			},

			/**
			 * @function sidebarModel/sidebarLinksModel/setHistoryLimitExceeded
			 */
			setHistoryLimitExceeded: (store, payload) => {
				const { chatId, isHistoryLimitExceeded } = payload;

				if (!Type.isNumber(chatId) || !Type.isBoolean(isHistoryLimitExceeded))
				{
					return;
				}

				store.commit('setHistoryLimitExceeded', {
					actionName: 'setHistoryLimitExceeded',
					data: {
						chatId,
						isHistoryLimitExceeded,
					},
				});
			},
			/** @function sidebarModel/sidebarLinksModel/delete */
			delete: (store, payload) => {
				const { chatId, id } = payload;
				const isValidParams = Type.isNumber(id) && Type.isNumber(chatId);
				const hasCollection = store.state.collection[chatId];

				if (!isValidParams || !hasCollection)
				{
					return;
				}

				store.commit('delete', {
					actionName: 'delete',
					data: {
						chatId,
						id,
					},
				});
			},
			/** @function sidebarModel/sidebarLinksModel/deleteByChatId */
			deleteByChatId: (store, payload) => {
				const { chatId } = payload;

				store.commit('deleteByChatId', {
					actionName: 'deleteByChatId',
					data: {
						chatId,
					},
				});
			},
		},
		mutations: {
			/**
			 * @param state
			 * @param {
			 * MutationPayload<SidebarLinksSetHistoryLimitExceededData, SidebarLinksSetHistoryLimitExceededActions>
			 * } payload
			 */
			setHistoryLimitExceeded: (state, payload) => {
				logger.log('SidebarLinksModel: setHistoryLimitExceeded mutation', payload);
				const { chatId, isHistoryLimitExceeded } = payload.data;

				if (!state.collection[chatId])
				{
					state.collection[chatId] = linkDefaultElement();
				}

				state.collection[chatId].isHistoryLimitExceeded = isHistoryLimitExceeded;
			},
			/**
			 * @param state
			 * @param {MutationPayload<SidebarLinksSetData, SidebarLinksSetActions>} payload
			 */
			set: (state, payload) => {
				logger.log('SidebarLinksModel: set mutation', payload);
				const { chatId, links } = payload.data;

				if (!state.collection[chatId])
				{
					state.collection[chatId] = linkDefaultElement();
				}

				links.forEach((value, key, map) => {
					state.collection[chatId].links.set(key, value);
				});
			},
			/**
			 * @param state
			 * @param {MutationPayload<SidebarLinksDeleteData, SidebarLinksDeleteActions>} payload
			 */
			delete: (state, payload) => {
				logger.log('SidebarLinksModel: delete mutation', payload);
				const { chatId, id } = payload.data;

				if (state.collection[chatId] && state.collection[chatId].links.has(id))
				{
					state.collection[chatId].links.delete(id);
				}
			},
			/**
			 * @param state
			 * @param {MutationPayload<SidebarLinksDeleteByChatIdData, SidebarLinksDeleteByChatIdActions>} payload
			 */
			deleteByChatId: (state, payload) => {
				logger.log('SidebarLinksModel: deleteByChatId mutation', payload);
				const { chatId } = payload.data;

				if (state.collection[chatId])
				{
					delete state.collection[chatId];
				}
			},
			/**
			 * @param state
			 * @param {MutationPayload<SidebarLinksSetHasNextPageData, SidebarLinksSetHasNextPageActions>} payload
			 */
			setHasNextPage: (state, payload) => {
				logger.log('SidebarLinksModel: setHasNextPage mutation', payload);
				const { chatId, hasNextPage } = payload.data;
				if (!state.collection[chatId])
				{
					state.collection[chatId] = linkDefaultElement();
				}

				state.collection[chatId].hasNextPage = hasNextPage;
			},
		},
	};

	function set(store, payload, actionName)
	{
		const { chatId, links } = payload;

		const newLinks = new Map();
		links.forEach((link) => {
			const prepareLink = { ...linkItem, ...validate(link) };
			newLinks.set(link.id, prepareLink);
		});

		store.commit('set', {
			actionName,
			data: {
				chatId,
				links: newLinks,
			},
		});
	}

	module.exports = { sidebarLinksModel };
});
