/**
 * @module crm/timeline
 */
jn.define('crm/timeline', (require, exports, module) => {
	const { qrauth } = require('qrauth/utils');
	const { FadeView } = require('animation/components/fade-view');
	const { StickyDate } = require('crm/timeline/ui/sticky-date');
	const { Divider } = require('layout/ui/timeline/components/divider');
	const { DateDivider } = require('crm/timeline/ui/date-divider');
	const { CreateReminder } = require('crm/timeline/ui/reminder');
	const { Banner, BannerStack } = require('crm/timeline/ui/banner');
	const { TimelinePushProcessor } = require('crm/timeline/services/push-processor');
	const { TimelineDataProvider } = require('crm/timeline/services/data-provider');
	const { dispatch } = require('statemanager/redux/store');
	const { usersUpserted } = require('statemanager/redux/slices/users');
	const {
		TimelineStreamPinned,
		TimelineStreamHistory,
		TimelineStreamScheduled,
	} = require('crm/timeline/stream');
	const { ItemPositionCalculator } = require('crm/timeline/stream/utils');
	const { TimelineScheduler } = require('crm/timeline/scheduler');
	const { TimelineAction } = require('crm/timeline/action');
	const { get } = require('utils/object');
	const { Moment } = require('utils/date');
	const { EventEmitter } = require('event-emitter');
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { Type } = require('crm/type');
	const { Random } = require('utils/random');

	/**
	 * @class Timeline
	 */
	class Timeline extends LayoutComponent
	{
		static getFloatingMenuItems(context)
		{
			return TimelineScheduler.getFloatingMenuItems(context);
		}

		constructor(props)
		{
			super(props);

			this.uid = props.uid || Random.getString();
			this.timelineScopeEventBus = EventEmitter.createWithUid(this.uid);
			this.pushProcessor = new TimelinePushProcessor({
				timeline: this,
			});

			const { entity, user } = this.props;
			this.timelineScheduler = new TimelineScheduler({
				entity,
				user,
			});
			this.dataProvider = new TimelineDataProvider({
				entity,
			});

			this.initStreams();

			this.state = this.buildState(props);

			this.listViewRef = null;

			this.openDesktopEntityPage = this.openDesktopEntityPage.bind(this);
		}

		initStreams()
		{
			const commonSettings = {
				items: [],
				timelineScopeEventBus: this.timelineScopeEventBus,
				isEditable: this.isEditable,
				onItemAction: (params) => this.onItemAction(params),
				entityType: Type.resolveNameById(this.props.entity.typeId)?.toLowerCase(),
			};

			this.pinnedStream = new TimelineStreamPinned(commonSettings);
			this.scheduledStream = new TimelineStreamScheduled(commonSettings);
			this.historyStream = new TimelineStreamHistory(commonSettings);

			const itemPositionCalculator = new ItemPositionCalculator(this.streams);
			this.streams.map((stream) => stream.setItemPositionCalculator(itemPositionCalculator));
		}

		buildState(props)
		{
			this.pinnedStream.setItems(get(props, 'pinned', []));
			this.scheduledStream.setItems(get(props, 'scheduled', []));
			this.historyStream.setItems(get(props, 'history.items', []));

			dispatch(usersUpserted(props.users));

			return {
				refreshing: false,
			};
		}

		componentWillReceiveProps(props)
		{
			this.state = this.buildState(props);
		}

		componentDidMount()
		{
			this.timelineScopeEventBus.on('Crm.Timeline::onFeatureNotSupported', this.openDesktopEntityPage);
		}

		componentWillUnmount()
		{
			this.timelineScopeEventBus.off('Crm.Timeline::onFeatureNotSupported', this.openDesktopEntityPage);
		}

		/**
		 * @return {boolean}
		 */
		get isEditable()
		{
			return get(this.props, 'entity.isEditable', false);
		}

		/**
		 * @return {TimelineStreamBase[]}
		 */
		get streams()
		{
			return [
				this.pinnedStream,
				this.scheduledStream,
				this.historyStream,
			];
		}

		/**
		 * @public
		 * @param {object} message
		 */
		processPushMessage(message)
		{
			this.pushProcessor.handleMessage(message);
		}

		render()
		{
			return View(
				{
					style: Styles.fullscreenContainer,
				},
				new FadeView({
					visible: false,
					fadeInOnMount: true,
					notVisibleOpacity: 0.5,
					style: {
						flexGrow: 1,
					},
					slot: () => View(
						{
							style: Styles.fullscreenContainer,
						},
						this.renderItems(),
						new StickyDate({
							// ref: (ref) => StickyDate.useRef(ref),
						}),
					),
				}),
			);
		}

		renderItems()
		{
			const items = this.streams.flatMap((stream) => stream.exportToListView());
			items.unshift({
				type: 'PaddingTop',
				key: 'padding-top',
				props: { value: 16 },
			});
			items.push({
				type: 'PaddingBottom',
				key: 'padding-bottom',
				props: { value: 75 },
			});

			items.map((item, index) => item.index = index);

			return ListView({
				ref: this.registerListViewRef.bind(this),
				style: Styles.listView,
				data: [{ items }],
				isRefreshing: this.state.refreshing,
				onRefresh: () => this.refresh(),
				onScroll: (scrollParams) => {
					const { contentOffset } = scrollParams;
					StickyDate.onScroll(contentOffset.y);
					if (this.props.onScroll)
					{
						this.props.onScroll(scrollParams);
					}
				},
				renderItem: (props) => {
					return View(
						{
							style: {
								backgroundColor: AppTheme.colors.bgPrimary,
							},
						},
						this.renderItemContent(props),
					);
				},
			});
		}

		registerListViewRef(ref)
		{
			if (ref)
			{
				this.listViewRef = ref;
				this.streams.map((stream) => stream.registerListViewRef(ref));
			}
		}

		renderItemContent({ type, props, index })
		{
			// eslint-disable-next-line default-case
			switch (type)
			{
				case 'Divider':
					return Divider(props);
				case 'PaddingTop':
				case 'PaddingBottom':
					return View({
						style: { height: Number(props.value) },
					});
				case 'DateDivider':
					const moment = new Moment(props.date);

					return DateDivider({
						moment,
						onLayout: ({ y }) => StickyDate.registerBreakpoint(y, moment),
					});
				case 'CreateReminder':
					return CreateReminder({
						entityTypeId: this.props.entity.typeId,
						style: {
							marginBottom: 16,
						},
						onClick: () => this.timelineScheduler.openActivityEditor(),
					});
				case 'Banner':
					return Banner(props);
				case 'StickyDateBegin':
					return View(
						{
							style: Styles.stickyDatePixel,
							onLayout: ({ y }) => StickyDate.registerInitialOffset(y),
						},
					);
				case 'RecordNotSupported':
					return Banner({
						...props,
						onClick: () => this.openDesktopEntityPage(),
					});
				case 'RecordsNotSupported':
					return BannerStack({
						...props,
						onClick: () => this.openDesktopEntityPage(),
					});
			}

			if (type.startsWith('TimelineItem:pinned'))
			{
				return this.pinnedStream.renderItem(props.id, index);
			}

			if (type.startsWith('TimelineItem:scheduled'))
			{
				return this.scheduledStream.renderItem(props.id, index);
			}

			if (type.startsWith('TimelineItem:history'))
			{
				return this.historyStream.renderItem(props.id, index);
			}

			return null;
		}

		scrollToTheTop(animate = true)
		{
			if (this.listViewRef)
			{
				this.listViewRef.scrollToBegin(animate);
			}
		}

		refresh()
		{
			this.timelineScopeEventBus.emit('Crm.Timeline::onTimelineRefresh', []);
			this.setState({ refreshing: true }, () => {
				this.dataProvider.loadTimeline()
					.then((data) => {
						this.setState(this.buildState(data), () => this.emitTabCounterChange());
					})
					.catch(() => this.setState({ refreshing: false }));
			});
		}

		/**
		 * @public
		 */
		emitTabCounterChange()
		{
			if (this.scheduledStream)
			{
				this.timelineScopeEventBus.emit('DetailCard::onTabCounterChange', [
					'timeline',
					this.scheduledStream.getAttentionableItems().length,
				]);

				this.timelineScopeEventBus.emit('Crm.Timeline::onCounterChange', [
					{
						needsAttention: this.scheduledStream.getNeedsAttentionItems().length,
						incomingChannel: this.scheduledStream.getIncomingChannelItems().length,
						total: this.scheduledStream.getItems().length,
					},
				]);
			}
		}

		openDesktopEntityPage()
		{
			qrauth.open({
				title: Loc.getMessage('CRM_TIMELINE_DESKTOP_VERSION'),
				redirectUrl: this.props.entity.detailPageUrl,
				analyticsSection: 'crm',
			});
		}

		onItemAction(params = {})
		{
			TimelineAction.execute({
				...params,
				entity: this.props.entity,
				scheduler: this.timelineScheduler,
			});
		}

		handleFloatingMenuAction(providerId)
		{
			this.timelineScheduler.openEditorByProviderId(providerId, { detailCard: this.props.detailCard });
		}
	}

	const Styles = {
		fullscreenContainer: {
			flexDirection: 'column',
			flexGrow: 1,
			backgroundColor: AppTheme.colors.bgPrimary,
		},
		listView: {
			backgroundColor: AppTheme.colors.bgPrimary,
			flexDirection: 'column',
			flexGrow: 1,
			position: 'absolute',
			width: '100%',
			height: '100%',
			top: -1,
		},
		stickyDatePixel: {
			width: '100%',
			height: 1,
			opacity: 0,
		},
	};

	module.exports = { Timeline };
});
