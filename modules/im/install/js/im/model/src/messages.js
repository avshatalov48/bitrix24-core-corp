/**
 * Bitrix Messenger
 * Message model (Vuex Builder model)
 *
 * @package bitrix
 * @subpackage im
 * @copyright 2001-2019 Bitrix
 */


import {Vue} from 'ui.vue';
import {VuexBuilderModel} from 'ui.vue.vuex';
import {MutationType, StorageLimit} from 'im.const';
import {Utils} from "im.utils";

const IntersectionType = {
	empty: 'empty',
	equal: 'equal',
	none: 'none',
	found: 'found',
	foundReverse: 'foundReverse',
};

class MessagesModel extends VuexBuilderModel
{
	getName()
	{
		return 'messages';
	}

	getState()
	{
		return {
			created: 0,
			collection: {},
			mutationType: {},
			saveMessageList: {},
			saveFileList: {},
			saveUserList: {},
		}
	}

	getElementState()
	{
		return {
			templateId: 0,
			templateType: 'message',

			id: 0,
			chatId: 0,
			authorId: 0,
			date: new Date(),
			text: "",
			textConverted: "",
			params: {
				TYPE : 'default',
				COMPONENT_ID : 'bx-messenger-message',
			},

			push: false,
			unread: false,
			sending: false,
			error: false,
			retry: false,
			blink: false,
		};
	}

	getGetters()
	{
		return {

			getMutationType: state => chatId =>
			{
				if (!state.mutationType[chatId])
				{
					return {initialType: MutationType.none, appliedType: MutationType.none};
				}

				return state.mutationType[chatId];
			},
			getLastId: state => chatId =>
			{
				if (!state.collection[chatId] || state.collection[chatId].length <= 0)
				{
					return null;
				}

				let lastId = 0;

				for (let i = 0; i < state.collection[chatId].length; i++)
				{
					let element = state.collection[chatId][i];
					if (
						element.push
						|| element.sending
						|| element.id.toString().startsWith('temporary')
					)
					{
						continue;
					}

					if (lastId < element.id)
					{
						lastId = element.id;
					}
				}

				return lastId? lastId: null;
			},
			getMessage: state => (chatId, messageId) =>
			{
				if (!state.collection[chatId] || state.collection[chatId].length <= 0)
				{
					return null;
				}

				for (let index = state.collection[chatId].length-1; index >= 0; index--)
				{
					if (state.collection[chatId][index].id === messageId)
					{
						return state.collection[chatId][index];
					}
				}

				return null;
			},
			get: state => chatId =>
			{
				if (!state.collection[chatId] || state.collection[chatId].length <= 0)
				{
					return [];
				}

				return state.collection[chatId];
			},
			getBlank: state => params =>
			{
				return this.getElementState();
			},
			getSaveFileList: state => params =>
			{
				return state.saveFileList;
			},
			getSaveUserList: state => params =>
			{
				return state.saveUserList;
			},
		}
	}

