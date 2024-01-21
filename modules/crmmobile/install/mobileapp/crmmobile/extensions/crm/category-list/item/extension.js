/**
 * @module crm/category-list/item
 */
jn.define('crm/category-list/item', (require, exports, module) => {
	const { Haptics } = require('haptics');
	const { PureComponent } = require('layout/pure-component');
	const { funnelIcon } = require('assets/stages');
	const AppTheme = require('apptheme');
	const ACTIVE_COLOR = AppTheme.colors.accentSoftBlue1;

	/**
	 * @class CategoryListItem
	 */
	class CategoryListItem extends PureComponent
	{
		get entityTypeId()
		{
			return BX.prop.getNumber(this.props, 'entityTypeId', null);
		}

		get enabled()
		{
			return BX.prop.getBoolean(this.props, 'enabled', true);
		}

		get showBottomBorder()
		{
			return BX.prop.getBoolean(this.props, 'showBottomBorder', true);
		}

		isActiveCategory()
		{
			return BX.prop.getBoolean(this.props, 'active', false);
		}

		shouldShowTunnels()
		{
			return BX.prop.getBoolean(this.props, 'showTunnels', true);
		}

		shouldShowCounters()
		{
			return BX.prop.getBoolean(this.props, 'showCounters', true);
		}

		onSelectCategory()
		{
			if (!this.enabled)
			{
				return;
			}

			if (!this.isActiveCategory())
			{
				Haptics.impactLight();
			}

			const { category, onSelectCategory } = this.props;
			if (onSelectCategory)
			{
				onSelectCategory(category);
			}
		}

		render()
		{
			const { category: { categoryId } } = this.props;

			return View(
				{
					testId: `CategoryListItem-${categoryId}`,
					style: styles.categoryWrapper(this.enabled),
					onClick: () => this.onSelectCategory(),
				},
				this.renderFunnelIcon(),
				this.renderCategoryContent(),
			);
		}

		renderFunnelIcon()
		{
			const hasTunnelsToRender = this.hasTunnelsToRender();

			return View(
				{
					style: {
						flexDirection: 'column',
						justifyContent: hasTunnelsToRender ? 'flex-start' : 'center',
						margin: 4,
						marginRight: 0,
						marginBottom: this.showBottomBorder ? 5 : 4,

						borderTopLeftRadius: 8,
						borderBottomLeftRadius: 8,
						backgroundColor: this.isActiveCategory() ? ACTIVE_COLOR : AppTheme.colors.bgContentPrimary,
					},
				},
				Image(
					{
						style: styles.funnelIcon(hasTunnelsToRender),
						tintColor: AppTheme.colors.base3,
						svg: {
							content: funnelIcon(),
						},
					},
				),
			);
		}

		renderCategoryContent()
		{
			return View(
				{
					testId: `CategoryListItemIsActive-${this.isActiveCategory()}`,
					style: styles.categoryContentWrapper(this.showBottomBorder),
				},
				View(
					{
						style: {
							...styles.categoryRowWrapper(this.isActiveCategory()),
							...styles.rowIndent(this.hasTunnelsToRender()),
						},
					},
					View(
						{
							style: styles.categoryContentContainer,
						},
						View(
							{
								style: styles.categoryContent,
							},
							this.renderCategoryTitle(),
						),
						View(
							{
								style: styles.actionContainer,
							},
							this.renderCounter(),
							this.renderEditButton(),
						),
					),
					this.hasTunnelsToRender() && View(
						{
							testId: 'CategoryListItemTunnelsContainer',
							style: styles.tunnelsWrapper,
						},
						...this.renderTunnels(),
					),
				),
			);
		}

		hasTunnelsToRender()
		{
			if (!this.shouldShowTunnels())
			{
				return false;
			}

			const {
				category: {
					tunnels: categoryTunnels = [],
				},
			} = this.props;

			return Array.isArray(categoryTunnels) && categoryTunnels.length > 0;
		}

		renderTunnels()
		{
			const {
				category: {
					tunnels: categoryTunnels = [],
				},
			} = this.props;

			return categoryTunnels.map((tunnelId) => Crm.Tunnel({
				tunnelId,
				entityTypeId: this.entityTypeId,
			}));
		}

		renderCategoryTitle()
		{
			const {
				category: {
					name: categoryName,
				},
			} = this.props;

			return View(
				{
					testId: 'CategoryListItemTitle',
					style: styles.categoryTitleContainer,
				},
				Text(
					{
						style: styles.categoryTitle,
						text: categoryName,
						numberOfLines: 1,
						ellipsize: 'end',
					},
				),
				this.renderCurrentCategoryIcon(),
			);
		}

		renderCurrentCategoryIcon()
		{
			if (!this.isActiveCategory())
			{
				return null;
			}

			return Image(
				{
					style: styles.currentCategoryIcon,
					tintColor: AppTheme.colors.base3,
					svg: {
						content: svgImages.currentCategoryIcon,
					},
				},
			);
		}

		renderCounter()
		{
			if (!this.shouldShowCounters())
			{
				return null;
			}

			const {
				category: {
					counter: categoryCounter,
				},
			} = this.props;

			if (Number.isInteger(categoryCounter) && categoryCounter > 0)
			{
				return View(
					{
						testId: 'CategoryListItemCounter',
						style: styles.counterContainer,
					},
					Text(
						{
							text: String(categoryCounter),
							style: styles.counterText,
						},
					),
				);
			}

			return null;
		}

		renderEditButton()
		{
			const {
				readOnly,
				category: {
					id: kanbanSettingsId,
				},
				canUserEditCategory,
			} = this.props;

			if (readOnly || !canUserEditCategory)
			{
				return null;
			}

			return Image(
				{
					testId: 'CategoryListItemEditButton',
					style: styles.editButtonIcon,
					onClick: () => this.onEditCategory(kanbanSettingsId),
					tintColor: AppTheme.colors.base3,
					svg: {
						content: svgImages.editButton,
					},
				},
			);
		}

		onEditCategory(id)
		{
			const { onEditCategory } = this.props;
			if (typeof onEditCategory === 'function')
			{
				onEditCategory(id);
			}
		}
	}

	const styles = {
		rowIndent: (showTunnels = false) => ({
			paddingVertical: showTunnels ? 0 : 10,
			marginVertical: 4,
		}),
		categoryWrapper: (enabled) => ({
			flexDirection: 'row',
			opacity: enabled ? 1 : 0.52,
		}),
		categoryRowWrapper: (isActiveCategory) => ({
			backgroundColor: isActiveCategory ? ACTIVE_COLOR : AppTheme.colors.bgContentPrimary,
			marginRight: 4,
			borderTopRightRadius: 8,
			borderBottomRightRadius: 8,
		}),
		funnelIcon: (showTunnels) => ({
			marginTop: showTunnels ? 10 : 0,
			width: 28,
			height: 28,
			marginLeft: 16,
			marginRight: 14,
		}),
		categoryContentWrapper: (showBottomBorder) => ({
			flex: 1,
			borderBottomWidth: showBottomBorder ? 1 : 0,
			borderBottomColor: AppTheme.colors.bgSeparatorPrimary,
		}),
		categoryContentContainer: {
			flexDirection: 'row',
			alignItems: 'center',
			height: 37,
		},
		categoryContent: {
			flex: 1,
			flexDirection: 'row',
			marginLeft: 3,
		},
		actionContainer: {
			flexDirection: 'row',
			alignItems: 'center',
		},
		categoryTitleContainer: {
			flexShrink: 2,
			flexDirection: 'row',
			alignItems: 'center',
		},
		categoryTitle: {
			flexShrink: 1,
			fontSize: 18,
			color: AppTheme.colors.base1,
		},
		currentCategoryIcon: {
			marginHorizontal: 8,
			width: 18.27,
			height: 13.7,
		},
		counterContainer: {
			marginHorizontal: 10,
			borderRadius: 11,
			paddingVertical: 2,
			paddingHorizontal: 6,
			backgroundColor: AppTheme.colors.accentMainAlert,
			justifyContent: 'center',
			alignItems: 'center',
		},
		counterText: {
			fontSize: 12,
			color: AppTheme.colors.baseWhiteFixed,
			fontWeight: '500',
		},
		editButtonIcon: {
			width: 46,
			height: 37,
		},
		tunnelsWrapper: {
			marginTop: -3,
			marginBottom: 6,
			marginLeft: 3,
		},
	};
	const svgImages = {
		currentCategoryIcon: '<svg width="19" height="15" viewBox="0 0 19 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.47688 14.351L0 8.03873L2.26691 5.82945L6.47688 9.9324L16.0025 0.648926L18.2694 2.85821L6.47688 14.351Z" fill="#0091e3"/></svg>',
		editButton: '<svg width="46" height="37" viewBox="0 0 46 37" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M27.4358 11.0352L29.9863 13.6126L20.0087 23.5634L17.4581 20.986L27.4358 11.0352ZM16.0255 24.673C16.0014 24.7643 16.0272 24.8607 16.0927 24.9279C16.1599 24.995 16.2563 25.0209 16.3476 24.995L19.1988 24.2269L16.7938 21.8227L16.0255 24.673Z" fill="#D5D7DB"/></svg>',
	};

	module.exports = { CategoryListItem };
});
