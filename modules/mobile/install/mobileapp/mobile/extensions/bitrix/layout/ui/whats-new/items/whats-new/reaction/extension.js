/**
 * @module layout/ui/whats-new/items/whats-new/reaction
 */
jn.define('layout/ui/whats-new/items/whats-new/reaction', (require, exports, module) => {
	const { Indent } = require('tokens');
	const { ReactionIcon, ReactionIconView, REACTION_ICON_TYPE } = require('ui-system/blocks/reaction/icon');
	const { BadgeCounter, BadgeCounterDesign, BadgeCounterSize } = require('ui-system/blocks/badges/counter');
	const { connect } = require('statemanager/redux/connect');
	const { toNumber } = require('utils/number');
	const { WhatsNewAnalytics } = require('layout/ui/whats-new/analytics');

	const {
		sendReactionThunk,
		REACTION_ACTIONS,
		REACTIONS_NAMES,
		selectReactionByItemIdAndReactionName,
	} = require('statemanager/redux/slices/whats-new');

	/**
	 * @class WhatsNewReaction
	 */
	class WhatsNewReaction extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.onReactionClick = this.onReactionClick.bind(this);
		}

		get testId()
		{
			return this.props.testId;
		}

		get isLast()
		{
			return this.props.isLast;
		}

		get reactionName()
		{
			return this.props.reactionName;
		}

		get reactionCount()
		{
			return this.props.reactionCount;
		}

		get isSelected()
		{
			return this.props.isSelected;
		}

		render()
		{
			const icon = this.getReactionByName(this.reactionName);
			if (!icon)
			{
				return View({});
			}

			return View(
				{
					testId: `${this.testId}-reaction-${this.reactionName}`,
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						marginRight: !this.isLast && Indent.L.toNumber(),
						padding: Indent.XS.toNumber(),
					},
					onClick: this.onReactionClick,
				},
				ReactionIconView({
					testId: `${this.testId}-reaction-${this.reactionName}-icon`,
					icon,
					size: 24,
					type: REACTION_ICON_TYPE.png,
				}),
				BadgeCounter({
					testId: `${this.testId}-reaction-${this.reactionName}-count`,
					value: this.reactionCount,
					design: this.isSelected ? BadgeCounterDesign.PRIMARY : BadgeCounterDesign.LIGHT_GREY,
					style: {
						marginLeft: Indent.S.toNumber(),
					},
					size: BadgeCounterSize.XS,
					showRawValue: true,
				}),
			);
		}

		onReactionClick()
		{
			this.props.sendReactionThunk({
				itemId: this.props.itemId,
				auth: this.props.auth,
				notificationAction: this.isSelected ? REACTION_ACTIONS.REMOVE_REACTION : REACTION_ACTIONS.MAKE_REACTION,
				notificationCode: this.reactionName,
			});

			new WhatsNewAnalytics()
				.setEvent(this.isSelected ? WhatsNewAnalytics.Event.removeReaction : WhatsNewAnalytics.Event.makeReaction)
				.setType(this.reactionName)
				.setP4(WhatsNewAnalytics.getWhatsNewItemId(this.props.itemId))
				.send();
		}

		getReactionByName(name)
		{
			switch (name)
			{
				case REACTIONS_NAMES.FIRE:
					return ReactionIcon.WHATS_NEW_FIRE;
				case REACTIONS_NAMES.LIKE:
					return ReactionIcon.WHATS_NEW_LIKE;
				case REACTIONS_NAMES.DISLIKE:
					return ReactionIcon.WHATS_NEW_DISLIKE;
				default:
					return null;
			}
		}
	}

	const mapStateToProps = (state, ownProps) => {
		const reaction = selectReactionByItemIdAndReactionName(
			state,
			ownProps.itemId,
			ownProps.reactionName,
		);

		return {
			testId: ownProps.testId,
			isLast: ownProps.isLast,
			reactionName: ownProps.reactionName,
			itemId: ownProps.itemId,

			reactionCount: toNumber(reaction.count),
			isSelected: reaction?.select,
		};
	};

	const mapDispatchToProps = ({
		sendReactionThunk,
	});

	WhatsNewReaction.propTypes = {
		testId: PropTypes.string.isRequired,
		isLast: PropTypes.bool,
		reactionName: PropTypes.string.isRequired,
		reactionCount: PropTypes.number.isRequired,
		itemId: PropTypes.number.isRequired,
		auth: PropTypes.string.isRequired,
	};

	module.exports = {
		WhatsNewReaction: connect(mapStateToProps, mapDispatchToProps)(WhatsNewReaction),
	};
});
