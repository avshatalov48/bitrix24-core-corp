/**
 * @module layout/ui/kanban/toolbar
 */
jn.define('layout/ui/kanban/toolbar', (require, exports, module) => {
	const { AppTheme } = require('apptheme/extended');
	const { mergeImmutable } = require('utils/object');
	const { Filler } = require('layout/ui/kanban/toolbar/filler');
	const { StageSummary } = require('layout/ui/kanban/toolbar/stage-summary');
	const { StageDropdown, StageDropdownClass } = require('layout/ui/kanban/toolbar/stage-dropdown');
	const { PureComponent } = require('layout/pure-component');

	/**
	 * @abstract
	 */
	class KanbanToolbar extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				loading: true,
				activeStageId: null,
			};

			this.onToolbarClick = this.onToolbarClick.bind(this);
		}

		get layout()
		{
			return this.props.layout || {};
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
		 * @protected
		 * @abstract
		 */
		onToolbarClick()
		{}

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
					{
						color: AppTheme.colors.shadowPrimary,
						radius: 3,
						offset: {
							y: 3,
						},
						inset: {
							left: 3,
							right: 3,
						},
					},
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
		mainWrapper: {
			flexDirection: 'row',
			height: 60,
			paddingHorizontal: 10,
			paddingTop: 9,
			backgroundColor: AppTheme.colors.bgContentPrimary,
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
			color: AppTheme.colors.base4,
			fontSize: 14,
			fontWeight: '500',
			marginBottom: Application.getPlatform() === 'android' ? 0 : 2,
		},
		columnContent: {
			color: AppTheme.colors.base2,
			fontSize: 14,
			fontWeight: '500',
			marginTop: Application.getPlatform() === 'android' ? 1 : 3,
		},
	};

	module.exports = { KanbanToolbar, Filler, StageSummary, StageDropdown, defaultStyles, StageDropdownClass };
});
