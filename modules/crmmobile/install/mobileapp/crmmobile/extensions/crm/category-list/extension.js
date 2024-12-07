/**
 * @module crm/category-list
 */
jn.define('crm/category-list', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { lock } = require('assets/common');
	const { CategoryListItem } = require('crm/category-list/item');
	const { CategorySelectActions } = require('crm/category-list/actions');

	/**
	 * @class CategoryList
	 */
	class CategoryList extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.layout = props.layout || layout;

			this.state = {
				categories: BX.prop.getArray(props, 'categories', []),
				currentCategoryId: this.props.currentCategoryId,
				enabled: true,
			};

			this.onSelectCategoryHandler = this.onSelectCategory.bind(this);
			this.onEditCategoryHandler = this.editCategory.bind(this);
			this.onCreateCategoryHandler = this.onCreateCategory.bind(this);
		}

		get entityTypeId()
		{
			return BX.prop.getNumber(this.props, 'entityTypeId', null);
		}

		componentWillReceiveProps(props)
		{
			this.state.categories = BX.prop.getArray(props, 'categories', []);
			this.state.currentCategoryId = props.currentCategoryId;
		}

		onCreateCategory()
		{
			const { onCreateCategory } = this.props;
			if (typeof onCreateCategory === 'function')
			{
				onCreateCategory(this.state.categories);
			}
		}

		canUserAddCategory()
		{
			return BX.prop.getBoolean(this.props, 'canUserAddCategory', true);
		}

		isReadOnly()
		{
			return BX.prop.getBoolean(this.props, 'readOnly', false);
		}

		render()
		{
			const categories = this.state.categories.map((category, index) => {
				return this.renderCategory(category, index === this.state.categories.length - 1);
			});

			return ScrollView(
				{
					style: {
						flex: 1,
					},
				},
				View(
					{
						style: styles.listContainer,
					},
					...categories,
					this.renderCreateCategoryButton(),
				),
			);
		}

		renderCreateCategoryButton()
		{
			if (this.isReadOnly() || !this.canUserEditCategory())
			{
				return null;
			}

			return View(
				{
					style: {
						paddingTop: 4,
						paddingBottom: 6,
					},
				},
				new BaseButton({
					icon: this.canUserAddCategory() ? svgImages.createCategoryButtonIcon : lock,
					text: BX.message('CRM_CATEGORY_CREATE_CATEGORY2'),
					style: {
						button: {
							borderColor: AppTheme.colors.bgContentPrimary,
							justifyContent: 'flex-start',
						},
						icon: {
							tintColor: AppTheme.colors.base3,
							marginRight: this.canUserAddCategory() ? 22 : 12,
							marginLeft: this.canUserAddCategory() ? 28 : 22,
							width: this.canUserAddCategory() ? 12 : 28,
							height: this.canUserAddCategory() ? 12 : 28,
						},
						text: {
							color: AppTheme.colors.base1,
							fontWeight: 'normal',
							fontSize: 18,
						},
					},
					onClick: this.onCreateCategoryHandler,
				}),
			);
		}

		canUserEditCategory()
		{
			return this.props.canUserEditCategory;
		}

		renderCategory(category, isLast)
		{
			const { showCounters, showTunnels } = this.props;
			const canUserEditCategory = this.canUserEditCategory();
			const showBottomBorder = !isLast || (!this.isReadOnly() && canUserEditCategory);

			return new CategoryListItem({
				entityTypeId: this.entityTypeId,
				category,
				layout: this.layout,
				active: this.state.currentCategoryId === category.categoryId,
				onSelectCategory: this.onSelectCategoryHandler,
				onEditCategory: this.onEditCategoryHandler,
				readOnly: this.isReadOnly(),
				enabled: this.isCategoryEnabled(category),
				canUserEditCategory,
				showBottomBorder,
				showCounters,
				showTunnels,
			});
		}

		isCategoryEnabled(category)
		{
			return this.state.enabled && !this.disabledCategoryIds.includes(category.id);
		}

		get disabledCategoryIds()
		{
			return BX.prop.getArray(this.props, 'disabledCategoryIds', []);
		}

		onSelectCategory(category)
		{
			const { selectAction, openStageListHandler, uid, onSelectCategory, entityTypeId } = this.props;

			if (onSelectCategory)
			{
				return (
					this
						.disableCategoryList()
						.then(() => onSelectCategory(category, this.layout))
						.catch(() => this.enableCategoryList())
				);
			}

			if (selectAction)
			{
				const params = [category, entityTypeId];
				switch (selectAction)
				{
					case CategorySelectActions.SelectCurrentCategory:
						BX.postComponentEvent('Crm.CategoryList::onSelectedCategory', params);
						this.layout.close();
						break;

					case CategorySelectActions.CreateTunnel:
						if (typeof openStageListHandler === 'function')
						{
							openStageListHandler(category);
						}
						break;

					case CategorySelectActions.SelectTunnelDestination:
						if (typeof openStageListHandler === 'function')
						{
							openStageListHandler(category);
						}
						break;
					default:
						break;
				}
			}
		}

		enableCategoryList()
		{
			if (this.state.enable === true)
			{
				return Promise.resolve();
			}

			return new Promise((resolve) => {
				this.setState({
					enabled: true,
				}, resolve);
			});
		}

		disableCategoryList()
		{
			if (this.state.enable === false)
			{
				return Promise.resolve();
			}

			return new Promise((resolve) => {
				this.setState({
					enabled: false,
				}, resolve);
			});
		}

		editCategory(categoryId)
		{
			const { onEditCategory } = this.props;
			if (typeof onEditCategory === 'function')
			{
				onEditCategory(categoryId, this.state.categories);
			}
		}
	}

	const styles = {
		listContainer: {
			flexDirection: 'column',
			backgroundColor: AppTheme.colors.bgContentPrimary,
			borderRadius: 12,
		},
	};

	const svgImages = {
		createCategoryButtonIcon: '<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 0H7V12H5V0Z" fill="#525C69"/><path d="M12 5V7L0 7L1.19209e-07 5L12 5Z" fill="#525C69"/></svg>',
	};

	module.exports = { CategoryList };
});
