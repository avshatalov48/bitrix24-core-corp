/* eslint-disable no-param-reassign */
/**
 * @module im/messenger/model/files/transcript/model
 */
jn.define('im/messenger/model/files/transcript/model', (require, exports, module) => {
	const { Type } = require('type');
	const { clone, isEqual } = require('utils/object');

	const { TranscriptStatus } = require('im/messenger/const');
	const { transcriptDefaultElement } = require('im/messenger/model/files/transcript/default-element');
	const { ModelUtils } = require('im/messenger/lib/utils');
	const { validate } = require('im/messenger/model/files/transcript/validator');

	const { getLogger } = require('im/messenger/lib/logger');
	const logger = getLogger('model--files-transcript');

	/** @type {TranscriptMessengerModel} */
	const transcriptModel = {
		namespaced: true,
		state: () => ({
			collection: {},
		}),
		getters: {
			/**
			 * @function filesModel/transcriptModel/getById
			 * @return {TranscriptModelState | null}
			 */
			getById: (state) => (fileId) => {
				return state.collection[fileId] ?? null;
			},

			/**
			 * @function filesModel/transcriptModel/hasTranscript
			 * @return {boolean}
			 */
			hasTranscript: (state) => (fileId) => fileId in state.collection,

			/**
			 * @function filesModel/transcriptModel/hasTranscriptText
			 * @return {boolean}
			 */
			hasTranscriptText: (state, getters) => (fileId) => {
				const transcript = getters.getById(fileId);

				return Type.isStringFilled(transcript?.text);
			},
		},
		actions: {
			/** @function filesModel/transcriptModel/set */
			set: (store, payload) => {
				const { itemList, actionName = 'set' } = ModelUtils.normalizeItemListPayload(payload);
				const transcriptList = itemList.map((transcript) => {
					const result = validate(store, { ...transcript });

					return {
						...transcriptDefaultElement,
						...result,
					};
				});

				const existingTranscriptList = [];
				const newTranscriptList = [];
				transcriptList.forEach((transcript) => {
					if (!store.getters.hasTranscript(transcript.fileId))
					{
						newTranscriptList.push(transcript);

						return;
					}

					const isEqualTranscript = isEqual(transcript, store.getters.getById(transcript.fileId));
					if (!isEqualTranscript)
					{
						existingTranscriptList.push(transcript);
					}
				});

				if (existingTranscriptList.length > 0)
				{
					store.commit('update', {
						actionName,
						data: {
							transcriptList: existingTranscriptList,
						},
					});
				}

				if (newTranscriptList.length > 0)
				{
					store.commit('add', {
						actionName,
						data: {
							transcriptList: newTranscriptList,
						},
					});
				}
			},

			/** @function filesModel/transcriptModel/setFromLocalDatabase */
			setFromLocalDatabase: (store, payload) => {
				const { itemList } = ModelUtils.normalizeItemListPayload(payload);
				const transcriptList = itemList.map((transcript) => {
					const result = validate(store, { ...transcript });

					return {
						...transcriptDefaultElement,
						...result,
					};
				});

				const newTranscriptList = transcriptList
					.filter((transcript) => !store.getters.hasTranscript(transcript.fileId));

				if (newTranscriptList.length > 0)
				{
					store.commit('add', {
						actionName: 'setFromLocalDatabase',
						data: {
							transcriptList: newTranscriptList,
						},
					});
				}
			},

			/**
			 * @function filesModel/transcriptModel/toggleText
			 * @param {MessengerStore<MessengerModel<TranscriptModelCollection>>} store
			 * @param {FileId} payload.fileId
			 */
			toggleText: (store, payload) => {
				const { fileId } = payload;
				const transcript = clone(store.getters.getById(fileId));

				if (!Type.isStringFilled(transcript?.text))
				{
					return;
				}

				const newStatus = transcript.status === TranscriptStatus.ready
					? TranscriptStatus.expanded
					: TranscriptStatus.ready;
				transcript.status = newStatus;

				store.commit('update', {
					actionName: 'toggleText',
					data: {
						transcriptList: [transcript],
					},
				});
			},

			/** @function filesModel/transcriptModel/delete */
			delete: (store, payload) => {
				const { fileId } = payload;
				if (!store.state.collection[fileId])
				{
					return;
				}

				store.commit('delete', {
					actionName: 'delete',
					data: { fileId },
				});
			},

			/** @function filesModel/transcriptModel/deleteByChatId */
			deleteByChatId: (store, payload) => {
				const { chatId } = payload;

				store.commit('deleteByChatId', {
					actionName: 'deleteByChatId',
					data: { chatId },
				});
			},
		},
		mutations: {
			/**
			 * @param state
			 * @param {MutationPayload<TranscriptAddData, TranscriptModelMutation>} payload
			 */
			add: (state, payload) => {
				logger.log('transcriptModel: add mutation', payload);

				const {
					transcriptList,
				} = payload.data;

				transcriptList.forEach((transcript) => {
					state.collection[transcript.fileId] = transcript;
				});
			},

			/**
			 * @param state
			 * @param {MutationPayload<TranscriptAddData, TranscriptModelMutation>} payload
			 */
			update: (state, payload) => {
				logger.log('transcriptModel: update mutation', payload);

				const {
					transcriptList,
				} = payload.data;

				transcriptList.forEach((transcript) => {
					state.collection[transcript.fileId] = {
						...state.collection[transcript.fileId],
						...transcript,
					};
				});
			},

			/**
			 * @param state
			 * @param {MutationPayload<TranscriptDeleteData, TranscriptDeleteActions>} payload
			 */
			delete: (state, payload) => {
				logger.log('transcriptModel: delete mutation', payload);
				const { fileId } = payload.data;

				delete state.collection[fileId];
			},

			/**
			 * @param state
			 * @param {MutationPayload<TranscriptDeleteByChatIdData, TranscriptDeleteByChatIdActions>} payload
			 */
			deleteByChatId: (state, payload) => {
				logger.log('transcriptModel: deleteByChatId mutation', payload);

				const { chatId } = payload.data;

				for (const transcript of Object.values(state.collection))
				{
					if (transcript.chatId === chatId)
					{
						delete state.collection[transcript.fileId];
					}
				}
			},
		},
	};

	module.exports = { transcriptModel };
});
