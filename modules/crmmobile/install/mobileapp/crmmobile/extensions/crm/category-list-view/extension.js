/**
 * @module crm/category-list-view
 */
jn.define('crm/category-list-view', (require, exports, module) => {

	const { mergeImmutable, isEqual } = require('utils/object');
	const { NotifyManager } = require('notify-manager');
	const { CategoryStorage } = require('crm/storage/category');
	const { CategoryList } = require('crm/category-list');
	const { StageSelectActions } = require('crm/stage-list/actions');
	const { NavigationLoader } = require('navigation-loader');
	const { StageListView } = require('crm/stage-list-view');
	const { PlanRestriction } = require('layout/ui/plan-restriction');
	const { throttle } = require('utils/function');

	const DEAL_CATEGORY_LIMIT_RESTRICTION_NAME = 'crm_clr_cfg_deal_category';

	/**
	 * @class CategoryListView
	 */
	class CategoryListView extends LayoutComponent
	{
		static open(props, widgetParams = {}, parentWidget = PageManager)
		{
			return new Promise((resolve) => {
				const { selectAction } = props;
				const params = {
					modal: true,
					title: this.getNavigationTitle(selectAction),
					backdrop: {
						showOnTop: true,
						forceDismissOnSwipeDown: true,
						horizontalSwipeAllowed: false,
						swipeContentAllowed: false,
						navigationBarColor: '#eef2f4',
					},
				};

				parentWidget
					.openWidget('layout', mergeImmutable(params, widgetParams))
					.then((layout) => {
						layout.showComponent(new this({ ...props, layout }));
						resolve(layout);
					})
				;
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

			this.layout = props.layout || layout;

			this.state = {
				categoryList: this.getCategoryListFromStorage(props.entityTypeId),
			};

			this.createCategoryHandler = throttle(this.createCategory, 500, this);
			this.editCategoryHandler = throttle(this.openEditCategory, 500, this);
			this.openStageListHandler = throttle(this.openStageList, 500, this);
		}

		componentWillReceiveProps(newProps)
		{
			this.state.categoryList = this.getCategoryListFromStorage(newProps.entityTypeId);
		}

		componentDidMount()
		{
			BX.addCustomEvent('Crm.CategoryDetail::onDeleteCategory', () => this.getCategoryListFromStorage(this.props.entityTypeId));
			BX.addCustomEvent('Crm.CategoryDetail::onClose', () => this.getCategoryListFromStorage(this.props.entityTypeId));

			this.layout.enableNavigationBarBorder(false);

			CategoryStorage
				.subscribeOnChange(() => this.reloadCategoryList())
				.subscribeOnLoading(({ status }) => NavigationLoader.setLoading(status, this.layout))
				.markReady()
			;
		}

		getCategoryListFromStorage(entityTypeId)
		{
			return CategoryStorage.getCategoryList(entityTypeId);
		}

		reloadCategoryList()
		{
			const categoryList = this.getCategoryListFromStorage(this.props.entityTypeId);
			if (!isEqual(this.state.categoryList, categoryList))
			{
				this.setState({ categoryList });
			}
		}

		/**
		 * @return {*[]}
		 */
		getCategories()
		{
			if (!this.state.categoryList)
			{
				return [];
			}

			return this.state.categoryList.categories;
		}

		/**
		 * @return {*[]}
		 */
		getRestrictions()
		{
			if (!this.state.categoryList || !Array.isArray(this.state.categoryList.restrictions))
			{
				return [];
			}

			return this.state.categoryList.restrictions;
		}

		/**
		 * @return {Boolean}
		 */
		canUserEditCategory()
		{
			if (!this.state.categoryList)
			{
				return false;
			}

			return this.state.categoryList.canUserEditCategory;
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						backgroundColor: '#eef2f4',
					},
				},
				this.state.categoryList === null ? this.renderLoader() : this.renderContent(),
			);
		}

		renderLoader()
		{
			return new LoadingScreenComponent();
		}

		renderContent()
		{
			const showCounters = BX.prop.getBoolean(this.props, 'showCounters', true);
			const showTunnels = BX.prop.getBoolean(this.props, 'showTunnels', true);

			let currentCategory = this.getCategories().find((category) => category.id === this.props.currentCategoryId);
			if (!currentCategory)
			{
				currentCategory = this.getCategories()[0];
			}

			return new CategoryList({
				entityTypeId: this.props.entityTypeId,
				currentCategoryId: currentCategory && currentCategory.id,
				needSaveCurrentCategoryId: this.props.needSaveCurrentCategoryId,
				categories: this.getCategories(),
				onCreateCategory: this.createCategoryHandler,
				onEditCategory: this.editCategoryHandler,
				canUserEditCategory: this.canUserEditCategory(),
				canUserAddCategory: !this.hasDealCategoryLimitRestriction(),
				readOnly: this.props.readOnly,
				selectAction: this.props.selectAction,
				onSelectCategory: this.props.onSelectCategory,
				layout: this.layout,
				openStageListHandler: this.openStageListHandler,
				enableSelect: this.props.enableSelect,
				uid: this.props.uid,
				disabledCategoryIds: this.props.disabledCategoryIds,
				showCounters,
				showTunnels,
			});
		}

		openEditCategory(categoryId, categories = null)
		{
			categories = categories || this.getCategories();

			ComponentHelper.openLayout({
				name: 'crm:crm.category.detail',
				componentParams: {
					entityTypeId: this.props.entityTypeId,
					categoryId,
					categories,
				},
				widgetParams: {
					modal: true,
					backdrop: {
						showOnTop: true,
						forceDismissOnSwipeDown: true,
						swipeContentAllowed: false,
						horizontalSwipeAllowed: false,
						navigationBarColor: '#eef2f4',
					},
				},
			}, this.layout);
		}

		openStageList(category)
		{
			void StageListView.open(
				{
					entityTypeId: this.props.entityTypeId,
					categoryId: category.id,
					activeStageId: this.props.activeStageId,
					selectAction: this.props.selectAction,
					uid: this.props.uid,
					enableStageSelect: true,
					disabledStageIds: this.props.disabledStageIds,
					stageParams: {
						showTunnels: true,
					},
					onViewHidden: ({ stageAction }) => {
						if (
							stageAction === StageSelectActions.SelectTunnelDestination
							|| stageAction === StageSelectActions.CreateTunnel
						)
						{
							this.layout.close();
						}
					},
				},
				{},
				this.layout,
			);
		}

		createCategory(categories)
		{
			const sort = categories[categories.length - 1].sort + 100;

			if (this.hasDealCategoryLimitRestriction())
			{
				void PlanRestriction.open(
					{
						title: BX.message('M_CRM_CATEGORY_LIST_NAVIGATION_TITLE2'),
					},
					this.layout,
				);
			}
			else
			{
				const { entityTypeId } = this.props;

				NotifyManager.showLoadingIndicator();

				CategoryStorage
					.createCategory(entityTypeId, {
						name: BX.message('M_CRM_CATEGORY_LIST_DEFAULT_CATEGORY_NAME2'),
						sort,
					})
					.then((id) => {
						NotifyManager.hideLoadingIndicator(true, BX.message('M_CRM_CATEGORY_LIST_SUCCESS_CREATION2'), 1000);
						setTimeout(() => this.openEditCategory(id), 1300);
					})
					.catch((response) => NotifyManager.showErrors(response.errors))
				;
			}
		}

		getDealCategoryLimitRestriction()
		{
			return (
				this.getRestrictions()
					.find((restriction) => restriction.name === DEAL_CATEGORY_LIMIT_RESTRICTION_NAME)
			);
		}

		hasDealCategoryLimitRestriction()
		{
			const dealCategoryLimitRestriction = this.getDealCategoryLimitRestriction();

			return !dealCategoryLimitRestriction || dealCategoryLimitRestriction.isExceeded;
		}
	}

	module.exports = { CategoryListView };
});
