/**
 * @module layout/ui/stage-list
 */
jn.define('layout/ui/stage-list', (require, exports, module) => {
	/**
	 * @typedef {Object} StageListParams
	 * from props
	 * @property {string} [title]
	 * @property {number} [activeStageId]
	 * @property {Object} [stageParams]
	 * @property {boolean} [readOnly]
	 * @property {boolean} [canMoveStages]
	 * @property {boolean} [enableStageSelect]

	 * @property {string} [kanbanSettingsId]

	 * from store
	 * @property {string} [processStages]
	 * @property {boolean} [successStages]
	 * @property {string} [failedStages]
	 *
	 * @property {function} onOpenStageDetail
	 * @property {function} onSelectedStage
	 */
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { clone } = require('utils/object');
	const { PureComponent } = require('layout/pure-component');

	const {
		MIN_STAGE_HEIGHT,
	} = require('layout/ui/stage-list/item');

	const ALL_STAGES_ITEM_ID = 0;
	const ALL_STAGES_ITEM_STATUS_ID = '';

	const STAY_STAGE_ITEM_ID = 0;
	const STAY_STAGE_ITEM_STATUS_ID = '';

	/**
	 * @class StageList
	 */
	class StageList extends PureComponent
	{
		/**
		 * @param {StageListParams} props
		 */
		constructor(props)
		{
			super(props);
			this.initStageProps(props);
			this.onSelectedStage = this.handlerOnSelectedStage.bind(this);
			this.onOpenStageDetailHandler = this.onOpenStageDetail.bind(this);
		}

		get kanbanSettingsId()
		{
			return BX.prop.getString(this.props, 'kanbanSettingsId', null);
		}

		get disabledStageIds()
		{
			return BX.prop.getArray(this.props, 'disabledStageIds', []);
		}

		get stageIdsBySemantics()
		{
			return BX.prop.get(this.props, 'stageIdsBySemantics', {});
		}

		get processStages()
		{
			return BX.prop.getArray(this.stageIdsBySemantics, 'processStages', []);
		}

		get successStages()
		{
			return BX.prop.getArray(this.stageIdsBySemantics, 'successStages', []);
		}

		get failedStages()
		{
			return BX.prop.getArray(this.stageIdsBySemantics, 'failedStages', []);
		}

		get readOnly()
		{
			return BX.prop.getBoolean(this.props, 'readOnly', true);
		}

		get title()
		{
			return BX.prop.getString(this.props, 'title', '');
		}

		get isReversed()
		{
			return BX.prop.getBoolean(this.props, 'isReversed', false);
		}

		get shouldShowStageListTitle()
		{
			return BX.prop.getBoolean(this.props, 'shouldShowStageListTitle', true);
		}

		initStageProps(props)
		{
			this.stageParams = props.stageParams || {};

			this.showTotal = BX.prop.get(this.stageParams, 'showTotal', false);
			this.showCount = BX.prop.get(this.stageParams, 'showCount', false);
			this.showCounters = BX.prop.get(this.stageParams, 'showCounters', false);
			this.showAllStagesItem = BX.prop.get(this.stageParams, 'showAllStagesItem', false);
			this.showStayStageItem = BX.prop.get(this.stageParams, 'showStayStageItem', false);
		}

		prepareStagesData(stages)
		{
			return stages
				.reduce((acc, stageId, index) => ([
					...acc,
					{
						id: stageId,
						type: this.showContentBorder(index, stages.length - 1) ? 'with border' : 'without border',
						key: String(stageId),
						index: index + 1,
						showContentBorder: this.showContentBorder(index, stages.length - 1),
					},
				]), []);
		}

		showContentBorder(index, lastIndex)
		{
			return index !== lastIndex;
		}

		render()
		{
			return View(
				{
					style: {
						borderRadius: 12,
						flexDirection: 'column',
						marginBottom: 8,
						backgroundColor: AppTheme.colors.bgContentPrimary,
					},
				},
				this.shouldShowStageListTitle && Text({
					text: this.getStageListTitle(),
					style: {
						color: AppTheme.colors.base2,
						fontSize: 15,
						fontWeight: '500',
						marginLeft: 22,
						marginRight: 19,
						marginBottom: 4,
						marginTop: 13,
					},
				}),
				this.renderStayStageItem(),
				this.renderAllStagesItem(),
				this.renderProcessStageList(),
				this.renderFinalStages(),
			);
		}

		getStageListTitle()
		{
			if (this.title)
			{
				return this.title;
			}

			return BX.message('STAGE_LIST_DEFAULT_TITLE');
		}

		renderProcessStageList()
		{
			const stages = this.prepareStagesData(this.processStages);

			return ListView({
				data: [{ items: stages }],
				renderItem: (stage) => this.renderStageListItem(stage),
				dragInteractionEnabled: !this.readOnly,
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
			return stages.length * MIN_STAGE_HEIGHT + 5;
		}

		renderStageListItem(stage)
		{
			throw new Error('StageList: renderStageListItem method must be implemented');
		}

		moveStage(fromIndex, toIndex)
		{
			const stage = this.processStages[fromIndex];
			let list = [...this.processStages];

			list.splice(fromIndex, 1);
			list.splice(toIndex, 0, stage);

			//list = this.updateStagesSort(list);
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

		getActiveStageId()
		{
			const { activeStageId } = this.props;
			if (activeStageId !== null)
			{
				return activeStageId;
			}

			return this.kanbanSettingsId;
		}

		renderStayStageItem()
		{
			if (this.showStayStageItem)
			{
				return this.renderStageListItem(this.getStayStageItem());
			}

			return null;
		}

		/**
		 * @returns {{color: string, statusId: string, name: *, id: number}}
		 */
		getStayStageItem()
		{
			return stayStageItem(true);
		}

		renderAllStagesItem()
		{
			if (this.showAllStagesItem && !this.processStages.some((stage) => stage.id === 0))
			{
				return this.renderStageListItem(this.getAllStagesItem());
			}

			return null;
		}

		getAllStagesItem()
		{
			return allStagesItem(this.kanbanSettingsId);
		}

		renderFinalStages()
		{
			const finalStages = [...this.successStages, ...this.failedStages];
			if (finalStages.length === 0)
			{
				return null;
			}

			return View(
				{},
				View(
					{
						style: {
							height: 4,
							backgroundColor: AppTheme.colors.bgSecondary,
						},
					},
				),
				...this.prepareStagesData(finalStages).map((stage) => this.renderStageListItem(stage)),
			);
		}

		onOpenStageDetail(stage)
		{
			const { onOpenStageDetail } = this.props;

			if (onOpenStageDetail)
			{
				onOpenStageDetail(stage);
			}
		}

		handlerOnSelectedStage(stage)
		{
			if (this.props.onSelectedStage)
			{
				if (stage === this.kanbanSettingsId)
				{
					this.props.onSelectedStage(this.kanbanSettingsId);
				}
				else
				{
					this.props.onSelectedStage(stage);
				}
			}
		}
	}

	const allStagesItem = (kanbanSettingsId) => ({
		id: kanbanSettingsId,
		stage: {
			id: kanbanSettingsId,
			color: AppTheme.colors.bgContentPrimary,
			name: BX.message('STAGE_LIST_ALL_STAGES_TITLE'),
			statusId: ALL_STAGES_ITEM_STATUS_ID,
			listMode: true,
		},
		showContentBorder: true,
	});

	const stayStageItem = (showContentBorder = false) => ({
		id: STAY_STAGE_ITEM_ID,
		statusId: STAY_STAGE_ITEM_STATUS_ID,
		color: AppTheme.colors.bgContentPrimary,
		borderColor: AppTheme.colors.accentBrandBlue,
		name: Loc.getMessage('STAGE_LIST_STAY_STAGE_TITLE'),
		count: null,
		total: null,
		currency: null,
		showContentBorder,
		listMode: false,
	});

	module.exports = { StageList, stayStageItem, allStagesItem };
});
