/**
 * @module crm/category-list/item
 */
jn.define('crm/category-list/item', (require, exports, module) => {
	const { Haptics } = require('haptics');
	const { PureComponent } = require('layout/pure-component');
	const { Color, Indent, Corner } = require('tokens');
	const { Text2 } = require('ui-system/typography/text');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { BadgeCounter, BadgeCounterDesign } = require('ui-system/blocks/badges/counter');
	const { Tunnel } = require('crm/tunnel');

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
			const hasTunnelsToRender = this.hasTunnelsToRender();

			return View(
				{
					testId: `CategoryListItem-${categoryId}`,
					onClick: () => this.onSelectCategory(),
				},
				this.renderFocus(),
				View(
					{
						style: {
							flexDirection: 'row',
						},
					},
					IconView({
						color: Color.base1,
						icon: Icon.CRM,
						size: 30,
						style: {
							marginLeft: Indent.XL3.toNumber(),
							marginRight: Indent.XL2.toNumber(),
							marginVertical: hasTunnelsToRender ? Indent.L.toNumber() : Indent.XL.toNumber(),
							opacity: this.enabled ? 1 : 0.52,
						},
					}),
					View(
						{
							style: {
								flexDirection: 'row',
								borderBottomWidth: this.showBottomBorder ? 1 : 0,
								borderBottomColor: Color.bgContentSecondary.toHex(),
								flexGrow: 2,
								paddingVertical: Indent.L.toNumber(),
								paddingRight: Indent.XL3.toNumber(),
							},
						},
						View(
							{
								style: {
									flexDirection: 'column',
									flexGrow: 2,
									justifyContent: 'center',
									opacity: this.enabled ? 1 : 0.52,
								},
							},
							this.renderCategoryTitle(),
							this.hasTunnelsToRender() && View(
								{
									testId: 'CategoryListItemTunnelsContainer',
									style: {
										flexGrow: 2,
										marginTop: Indent.M.toNumber(),
									},
								},
								...this.renderTunnels(),
							),
						),
						View(
							{
								style: {
									flexDirection: 'row',
									alignItems: 'flex-start',
									opacity: this.enabled ? 1 : 0.52,
								},
							},
							this.renderCounter(),
							this.renderEditButton(),
						),
					),
				),
			);
		}

		renderFocus()
		{
			return this.isActiveCategory() && View(
				{
					style: {
						opacity: this.enabled ? 1 : 0.52,
						position: 'absolute',
						top: 4,
						bottom: 6,
						left: 4,
						right: 4,
						backgroundColor: Color.accentSoftBlue2.toHex(),
						borderRadius: Corner.M.toNumber(),
					},
				},
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

			return categoryTunnels.map((tunnelId) => View(
				{
					style: {
						marginBottom: Indent.M.toNumber(),
					},
				},
				Tunnel({
					tunnelId,
					entityTypeId: this.entityTypeId,
				}),
			));
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
					style: {
						flexShrink: 2,
						flexDirection: 'row',
					},
				},
				Text2(
					{
						style: {
							flex: 1,
						},
						text: categoryName,
						color: Color.base0,
						numberOfLines: 1,
						ellipsize: 'end',
					},
				),
				this.isActiveCategory() && IconView({
					icon: Icon.CHECK,
					size: 24,
					color: Color.accentMainPrimary,
					style: {
						marginHorizontal: Indent.XS2.toNumber(),
					},
				}),
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
				return BadgeCounter({
					testId: 'CategoryListItemCounter',
					value: categoryCounter,
					design: BadgeCounterDesign.ALERT,
					style: {
						marginLeft: Indent.XL2.toNumber(),
						alignSelf: this.hasTunnelsToRender() ? 'flex-start' : 'center',
					},
				});
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

			return IconView({
				testId: 'CategoryListItemEditButton',
				color: Color.base4,
				icon: Icon.EDIT,
				size: 24,
				onClick: () => this.onEditCategory(kanbanSettingsId),
				style: {
					marginLeft: Indent.XL2.toNumber(),
					alignSelf: this.hasTunnelsToRender() ? 'flex-start' : 'center',
				},
			});
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

	module.exports = { CategoryListItem };
});
