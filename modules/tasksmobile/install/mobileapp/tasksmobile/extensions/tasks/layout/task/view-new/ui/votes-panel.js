/**
 * @module tasks/layout/task/view-new/ui/votes-panel
 */
jn.define('tasks/layout/task/view-new/ui/votes-panel', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { Loc } = require('loc');
	const { Color, Indent } = require('tokens');
	const { connect } = require('statemanager/redux/connect');
	const {
		selectIsPositive,
		selectIsNegative,
		selectPositiveCounter,
		selectNegativeCounter,
	} = require('statemanager/redux/slices/reactions/selector');
	const { handleReactionChange } = require('statemanager/redux/slices/reactions');
	const { Text5 } = require('ui-system/typography/text');
	const { ChipButton, ChipButtonDesign, ChipButtonMode } = require('ui-system/blocks/chips/chip-button');
	const { EntityType } = require('tasks/enum');

	const DEFAULT_REACTION = 'like';
	const ACTION_TYPE = {
		POSITIVE: 'plus',
		NEGATIVE: 'minus',
	};

	class VotesPanel extends PureComponent
	{
		get #taskId()
		{
			return this.props.taskId;
		}

		bindRef(ref)
		{
			if (ref)
			{
				this.likeButtonRef = ref;
			}
		}

		#setVote(action)
		{
			handleReactionChange({
				entityType: EntityType.TASK,
				entityId: this.#taskId,
				reaction: DEFAULT_REACTION,
				action,
			});
		}

		getVoteText(counter, messageKey)
		{
			let message = Loc.getMessage(messageKey);

			if (counter > 0)
			{
				message = Loc.getMessage(`${messageKey}_WITH_COUNTER`).replace('#QUANTITY#', String(counter));
			}

			return message;
		}

		renderButton(testId, isSelected, text, actionType, marginLeft)
		{
			return ChipButton({
				testId,
				dropdown: false,
				compact: false,
				rounded: false,
				mode: ChipButtonMode.OUTLINE,
				design: isSelected ? ChipButtonDesign.PRIMARY : ChipButtonDesign.BLACK,
				text,
				onClick: () => this.#setVote(actionType),
				forwardRef: (ref) => this.bindRef(ref),
				style: {
					marginLeft,
				},
			});
		}

		render()
		{
			const { isPositive, isNegative, negativeCounter, positiveCounter } = this.props;

			const positiveText = this.getVoteText(positiveCounter, 'M_TASK_RATING_LIKE_YES');
			const negativeText = this.getVoteText(negativeCounter, 'M_TASK_RATING_LIKE_NO');

			return View(
				{
					style: {
						display: 'flex',
						alignItems: 'center',
						flexDirection: 'row',
					},
				},
				Text5({
					text: Loc.getMessage('M_TASK_RATING_LIKE_QUESTION'),
					color: Color.base3,
					ellipsize: 'middle',
				}),
				this.renderButton('positive-reaction-chip-button', isPositive, positiveText, ACTION_TYPE.POSITIVE, Indent.XL.toNumber()),
				this.renderButton('negative-reaction-chip-button', isNegative, negativeText, ACTION_TYPE.NEGATIVE, Indent.XL2.toNumber()),
			);
		}
	}

	const mapStateToProps = (state, { taskId, userId }) => {
		const isPositive = selectIsPositive(state, userId, taskId, EntityType.TASK);
		const isNegative = selectIsNegative(state, userId, taskId, EntityType.TASK);
		const positiveCounter = selectPositiveCounter(state, taskId, EntityType.TASK);
		const negativeCounter = selectNegativeCounter(state, taskId, EntityType.TASK);

		return {
			isPositive,
			isNegative,
			positiveCounter,
			negativeCounter,
		};
	};

	module.exports = {
		VotesPanel: connect(mapStateToProps)(VotesPanel),
	};
});
