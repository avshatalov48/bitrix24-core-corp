/**
 * @module layout/ui/whats-new/items/whats-new/redux-content
 */
jn.define('layout/ui/whats-new/items/whats-new/redux-content', (require, exports, module) => {
	const { connect } = require('statemanager/redux/connect');
	const { PureComponent } = require('layout/pure-component');

	const { Color, Indent, Corner } = require('tokens');
	const { Card, CardDesign } = require('ui-system/layout/card');
	const { H5 } = require('ui-system/typography/heading');
	const { Text7 } = require('ui-system/typography/text');

	const { ChipStatus, ChipStatusDesign, ChipStatusMode } = require('ui-system/blocks/chips/chip-status');
	const { CollapsibleText } = require('layout/ui/collapsible-text');
	const { Moment } = require('utils/date/moment');
	const { TimeAgoFormat } = require('layout/ui/friendly-date/time-ago-format');
	const { DynamicDateFormatter } = require('utils/date/dynamic-date-formatter');
	const { WhatsNewReaction } = require('layout/ui/whats-new/items/whats-new/reaction');
	const { ChipButton, ChipButtonMode, ChipButtonDesign } = require('ui-system/blocks/chips/chip-button');

	const {
		selectById,
		selectCategories,
		selectAllCategoriesTitle,
		REACTIONS_NAMES,
	} = require('statemanager/redux/slices/whats-new');
	const { ACTION_TYPE, BOTTOM_DESCRIPTION_TYPE } = require('statemanager/redux/slices/whats-new/meta');
	const { Loc } = require('loc');
	const { ShimmedSafeImage } = require('layout/ui/safe-image');
	const { chain, transition } = require('animation');
	const { ShimmerView } = require('layout/polyfill');
	const { WhatsNewAnalytics } = require('layout/ui/whats-new/analytics');

	/**
	 * @class WhatsNewItemContent
	 * @typedef {Object} WhatsNewItemContentProps
	 * @property {string} [testId]
	 * @property {Object} item
	 * @property {number} item.id
	 * @property {string} [item.name]
	 * @property {string} [item.description]
	 * @property {string} [item.bottomDescription]
	 * @property {Object} [item.action]
	 * @property {string} [item.auth]
	 * @property {string} [item.categoryCode]
	 * @property {Object} [item.reactions]
	 * @property {number} [item.time]

	 * @property {Array} [categories]
	 */
	class WhatsNewItemContent extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.bindCardRef = this.bindCardRef.bind(this);
		}

		get testId()
		{
			return `${this.props.testId}-${this.props.item.id}`;
		}

		get categories()
		{
			return this.props.categories ?? [];
		}

		get item()
		{
			return this.props.item ?? {};
		}

		animateReading()
		{
			const { isNew, isRead } = this.item;

			if (isNew && !isRead && this.cardRef)
			{
				const transitionToNewState = transition(this.cardRef, {
					duration: 0,
					backgroundColor: Color.accentSoftOrange2.toHex(),
				});

				const transitionToDefaultState = transition(this.cardRef, {
					duration: 1000,
					backgroundColor: Color.bgContentPrimary.toHex(),
				});

				const animate = chain(
					transitionToNewState,
					transitionToDefaultState,
				);

				return animate();
			}

			return Promise.resolve();
		}

		bindCardRef(ref)
		{
			this.cardRef = ref;
			this.props.bindReduxComponentRef?.(this);
		}

		render()
		{
			const { name, description } = this.item;

			return View(
				{
					style: {
						backgroundColor: Color.accentSoftBlue3.toHex(),
					},
				},
				Card(
					{
						ref: this.bindCardRef,
						testId: this.testId,
						hideCross: true,
						design: CardDesign.PRIMARY,
						style: {
							marginTop: Indent.M.toNumber(),
							marginBottom: Indent.XS.toNumber(),
							marginHorizontal: Indent.XL3.toNumber(),

							borderWidth: 1,
							borderColor: Color.accentSoftBlue1.toHex(),
						},
					},
					View(
						{
							style: {
								flexDirection: 'column',
							},
						},
						View(
							{
								style: {
									flexDirection: 'row',
									justifyContent: 'space-between',
									marginBottom: Indent.XL.toNumber(),
								},
							},
							this.renderCategory(),
							this.renderTime(),
						),
						name && H5({
							testId: `${this.testId}-title`,
							text: name,
							color: Color.base1,
							style: {
								marginBottom: Indent.XS.toNumber(),
							},
							numberOfLines: 2,
							ellipsize: 'end',
						}),
						description && new CollapsibleText({
							testId: `${this.testId}-description`,
							value: description,
							canExpand: true,
							style: {
								marginBottom: Indent.XL3.toNumber(),
							},
							maxLettersCount: 110,
							maxNewLineCount: 3,
							ellipsize: 'end',
							onAfterExpand: this.onAfterExpandDescription,
						}),
						this.renderContentBlock(),
						this.renderFooter(),
						this.renderUpdateBlock(),
					),
				),
			);
		}

		/**
		 * @param {boolean} expanded
		 */
		onAfterExpandDescription = (expanded) => {
			const { id } = this.item;

			if (expanded)
			{
				new WhatsNewAnalytics()
					.setEvent(WhatsNewAnalytics.Event.block_go_read)
					.setP4(WhatsNewAnalytics.getWhatsNewItemId(id))
					.send();
			}
		};

		renderCategory()
		{
			const { categoryCode } = this.item;

			const itemCategory = this.categories.find((category) => category.code === categoryCode);

			if (!itemCategory)
			{
				return null;
			}

			return ChipStatus({
				testId: `${this.testId}-category`,
				text: itemCategory.name,
				mode: ChipStatusMode.TINTED,
				design: ChipStatusDesign.PRIMARY,
				compact: true,
			});
		}

		renderTime()
		{
			const { time } = this.item;

			if (!time)
			{
				return null;
			}

			return Text7({
				text: this.getFormattedDate(time),
				color: Color.base4,
				testId: `${this.testId}-time`,
			});
		}

		/**
		 * @param {number} time
		 * @return {String|null}
		 */
		getFormattedDate(time)
		{
			const timeAgoTextBuilder = new TimeAgoFormat();

			const formatter = new DynamicDateFormatter({
				config: {
					past: {
						[DynamicDateFormatter.deltas.HOUR]: (m) => timeAgoTextBuilder.formatMinutes(m),
						[DynamicDateFormatter.deltas.DAY]: (m) => timeAgoTextBuilder.formatHours(m),
						[DynamicDateFormatter.deltas.WEEK]: (m) => timeAgoTextBuilder.formatDays(m),
						[DynamicDateFormatter.deltas.MONTH]: (m) => timeAgoTextBuilder.formatDays(m),
						[DynamicDateFormatter.deltas.YEAR]: (m) => timeAgoTextBuilder.formatMonths(m),
					},
				},
			});

			return formatter.format(new Moment(time * 1000));
		}

		renderContentBlock()
		{
			const { bottomDescriptionType, bottomDescription } = this.item;

			return View(
				{
					style: {
						width: '100%',
						height: 196,
						alignSelf: 'center',
						borderRadius: Corner.L.toNumber(),
						marginBottom: Indent.XL3.toNumber(),
						borderWidth: 1,
						borderColor: Color.base7.toHex(),
					},
				},
				this.getContentByType(bottomDescriptionType, bottomDescription),
			);
		}

		getContentByType(type, uri)
		{
			if (!uri)
			{
				return ShimmerView(
					{ animating: true },
					View({
						style: {
							width: '100%',
							height: 196,
							backgroundColor: Color.base7.toHex(),
						},
					}),
				);
			}

			switch (type)
			{
				case BOTTOM_DESCRIPTION_TYPE.lottie:
					return LottieView(
						{
							testId: `${this.testId}-bottom-description-${type}`,
							style: {
								width: '100%',
								height: 196,
							},
							data: {
								uri,
							},
							params: {
								loopMode: 'loop',
							},
							autoPlay: true,
						},
					);
				case BOTTOM_DESCRIPTION_TYPE.image:
					return ShimmedSafeImage(
						{
							testId: `${this.testId}-bottom-description-${type}`,
							style: {
								width: '100%',
								height: 196,
							},
							resizeMode: 'cover',
							uri,
						},
					);
				case BOTTOM_DESCRIPTION_TYPE.video:
					return Video({
						testId: `${this.testId}-bottom-description-${type}`,
						style: {
							width: '100%',
							height: 196,
						},
						uri,
						mute: true,
						enableControls: true,
						loop: true,
					});
				default:
					return ShimmerView(
						{ animating: true },
						View({
							style: {
								width: '100%',
								height: 196,
								backgroundColor: Color.base7.toHex(),
							},
						}),
					);
			}
		}

		renderFooter()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'space-between',
						alignItems: 'center',
					},
				},
				this.renderReactions(),
				this.renderActionButton(),
			);
		}

		renderUpdateBlock()
		{
			const { isUnsupportedFeature } = this.item;
			if (isUnsupportedFeature)
			{
				return View(
					{
						style: {
							marginVertical: Indent.XL3.toNumber(),
						},
					},
					Text7({
						text: Loc.getMessage('WHATS_NEW_ITEM_UPDATE_TEXT'),
						color: Color.base2,
						style: {
							textAlign: 'center',
						},
					}),
				);
			}

			return null;
		}

		renderReactions()
		{
			const { id, auth } = this.item;

			const reactions = [REACTIONS_NAMES.FIRE, REACTIONS_NAMES.LIKE, REACTIONS_NAMES.DISLIKE];

			return View(
				{
					style: {
						flexDirection: 'row',
						marginRight: Indent.XL2.toNumber(),
					},
				},
				...reactions.map((name, index) => WhatsNewReaction({
					testId: `${this.testId}-reaction-${name}`,
					reactionName: name,
					isLast: index === reactions.length - 1,
					itemId: id,
					auth,
				})),
			);
		}

		renderActionButton()
		{
			const { actionType, actionName } = this.item;

			if (!actionName)
			{
				return null;
			}

			if (actionType === ACTION_TYPE.HELPDESK_LINK)
			{
				return ChipButton({
					compact: true,
					mode: ChipButtonMode.OUTLINE,
					design: ChipButtonDesign.PRIMARY,
					testId: `${this.testId}-helpdesk-button`,
					text: actionName,
					onClick: this.openHelpdeskArticle,
				});
			}

			if (actionType === ACTION_TYPE.DEMO_LINK)
			{
				return ChipButton({
					compact: true,
					testId: `${this.testId}-button`,
					text: actionName,
					mode: ChipButtonMode.SOLID,
					design: ChipButtonDesign.PRIMARY,
					onClick: this.openLink,
				});
			}

			return ChipButton({
				compact: true,
				testId: `${this.testId}-button`,
				text: actionName,
				mode: ChipButtonMode.SOLID,
				design: ChipButtonDesign.PRIMARY,
			});
		}

		openHelpdeskArticle = () => {
			const { actionArticleId, id } = this.item;

			if (actionArticleId)
			{
				helpdesk.openHelpArticle(actionArticleId, null);

				new WhatsNewAnalytics()
					.setEvent(WhatsNewAnalytics.Event.openHelpdesk)
					.setP4(WhatsNewAnalytics.getWhatsNewItemId(id))
					.send();
			}
		};

		openLink = () => {
			// todo: callback for action button with type link

			const { id } = this.item;

			new WhatsNewAnalytics()
				.setEvent(WhatsNewAnalytics.Event.transitionToNovelty)
				.setP4(WhatsNewAnalytics.getWhatsNewItemId(id))
				.send();
		};
	}

	const mapStateToProps = (state, ownProps) => {
		const item = selectById(state, ownProps.id);

		return {
			testId: ownProps.testId,
			bindReduxComponentRef: ownProps.bindReduxComponentRef,
			item: {
				id: item.id,
				bottomDescription: item.bottomDescription,
				bottomDescriptionType: item.bottomDescriptionType,
				name: item.name,
				description: item.description,
				actionType: item.actionType,
				actionName: item.actionName,
				actionArticleId: item.actionArticleId,
				auth: item.auth,
				categoryCode: item.categoryCode,
				time: item.time,
				isNew: item.isNew,
				isRead: item.isRead,
				isUnsupportedFeature: item.isUnsupportedFeature,
			},
			categories: selectCategories(state),
			allCategoriesTitle: selectAllCategoriesTitle(state),
		};
	};

	module.exports = {
		WhatsNewItemContent: connect(mapStateToProps)(WhatsNewItemContent),
	};
});