	getActions()
	{
		return {
			add: (store, payload) =>
			{
				let result = this.validate(Object.assign({}, payload));
				result.params = Object.assign({}, this.getElementState().params, result.params);
				result.id = 'temporary' + (new Date).getTime() + store.state.created;
				result.templateId = result.id;
				result.unread = false;

				store.commit('add', Object.assign({}, this.getElementState(), result));

				if (payload.sending !== false)
				{
					store.dispatch('actionStart', {
						id: result.id,
						chatId: result.chatId,
					});
				}

				return result.id;
			},
			actionStart: (store, payload) =>
			{
				if (/^\d+$/.test(payload.id))
				{
					payload.id = parseInt(payload.id);
				}

				payload.chatId = parseInt(payload.chatId);

				Vue.nextTick(() => {
					store.commit('update', {
						id : payload.id ,
						chatId : payload.chatId,
						fields : {sending: true}
					});
				});
			},
			actionError: (store, payload) =>
			{
				if (/^\d+$/.test(payload.id))
				{
					payload.id = parseInt(payload.id);
				}
				payload.chatId = parseInt(payload.chatId);

				Vue.nextTick(() => {
					store.commit('update', {
						id : payload.id ,
						chatId : payload.chatId,
						fields : {sending: false, error: true, retry: payload.retry !== false}
					});
				});
			},
			actionFinish: (store, payload) =>
			{
				if (/^\d+$/.test(payload.id))
				{
					payload.id = parseInt(payload.id);
				}
				payload.chatId = parseInt(payload.chatId);

				Vue.nextTick(() => {
					store.commit('update', {
						id : payload.id ,
						chatId : payload.chatId,
						fields : {sending: false, error: false, retry: false}
					});
				});
			},
			set: (store, payload) =>
			{
				if (payload instanceof Array)
				{
					payload = payload.map(message => this.prepareMessage(message));
				}
				else
				{
					let result = this.prepareMessage(payload);
					(payload = []).push(result);
				}

				store.commit('set', {
					insertType : MutationType.set,
					data : payload
				});
			},
			setAfter: (store, payload) =>
			{
				if (payload instanceof Array)
				{
					payload = payload.map(message => this.prepareMessage(message));
				}
				else
				{
					let result = this.prepareMessage(payload);
					(payload = []).push(result);
				}

				store.commit('set', {
					insertType : MutationType.setAfter,
					data : payload
				});
			},
			setBefore: (store, payload) =>
			{
				if (payload instanceof Array)
				{
					payload = payload.map(message => this.prepareMessage(message));
				}
				else
				{
					let result = this.prepareMessage(payload);
					(payload = []).push(result);
				}

				store.commit('set', {
					insertType : MutationType.setBefore,
					data : payload
				});
			},
			update: (store, payload) =>
			{
				if (/^\d+$/.test(payload.id))
				{
					payload.id = parseInt(payload.id);
				}
				if (/^\d+$/.test(payload.chatId))
				{
					payload.chatId = parseInt(payload.chatId);
				}

				store.commit('initCollection', {chatId: payload.chatId});

				let index = store.state.collection[payload.chatId].findIndex(el => el.id === payload.id);
				if (index < 0)
				{
					return false;
				}

				let result = this.validate(Object.assign({}, payload.fields));

				if (result.params)
				{
					result.params = Object.assign(
						{},
						this.getElementState().params,
						store.state.collection[payload.chatId][index].params,
						result.params
					);
				}

				store.commit('update', {
					id : payload.id,
					chatId : payload.chatId,
					index : index,
					fields : result
				});

				if (payload.fields.blink)
				{
					setTimeout(() => {
						store.commit('update', {
							id : payload.id ,
							chatId : payload.chatId,
							fields : {blink: false}
						});
					}, 1000);
				}

				return true;
			},
			delete: (store, payload) =>
			{
				if (!(payload.id instanceof Array))
				{
					payload.id = [payload.id];
				}

				payload.id = payload.id.map(id => {
					if (/^\d+$/.test(id))
					{
						id = parseInt(id);
					}
					return id;
				});

				store.commit('delete', {
					chatId : payload.chatId,
					elements : payload.id,
				});

				return true;
			},
			clear: (store, payload) =>
			{
				payload.chatId = parseInt(payload.chatId);

				store.commit('clear', {
					chatId : payload.chatId
				});

				return true;
			},
			applyMutationType: (store, payload) =>
			{
				payload.chatId = parseInt(payload.chatId);

				store.commit('applyMutationType', {
					chatId : payload.chatId
				});

				return true;
			},
			readMessages: (store, payload) =>
			{
				payload.readId = parseInt(payload.readId) || 0;
				payload.chatId = parseInt(payload.chatId);

				if (typeof store.state.collection[payload.chatId] === 'undefined')
				{
					return {count: 0}
				}

				let count = 0;
				for (let index = store.state.collection[payload.chatId].length-1; index >= 0; index--)
				{
					let element = store.state.collection[payload.chatId][index];
					if (!element.unread)
						continue;

					if (payload.readId === 0 || element.id <= payload.readId)
					{
						count++;
					}
				}

				store.commit('readMessages', {
					chatId: payload.chatId,
					readId: payload.readId,
				});

				return {count};
			},
			unreadMessages: (store, payload) =>
			{
				payload.unreadId = parseInt(payload.unreadId) || 0;
				payload.chatId = parseInt(payload.chatId);

				if (typeof store.state.collection[payload.chatId] === 'undefined' || !payload.unreadId)
				{
					return {count: 0}
				}

				let count = 0;
				for (let index = store.state.collection[payload.chatId].length-1; index >= 0; index--)
				{
					let element = store.state.collection[payload.chatId][index];
					if (element.unread)
						continue;

					if (element.id >= payload.unreadId)
					{
						count++;
					}
				}

				store.commit('unreadMessages', {
					chatId: payload.chatId,
					unreadId: payload.unreadId,
				});

				return {count};
			},
		}
	}

