/**
 * @module statemanager/redux/slices/reactions/selector
 */
jn.define('statemanager/redux/slices/reactions/selector', (require, exports, module) => {
	const { sliceName, reactionsAdapter } = require('statemanager/redux/slices/reactions/meta');
	const { usersSelector } = require('statemanager/redux/slices/users/selector');
	const { createDraftSafeSelector } = require('statemanager/redux/toolkit');

	const reactionsSelector = reactionsAdapter.getSelectors((state) => state[sliceName]);

	const selectReactionsAll = reactionsSelector.selectAll;
	const selectUsersEntities = usersSelector.selectEntities;

	const selectReactionKey = (entityType, entityId, reactionId) => `${entityType}_${entityId}_${reactionId}`;

	const selectReactionByItsId = (state, entityType, entityId, reactionId) => {
		const key = selectReactionKey(entityType, entityId, reactionId);

		return state.entities[key] || null;
	};

	const selectReactionsByEntity = createDraftSafeSelector(
		selectReactionsAll,
		(state, entityId, entityType) => ({ entityId, entityType }),
		(reactions, { entityId, entityType }) => {
			return reactions.filter((reaction) => reaction.entityId === entityId && reaction.entityType === entityType);
		},
	);

	const selectUserReaction = createDraftSafeSelector(
		(state, userId, entityType, entityId) => selectReactionsByEntity(state, entityId, entityType),
		(state, userId) => userId,
		(reactions, userId) => reactions.find((reaction) => reaction.userIds.includes(userId)) || null,
	);

	const selectReactionsUserIds = createDraftSafeSelector(
		selectReactionsByEntity,
		(reactions) => reactions.flatMap((reaction) => reaction.userIds),
	);

	const selectUsersWithReactions = createDraftSafeSelector(
		selectReactionsUserIds,
		selectUsersEntities,
		(userIds, users) => userIds.map((id) => users[id]).filter(Boolean),
	);

	const selectTotalReactionsCountByEntity = createDraftSafeSelector(
		selectReactionsByEntity,
		(reactions) => reactions.reduce((sum, reaction) => sum + reaction.userIds.length, 0),
	);

	const selectIsOnlyMyReaction = createDraftSafeSelector(
		selectReactionsUserIds,
		(state, entityId, entityType, userId) => userId,
		(userIds, userId) => userIds.length === 1 && userIds[0] === userId,
	);

	const selectReactionsCounterWithoutCurrentUser = createDraftSafeSelector(
		selectReactionsByEntity,
		(state, entityId, entityType, currentUserId) => currentUserId,
		(reactions, currentUserId) => reactions.reduce(
			(totalSum, reaction) => totalSum + reaction.userIds.filter(
				(userId) => userId !== currentUserId,
			).length,
			0,
		),
	);

	const createReactionTypeSelector = (field) => createDraftSafeSelector(
		(state, userId, entityId, entityType) => selectReactionsByEntity(state, entityId, entityType),
		(state, userId) => userId,
		(reactions, userId) => reactions.some((reaction) => reaction[field]?.includes(userId)),
	);

	const selectIsPositive = createReactionTypeSelector('positiveUserIds');
	const selectIsNegative = createReactionTypeSelector('negativeUserIds');

	const createCounterSelector = (field) => createDraftSafeSelector(
		selectReactionsByEntity,
		(reactions) => reactions.reduce((sum, reaction) => sum + reaction[field].length, 0),
	);

	const selectPositiveCounter = createCounterSelector('positiveUserIds');
	const selectNegativeCounter = createCounterSelector('negativeUserIds');

	const {
		selectAll,
		selectById,
	} = reactionsSelector;

	module.exports = {
		reactionsSelector,
		selectReactionByItsId,
		selectReactionKey,
		selectUserReaction,
		selectReactionsByEntity,
		selectUsersWithReactions,
		selectTotalReactionsCountByEntity,
		selectReactionsCounterWithoutCurrentUser,
		selectIsOnlyMyReaction,

		selectIsPositive,
		selectIsNegative,
		selectPositiveCounter,
		selectNegativeCounter,

		selectAll,
		selectById,
	};
});
