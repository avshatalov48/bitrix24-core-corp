/**
 * @module layout/ui/kanban/toolbar
 */
jn.define('layout/ui/kanban/toolbar', (require, exports, module) => {
	const { mergeImmutable } = require('utils/object');
	const { Filler } = require('layout/ui/kanban/toolbar/filler');
	const { StageSummary } = require('layout/ui/kanban/toolbar/stage-summary');
	const { StageDropdown } = require('layout/ui/kanban/toolbar/stage-dropdown');

	/**
	 * @abstract
	 */
	class KanbanToolbar extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				loading: true,
				activeStageId: null,
			};
		}

		/**
		 * @protected
		 * @return {KanbanToolbarProps}
		 */
		getProps()
		{
			return this.props;
		}

		/**
		 * @protected
		 * @return {Object}
		 */
		getStyles()
		{
			return mergeImmutable(defaultStyles, BX.prop.getObject(this.getProps(), 'style', {}));
		}

		/**
		 * @protected
		 * @abstract
		 * @return {string}
		 */
		getTestId()
		{
			return 'KanbanToolbar';
		}

		/**
		 * @protected
		 * @abstract
		 * @return {KanbanStage[]}
		 */
		getStages()
		{
			return [];
		}

		/**
		 * @protected
		 * @param {number} id
		 * @return {KanbanStage|undefined}
		 */
		getStageById(id)
		{
			const stages = this.getStages();

			return stages.find((stage) => stage.id === id);
		}

		/**
		 * @protected
		 * @param {string} statusId
		 * @return {KanbanStage|undefined}
		 */
		getStageByStatusId(statusId)
		{
			const stages = this.getStages();

			return stages.find((stage) => stage.statusId === statusId);
		}

		/**
		 * @protected
		 * @returns {null|KanbanStage}
		 */
		getActiveStage()
		{
			const stages = this.getStages();

			if (stages.length === 0 || !this.getActiveStageId())
			{
				return null;
			}

			return this.getStageById(this.getActiveStageId());
		}

		/**
		 * @public
		 * @returns {null|Number}
		 */
		getActiveStageId()
		{
			return this.state.activeStageId;
		}

		/**
		 * @public
		 * @param {Number|null} activeStageId
		 */
		setActiveStage(activeStageId)
		{
			if (this.getActiveStageId() !== activeStageId)
			{
				this.setState({ activeStageId }, () => this.onChangeStage(activeStageId));
			}
		}

		/**
		 * @protected
		 * @param {number} activeStageId
		 */
		onChangeStage(activeStageId)
		{
			if (this.getProps().onChangeStage)
			{
				this.getProps().onChangeStage(activeStageId);
			}
		}

		/**
		 * @public
		 * @return {Map<string, KanbanStage>}
		 */
		getColumns()
		{
			const columns = new Map();

			this.getStages().forEach((stage) => {
				columns.set(stage.statusId, stage);
			});

			return columns;
		}

		/**
		 * @protected
		 * @abstract
		 */
		onToolbarClick() {}

		/**
		 * @protected
		 * @return {boolean}
		 */
		isLoading()
		{
			return Boolean(this.state.loading);
		}

		/**
		 * @protected
		 */
		render()
		{
			return this.renderContainer(
				this.renderStageSelector(),
				this.renderCurrentStageSummary(),
			);
		}

		/**
		 * @protected
		 * @param {object[]} children
		 * @return {object}
		 */
		renderContainer(...children)
		{
			const styles = this.getStyles();

			return View(
				{
					style: styles.rootWrapper,
					testId: this.getTestId(),
					onClick: () => this.onToolbarClick(),
				},
				Shadow(
					styles.shadow,
					View(
						{
							style: styles.mainWrapper,
						},
						...children,
					),
				),
			);
		}

		renderStageSelector()
		{
			return View();
		}

		/**
		 * @protected
		 * @return {object|null}
		 * @see StageSummary
		 */
		renderCurrentStageSummary()
		{
			return null;
		}
	}

	const defaultStyles = {
		rootWrapper: {
			position: 'absolute',
			left: 0,
			right: 0,
			top: 0,
		},
		shadow: {
			color: '#0f000000',
			radius: 2,
			offset: {
				y: 2,
			},
			inset: {
				left: 2,
				right: 2,
			},
			style: {
				borderBottomLeftRadius: 12,
				borderBottomRightRadius: 12,
				marginBottom: 2,
			},
		},
		mainWrapper: {
			flexDirection: 'row',
			height: 60,
			paddingHorizontal: 10,
			paddingTop: 9,
			backgroundColor: '#ffffff',
			borderBottomLeftRadius: 12,
			borderBottomRightRadius: 12,
			justifyContent: 'space-between',
			alignItems: 'flex-start',
		},
		stageSelectorWrapper: {
			flex: 10,
			paddingRight: 10,
		},
		currentStageSummaryWrapper: {
			flex: 4,
			paddingRight: 10,
		},
		columnTitle: {
			color: '#a8adb4',
			fontSize: 14,
			fontWeight: '500',
			marginBottom: Application.getPlatform() === 'android' ? 0 : 2,
		},
		columnContent: {
			color: '#525c69',
			fontSize: 14,
			fontWeight: '500',
			marginTop: Application.getPlatform() === 'android' ? 1 : 3,
		},
	};

	module.exports = { KanbanToolbar, Filler, StageSummary, StageDropdown, defaultStyles };
});