	getMutations()
	{
		return {
			initCollection: (state, payload) =>
			{
				return this.initCollection(state, payload);
			},
			add: (state, payload) =>
			{
				this.initCollection(state, {chatId: payload.chatId});
				this.setMutationType(state, {chatId: payload.chatId, initialType: MutationType.add});

				state.collection[payload.chatId].push(payload);
				state.saveMessageList[payload.chatId].push(payload.id);

				state.created += 1;

				this.saveState(state, payload.chatId);
			},
			set: (state, payload) =>
			{
				let chats = [];
				let chatsSave = [];
				let mutationType = {};

				mutationType.initialType = payload.insertType;

				if (payload.insertType === MutationType.set)
				{
					payload.insertType = MutationType.setAfter;

					let elements = {};
					payload.data.forEach(element => {
						if (!elements[element.chatId])
						{
							elements[element.chatId] = [];
						}
						elements[element.chatId].push(element.id);
					});

					for (let chatId in elements)
					{
						if (!elements.hasOwnProperty(chatId))
							continue;

						this.initCollection(state, {chatId});

						if (
							state.saveMessageList[chatId].length > elements[chatId].length
							|| elements[chatId].length < StorageLimit.messages
						)
						{
							state.collection[chatId] = state.collection[chatId].filter(element => elements[chatId].includes(element.id));
							state.saveMessageList[chatId] = state.saveMessageList[chatId].filter(id => elements[chatId].includes(id));
						}

						let intersection = this.manageCacheBeforeSet(
							[...state.saveMessageList[chatId].reverse()],
							elements[chatId]
						);
						if (intersection.type === IntersectionType.none)
						{
							if (intersection.foundElements.length > 0)
							{
								state.collection[chatId] = state.collection[chatId].filter(element => !intersection.foundElements.includes(element.id));
								state.saveMessageList[chatId] = state.saveMessageList[chatId].filter(id => !intersection.foundElements.includes(id));
							}

							this.removeIntersectionCacheElements = state.collection[chatId].map(element => element.id);

							clearTimeout(this.removeIntersectionCacheTimeout);
							this.removeIntersectionCacheTimeout = setTimeout(() => {
								state.collection[chatId] = state.collection[chatId].filter(element => !this.removeIntersectionCacheElements.includes(element.id));
								state.saveMessageList[chatId] = state.saveMessageList[chatId].filter(id => !this.removeIntersectionCacheElements.includes(id));
								this.removeIntersectionCacheElements = [];
							}, 1000);
						}
						else
						{
							if (intersection.type === IntersectionType.foundReverse)
							{
								payload.insertType = MutationType.setBefore;
								payload.data = payload.data.reverse();
							}
						}

						if (intersection.foundElements.length > 0)
						{
							if (intersection.type === IntersectionType.found && intersection.noneElements[0])
							{
								mutationType.scrollStickToTop = false;
								mutationType.scrollMessageId = intersection.foundElements[intersection.foundElements.length-1];
							}
							else
							{
								mutationType.scrollStickToTop = false;
								mutationType.scrollMessageId = 0;
							}
						}
						else if (intersection.type === IntersectionType.none)
						{
							mutationType.scrollStickToTop = false;
							mutationType.scrollMessageId = payload.data[0].id;
						}
					}
				}

				mutationType.appliedType = payload.insertType;

				for (let element of payload.data)
				{
					this.initCollection(state, {chatId: element.chatId});

					let index = state.collection[element.chatId].findIndex(el => el.id === element.id);
					if (index > -1)
					{
						delete element.templateId;

						state.collection[element.chatId][index] = Object.assign(
							state.collection[element.chatId][index],
							element
						);
					}
					else if (payload.insertType === MutationType.setBefore)
					{
						state.collection[element.chatId].unshift(element);
					}
					else if (payload.insertType === MutationType.setAfter)
					{
						state.collection[element.chatId].push(element);
					}

					chats.push(element.chatId);

					if (this.store.getters['dialogues/canSaveChat'] && this.store.getters['dialogues/canSaveChat'](element.chatId))
					{
						chatsSave.push(element.chatId);
					}
				}

				chats = [...new Set(chats)];
				chatsSave = [...new Set(chatsSave)];

				// check array for correct order of messages
				if (mutationType.initialType === MutationType.set)
				{
					chats.forEach(chatId =>
					{
						let lastElementId = 0;
						let needApplySort = false;
						for (let i = 0; i < state.collection[chatId].length; i++)
						{
							let element = state.collection[chatId][i];
							if (element.id < lastElementId)
							{
								needApplySort = true;
								break;
							}

							lastElementId = element.id;
						}
						if (needApplySort)
						{
							state.collection[chatId].sort((a, b) => a.id - b.id);
						}
					});
				}

				chats.forEach(chatId => {
					this.setMutationType(state, {chatId: chatId, ...mutationType});
				});

				if (mutationType.initialType !== MutationType.setBefore)
				{
					chatsSave.forEach(chatId => {
						this.saveState(state, chatId);
					});
				}
			},
			update: (state, payload) =>
			{
				this.initCollection(state, {chatId: payload.chatId});

				let index = -1;
				if (typeof payload.index !== 'undefined' && state.collection[payload.chatId][payload.index])
				{
					index = payload.index;
				}
				else
				{
					index = state.collection[payload.chatId].findIndex(el => el.id === payload.id);
				}

				if (index >= 0)
				{
					let isSaveState = (
						state.saveMessageList[payload.chatId].includes(state.collection[payload.chatId][index].id)
						|| payload.fields.id && !payload.fields.id.toString().startsWith('temporary') && state.collection[payload.chatId][index].id.toString().startsWith('temporary')
					);

					delete payload.fields.templateId;

					state.collection[payload.chatId][index] = Object.assign(
						state.collection[payload.chatId][index],
						payload.fields
					);

					if (isSaveState)
					{
						this.saveState(state, payload.chatId);
					}
				}
			},
			delete: (state, payload) =>
			{
				this.initCollection(state, {chatId: payload.chatId});
				this.setMutationType(state, {chatId: payload.chatId, initialType: MutationType.delete});

				state.collection[payload.chatId] = state.collection[payload.chatId].filter(element => !payload.elements.includes(element.id));

				if (state.saveMessageList[payload.chatId].length > 0)
				{
					for (let id of payload.elements)
					{
						if (state.saveMessageList[payload.chatId].includes(id))
						{
							this.saveState(state, payload.chatId);

							break;
						}
					}
				}
			},
			clear: (state, payload) =>
			{
				this.initCollection(state, {chatId: payload.chatId});
				this.setMutationType(state, {chatId: payload.chatId, initialType: 'clear'});

				state.collection[payload.chatId] = [];
				state.saveMessageList[payload.chatId] = [];
			},
			applyMutationType: (state, payload) =>
			{
				if (typeof state.mutationType[payload.chatId] === 'undefined')
				{
					Vue.set(state.mutationType, payload.chatId, {applied: false, initialType: MutationType.none, appliedType: MutationType.none, scrollStickToTop: 0, scrollMessageId: 0});
				}

				state.mutationType[payload.chatId].applied = true;
			},
			readMessages: (state, payload) =>
			{
				this.initCollection(state, {chatId: payload.chatId});

				let saveNeeded = false;
				for (let index = state.collection[payload.chatId].length-1; index >= 0; index--)
				{
					let element = state.collection[payload.chatId][index];
					if (!element.unread)
						continue;

					if (payload.readId === 0 || element.id <= payload.readId)
					{
						state.collection[payload.chatId][index] = Object.assign(
							state.collection[payload.chatId][index],
							{unread: false}
						);
						saveNeeded = true;
					}
				}
				if (saveNeeded)
				{
					this.saveState(state, payload.chatId);
				}
			},
			unreadMessages: (state, payload) =>
			{
				this.initCollection(state, {chatId: payload.chatId});

				let saveNeeded = false;
				for (let index = state.collection[payload.chatId].length-1; index >= 0; index--)
				{
					let element = state.collection[payload.chatId][index];
					if (element.unread)
						continue;

					if (element.id >= payload.unreadId)
					{
						state.collection[payload.chatId][index] = Object.assign(
							state.collection[payload.chatId][index],
							{unread: true}
						);
						saveNeeded = true;
					}
				}
				if (saveNeeded)
				{
					this.saveState(state, payload.chatId);
					this.updateSubordinateStates();
				}
			},
		}
	}

