/**
 * @module statemanager/redux/slices/reactions
 */
jn.define('statemanager/redux/slices/reactions', (require, exports, module) => {
	const { ReducerRegistry } = require('statemanager/redux/reducer-registry');
	const { createSlice } = require('statemanager/redux/toolkit');
	const { sliceName, initialState, reactionsAdapter } = require('statemanager/redux/slices/reactions/meta');
	const {
		changeReaction,
		handleReactionChange,
	} = require('statemanager/redux/slices/reactions/thunk');
	const {
		changeReactionPending,
		changeReactionFulfilled,
		changeReactionRejected,
	} = require('statemanager/redux/slices/reactions/extra-reducer');
	const {
		selectReactionKey,
		selectReactionByItsId,
	} = require('statemanager/redux/slices/reactions/selector');

	const ActionType = {
		POSITIVE: 'PLUS',
		NEGATIVE: 'MINUS',
	};

	const commandHandler = (handlers, defaultHandler) => (commandKey, ...args) => {
		const handler = handlers[commandKey] || defaultHandler;

		return handler(...args);
	};
	const removeItem = (array, item) => (array || []).filter((i) => i !== item);
	const addUniqueDirectly = (array, item) => {
		if (Array.isArray(array) && !array.includes(item))
		{
			array.push(item);
		}
	};

	const reactionHandlers = {
		[ActionType.POSITIVE]: (reaction, userId) => {
			addUniqueDirectly(reaction.positiveUserIds, userId);
			addUniqueDirectly(reaction.userIds, userId);
		},
		[ActionType.NEGATIVE]: (reaction, userId) => {
			addUniqueDirectly(reaction.negativeUserIds, userId);
			addUniqueDirectly(reaction.userIds, userId);
		},
	};

	const defaultReactionHandler = (reaction, userId) => {
		addUniqueDirectly(reaction.userIds, userId);
	};

	const initReaction = (userId, actionType) => ({
		userIds: [userId],
		positiveUserIds: actionType === ActionType.POSITIVE ? [userId] : [],
		negativeUserIds: actionType === ActionType.NEGATIVE ? [userId] : [],
	});

	const reactionsSlice = createSlice({
		name: sliceName,
		initialState,
		reducers: {
			reactionsUpserted: (state, { payload }) => {
				reactionsAdapter.upsertMany(state, payload);
			},
			reactionAdded: (state, { payload }) => {
				const { entityId, entityType, userId, reactionId, actionType } = payload;
				const targetReaction = selectReactionByItsId(state, entityType, entityId, reactionId);

				if (targetReaction)
				{
					if (!targetReaction.positiveUserIds)
					{
						targetReaction.positiveUserIds = [];
					}

					if (!targetReaction.negativeUserIds)
					{
						targetReaction.negativeUserIds = [];
					}

					const handleReaction = commandHandler(reactionHandlers, defaultReactionHandler);
					handleReaction(actionType, targetReaction, userId);
				}
				else
				{
					reactionsAdapter.addOne(state, {
						...initReaction(userId, actionType),
						reactionId,
						entityId,
						entityType,
					});
				}
			},
			reactionChanged: (state, { payload }) => {
				const { entityId, entityType, userId, reactionId } = payload;

				Object.values(state.entities).forEach((reaction) => {
					if (reaction.entityId === entityId && reaction.entityType === entityType)
					{
						const index = reaction.userIds.indexOf(userId);
						if (index !== -1)
						{
							reaction.userIds.splice(index, 1);
						}

						const positiveIndex = reaction.positiveUserIds.indexOf(userId);
						if (positiveIndex !== -1)
						{
							reaction.positiveUserIds.splice(positiveIndex, 1);
						}
					}
				});

				const targetReaction = selectReactionByItsId(state, entityType, entityId, reactionId);

				if (targetReaction)
				{
					addUniqueDirectly(targetReaction.userIds, userId);
					addUniqueDirectly(targetReaction.positiveUserIds, userId);
					targetReaction.negativeUserIds = [];
				}
				else
				{
					reactionsAdapter.addOne(state, {
						...initReaction(userId, ActionType.POSITIVE),
						reactionId,
						entityId,
						entityType,
					});
				}
			},
			reactionCancelled: (state, { payload }) => {
				const { entityId, entityType, userId, reactionId } = payload;
				const currentReactionKey = selectReactionKey(entityType, entityId, reactionId);
				const targetReaction = selectReactionByItsId(state, entityType, entityId, reactionId);

				if (targetReaction)
				{
					reactionsAdapter.updateOne(state, {
						id: currentReactionKey,
						changes: {
							userIds: removeItem(targetReaction.userIds, userId),
							positiveUserIds: removeItem(targetReaction.positiveUserIds, userId),
							negativeUserIds: removeItem(targetReaction.negativeUserIds, userId),
						},
					});
				}
			},
		},
		extraReducers: (builder) => {
			builder
				.addCase(changeReaction.pending, changeReactionPending)
				.addCase(changeReaction.fulfilled, changeReactionFulfilled)
				.addCase(changeReaction.rejected, changeReactionRejected);
		},
	});

	const {
		reactionsUpserted,
		reactionAdded,
		reactionChanged,
		reactionCancelled,
	} = reactionsSlice.actions;

	ReducerRegistry.register(sliceName, reactionsSlice.reducer);

	module.exports = {
		reactionsUpserted,
		reactionAdded,
		reactionChanged,
		reactionCancelled,
		changeReaction,
		handleReactionChange,
	};
});
