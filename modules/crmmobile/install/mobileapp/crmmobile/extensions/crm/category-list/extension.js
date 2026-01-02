/**
 * @module crm/category-list
 */
jn.define('crm/category-list', (require, exports, module) => {
	const { CategoryListItem } = require('crm/category-list/item');
	const { CategorySelectActions } = require('crm/category-list/actions');
	const { Color, Corner } = require('tokens');
	const { FloatingActionButton } = require('ui-system/form/buttons/floating-action-button');

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

			this.floatingActionButtonRef = null;
			this.initFloatingButton();

			this.onSelectCategoryHandler = this.onSelectCategory.bind(this);
			this.onEditCategoryHandler = this.editCategory.bind(this);
		}

		get entityTypeId()
		{
			return BX.prop.getNumber(this.props, 'entityTypeId', null);
		}

		initFloatingButton()
		{
			if (this.isReadOnly() || !this.canUserEditCategory())
			{
				return;
			}

			this.floatingActionButtonRef = FloatingActionButton({
				testId: 'category-list-create-category-button',
				parentLayout: this.props.layout,
				onClick: () => this.onCreateCategory(),
			});
		}

		componentDidMount()
		{
			this.floatingActionButtonRef?.setFloatingButton({
				hide: false,
			});
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
						style: {
							flexDirection: 'column',
							backgroundColor: Color.bgContentPrimary.toHex(),
							borderRadius: Corner.L.toNumber(),
						},
					},
					...categories,
				),
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
				showBottomBorder: !isLast,
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
			const { selectAction, openStageListHandler, onSelectCategory, entityTypeId } = this.props;

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

			return Promise.resolve();
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

	module.exports = { CategoryList };
});