	initCollection(state, payload)
	{
		if (typeof payload.chatId === 'undefined')
		{
			return false;
		}

		if (
			typeof payload.chatId === 'undefined'
			|| typeof state.collection[payload.chatId] !== 'undefined'
		)
		{
			return true;
		}

		Vue.set(state.collection, payload.chatId, payload.messages? [].concat(payload.messages): []);
		Vue.set(state.mutationType, payload.chatId, {applied: false, initialType: MutationType.none, appliedType: MutationType.none, scrollStickToTop: 0, scrollMessageId: 0});
		Vue.set(state.saveMessageList, payload.chatId, []);
		Vue.set(state.saveFileList, payload.chatId, []);
		Vue.set(state.saveUserList, payload.chatId, []);

		return true;
	}

	setMutationType(state, payload)
	{
		let mutationType = {
			applied: false,
			initialType: MutationType.none,
			appliedType: MutationType.none,
			scrollStickToTop: false,
			scrollMessageId: 0
		};

		if (payload.initialType && !payload.appliedType)
		{
			payload.appliedType = payload.initialType;
		}

		if (typeof state.mutationType[payload.chatId] === 'undefined')
		{
			Vue.set(state.mutationType, payload.chatId, mutationType);
		}

		state.mutationType[payload.chatId] = {...mutationType, ...payload};

		return true;
	}

	prepareMessage(message)
	{
		let result = this.validate(Object.assign({}, message));

		result.params = Object.assign({}, this.getElementState().params, result.params);
		result.templateId = result.id;

		return Object.assign({}, this.getElementState(), result);
	}

