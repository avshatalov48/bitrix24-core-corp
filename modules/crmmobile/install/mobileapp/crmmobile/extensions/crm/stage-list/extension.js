/**
 * @module crm/stage-list
 */
jn.define('crm/stage-list', (require, exports, module) => {

	const { clone } = require('utils/object');
	const { CategoryCountersStoreManager } = require('crm/state-storage');
	const {
		StageListItem,
		TUNNEL_HEIGHT,
		MIN_STAGE_HEIGHT,
		FIRST_TUNNEL_ADDITIONAL_HEIGHT,
	} = require('crm/stage-list/item');

	const ALL_STAGES_ITEM_ID = 0;
	const ALL_STAGES_ITEM_STATUS_ID = '';

	/**
	 * @class StageList
	 */
	class StageList extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.initStageProps(props);

			this.onOpenStageDetail = this.handlerOnOpenStageDetail.bind(this);

			this.state.categoryCounters = CategoryCountersStoreManager.getStages();
		}

		componentWillReceiveProps(props)
		{
			this.initStageProps(props);
		}

		initStageProps(props)
		{
			const { stageParams } = props;

			this.showTunnels = BX.prop.get(stageParams, 'showTunnels', false);
			this.showTotal = BX.prop.get(stageParams, 'showTotal', false);
			this.showCount = BX.prop.get(stageParams, 'showCount', false);
			this.showCounters = BX.prop.get(stageParams, 'showCounters', false);
			this.showAllStagesItem = BX.prop.get(stageParams, 'showAllStagesItem', false);
		}

		prepareStagesData(stages)
		{
			return stages
				.sort(({ sort: sortA }, { sort: sortB }) => sortA - sortB)
				.reduce((acc, stage, index) => ([
					...acc,
					{
						...stage,
						type: `${stage.tunnels.length} ${this.showContentBorder(index, stages.length - 1) ? 'with border' : ''}`,
						key: String(stage.id),
						index: index + 1,
						showContentBorder: this.showContentBorder(index, stages.length - 1),
					},
				]), []);
		}

		showContentBorder(index, lastIndex)
		{
			return index !== lastIndex;
		}

		/**
		 * @returns {{color: string, statusId: string, name: *, id: number}}
		 */
		getAllStagesItem()
		{
			const { categoryCounters } = this.state;

			const allStageItem = categoryCounters.reduce((acc, stage) => {
				acc.count += stage.count;
				acc.total += stage.total;
				return acc;
			}, {
				count: 0,
				total: 0,
			});

			return {
				id: ALL_STAGES_ITEM_ID,
				statusId: ALL_STAGES_ITEM_STATUS_ID,
				color: '#e6ecef',
				name: BX.message('CRM_STAGE_LIST_ALL_STAGES_TITLE'),
				count: allStageItem.count,
				total: allStageItem.total,
				currency: categoryCounters[0].currency,
				tunnels: [],
				showContentBorder: true,
				listMode: true,
			};
		}

		handlerOnOpenStageDetail(stage)
		{
			const { onOpenStageDetail } = this.props;
			if (typeof onOpenStageDetail === 'function')
			{
				onOpenStageDetail(stage);
			}
		}

		moveStage(fromIndex, toIndex)
		{
			const { processStages } = this.props;

			const stage = processStages[fromIndex];
			let list = [...processStages];

			list.splice(fromIndex, 1);
			list.splice(toIndex, 0, stage);

			list = this.updateStagesSort(list);
			this.onStageMove(list);
		}

		updateStagesSort(stages)
		{
			return stages.map((stage, index) => {
				stage = clone(stage);

				stage.sort = index * 10 + 10;
				stage.index = index + 1;

				return stage;
			});
		}

		onStageMove(list)
		{
			const { onStageMove } = this.props;
			if (typeof onStageMove === 'function')
			{
				onStageMove(list);
			}
		}

		renderProcessStageList(stages)
		{
			const { readOnly } = this.props;

			return ListView({
				data: [{ items: stages }],
				renderItem: (stage) => this.renderStageListItem(stage),
				dragInteractionEnabled: !readOnly,
				onItemDrop: (itemMove) => this.moveStage(itemMove.from.index, itemMove.to.index),
				style: {
					height: this.calculateHeight(stages),
					alignItems: 'center',
				},
				isScrollable: false,
			});
		}

		calculateHeight(stages)
		{
			return stages.reduce((height, stage) => {
				height += MIN_STAGE_HEIGHT;

				if (this.showTunnels && stage.tunnels.length)
				{
					height += FIRST_TUNNEL_ADDITIONAL_HEIGHT;
					height += TUNNEL_HEIGHT * stage.tunnels.length;
				}

				return height;
			}, 5);
		}

		getActiveStageId()
		{
			const { activeStageId } = this.props;
			if (activeStageId !== null)
			{
				return activeStageId;
			}

			return ALL_STAGES_ITEM_ID;
		}

		renderStageListItem(stage)
		{
			const { readOnly, onSelectedStage, canMoveStages } = this.props;
			const active = this.getActiveStageId() === stage.id;

			const { categoryCounters } = this.state;

			let stageData = { ...stage, active };
			const counters = categoryCounters.find(stageCounters => stageCounters.id === stage.id);

			if (stage.id && counters)
			{
				stageData = { ...stageData, ...counters };
			}

			return new StageListItem({
				readOnly,
				onSelectedStage,
				canMoveStages,
				showTunnels: this.showTunnels,
				showTotal: this.showTotal,
				showCount: this.showCount,
				showCounters: this.showCounters,
				showAllStagesItem: this.showAllStagesItem,
				stage: stageData,
				onOpenStageDetail: this.onOpenStageDetail,
				enableStageSelect: this.props.enableStageSelect,
				enabled: this.isStageEnabled(stage),
				unsuitable: this.isUnsuitableStage(stage),
			});
		}

		isStageEnabled(stage)
		{
			return !this.disabledStageIds.includes(stage.id);
		}

		get disabledStageIds()
		{
			return BX.prop.getArray(this.props, 'disabledStageIds', []);
		}

		isUnsuitableStage(stage)
		{
			return this.unsuitableStageIds.includes(stage.id);
		}

		get unsuitableStageIds()
		{
			return BX.prop.getArray(this.props, 'unsuitableStages', []);
		}

		render()
		{
			const { processStages, finalStages, title } = this.props;

			return View(
				{
					style: styles.stagesContainer,
				},
				Text({
					text: title || BX.message('CRM_STAGE_LIST_TITLE'),
					style: styles.stagesTitle,
				}),
				this.renderAllStagesItem(processStages),
				this.renderProcessStageList(this.prepareStagesData(processStages)),
				finalStages.length && View({ style: styles.delimeter }),
				...this.prepareStagesData(finalStages).map((stage) => this.renderStageListItem(stage)),
			);
		}

		renderAllStagesItem(stages)
		{
			const { stageParams } = this.props;

			if (stageParams && stageParams.showAllStagesItem && !stages.find(stage => stage.id === 0))
			{
				return this.renderStageListItem(this.getAllStagesItem());
			}

			return null;
		}
	}

	const styles = {
		stagesContainer: {
			borderRadius: 12,
			backgroundColor: '#fff',
			paddingTop: 9,
			flexDirection: 'column',
			marginBottom: 8,
		},
		stagesTitle: {
			color: '#525c69',
			fontSize: 15,
			fontWeight: '500',
			marginLeft: 22,
			marginRight: 19,
			marginBottom: 4,
		},
		delimeter: {
			backgroundColor: '#d9dce2',
			height: 4,
		},
	};

	module.exports = { StageList };
});
