/**
 * @module statemanager/redux/slices/reactions/thunk
 */
jn.define('statemanager/redux/slices/reactions/thunk', (require, exports, module) => {
	const { createAsyncThunk } = require('statemanager/redux/toolkit');
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { sliceName } = require('statemanager/redux/slices/reactions/meta');
	const { selectVoteSignTokenByEntityId } = require('statemanager/redux/slices/reactions-vote-key');
	const {
		selectUserReaction,
	} = require('statemanager/redux/slices/reactions/selector');
	const { selectReactionsTemplate } = require('statemanager/redux/slices/settings/selector');
	const store = require('statemanager/redux/store');
	const { dispatch } = store;
	const userId = Number(env.userId);

	const ACTION_TYPE = {
		PLUS: 'plus',
		CANCEL: 'cancel',
		CHANGE: 'change',
	};

	const ACTION = {
		PLUS: 'plus',
		MINUS: 'minus',
	};

	const TEMPLATE = {
		LIKE: 'like',
		LIKE_GRAPHIC: 'like_graphic',
	};

	const runActionPromise = ({ action, options }) => new Promise((resolve) => {
		(new RunActionExecutor(action, options)).setHandler(resolve).call(false);
	});

	const executeVoteAction = async ({ entityType, entityId, nextReaction, voteSignToken, userActionType }) => {
		const response = await runActionPromise({
			action: 'main.rating.vote',
			options: {
				params: {
					RATING_VOTE_TYPE_ID: entityType,
					RATING_VOTE_ENTITY_ID: entityId,
					RATING_VOTE_ACTION: userActionType,
					RATING_VOTE_REACTION: nextReaction,
					RATING_VOTE_KEY_SIGNED: voteSignToken,
				},
			},
		});

		if (response.status !== 'success')
		{
			throw new Error(`Failed to ${userActionType} reaction`);
		}

		return response.data;
	};

	const handleReactionChange = ({ entityType, entityId, reaction, action }) => {
		const prevReaction = selectUserReaction(store.getState(), userId, entityType, entityId);
		const template = selectReactionsTemplate(store.getState());

		dispatch(changeReaction({ entityType, entityId, prevReaction, nextReaction: reaction, action, template }));
	};

	const determineUserActionTypeForLike = (prevReaction, nextReaction) => {
		if (prevReaction === undefined || prevReaction === null)
		{
			return ACTION_TYPE.PLUS;
		}

		if (prevReaction.reactionId === nextReaction)
		{
			return ACTION_TYPE.CANCEL;
		}

		return ACTION_TYPE.CHANGE;
	};

	const determineUserActionTypeForVote = (state, entityType, entityId, action, prevReaction) => {
		if (!prevReaction)
		{
			return action === ACTION.PLUS ? ACTION.PLUS : ACTION.MINUS;
		}

		const isPositive = prevReaction.positiveUserIds?.includes(userId);
		const isNegative = prevReaction.negativeUserIds?.includes(userId);

		if (action === ACTION.PLUS)
		{
			if (!isPositive && !isNegative)
			{
				return ACTION.PLUS;
			}

			if (isPositive)
			{
				return ACTION_TYPE.CANCEL;
			}

			if (isNegative)
			{
				return ACTION.PLUS;
			}
		}
		else if (action === ACTION.MINUS)
		{
			if (!isPositive && !isNegative)
			{
				return ACTION.MINUS;
			}

			if (isNegative)
			{
				return ACTION_TYPE.CANCEL;
			}

			if (isPositive)
			{
				return ACTION.MINUS;
			}
		}

		return '';
	};

	const changeReaction = createAsyncThunk(
		`${sliceName}/changeReaction`,
		async ({ entityType, entityId, prevReaction, nextReaction, action, template }, { rejectWithValue, getState }) => {
			try
			{
				const state = getState();
				const voteSignToken = selectVoteSignTokenByEntityId(state, entityType, entityId);

				if (!voteSignToken)
				{
					return rejectWithValue('voteSignToken is missing.');
				}

				let userActionType = '';

				if (template === TEMPLATE.LIKE || template === TEMPLATE.LIKE_GRAPHIC)
				{
					userActionType = determineUserActionTypeForLike(prevReaction, nextReaction);
				}
				else
				{
					userActionType = determineUserActionTypeForVote(state, entityType, entityId, action, prevReaction);
				}

				const result = await executeVoteAction({
					entityType,
					entityId,
					nextReaction,
					voteSignToken,
					userActionType,
				});

				if (result.error)
				{
					return rejectWithValue(result.error);
				}

				return result.data;
			}
			catch (error)
			{
				return console.error(error);
			}
		},
	);

	module.exports = {
		changeReaction,
		handleReactionChange,
	};
});