	manageCacheBeforeSet(cache, elements, recursive = false)
	{
		let result = {
			type: IntersectionType.empty,
			foundElements: [],
			noneElements: []
		};

		if (!cache || cache.length <= 0)
		{
			return result;
		}

		for (let id of elements)
		{
			if (cache.includes(id))
			{
				if (result.type === IntersectionType.empty)
				{
					result.type = IntersectionType.found;
				}
				result.foundElements.push(id);
			}
			else
			{
				if (result.type === IntersectionType.empty)
				{
					result.type = IntersectionType.none;
				}
				result.noneElements.push(id);
			}
		}

		if (
			result.type === IntersectionType.found
			&& cache.length === elements.length
			&& result.foundElements.length === elements.length
		)
		{
			result.type = IntersectionType.equal;
		}
		else if (
			result.type === IntersectionType.none
			&& !recursive
			&& result.foundElements.length > 0
		)
		{
			let reverseResult = this.manageCacheBeforeSet(cache.reverse(), elements.reverse(), true);
			if (reverseResult.type === IntersectionType.found)
			{
				reverseResult.type = IntersectionType.foundReverse;
				return reverseResult;
			}
		}

		return result;
	}

	updateSaveLists(state, chatId)
	{
		if (!this.isSaveAvailable())
		{
			return true;
		}

		if (
			!chatId
			|| !this.store.getters['dialogues/canSaveChat']
			|| !this.store.getters['dialogues/canSaveChat'](chatId)
		)
		{
			return false;
		}

		this.initCollection(state, {chatId: chatId});

		let count = 0;
		let saveMessageList = [];
		let saveFileList = [];
		let saveUserList = [];

		let dialog = this.store.getters['dialogues/getByChatId'](chatId);
		if (dialog && dialog.type === 'private')
		{
			saveUserList.push(parseInt(dialog.dialogId));
		}

		for (let index = state.collection[chatId].length-1; index >= 0; index--)
		{
			if (state.collection[chatId][index].id.toString().startsWith('temporary'))
			{
				continue;
			}

			if (count >= StorageLimit.messages && !state.collection[chatId][index].unread)
			{
				break;
			}

			saveMessageList.unshift(state.collection[chatId][index].id);

			count++;
		}

		saveMessageList = saveMessageList.slice(0, StorageLimit.messages);

		state.collection[chatId].filter(element => saveMessageList.includes(element.id)).forEach(element =>
		{
			if (element.authorId > 0)
			{
				saveUserList.push(element.authorId);
			}

			if (element.params.FILE_ID instanceof Array)
			{
				saveFileList = element.params.FILE_ID.concat(saveFileList);
			}
		});

		state.saveMessageList[chatId] = saveMessageList;
		state.saveFileList[chatId] = [...new Set(saveFileList)];
		state.saveUserList[chatId] = [...new Set(saveUserList)];

		return true;
	}

	getSaveTimeout()
	{
		return 150;
	}

	saveState(state, chatId)
	{
		if (!this.updateSaveLists(state, chatId))
		{
			return false;
		}

		super.saveState(() =>
		{
			let storedState = {
				collection: {},
				saveMessageList: {},
				saveUserList: {},
				saveFileList: {},
			};

			for (let chatId in state.saveMessageList)
			{
				if (!state.saveMessageList.hasOwnProperty(chatId))
				{
					continue;
				}

				if (!state.collection[chatId])
				{
					continue;
				}

				if (!storedState.collection[chatId])
				{
					storedState.collection[chatId] = [];
				}

				state.collection[chatId]
					.filter(element => state.saveMessageList[chatId].includes(element.id))
					.forEach(element => storedState.collection[chatId].push(element))
				;

				storedState.saveMessageList[chatId] = state.saveMessageList[chatId];
				storedState.saveFileList[chatId] = state.saveFileList[chatId];
				storedState.saveUserList[chatId] = state.saveUserList[chatId];
			}

			return storedState;
		});
	}

	updateSubordinateStates()
	{
		this.store.dispatch('users/saveState');
		this.store.dispatch('files/saveState');
	}

