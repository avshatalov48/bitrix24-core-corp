/**
 * @module layout/ui/stage-list-view
 */
jn.define('layout/ui/stage-list-view', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { LoadingScreenComponent } = require('layout/ui/loading-screen');
	const { StageSelectActions } = require('layout/ui/stage-list/actions');
	const { PureComponent } = require('layout/pure-component');
	const { largePen } = require('assets/common');
	const { funnelIcon } = require('assets/stages');
	const { NavigationLoader } = require('navigation-loader');
	const { throttle } = require('utils/function');

	/**
	 * @class StageListView
	 */
	class StageListView extends PureComponent
	{
		static getWidgetParams()
		{
			return {
				modal: true,
				backgroundColor: AppTheme.colors.bgSecondary,
				backdrop: {
					showOnTop: true,
					forceDismissOnSwipeDown: true,
					horizontalSwipeAllowed: false,
					swipeContentAllowed: true,
					navigationBarColor: AppTheme.colors.bgSecondary,
				},
				titleParams: {
					svg: {
						content: funnelIcon(),
					},
				},
			};
		}

		constructor(props)
		{
			super(props);

			this.selectedStage = null;
			this.isClosing = false;

			this.navigationLoader = NavigationLoader.getInstance(this.layout);

			this.onSelectedStageHandler = this.onSelectedStage.bind(this);
			this.onOpenStageDetailHandler = throttle(this.handlerOnOpenStageDetail, 500, this);
		}

		get kanbanSettingsName()
		{
			return BX.prop.getString(this.props, 'kanbanSettingsName', '');
		}

		get kanbanSettingsId()
		{
			return BX.prop.getString(this.props, 'kanbanSettingsId', '');
		}

		get stageIdsBySemantics()
		{
			return BX.prop.getObject(this.props, 'stageIdsBySemantics', {});
		}

		get isEditable()
		{
			return BX.prop.getBoolean(this.props, 'editable', false);
		}

		get layout()
		{
			return BX.prop.get(this.props, 'layout', {});
		}

		get enableStageSelect()
		{
			return BX.prop.getBoolean(this.props, 'enableStageSelect', false);
		}

		get selectAction()
		{
			return BX.prop.getString(this.props, 'selectAction', StageSelectActions.ChangeEntityStage);
		}

		get disabledStageIds()
		{
			return BX.prop.getArray(this.props, 'disabledStageIds', []);
		}

		get data()
		{
			return BX.prop.getObject(this.props, 'data', {});
		}

		get uid()
		{
			return BX.prop.getString(this.props, 'uid', null);
		}

		get status()
		{
			return BX.prop.getString(this.props, 'status', null);
		}

		get isReversed()
		{
			return BX.prop.getBoolean(this.props, 'isReversed', false);
		}

		componentDidMount()
		{
			this.layout.setListener((eventName) => {
				if (eventName === 'onViewHidden' || eventName === 'onViewRemoved')
				{
					this.handleOnViewHidden();
				}
			});

			this.initNavigation(this.props);
		}

		componentWillReceiveProps(props)
		{
			this.setTitle(props.kanbanSettingsName);
		}

		initNavigation(params)
		{
			this.setTitle(params.kanbanSettingsName);

			if (this.isEditable && this.selectAction === StageSelectActions.ChangeEntityStage)
			{
				this.layout.setRightButtons([
					{
						type: 'edit',
						svg: {
							content: largePen(),
						},
						callback: () => this.handlerCategoryEditOpen(),
					},
				]);
			}
		}

		setTitle(title)
		{
			const titleText = this.getTitleForNavigation(title);
			this.layout.setTitle({
				text: titleText,
				svg: {
					content: funnelIcon(),
				},
			}, true);
		}

		/**
		 * @return {Promise}
		 */
		closeLayout()
		{
			if (this.isClosing)
			{
				return Promise.reject();
			}

			this.isClosing = true;
			this.layout.back();

			return new Promise((resolve) => {
				this.layout.close(resolve);
			});
		}

		render()
		{
			return ScrollView(
				{
					resizableByKeyboard: true,
					safeArea: {
						bottom: true,
						top: true,
						left: true,
						right: true,
					},
					style: {
						flex: 1,
					},
				},
				View(
					{},
					this.isLoading() ? this.renderLoader() : this.renderStageList(),
				),
			);
		}

		isLoading()
		{
			return !(this.stageIdsBySemantics
				&& Array.isArray(this.stageIdsBySemantics.processStages)
				&& this.stageIdsBySemantics.processStages.length > 0);
		}

		renderLoader()
		{
			return View(
				{
					style: {
						height: device.screen.height - 90,
						width: device.screen.width,
						alignItems: 'center',
						justifyContent: 'center',
					},
				},
				new LoadingScreenComponent(),
			);
		}

		renderStageList()
		{
			throw new Error('Method renderStageList should be implemented');
		}

		onSelectedStage(stage)
		{
			if (this.selectAction === StageSelectActions.ChangeEntityStage)
			{
				this.onChangeEntityStage(stage.id);
			}
		}

		onChangeEntityStage(stageId, statusId)
		{
			const { onStageSelect } = this.props;

			if (onStageSelect)
			{
				this.closeLayout()
					.then(() => {
						onStageSelect(stageId, statusId, {}, this.data, this.uid);
					})
					.catch(() => {});
			}
		}

		getDisabledStageIdsByCategory()
		{
			return [];
		}

		/**
		 * @abstract
		 */
		handlerOnOpenStageDetail(stage)
		{
			throw new Error('Method handlerOnOpenStageDetail should be implemented');
		}

		/**
		 * @abstract
		 */
		handlerCategoryEditOpen()
		{
			throw new Error('Method handlerCategoryEditOpen should be implemented');
		}

		getStageListTitle()
		{
			return BX.message('STAGE_LIST_VIEW_BACKDROP_DEFAULT_TITLE');
		}

		handleOnViewHidden()
		{
			const { onViewHidden } = this.props;

			if (typeof onViewHidden === 'function')
			{
				onViewHidden({
					stageAction: this.selectedStage ? this.selectAction : null,
					selectedStage: this.selectedStage,
				});
			}
		}

		getStageReadOnly()
		{
			return this.props.readOnly;
		}

		getTitleForNavigation(title)
		{
			return BX.message('STAGE_LIST_VIEW_FUNNEL_NOT_LOADED_TITLE2');
		}
	}

	module.exports = {
		StageListView,
		StageSelectActions,
	};
});
