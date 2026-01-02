/**
 * @module tasks/layout/task/view-new/ui/likes-panel
 */
jn.define('tasks/layout/task/view-new/ui/likes-panel', (require, exports, module) => {
	const { Color, Indent, Component } = require('tokens');
	const { connect } = require('statemanager/redux/connect');
	const { menu } = require('native/contextmenu');
	const { ReactionListController } = require('layout/ui/reaction-list');
	const {
		selectReactionsByEntity,
		selectUsersWithReactions,
		selectTotalReactionsCountByEntity,
		selectIsOnlyMyReaction,
	} = require('statemanager/redux/slices/reactions/selector');
	const { Type } = require('type');
	const { selectVoteSignTokenByEntityId, selectIsVoteSignTokenLoaded } = require('statemanager/redux/slices/reactions-vote-key');
	const { selectReactionsTemplate } = require('statemanager/redux/slices/settings/selector');
	const { handleReactionChange } = require('statemanager/redux/slices/reactions');
	const { Text6 } = require('ui-system/typography/text');
	const { ChipButtonMode } = require('ui-system/blocks/chips/chip-button');
	const { ChipReaction } = require('ui-system/blocks/chips/chip-reaction');
	const { ElementsStack, ElementsStackDirection } = require('elements-stack');
	const { Line } = require('utils/skeleton');
	const { ReactionIconView, ReactionIcon } = require('ui-system/blocks/reaction/icon');
	const { ViewsCount } = require('tasks/layout/task/view-new/ui/views-count');
	const { Haptics } = require('haptics');
	const { EntityType } = require('tasks/enum');
	const { VotesPanel } = require('tasks/layout/task/view-new/ui/votes-panel');

	const LIKE = 'like';
	const LIKE_GRAPHIC = 'like_graphic';
	const ACTION_TYPE = {
		POSITIVE: 'plus',
		NEGATIVE: 'minus',
	};

	const renderShimmer = () => {
		const marginBottom = Indent.XS.toNumber();
		const radius = Component.elementAccentCorner.toNumber();

		return Line(99, 24, 0, marginBottom, radius);
	};

	const LikesPanel = ({
		isLoadingAfterCreation,
		hasVoteSignToken,
		taskId,
		users,
		userId,
		reactions,
		testId,
		template,
		reactionLength,
		isOnlyMyReaction,
		voteSignToken,
	}) => {
		const isTemplateLoaded = !Type.isUndefined(template);
		const isReactionTemplate = template === LIKE || template === LIKE_GRAPHIC;

		return View(
			{
				testId: `${testId}_Container`,
				style: Styles.likesPanel,
			},
			isTemplateLoaded
				&& hasVoteSignToken
				&& !isLoadingAfterCreation
				? (isReactionTemplate
					? new LikeButton({
						reactions,
						users,
						userId,
						taskId,
						template,
						reactionLength,
						isOnlyMyReaction,
						voteSignToken,
					})
					: VotesPanel({
						userId,
						taskId,
						template,
					}))
				: renderShimmer(),
			ViewsCount({ testId, taskId }),
		);
	};

	class LikeButton extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.likeButtonRef = null;
		}

		get #reactions()
		{
			return this.props.reactions ?? null;
		}

		get #userId()
		{
			return this.props.userId ?? null;
		}

		get #taskId()
		{
			return this.props.taskId;
		}

		get #selectedReactions()
		{
			return this.#reactions.filter((reaction) => reaction.userIds.length > 0);
		}

		get #template()
		{
			return this.props.template;
		}

		bindRef(ref)
		{
			if (ref)
			{
				this.likeButtonRef = ref;
			}
		}

		#getCurrentUserReaction()
		{
			return this.#reactions.find((reaction) => reaction.userIds.includes(this.#userId)) ?? null;
		}

		#getReactionIds(filteredReactions)
		{
			return [...new Set(
				filteredReactions.map((reaction) => reaction.reactionId),
			)];
		}

		#isCurrentUserReacted()
		{
			return Boolean(this.#getCurrentUserReaction());
		}

		#openReactionPicker()
		{
			const pickerHandle = (reactionId) => {
				this.#setReaction(reactionId, ACTION_TYPE.POSITIVE);

				menu.off('reactionTap', pickerHandle);
			};

			menu.on('reactionTap', pickerHandle);

			const menuData = {
				reactionList: ReactionIcon.getDataForContextMenu(),
			};

			menu.showPopupReactions(menuData, this.likeButtonRef);
		}

		#openUserReactionList()
		{
			ReactionListController.open({
				entityType: EntityType.TASK,
				entityId: this.#taskId,
				voteSignToken: this.props.voteSignToken,
				withRedux: true,
			});
		}

		#setReaction(reactionId, action = ACTION_TYPE.POSITIVE)
		{
			Haptics.impactLight();

			handleReactionChange({
				entityType: EntityType.TASK,
				entityId: this.#taskId,
				reaction: reactionId,
				action,
			});
		}

		renderReactions()
		{
			const currentUserReaction = this.#getCurrentUserReaction() ?? LIKE;
			const entities = this.getEntitiesForReactionStack();

			return View(
				{
					style: Styles.currentUserReactionView,
				},
				this.renderReactionButton(currentUserReaction),
				entities.length > 0 && this.renderReactionStack(entities),
				entities.length > 0 && this.renderReactionStackCounter(),
			);
		}

		getEntitiesForReactionStack()
		{
			const filteredReactions = this.#selectedReactions;
			const iconsToDisplay = this.#getReactionIds(filteredReactions);

			return iconsToDisplay.map((reactionId) => {
				const icon = ReactionIcon.getIconByReactionId(reactionId);

				return ReactionIconView({
					id: reactionId,
					testId: `reaction-stack-entity-${reactionId}`,
					size: 17,
					offset: -1,
					icon,
				});
			});
		}

		renderReactionStack(entities)
		{
			const isOnlyMyReaction = this.props.isOnlyMyReaction;
			if (isOnlyMyReaction)
			{
				return View({});
			}

			return ElementsStack(
				{
					style: {
						marginLeft: 8,
					},
					direction: ElementsStackDirection.RIGHT,
					offset: Indent.XS.toNumber(),
					radius: null,
					indent: null,
					testId: 'reaction-stack',
					onClick: () => {
						this.#openUserReactionList();
					},
				},
				...entities,
			);
		}

		renderReactionStackCounter()
		{
			const isOnlyMyReaction = this.props.isOnlyMyReaction;
			const reactionsLength = this.props.reactionLength;

			if (isOnlyMyReaction)
			{
				return View({});
			}

			return Text6({
				testId: `reaction-stack-counter-${reactionsLength}`,
				text: String(reactionsLength),
				style: {
					marginLeft: Indent.S.toNumber(),
					color: Color.base5.toHex(),
				},
			});
		}

		renderReactionButton(reaction)
		{
			const isCurrentUserReacted = this.#isCurrentUserReacted();
			const reactionId = reaction.reactionId ?? LIKE;

			return ChipReaction({
				userId: this.#userId,
				reactionId,
				compact: true,
				selected: isCurrentUserReacted,
				mode: isCurrentUserReacted ? ChipButtonMode.SOLID : ChipButtonMode.OUTLINE,
				testId: 'chip-reaction',
				onClick: () => this.#setReaction(reactionId || LIKE, ACTION_TYPE.POSITIVE),
				onLongClick: () => this.#openReactionPicker(),
				forwardRef: (ref) => this.bindRef(ref),
			});
		}

		render()
		{
			let content = null;

			if (this.#template)
			{
				content = this.#reactions
					? this.renderReactions()
					: renderShimmer();
			}
			else
			{
				content = renderShimmer();
			}

			return View({}, content);
		}
	}

	const Styles = {
		likesPanel: {
			paddingTop: Component.areaPaddingT.toNumber(),
			paddingBottom: Indent.S.toNumber(),
			paddingHorizontal: Component.areaPaddingLr.toNumber(),
			flexDirection: 'row',
			justifyContent: 'space-between',
			alignItems: 'center',
			minHeight: 52,
		},
		currentUserReactionView: {
			display: 'flex',
			flexDirection: 'row',
			justifyContent: 'center',
			alignItems: 'center',
		},
		reactionView: {
			display: 'flex',
			flexDirection: 'row',
			justifyContent: 'flex-start',
			alignItems: 'flex-start',
			marginRight: 8,
		},
		defaultLikeButton: {
			paddingVertical: Indent.XS.toNumber(),
			paddingRight: Indent.L.toNumber(),
			paddingLeft: Indent.S.toNumber(),
			flexDirection: 'row',
			alignItems: 'center',
			borderWidth: 1,
			borderRadius: Component.elementAccentCorner.toNumber(),
			borderColor: Color.base5.toHex(),
		},
	};

	const mapStateToProps = (state, { taskId, userId }) => {
		const reactions = selectReactionsByEntity(state, taskId, EntityType.TASK);
		const users = selectUsersWithReactions(state, taskId, EntityType.TASK);
		const template = selectReactionsTemplate(state);
		const reactionLength = selectTotalReactionsCountByEntity(state, taskId, EntityType.TASK);
		const isOnlyMyReaction = selectIsOnlyMyReaction(state, taskId, EntityType.TASK, userId);
		const voteSignToken = selectVoteSignTokenByEntityId(state, EntityType.TASK, taskId);
		const hasVoteSignToken = selectIsVoteSignTokenLoaded(state, EntityType.TASK, taskId);

		return {
			reactions,
			users,
			template,
			reactionLength,
			isOnlyMyReaction,
			voteSignToken,
			hasVoteSignToken,
		};
	};

	module.exports = {
		LikesPanel: connect(mapStateToProps)(LikesPanel),
	};
});
