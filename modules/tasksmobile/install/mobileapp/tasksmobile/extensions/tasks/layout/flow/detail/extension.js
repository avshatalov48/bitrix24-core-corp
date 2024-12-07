/**
 * @module tasks/layout/flow/detail
 */
jn.define('tasks/layout/flow/detail', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { Loc } = require('loc');
	const { Color, Indent, Corner } = require('tokens');
	const { Text3 } = require('ui-system/typography/text');
	const { TasksFlowList, ListType } = require('tasks/layout/flow/list');
	const { FlowDetailCommon } = require('tasks/layout/flow/detail/src/common');

	const store = require('statemanager/redux/store');
	const { selectById } = require('tasks/statemanager/redux/slices/flows');

	const DetailTabType = {
		COMMON: 'common',
		FLOWS: 'flows',
	};

	/**
	 * @class FlowDetail
	 */
	class FlowDetail extends PureComponent
	{
		get layout()
		{
			return this.props.layout;
		}

		constructor(props)
		{
			super(props);
			this.state = {
				selectedTab: DetailTabType.COMMON,
			};
		}

		render()
		{
			return View(
				{
					style: {
						width: '100%',
						backgroundColor: Color.bgSecondary.toHex(),
					},
				},
				this.renderTabsHeader(),
				this.renderTabContent(),
			);
		}

		renderTabContent = () => {
			return this.state.selectedTab === DetailTabType.COMMON
				? this.renderCommonTabContent()
				: this.renderFlowsTabContent();
		};

		renderCommonTabContent = () => {
			return FlowDetailCommon({
				id: this.props.flowId,
				layout: this.layout,
			});
		};

		renderFlowsTabContent = () => {
			const flow = selectById(store.getState(), this.props.flowId);

			return View(
				{
					style: {
						width: '100%',
						flex: 1,
						justifyContent: 'start',
						alignItems: 'start',
					},
				},
				new TasksFlowList({
					currentUserId: Number(env.userId),
					creatorId: Number(flow.creatorId),
					excludedFlowId: Number(flow.id),
					listType: ListType.SIMILAR_FLOW,
					layout: this.layout,
				}),
			);
		};

		get testId()
		{
			return `flow-detail-${this.props.flowId}`;
		}

		renderTabsHeader()
		{
			return View(
				{
					style: {
						width: '100%',
						paddingVertical: Indent.XS.toNumber(),
						paddingHorizontal: Indent.XL3.toNumber(),
						alignItems: 'center',
						justifyContent: 'center',
					},
				},
				View(
					{
						style: {
							backgroundColor: Color.bgContentTertiary.toHex(),
							padding: Indent.XS2.toNumber(),
							flexDirection: 'row',
							width: '100%',
							borderRadius: Corner.S.toNumber(),
						},
					},
					this.renderTabHeader(DetailTabType.COMMON, this.state.selectedTab === DetailTabType.COMMON),
					this.renderTabHeader(DetailTabType.FLOWS, this.state.selectedTab === DetailTabType.FLOWS),
				),
			);
		}

		renderTabHeader = (type, isSelected = false) => {
			const text = type === DetailTabType.COMMON
				? Loc.getMessage('TASKSMOBILE_FLOW_DETAIL_TAB_COMMON_TITLE')
				: Loc.getMessage('TASKSMOBILE_FLOW_DETAIL_TAB_AUTHOR_FLOWS_TITLE');

			return View(
				{
					style: {
						borderRadius: Corner.XS.toNumber(),
						alignItems: 'center',
						justifyContent: 'center',
						padding: Indent.XS.toNumber(),
						width: '50%',
						backgroundColor: isSelected ? Color.bgNavigation.toHex() : null,
						// marginRight: type === DetailTabType.COMMON ? Indent.XS.toNumber() : 0,
					},
					onClick: () => {
						if (!isSelected)
						{
							this.setState({
								selectedTab: type,
							});
						}
					},
				},
				Text3({
					testId: `${this.testId}-tab-header-text`,
					text,
					numberOfLines: 1,
					ellipsize: 'end',
				}),
			);
		};

		/**
		 * @public
		 * @function open
		 * @params {object} params
		 * @params {layout} params.flowId
		 * @params {layout} [params.parentLayout = null]
		 * @params {object} [params.openWidgetConfig = {}]
		 * @return void
		 */
		static open({
			flowId,
			parentLayout = null,
			openWidgetConfig = {},
		})
		{
			const config = {
				enableNavigationBarBorder: false,
				modal: true,
				titleParams: {
					text: Loc.getMessage('TASKSMOBILE_FLOW_DETAIL_TITLE'),
					type: 'entity',
				},
				backdrop: {
					// showOnTop: true,
					onlyMediumPosition: true,
					mediumPositionPercent: 85,
					bounceEnable: false,
					swipeAllowed: true,
					swipeContentAllowed: true,
					horizontalSwipeAllowed: false,
					shouldResizeContent: true,
					adoptHeightByKeyboard: true,
				},
				...openWidgetConfig,
				onReady: (readyLayout) => {
					const detailInstance = new FlowDetail({
						layout: readyLayout,
						flowId,
					});

					readyLayout.showComponent(detailInstance);
				},
			};

			if (parentLayout)
			{
				parentLayout.openWidget('layout', config);

				return;
			}

			PageManager.openWidget('layout', config);
		}
	}

	module.exports = { FlowDetail };
});