	validate(fields)
	{
		const result = {};

		if (typeof fields.id === "number")
		{
			result.id = fields.id;
		}
		else if (typeof fields.id === "string")
		{
			if (fields.id.startsWith('temporary'))
			{
				result.id = fields.id;
			}
			else
			{
				result.id = parseInt(fields.id);
			}
		}

		if (typeof fields.templateId === "number")
		{
			result.templateId = fields.templateId;
		}
		else if (typeof fields.templateId === "string")
		{
			if (fields.templateId.startsWith('temporary'))
			{
				result.templateId = fields.templateId;
			}
			else
			{
				result.templateId = parseInt(fields.templateId);
			}
		}

		if (typeof fields.chat_id !== 'undefined')
		{
			fields.chatId = fields.chat_id;
		}
		if (typeof fields.chatId === "number" || typeof fields.chatId === "string")
		{
			result.chatId = parseInt(fields.chatId);
		}
		if (typeof fields.date !== "undefined")
		{
			result.date = Utils.date.cast(fields.date);
		}

		// previous P&P format
		if (typeof fields.textOriginal === "string" || typeof fields.textOriginal === "number")
		{
			result.text = fields.textOriginal.toString();

			if (typeof fields.text === "string" || typeof fields.text === "number")
			{
				result.textConverted = this.convertToHtml({
					text: fields.text.toString(),
					isConverted: true
				});
			}
		}
		else // modern format
		{
			if (typeof fields.text_converted !== 'undefined')
			{
				fields.textConverted = fields.text_converted;
			}
			if (typeof fields.textConverted === "string" || typeof fields.textConverted === "number")
			{
				result.textConverted = fields.textConverted.toString();
			}
			if (typeof fields.text === "string" || typeof fields.text === "number")
			{
				result.text = fields.text.toString();

				let isConverted = typeof result.textConverted !== 'undefined';

				result.textConverted = this.convertToHtml({
					text: isConverted? result.textConverted: result.text,
					isConverted
				});
			}
		}

		if (typeof fields.senderId !== 'undefined')
		{
			fields.authorId = fields.senderId;
		}
		else if (typeof fields.author_id !== 'undefined')
		{
			fields.authorId = fields.author_id;
		}
		if (typeof fields.authorId === "number" || typeof fields.authorId === "string")
		{
			if (fields.system === true || fields.system === 'Y')
			{
				result.authorId = 0;
			}
			else
			{
				result.authorId = parseInt(fields.authorId);
			}
		}

		if (typeof fields.params === "object" && fields.params !== null)
		{
			const params = this.validateParams(fields.params);
			if (params)
			{
				result.params = params;
			}
		}

		if (typeof fields.push === "boolean")
		{
			result.push = fields.push;
		}

		if (typeof fields.sending === "boolean")
		{
			result.sending = fields.sending;
		}

		if (typeof fields.unread === "boolean")
		{
			result.unread = fields.unread;
		}

		if (typeof fields.blink === "boolean")
		{
			result.blink = fields.blink;
		}

		if (typeof fields.error === "boolean" || typeof fields.error === "string")
		{
			result.error = fields.error;
		}

		if (typeof fields.retry === "boolean")
		{
			result.retry = fields.retry;
		}

		return result;
	}

	validateParams(params)
	{
		const result = {};
		try
		{
			for (let field in params)
			{
				if (!params.hasOwnProperty(field))
				{
					continue;
				}

				if (field === 'COMPONENT_ID')
				{
					if (typeof params[field] === "string" && BX.Vue.isComponent(params[field]))
					{
						result[field] = params[field];
					}
				}
				else if (field === 'LIKE')
				{
					if (params[field] instanceof Array)
					{
						result['REACTION'] = {like: params[field].map(element => parseInt(element))};
					}
				}
				else if (field === 'CHAT_LAST_DATE')
				{
					result[field] = Utils.date.cast(params[field]);
				}
				else
				{
					result[field] = params[field];
				}
			}
		}
		catch (e) {}

		let hasResultElements = false;
		for (let field in result)
		{
			if (!result.hasOwnProperty(field))
			{
				continue;
			}

			hasResultElements = true;
			break
		}

		return hasResultElements? result: null;
	}

