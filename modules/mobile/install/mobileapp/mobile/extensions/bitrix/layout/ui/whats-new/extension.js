/**
 * @module layout/ui/whats-new
 */
jn.define('layout/ui/whats-new', (require, exports, module) => {
	const { SimpleList } = require('layout/ui/simple-list');
	const { ListItemsFactory, ListItemType } = require('layout/ui/whats-new/items');
	const { SkeletonFactory } = require('layout/ui/simple-list/skeleton');
	const { WhatsNewItemSkeleton } = require('layout/ui/whats-new/items/skeleton');
	const {
		fetchWhatsNewThunk,
		fetchUrlParamsThunk,
		selectUrlParams,
		updateReadNewsThunk,
		setHasReadNews,
		selectArticlesWithMinimalParams,
		selectIsIdleStatus,
		selectIsLoadingStatus,
		selectIsSuccessStatus,
		selectIsFailedStatus,
		selectIsLast,
		selectHasReadNews,
		selectNewCheckTime,
		selectHasUnsupportedFeatures,
		updateLocalWhatsNewsParamsThunk,
		selectErrorStatus,
	} = require('statemanager/redux/slices/whats-new');
	const { connect } = require('statemanager/redux/connect');
	const { Indent, Color } = require('tokens');
	const { H3 } = require('ui-system/typography/heading');
	const { Loc } = require('loc');
	const { ReactionIcon, ReactionIconView, REACTION_ICON_TYPE } = require('ui-system/blocks/reaction/icon');
	const { debounce } = require('utils/function');
	const { Type } = require('type');
	const { ITEM_LOAD_LIMIT, HEADER_HEIGHT } = require('layout/ui/whats-new/meta');
	const { DialogFooter } = require('ui-system/layout/dialog-footer');
	const { ButtonSize, ButtonDesign, Button } = require('ui-system/form/buttons/button');
	const { openStore } = require('utils/store');
	const { WhatsNewAnalytics } = require('layout/ui/whats-new/analytics');
	const { isEmpty } = require('utils/object');

	SkeletonFactory.register(ListItemType.WhatsNewItem, WhatsNewItemSkeleton);
	const MAX_ANIMATION_RETRIES = 5;
	const ANIMATE_ITEM_DELAY = 50;
	const RETRY_ANIMATION_DELAY = 300;

	/**
	 * @class WhatsNewList
	 * @typedef {Object} WhatsNewProps
	 * @property {string} layout
	 * Redux properties
	 * @property {Array} articles
	 * @property {boolean} isIdle
	 * @property {boolean} isLoading
	 * @property {boolean} isSucceeded
	 * @property {boolean} isFailed
	 * @property {boolean} isLast
	 * @property {boolean} hasReadNews
	 * @property {boolean} hasUnsupportedFeatures
	 * Redux actions
	 * @property {function} fetchWhatsNewThunk
	 * @property {function} updateReadNewsThunk
	 * @property {function} setHasReadNews
	 *
	 */
	class WhatsNewList extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.prepareState(props);

			this.blockPage = null;

			this.bindRef = this.bindRef.bind(this);
			this.loadItemsHandler = this.loadItemsHandler.bind(this);
			this.getItemCustomStyles = this.getItemCustomStyles.bind(this);

			this.listReadNewsIds = [];
			this.listMarkReadNewsIds = [];
			this.onItemsViewable = this.onItemsViewable.bind(this);
			this.sendReadNewsDebounced = debounce(this.sendReadNews, 500, this);

			this.initializeHeaderAnimationParams();
			this.renderFooter = this.renderFooter.bind(this);
			this.pendingAnimations = {}; // { [itemId]: remainingAttempts }
			this.animatingNow = new Set();
		}

		initializeHeaderAnimationParams()
		{
			// eslint-disable-next-line no-undef
			this.scrollOffset = Animated.newCalculatedValue2D(0, 0);

			this.headerTop = this.scrollOffset.getValue2().interpolate({
				inputRange: [-1, 0, HEADER_HEIGHT],
				outputRange: [0, 0, -HEADER_HEIGHT],
			});
		}

		get layout()
		{
			return this.props.layout || PageManager;
		}

		componentDidMount()
		{
			if (this.shouldInitialLoad())
			{
				// eslint-disable-next-line promise/catch-or-return
				this.loadInitialData()
					.then(() => {
						this.props.updateLocalWhatsNewsParamsThunk({});
					});
			}
		}

		shouldInitialLoad()
		{
			const { isIdle, hasReadNews, errorStatus } = this.props;

			return (
				isIdle
				|| hasReadNews
				|| (Number.isInteger(errorStatus) && errorStatus === 0)
			);
		}

		async loadInitialData()
		{
			if (isEmpty(this.props.urlParams))
			{
				try
				{
					await this.props.fetchUrlParamsThunk().unwrap();
				}
				catch (error)
				{
					console.error('Failed to fetch URL params:', error);

					return;
				}
			}

			await this.loadItemsHandler(0, false);
		}

		componentWillUnmount()
		{
			super.componentWillUnmount();

			if (this.listMarkReadNewsIds.length > 0)
			{
				this.props.setHasReadNews();
			}
		}

		componentWillReceiveProps(props)
		{
			this.prepareState(props);
		}

		prepareState(props)
		{
			const { articles, isFailed, isLoading, isLast, hasUnreadNews, errorStatus } = props;

			if (isFailed)
			{
				this.state.articles = [{
					id: ListItemType.ErrorItem,
					key: ListItemType.ErrorItem,
					type: ListItemType.ErrorItem,
				}];

				this.state.allItemsLoaded = Number.isInteger(errorStatus) && errorStatus !== 0;
				this.state.isRefreshing = false;
			}
			else
			{
				const articlesItems = articles.length > 0
					? this.addFinishNoveltyElementToArticles(articles, hasUnreadNews)
					: this.getEmptyArticleState(props);

				const preparedArticles = this.prepareArticlesData(articlesItems);

				this.state = {
					articles: preparedArticles,
					allItemsLoaded: !isLoading
						&& articles.length > 0 && (articles.length < ITEM_LOAD_LIMIT || isLast),
					forcedShowSkeleton: articles.length === 0,
				};
			}
		}

		bindRef(ref)
		{
			if (ref)
			{
				this.ref = ref;
			}
		}

		/**
		 * @param {number} blockPage
		 * @param {boolean} append
		 * @return {Promise<void>}
		 */
		loadItemsHandler(blockPage, append)
		{
			return new Promise((resolve, reject) => {
				if (this.blockPage === blockPage || this.state.allItemsLoaded)
				{
					resolve();

					return;
				}

				this.blockPage = blockPage;

				this.props.fetchWhatsNewThunk({
					blockPage,
				})
					.then(() => resolve())
					.catch((error) => {
						console.error(error);

						reject(error);
					});
			});
		}

		prepareArticlesData(articles)
		{
			return articles.map((item) => ({
				...item,
				type: item?.isUnsupportedFeature ? `${item.type}_update` : item.type,
				key: String(item.id),
			}));
		}

		/**
		 * @param {array} articles
		 * @param {boolean} hasUnreadNews
		 * @return {array}
		 */
		addFinishNoveltyElementToArticles(articles, hasUnreadNews)
		{
			if (!hasUnreadNews)
			{
				return articles;
			}

			const hasNewArticles = articles.some((article) => article.isNew);

			if (!hasNewArticles)
			{
				return articles;
			}

			const finishNoveltyElement = {
				id: ListItemType.FinishNoveltyItem,
				type: ListItemType.FinishNoveltyItem,
			};
			const index = articles.findIndex((article) => !article.isNew);

			return index === -1
				? [...articles, finishNoveltyElement]
				: [
					...articles.slice(0, index),
					finishNoveltyElement,
					...articles.slice(index),
				];
		}

		getEmptyArticleState(props)
		{
			if (props.isSucceeded)
			{
				return [
					{
						id: ListItemType.EmptyItem,
						type: ListItemType.EmptyItem,
					},
				];
			}

			return [];
		}

		render()
		{
			return View(
				{
					style: {
						flex: 1,
						backgroundColor: (this.props.articles.length === 0)
							? Color.accentSoftBlue3.toHex()
							: Color.bgContentPrimary.toHex(),
					},
					safeArea: {
						bottom: this.props.hasUnsupportedFeatures,
					},
				},
				new SimpleList({
					ref: this.bindRef,
					testId: 'whats-new-list',
					layout: this.layout,
					items: this.state.articles,
					loadItemsHandler: this.loadItemsHandler,
					showFloatingButton: this.props.hasUnsupportedFeatures,
					itemType: ListItemType.WhatsNewItem,
					itemFactory: ListItemsFactory,
					itemParams: {},
					onItemsViewable: this.onItemsViewable,
					itemsLoadLimit: ITEM_LOAD_LIMIT,
					showAirStyle: true,

					allItemsLoaded: this.state.allItemsLoaded,
					isRefreshing: this.props.isLoading && this.props.articles.length === 0,
					forcedShowSkeleton: this.state.forcedShowSkeleton,
					viewabilityConfig: {
						waitForInteraction: false,
					},
					styles: {
						wrapper: {
							flexDirection: 'column',
							flex: 1,
						},
						container: {
							flexDirection: 'column',
							justifyContent: 'center',
							alignItems: 'center',
							flex: 1,
						},
						reloadNotificationWrapper: {
							width: '100%',
							position: 'absolute',
							top: -50,
							flexDirection: 'row',
							justifyContent: 'center',
						},
						reloadNotification: {},
						textNotification: {},
					},
					showEmptySpaceItem: true,
					getItemCustomStyles: this.getItemCustomStyles,
					onScrollCalculated: {
						contentOffsetWithoutOverscroll: this.scrollOffset,
					},
					onRefresh: null,
					renderFloatingButton: this.renderFooter,
				}),
				this.renderHeader(),
			);
		}

		renderFooter()
		{
			return DialogFooter(
				{
					safeArea: false,
					keyboardButton: {
						text: Loc.getMessage('WHATS_NEW_LIST_UPDATE'),
						onClick: this.openStore,
						testId: 'category-detail-save-button',
						disabled: this.state.error,
					},
					style: {
						borderTopWidth: 1,
						borderTopColor: Color.bgSeparatorPrimary.toHex(),
					},
				},
				Button({
					design: ButtonDesign.FILLED,
					size: ButtonSize.L,
					text: Loc.getMessage('WHATS_NEW_LIST_UPDATE'),
					stretched: true,
					onClick: this.openStore,
					testId: 'whats-new-list-update-button',
					disabled: this.state.error,
				}),
			);
		}

		openStore = () => {
			openStore();

			new WhatsNewAnalytics()
				.setEvent(WhatsNewAnalytics.Event.clickStore)
				.send();
		};

		getItemCustomStyles(item, section, row)
		{
			if (item.key === 'EmptySpace_top' || item.key === 'EmptySpace_bottom')
			{
				return {
					minHeight: HEADER_HEIGHT,
					backgroundColor: Color.accentSoftBlue3.toHex(),
				};
			}

			return {};
		}

		renderHeader()
		{
			return View(
				{
					style: {
						height: HEADER_HEIGHT,
						position: 'absolute',
						top: this.headerTop,
						width: '100%',
						justifyContent: 'flex-end',
					},
				},
				View(
					{
						style: {
							justifyContent: 'center',
							flexDirection: 'row',
							alignItems: 'center',
							paddingVertical: Indent.L.toNumber(),
						},
					},
					ReactionIconView({
						testId: `${this.testId}-reaction-header-icon`,
						icon: ReactionIcon.WHATS_NEW_FIRE,
						size: 24,
						type: REACTION_ICON_TYPE.png,
					}),
					H3({
						style: {
							marginHorizontal: Indent.S.toNumber(),
						},
						testId: `${this.testId}_title`,
						text: Loc.getMessage('WHATS_NEW_LIST_TITLE'),
					}),
				),
			);
		}

		/**
		 * @param {Array} items
		 */
		onItemsViewable(items)
		{
			const newsIds = items
				.filter((item) => Type.isNumber(item?.id) && item?.isNew)
				.map((item) => item.id);

			this.listReadNewsIds.push(...newsIds);
			this.markNewsAsRead();
		}

		markNewsAsRead()
		{
			const correctList = [];

			this.listReadNewsIds.forEach((itemId) => {
				if (!this.listMarkReadNewsIds.includes(itemId))
				{
					setTimeout(() => {
						this.animateItem(itemId);
					}, ANIMATE_ITEM_DELAY);

					correctList.push(itemId);
					this.listMarkReadNewsIds.push(itemId);
				}
			});

			if (correctList.length > 0)
			{
				this.sendReadNewsDebounced(correctList);
			}

			setTimeout(() => this.retryPendingAnimations(), RETRY_ANIMATION_DELAY);
		}

		animateItem(itemId)
		{
			if (this.animatingNow.has(itemId))
			{
				return;
			}

			const itemWrapperRef = this?.ref.getItemComponent(itemId);

			if (itemWrapperRef?.reduxComponentRef?.animateReading)
			{
				this.runItemAnimation(itemId, itemWrapperRef.reduxComponentRef);
			}
			else
			{
				this.pendingAnimations[itemId] = (this.pendingAnimations[itemId] || MAX_ANIMATION_RETRIES) - 1;
			}
		}

		retryPendingAnimations()
		{
			const newPending = {};

			Object.entries(this.pendingAnimations).forEach(([itemIdStr, attemptsLeft]) => {
				const itemId = Number(itemIdStr);

				if (this.animatingNow.has(itemId))
				{
					newPending[itemId] = attemptsLeft;

					return;
				}

				const itemWrapperRef = this?.ref.getItemComponent(itemId);

				if (itemWrapperRef?.reduxComponentRef?.animateReading)
				{
					this.runItemAnimation(itemId, itemWrapperRef.reduxComponentRef);
				}
				else if (attemptsLeft > 1)
				{
					newPending[itemId] = attemptsLeft - 1;
				}
				else
				{
					console.warn(`Failed to animate item ${itemId} after ${MAX_ANIMATION_RETRIES} attempts`);
				}
			});

			this.pendingAnimations = newPending;
		}

		runItemAnimation(itemId, reduxComponentRef)
		{
			this.animatingNow.add(itemId);

			reduxComponentRef.animateReading()
				.then(() => {
					this.animatingNow.delete(itemId);
					delete this.pendingAnimations[itemId];
				})
				.catch((error) => {
					console.warn(`Animation failed for item ${itemId}:`, error);
					this.animatingNow.delete(itemId);
				});
		}

		sendReadNews(newsIds)
		{
			this.props.updateReadNewsThunk({
				newsIds,
				eventService: this.props.eventService,
			})
				.then(() => {
					this.props.updateLocalWhatsNewsParamsThunk({});
				})
				.catch((error) => {
					console.error(error);
				});
		}
	}

	const mapStateToProps = (state, { layout, hasUnreadNews }) => {
		const articles = selectArticlesWithMinimalParams(state);
		const articlesIds = articles.map((article) => article.id);

		return {
			layout,
			hasUnreadNews,
			articles: selectArticlesWithMinimalParams(state),
			isIdle: selectIsIdleStatus(state),
			isLoading: selectIsLoadingStatus(state),
			isSucceeded: selectIsSuccessStatus(state),
			isFailed: selectIsFailedStatus(state),
			isLast: selectIsLast(state),
			hasReadNews: selectHasReadNews(state),
			newCheckTime: selectNewCheckTime(state),
			hasUnsupportedFeatures: selectHasUnsupportedFeatures(state, articlesIds),
			errorStatus: selectErrorStatus(state),
			urlParams: selectUrlParams(state),
		};
	};

	const mapDispatchToProps = {
		fetchWhatsNewThunk,
		fetchUrlParamsThunk,
		updateReadNewsThunk,
		setHasReadNews,
		updateLocalWhatsNewsParamsThunk,
	};

	module.exports = {
		WhatsNewList: connect(mapStateToProps, mapDispatchToProps)(WhatsNewList),
	};
});
