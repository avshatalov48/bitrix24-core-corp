/**
 * @module statemanager/redux/slices/reactions/extra-reducer
 */
jn.define('statemanager/redux/slices/reactions/extra-reducer', (require, exports, module) => {
	const { reactionsAdapter } = require('statemanager/redux/slices/reactions/meta');
	const { selectReactionKey, selectReactionByItsId } = require('statemanager/redux/slices/reactions/selector');

	const userId = Number(env.userId);
	const VOTE_TYPE = {
		PLUS: 'plus',
		MINUS: 'minus',
	};

	const changeReactionPending = (state, action) => {
		const { entityType, entityId, prevReaction = null, nextReaction } = action.meta.arg;

		if (prevReaction)
		{
			const updatedUserIds = prevReaction.userIds
				? prevReaction.userIds.filter((id) => id !== userId)
				: [];
			const positiveUserIds = prevReaction.positiveUserIds
				? prevReaction.positiveUserIds.filter((id) => id !== userId)
				: [];
			const negativeUserIds = prevReaction.negativeUserIds
				? prevReaction.negativeUserIds.filter((id) => id !== userId)
				: [];

			reactionsAdapter.updateOne(state, {
				id: selectReactionKey(entityType, entityId, prevReaction.reactionId),
				changes: {
					userIds: updatedUserIds,
					positiveUserIds,
					negativeUserIds,
				},
			});

			if (
				prevReaction.reactionId === nextReaction
				&& (
					(action.meta.arg.action === VOTE_TYPE.PLUS && prevReaction.positiveUserIds?.includes(userId))
					|| (action.meta.arg.action === VOTE_TYPE.MINUS && prevReaction.negativeUserIds?.includes(userId))
				)
			)
			{
				return;
			}
		}

		const existingReaction = selectReactionByItsId(state, entityType, entityId, nextReaction);

		const newUserIds = existingReaction ? [...existingReaction.userIds] : [];
		const newPositiveUserIds = existingReaction ? [...existingReaction.positiveUserIds] : [];
		const newNegativeUserIds = existingReaction ? [...existingReaction.negativeUserIds] : [];

		if (action.meta.arg.action === VOTE_TYPE.PLUS)
		{
			newPositiveUserIds.push(userId);
		}
		else if (action.meta.arg.action === VOTE_TYPE.MINUS)
		{
			newNegativeUserIds.push(userId);
		}

		if (!newUserIds.includes(userId))
		{
			newUserIds.push(userId);
		}

		reactionsAdapter.upsertOne(state, {
			entityType,
			entityId,
			reactionId: nextReaction,
			userIds: newUserIds,
			positiveUserIds: newPositiveUserIds,
			negativeUserIds: newNegativeUserIds,
		});
	};

	const changeReactionFulfilled = (state, action) => {
		// todo action for fulfilled is???
	};

	const changeReactionRejected = (state, action) => {
		const { entityType, entityId, prevReaction = null, nextReaction } = action.meta.arg;

		if (prevReaction)
		{
			reactionsAdapter.updateOne(state, {
				id: selectReactionKey(entityType, entityId, prevReaction.reactionId),
				changes: {
					userIds: prevReaction.userIds,
					positiveUserIds: prevReaction.positiveUserIds,
					negativeUserIds: prevReaction.negativeUserIds,
				},
			});
		}

		const existingReaction = selectReactionByItsId(state, entityType, entityId, nextReaction.reactionId);

		if (existingReaction)
		{
			const updatedUserIds = existingReaction.userIds.filter((id) => id !== userId);
			const updatedPositiveUserIds = existingReaction.positiveUserIds.filter((id) => id !== userId);
			const updatedNegativeUserIds = existingReaction.negativeUserIds.filter((id) => id !== userId);

			reactionsAdapter.updateOne(state, {
				id: selectReactionKey(entityType, entityId, nextReaction.reactionId),
				changes: {
					userIds: updatedUserIds,
					positiveUserIds: updatedPositiveUserIds,
					negativeUserIds: updatedNegativeUserIds,
				},
			});
		}
	};

	module.exports = {
		changeReactionPending,
		changeReactionFulfilled,
		changeReactionRejected,
	};
});