	convertToHtml(params = {})
	{
		let {
			quote = true,
			image = true,
			text = '',
			highlightText = '',
			isConverted = false,
			enableBigSmile = true
		} = params;

		text = text.trim();

		if (!isConverted)
		{
			text = text.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
		}

		if (text.startsWith('/me'))
		{
			text = `<i>${text.substr(4)}</i>`;
		}
		else if (text.startsWith('/loud'))
		{
			text = `<b>${text.substr(6)}</b>`;
		}

		const quoteSign = "&gt;&gt;";
		if (quote && text.indexOf(quoteSign) >= 0)
		{
			let textPrepareFlag = false;
			let textPrepare = text.split(isConverted? "<br />": "\n");
			for (let i = 0; i < textPrepare.length; i++)
			{
				if (textPrepare[i].startsWith(quoteSign))
				{
					textPrepare[i] = textPrepare[i].replace(quoteSign, '<div class="bx-im-message-content-quote"><div class="bx-im-message-content-quote-wrap">');
					while (++i < textPrepare.length && textPrepare[i].startsWith(quoteSign))
					{
						textPrepare[i] = textPrepare[i].replace(quoteSign, '');
					}
					textPrepare[i - 1] += '</div></div><br>';
					textPrepareFlag = true;
				}
			}
			text = textPrepare.join("<br />");
		}

		text = text.replace(/\n/gi, '<br />');

		text = text.replace(/\t/gi, '&nbsp;&nbsp;&nbsp;&nbsp;');

		text = this.decodeBbCode(text, false, enableBigSmile);

		if (quote)
		{
			text = text.replace(/------------------------------------------------------<br \/>(.*?)\[(.*?)\]<br \/>(.*?)------------------------------------------------------(<br \/>)?/g, function (whole, p1, p2, p3, p4, offset) {
				return (offset > 0? '<br>': '') + "<div class=\"bx-im-message-content-quote\"><div class=\"bx-im-message-content-quote-wrap\"><div class=\"bx-im-message-content-quote-name\"><span class=\"bx-im-message-content-quote-name-text\">" + p1 + "</span><span class=\"bx-im-message-content-quote-name-time\">" + p2 + "</span></div>" + p3 + "</div></div><br />";
			});
			text = text.replace(/------------------------------------------------------<br \/>(.*?)------------------------------------------------------(<br \/>)?/g, function (whole, p1, p2, p3, offset) {
				return (offset > 0? '<br>': '') + "<div class=\"bx-im-message-content-quote\"><div class=\"bx-im-message-content-quote-wrap\">" + p1 + "</div></div><br />";
			});
		}

		if (image)
		{
			let changed = false;
			text = text.replace(/<a(.*?)>(http[s]{0,1}:\/\/.*?)<\/a>/ig, function(whole, aInner, text, offset)
			{
				if(!text.match(/(\.(jpg|jpeg|png|gif)\?|\.(jpg|jpeg|png|gif)$)/i) || text.indexOf("/docs/pub/") > 0 || text.indexOf("logout=yes") > 0)
				{
					return whole;
				}
				else
				{
					changed = true;
					return (offset > 0? '<br />':'')+'<a' +aInner+ ' target="_blank" class="bx-im-element-file-image"><img src="'+text+'" class="bx-im-element-file-image-source-text" onerror="BX.Messenger.Model.MessagesModel.hideErrorImage(this)"></a></span>';
				}
			});
			if (changed)
			{
				text = text.replace(/<\/span>(\n?)<br(\s\/?)>/ig, '</span>').replace(/<br(\s\/?)>(\n?)<br(\s\/?)>(\n?)<span/ig, '<br /><span');
			}
		}

		if (highlightText)
		{
			text = text.replace(new RegExp("(" + highlightText.replace(/[\-\[\]\/{}()*+?.\\^$|]/g, "\\$&") + ")", 'ig'), '<span class="bx-messenger-highlight">$1</span>');
		}

		if (enableBigSmile)
		{
			text = text.replace(
				/^(\s*<img\s+src=[^>]+?data-code=[^>]+?data-definition="UHD"[^>]+?style="width:)(\d+)(px[^>]+?height:)(\d+)(px[^>]+?class="bx-smile"\s*\/?>\s*)$/,
				function doubleSmileSize(match, start, width, middle, height, end) {
					return start + (parseInt(width, 10) * 1.7) + middle + (parseInt(height, 10) * 1.7) + end;
				}
			);
		}

		if (text.substr(-6) == '<br />')
		{
			text = text.substr(0, text.length - 6);
		}
		text = text.replace(/<br><br \/>/ig, '<br />');
		text = text.replace(/<br \/><br>/ig, '<br />');

		return text;
	};

	decodeBbCode(text, textOnly = false, enableBigSmile = true)
	{
		return MessagesModel.decodeBbCode({text, textOnly, enableBigSmile})
	}

	static decodeBbCode(params = {})
	{
		let {text, textOnly = false, enableBigSmile = true} = params;

		let codeReplacement = [];

		text = text.replace(/\[CODE\]\n?(.*?)\[\/CODE\]/sig, function(whole, text)
		{
			let id = codeReplacement.length;
			codeReplacement.push(text);
			return '####REPLACEMENT_MARK_'+id+'####';
		});

		text = text.replace(/\[LIKE\]/ig, '<span class="bx-smile bx-im-smile-like"></span>');
		text = text.replace(/\[DISLIKE\]/ig, '<span class="bx-smile bx-im-smile-dislike"></span>');

		text = text.replace(/\[USER=([0-9]{1,})\](.*?)\[\/USER\]/ig, (whole, userId, text) => '<span class="bx-im-mention" data-type="USER" data-value="'+userId+'">'+text+'</span>');

		text = text.replace(/\[CHAT=(imol\|)?([0-9]{1,})\](.*?)\[\/CHAT\]/ig, (whole, openlines, chatId, text) => openlines? text: '<span class="bx-im-mention" data-type="CHAT" data-value="chat'+chatId+'">'+text+'</span>'); // TODO tag CHAT

		text = text.replace(/\[CALL(?:=(.+?))?\](.+?)?\[\/CALL\]/ig, (whole, number, text) => '<span class="bx-im-mention" data-type="CALL" data-value="'+Utils.text.htmlspecialchars(number)+'">'+text+'</span>'); // TODO tag CHAT

		text = text.replace(/\[PCH=([0-9]{1,})\](.*?)\[\/PCH\]/ig, (whole, historyId, text) => text); // TODO tag PCH

		text = text.replace(/\[SEND(?:=(.+?))?\](.+?)?\[\/SEND\]/ig, (whole, command, text) =>
		{
			let html = '';

			text = text? text: command;
			command = (command? command: text).replace('<br />', '\n');

			if (!textOnly && text)
			{
				text = text.replace(/<([\w]+)[^>]*>(.*?)<\\1>/i, "$2", text);
				text = text.replace(/\[([\w]+)[^\]]*\](.*?)\[\/\1\]/i, "$2", text);

				html = '<span class="bx-im-message-command-wrap">'+
					'<span class="bx-im-message-command" data-entity="send">'+text+'</span>'+
					'<span class="bx-im-message-command-data">'+command+'</span>'+
				'</span>';
			}
			else
			{
				html = text;
			}

			return html;
		});

		text = text.replace(/\[PUT(?:=(.+?))?\](.+?)?\[\/PUT\]/ig, (whole, command, text) =>
		{
			let html = '';

			text = text? text: command;
			command = (command? command: text).replace('<br />', '\n');

			if (!textOnly && text)
			{
				text = text.replace(/<([\w]+)[^>]*>(.*?)<\/\1>/i, "$2", text);
				text = text.replace(/\[([\w]+)[^\]]*\](.*?)\[\/\1\]/i, "$2", text);

				html = '<span class="bx-im-message-command" data-entity="put">'+text+'</span>';
				html += '<span class="bx-im-message-command-data">'+command+'</span>';
			}
			else
			{
				html = text;
			}

			return html;
		});

