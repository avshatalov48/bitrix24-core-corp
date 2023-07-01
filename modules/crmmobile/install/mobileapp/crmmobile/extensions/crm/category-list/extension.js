/**
 * @module crm/category-list
 */
jn.define('crm/category-list', (require, exports, module) => {
	const { CategoryListItem } = require('crm/category-list/item');
	const { CategorySelectActions } = require('crm/category-list/actions');
	const { clone } = require('utils/object');
	const { lock } = require('assets/common');

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

		isEnabledSelect()
		{
			return BX.prop.getBoolean(this.props, 'enableSelect', false);
		}

		componentDidMount()
		{
			BX.addCustomEvent('Crm.CategoryDetail::onCreateCategory', this.setNewCategory.bind(this));
			BX.addCustomEvent('Crm.CategoryDetail::onUpdateCategory', this.onUpdateCategory.bind(this));
			BX.addCustomEvent('Crm.CategoryDetail::onDeleteCategory', this.onDeleteCategory.bind(this));
			BX.addCustomEvent('Crm.StageDetail::onUpdateStage', this.onUpdateStage.bind(this));
			BX.addCustomEvent('Crm.StageDetail::onDeleteStage', this.onDeleteStage.bind(this));
			BX.addCustomEvent('Crm.StageDetail::onUpdateTunnels', this.onUpdateTunnels.bind(this));
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
							borderColor: '#ffffff',
							justifyContent: 'flex-start',
						},
						icon: {
							marginRight: this.canUserAddCategory() ? 22 : 12,
							marginLeft: this.canUserAddCategory() ? 28 : 22,
							width: this.canUserAddCategory() ? 12 : 28,
							height: this.canUserAddCategory() ? 12 : 28,
						},
						text: {
							color: '#333333',
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
				category,
				layout: this.layout,
				active: this.state.currentCategoryId === category.id,
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
			const { selectAction, openStageListHandler, uid, onSelectCategory } = this.props;

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
				switch (selectAction)
				{
					case CategorySelectActions.SelectCurrentCategory:
						BX.postComponentEvent('Crm.CategoryList::onSelectedCategory', [category]);
						this.layout.close();
						break;

					case CategorySelectActions.CreateTunnel:
						BX.postComponentEvent('Crm.TunnelList::selectCategoryOnCreateTunnel', [category]);
						if (typeof openStageListHandler === 'function')
						{
							openStageListHandler(category);
						}
						break;

					case CategorySelectActions.SelectTunnelDestination:
						BX.postComponentEvent(`Crm.TunnelListItem::selectTunnelDestinationCategory-${uid}`, [category]);
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

		setNewCategory(data)
		{
			this.setState({
				categories: [
					...this.state.categories,
					data.category,
				],
			});
		}

		onDeleteCategory(categoryId)
		{
			const categoryIndex = this.state.categories.findIndex((category) => category.id === categoryId);
			if (categoryIndex !== -1)
			{
				const categories = [...this.state.categories];
				categories.splice(categoryIndex, 1);
				this.setState({ categories });
			}
		}

		onUpdateCategory(data)
		{
			const categories = [...this.state.categories];
			const categoryIndex = this.state.categories.findIndex((category) => category.id === data.category.id);
			if (categoryIndex !== -1)
			{
				categories[categoryIndex] = data.category;
			}

			for (const category of categories)
			{
				category.tunnels = category.tunnels.reduce((acc, tunnel) => {
					if (tunnel.dstCategoryId === data.category.id)
					{
						return [
							...acc,
							{
								...tunnel,
								dstCategoryName: data.category.name,
							},
						];
					}

					return [...acc, tunnel];
				}, []);
			}

			this.setState({
				categories,
			});
		}

		onUpdateStage(stage)
		{
			const categories = [...this.state.categories];

			for (let i = 0; i < categories.length; i++)
			{
				const tunnels = categories[i].tunnels.reduce((acc, tunnel) => {
					if (tunnel.dstStageId === stage.id)
					{
						return [
							...acc,
							{
								...tunnel,
								dstStageName: stage.name,
								dstStageColor: stage.color,
							},
						];
					}

					return [...acc, tunnel];
				}, []);

				categories[i] = {
					...categories[i],
					tunnels,
				};
			}

			this.setState({
				categories: [
					...categories,
				],
			});
		}

		onDeleteStage(stage)
		{
			const categories = [...this.state.categories];

			for (let i = 0; i < categories.length; i++)
			{
				const tunnelIndex = categories[i].tunnels
					.findIndex((tunnel) => tunnel.dstStageId === stage.id || tunnel.srcStageId === stage.id);

				if (tunnelIndex !== -1)
				{
					const tunnels = [...categories[i].tunnels];
					tunnels.splice(tunnelIndex, 1);
					categories[i] = {
						...categories[i],
						tunnels,
					};
				}
			}

			this.setState({
				categories: [
					...categories,
				],
			});
		}

		onDeleteTunnel(tunnel)
		{
			const categories = [...this.state.categories];
			const categoryIndex = categories.findIndex((category) => {
				return category.tunnels.find((item) => item.srcCategoryId === tunnel.srcCategoryId);
			});
			if (categoryIndex !== -1)
			{
				const tunnels = [...categories[categoryIndex].tunnels];
				const tunnelIndex = tunnels.findIndex((item) => item.robot.name === tunnel.robot.name);
				if (tunnelIndex !== -1)
				{
					tunnels.splice(tunnelIndex, 1);
					categories[categoryIndex] = {
						...categories[categoryIndex],
						tunnels,
					};

					this.setState({
						categories,
					});
				}
			}
		}

		onCreateTunnel(tunnel)
		{
			if (this.isEnabledSelect())
			{
				this.layout.close();
			}

			const categories = [...this.state.categories];
			const categoryIndex = categories.findIndex(((category) => category.id === tunnel.srcCategoryId));
			if (categoryIndex !== -1)
			{
				categories[categoryIndex].tunnels = [...categories[categoryIndex].tunnels, tunnel];

				this.setState({
					categories,
				});
			}
		}

		onUpdateTunnels(tunnels, categoryId)
		{
			const { categories } = this.state;
			const modifiedCategories = clone(categories);
			const categoryIndex = modifiedCategories.findIndex((category) => category.id === categoryId);
			if (categoryIndex !== -1)
			{
				modifiedCategories[categoryIndex] = {
					...categories[categoryIndex],
					tunnels,
				};

				this.setState({
					categories: modifiedCategories,
				});
			}
		}
	}

	const styles = {
		listContainer: {
			flexDirection: 'column',
			backgroundColor: '#ffffff',
			borderRadius: 12,
		},
	};

	const svgImages = {
		createCategoryButtonIcon: '<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 0H7V12H5V0Z" fill="#525C69"/><path d="M12 5V7L0 7L1.19209e-07 5L12 5Z" fill="#525C69"/></svg>',
	};

	module.exports = { CategoryList };
});
