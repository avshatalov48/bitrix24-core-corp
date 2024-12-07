/**
 * @module crm/category-list-view
 */
jn.define('crm/category-list-view', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { throttle } = require('utils/function');
	const { NotifyManager } = require('notify-manager');
	const { NavigationLoader } = require('navigation-loader');
	const { PureComponent } = require('layout/pure-component');
	const { LoadingScreenComponent } = require('layout/ui/loading-screen');

	const { StageSelectActions } = require('layout/ui/stage-list/actions');
	const { PlanRestriction } = require('layout/ui/plan-restriction');

	const { TypeId } = require('crm/type');
	const { CategoryList } = require('crm/category-list');

	const {
		getCrmKanbanUniqId,
		fetchCrmKanbanList,
		selectRestrictions,
		selectByEntityTypeId,
		selectCanUserEditCategory,
		createCrmKanban,
		selectIsFetchedList,
	} = require('crm/statemanager/redux/slices/kanban-settings');
	const { connect } = require('statemanager/redux/connect');

	const DEAL_CATEGORY_LIMIT_RESTRICTION_NAME = 'crm_clr_cfg_deal_category';

	/**
	 * @class CategoryListView
	 */
	class CategoryListView extends PureComponent
	{
		static getWidgetParams(selectAction)
		{
			return {
				modal: true,
				title: CategoryListView.getNavigationTitle(selectAction),
				backgroundColor: AppTheme.colors.bgSecondary,
				backdrop: {
					showOnTop: true,
					forceDismissOnSwipeDown: true,
					horizontalSwipeAllowed: false,
					swipeContentAllowed: false,
					navigationBarColor: AppTheme.colors.bgSecondary,
				},
			};
		}

		static open(props, widgetParams = {}, parentWidget = PageManager)
		{
			return new Promise((resolve) => {
				const { selectAction } = props;
				parentWidget
					.openWidget('layout', CategoryListView.getWidgetParams(selectAction))
					.then((layout) => {
						layout.enableNavigationBarBorder(false);
						layout.showComponent(connect(mapStateToProps, mapDispatchToProps)(this)({ layout, ...props }));
						resolve(layout);
					})
					.catch((error) => {
						console.error(error);
					});
			});
		}

		static getNavigationTitle(selectAction)
		{
			if (
				selectAction === StageSelectActions.SelectTunnelDestination
				|| selectAction === StageSelectActions.CreateTunnel
			)
			{
				return BX.message('M_CRM_CATEGORY_LIST_NAVIGATION_TITLE_ON_CHANGE_TUNNEL2');
			}

			return BX.message('M_CRM_CATEGORY_LIST_NAVIGATION_TITLE2');
		}

		constructor(props)
		{
			super(props);
			this.navigationLoader = NavigationLoader.getInstance(this.layout);

			this.createCategoryHandler = throttle(this.createCategory, 500, this);
			this.editCategoryHandler = throttle(this.openEditCategory, 500, this);
			this.openStageListHandler = throttle(this.openStageList, 500, this);
		}

		get layout()
		{
			return BX.prop.get(this.props, 'layout', null);
		}

		get entityTypeId()
		{
			return BX.prop.getNumber(this.props, 'entityTypeId', null);
		}

		get kanbanSettingsList()
		{
			return BX.prop.getArray(this.props, 'kanbanSettingsList', []);
		}

		get restrictions()
		{
			return BX.prop.getArray(this.props, 'restrictions', []);
		}

		get canUserEditCategory()
		{
			return BX.prop.getBoolean(this.props, 'canUserEditCategory', false);
		}

		get isFetchedList()
		{
			return BX.prop.getBoolean(this.props, 'isFetchedList', false);
		}

		componentDidMount()
		{
			this.layout.setListener((eventName) => {
				if (eventName === 'onViewHidden' || eventName === 'onViewRemoved')
				{
					this.handleOnViewHidden();
				}
			});

			if (!this.isFetchedList)
			{
				this.props.fetchCrmKanbanList({ entityTypeId: this.entityTypeId })
					.unwrap()
					.catch(() => {
						NavigationLoader.showDefaultError();
					});
			}
		}

		handleOnViewHidden()
		{
			const { onViewHidden } = this.props;

			if (typeof onViewHidden === 'function')
			{
				onViewHidden({
					selectedStage: this.selectedStage,
					selectedKanbanSettings: this.selectedKanbanSettings,
				});
			}
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
					},
				},
				this.isFetchedList ? this.renderContent() : this.renderLoader(),
			);
		}

		renderLoader()
		{
			return new LoadingScreenComponent({ backgroundColor: AppTheme.colors.bgPrimary });
		}

		renderContent()
		{
			const showCounters = BX.prop.getBoolean(this.props, 'showCounters', true);
			const showTunnels = BX.prop.getBoolean(this.props, 'showTunnels', true);

			const {
				currentCategoryId,
				entityTypeId,
				readOnly,
				needSaveCurrentCategoryId,
				selectAction,
				onSelectCategory,
				enableSelect, disabledCategoryIds, uid,
			} = this.props;

			let currentCategory = this.kanbanSettingsList.find((category) => category.categoryId === currentCategoryId);

			if (!currentCategory && currentCategoryId !== null)
			{
				currentCategory = this.kanbanSettingsList[0];
			}

			return new CategoryList({
				entityTypeId,
				currentCategoryId: currentCategory && currentCategory.categoryId,
				needSaveCurrentCategoryId,
				categories: this.kanbanSettingsList,
				onCreateCategory: this.createCategoryHandler,
				onEditCategory: this.editCategoryHandler,
				canUserEditCategory: this.canUserEditCategory,
				canUserAddCategory: !this.hasCategoryLimitRestriction(),
				readOnly,
				selectAction,
				onSelectCategory,
				layout: this.layout,
				openStageListHandler: this.openStageListHandler,
				enableSelect,
				uid,
				disabledCategoryIds,
				showCounters,
				showTunnels,
			});
		}

		async openEditCategory(kanbanSettingsId)
		{
			const { CrmKanbanSettings } = await requireLazy('crm:kanban/settings') || {};

			if (CrmKanbanSettings)
			{
				CrmKanbanSettings.open(
					{
						entityTypeId: this.entityTypeId,
						kanbanSettingsId,
					},
					this.layout,
				);
			}
		}

		openStageList(kanbanSettings)
		{
			void requireLazy('crm:stage-list-view').then(({ CrmStageListView }) => {
				const props = {
					entityTypeId: this.entityTypeId,
					kanbanSettingsId: kanbanSettings.id,
					categoryId: kanbanSettings.categoryId,
					activeStageId: this.props.activeStageId,
					selectAction: this.props.selectAction,
					uid: this.props.uid,
					enableStageSelect: true,
					disabledStageIds: this.props.disabledStageIds,
					stageParams: {
						showTunnels: true,
					},
					onViewHidden: (params) => {
						this.selectedStage = params.selectedStage;
						this.selectedKanbanSettings = kanbanSettings;
						if (
							params.stageAction === StageSelectActions.SelectTunnelDestination
							|| params.stageAction === StageSelectActions.CreateTunnel
						)
						{
							this.layout.close();
						}
					},
				};

				void CrmStageListView.open(props, this.layout);
			});
		}

		createCategory(categories)
		{
			const sort = categories[categories.length - 1].sort + 100;

			if (this.hasCategoryLimitRestriction())
			{
				PlanRestriction.open(
					{
						title: BX.message('M_CRM_CATEGORY_LIST_NAVIGATION_TITLE2'),
					},
					this.layout,
				);
			}
			else
			{
				NotifyManager.showLoadingIndicator();

				this.props.createCrmKanban(
					{
						entityTypeId: this.entityTypeId,
						fields: {
							name: BX.message('M_CRM_CATEGORY_LIST_DEFAULT_CATEGORY_NAME2'),
							sort,
						},
					},
				).unwrap()
					.then((response) => {
						NotifyManager.hideLoadingIndicator(
							true,
							BX.message('M_CRM_CATEGORY_LIST_SUCCESS_CREATION2'),
							1000,
						);
						const {
							data: categoryId,
						} = response || {};

						setTimeout(
							() => this.openEditCategory(getCrmKanbanUniqId(this.entityTypeId, categoryId)),
							1300,
						);
					})
					.catch(() => {
						NotifyManager.hideLoadingIndicator(false);
						NotifyManager.showDefaultError();
					});
			}
		}

		getDealCategoryLimitRestriction()
		{
			return (
				this.restrictions
					.find((restriction) => restriction.name === DEAL_CATEGORY_LIMIT_RESTRICTION_NAME)
			);
		}

		hasCategoryLimitRestriction()
		{
			if (this.entityTypeId !== TypeId.Deal)
			{
				return false;
			}

			const categoryLimitRestriction = this.getDealCategoryLimitRestriction();

			return !categoryLimitRestriction || categoryLimitRestriction.isExceeded;
		}
	}

	const mapStateToProps = (state, ownProps) => {
		const { entityTypeId } = ownProps;

		return {
			kanbanSettingsList: selectByEntityTypeId(state, entityTypeId),
			restrictions: selectRestrictions(state),
			canUserEditCategory: selectCanUserEditCategory(state),
			isFetchedList: selectIsFetchedList(state, entityTypeId),
		};
	};

	const mapDispatchToProps = ({
		fetchCrmKanbanList,
		createCrmKanban,
	});

	module.exports = { CategoryListView };
});