		let textElementSize = 0;
		if (enableBigSmile)
		{
			textElementSize = text.replace(/\[icon\=([^\]]*)\]/ig, '').trim().length;
		}

		text = text.replace(/\[icon\=([^\]]*)\]/ig, (whole) =>
		{
			let url = whole.match(/icon\=(\S+[^\s.,> )\];\'\"!?])/i);
			if (url && url[1])
			{
				url = url[1];
			}
			else
			{
				return '';
			}

			let attrs = {'src': url, 'border': 0};

			let size = whole.match(/size\=(\d+)/i);
			if (size && size[1])
			{
				attrs['width'] = size[1];
				attrs['height'] = size[1];
			}
			else
			{
				let width = whole.match(/width\=(\d+)/i);
				if (width && width[1])
				{
					attrs['width'] = width[1];
				}

				let height = whole.match(/height\=(\d+)/i);
				if (height && height[1])
				{
					attrs['height'] = height[1];
				}

				if (attrs['width'] && !attrs['height'])
				{
					attrs['height'] = attrs['width'];
				}
				else if (attrs['height'] && !attrs['width'])
				{
					attrs['width'] = attrs['height'];
				}
				else if (attrs['height'] && attrs['width'])
				{}
				else
				{
					attrs['width'] = 20;
					attrs['height'] = 20;
				}
			}

			attrs['width'] = attrs['width']>100? 100: attrs['width'];
			attrs['height'] = attrs['height']>100? 100: attrs['height'];

			if (enableBigSmile && textElementSize === 0 && attrs['width'] === attrs['height'] && attrs['width'] === 20)
			{
				attrs['width'] = 40;
				attrs['height'] = 40;
			}

			let title = whole.match(/title\=(.*[^\s\]])/i);
			if (title && title[1])
			{
				title = title[1];
				if (title.indexOf('width=') > -1)
				{
					title = title.substr(0, title.indexOf('width='))
				}
				if (title.indexOf('height=') > -1)
				{
					title = title.substr(0, title.indexOf('height='))
				}
				if (title.indexOf('size=') > -1)
				{
					title = title.substr(0, title.indexOf('size='))
				}
				if (title)
				{
					attrs['title'] = Utils.text.htmlspecialchars(title).trim();
					attrs['alt'] = attrs['title'];
				}
			}

			let attributes = '';
			for (let name in attrs)
			{
				if (attrs.hasOwnProperty(name))
				{
					attributes += name+'="'+attrs[name]+'" ';
				}
			}


			return '<img class="bx-smile bx-icon" '+attributes+'>';
		});

		codeReplacement.forEach((code, index) => {
			text = text.replace('####REPLACEMENT_MARK_'+index+'####',
				!textOnly? '<div class="bx-im-message-content-code">'+code+'</div>': code
			)
		});

		return text;
	}

	static hideErrorImage(element)
	{
		if (element.parentNode && element.parentNode)
		{
			element.parentNode.innerHTML = '<a href="'+element.src+'" target="_blank">'+element.src+'</a>';
		}
		return true;
	};
}

export {MessagesModel};