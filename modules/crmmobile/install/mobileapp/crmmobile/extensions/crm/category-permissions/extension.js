/**
 * @module crm/category-permissions
 */
jn.define('crm/category-permissions', (require, exports, module) => {
	const { Type } = require('crm/type');
	const { getEntityMessage } = require('crm/loc');
	const { ImageAfterTypes } = require('layout/ui/context-menu/item');
	const { ContextMenu } = require('layout/ui/context-menu');
	const { qrauth } = require('qrauth/utils');
	const { Color, Indent } = require('tokens');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Text3, Text4 } = require('ui-system/typography/text');

	const ACCESS = {
		ALL_FOR_ALL: 'X',
		NONE_FOR_ALL: '',
		OWN_FOR_ALL: 'A',
	};

	/**
	 * @class CategoryPermissions
	 */
	class CategoryPermissions extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				access: this.props.access,
			};

			this.menu = null;
		}

		get entityTypeId()
		{
			return BX.prop.getInteger(this.props, 'entityTypeId', 2);
		}

		get categoriesEnabled()
		{
			return BX.prop.getBoolean(this.props, 'categoriesEnabled', false);
		}

		get layout()
		{
			return BX.prop.get(this.props, 'layout', PageManager);
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						backgroundColor: Color.bgContentPrimary.toHex(),
						alignItems: 'center',
						padding: Indent.XL3.toNumber(),
					},
					onClick: () => this.showMenu(),
				},
				IconView({
					icon: Icon.LOCK,
					color: Color.base2,
					size: 28,
				}),
				View(
					{
						style: {
							flex: 1,
							flexDirection: 'column',
							marginLeft: Indent.M.toNumber(),
							marginRight: Indent.XL.toNumber(),
						},
					},
					Text3({
						text: this.getCurrentAccessText(),
						color: Color.base1,
					}),
					Text4({
						text: this.getPermissionTitle(),
						color: Color.base3,
					}),
				),
				IconView({
					icon: Icon.CHEVRON_TO_THE_RIGHT,
					color: Color.base4,
					size: 24,
				}),
			);
		}

		getPermissionTitle()
		{
			if (this.categoriesEnabled)
			{
				return BX.message('M_CRM_CATEGORY_PERMISSION_TITLE2');
			}

			return BX.message('M_CRM_CATEGORY_PERMISSION_ENTITY_TITLE');
		}

		getCurrentAccessText()
		{
			if (this.state.access === ACCESS.ALL_FOR_ALL)
			{
				return this.getAllForAllTitle();
			}

			if (this.state.access === ACCESS.OWN_FOR_ALL)
			{
				return this.getOwnForAllTitle();
			}

			if (this.state.access === ACCESS.NONE_FOR_ALL)
			{
				return BX.message('M_CRM_CATEGORY_PERMISSION_NONE_FOR_ALL_MSGVER_1');
			}

			return BX.message('M_CRM_CATEGORY_PERMISSION_MENU_CUSTOM');
		}

		getAllForAllTitle()
		{
			return getEntityMessage('M_CRM_CATEGORY_PERMISSION_ALL_FOR_ALL', this.entityTypeId);
		}

		getOwnForAllTitle()
		{
			return getEntityMessage('M_CRM_CATEGORY_PERMISSION_OWN_FOR_ALL', this.entityTypeId);
		}

		showMenu()
		{
			this.menu = new ContextMenu({
				actions: this.getMenuActions(),
				titlesBySectionCode: {
					managers: Type.isDynamicTypeById(this.entityTypeId)
						? BX.message('M_CRM_CATEGORY_PERMISSION_ITEM_PRIMARY_SECTION')
						: BX.message('M_CRM_CATEGORY_PERMISSION_DEAL_PRIMARY_SECTION'),
					additional: BX.message('M_CRM_CATEGORY_PERMISSION_MENU_ADDITIONAL_ACCESS'),
				},
				params: {
					title: this.getPermissionTitle(),
					showCancelButton: false,
					showActionLoader: false,
				},
				layoutWidget: this.layout,
			});

			this.menu.show(this.layout);
		}

		getMenuActions()
		{
			const { access } = this.state;

			return [
				{
					id: 'allForAll',
					title: this.getAllForAllTitle(),
					sectionCode: 'managers',
					isSelected: access === ACCESS.ALL_FOR_ALL,
					icon: Icon.GROUP,
					showSelectedImage: true,
					onClickCallback: () => this.setAccess(ACCESS.ALL_FOR_ALL),
				},
				{
					id: 'ownForAll',
					title: this.getOwnForAllTitle(),
					sectionCode: 'managers',
					isSelected: access === ACCESS.OWN_FOR_ALL,
					icon: Icon.PERSON,
					showSelectedImage: true,
					onClickCallback: () => this.setAccess(ACCESS.OWN_FOR_ALL),
				},
				{
					id: 'noneForAll',
					title: BX.message('M_CRM_CATEGORY_PERMISSION_NONE_FOR_ALL_MSGVER_1'),
					sectionCode: 'managers',
					isSelected: access === ACCESS.NONE_FOR_ALL,
					icon: Icon.BAN,
					showSelectedImage: true,
					onClickCallback: () => this.setAccess(ACCESS.NONE_FOR_ALL),
				},
				this.categoriesEnabled && {
					id: 'from-tunnels',
					sectionCode: 'additional',
					title: BX.message('M_CRM_CATEGORY_PERMISSION_MENU_COPY_FROM_TUNNELS2_MSGVER_1'),
					icon: Icon.COPY,
					onClickCallback: (id, parentId, parentParams) => this.openCategoryList(parentParams),
				},
				{
					id: 'custom',
					sectionCode: 'additional',
					title: BX.message('M_CRM_CATEGORY_PERMISSION_MENU_CUSTOM'),
					isSelected: this.isCustomAccess(access),
					showSelectedImage: true,
					icon: Icon.SETTINGS,
					data: {
						svgIconAfter: {
							type: ImageAfterTypes.WEB,
						},
					},
					onClickCallback: () => this.openOnDesktop(),
				},
			].filter(Boolean);
		}

		isCustomAccess(access)
		{
			return access !== ACCESS.OWN_FOR_ALL
				&& access !== ACCESS.NONE_FOR_ALL
				&& access !== ACCESS.ALL_FOR_ALL;
		}

		openOnDesktop()
		{
			this.menu.close(() => {
				qrauth.open({
					redirectUrl: '/crm/configs/perms/',
					analyticsSection: 'crm',
					layout: this.layout,
				});
			});

			return Promise.resolve({ closeMenu: false });
		}

		setAccess(access)
		{
			return new Promise((resolve) => {
				this.setState({ access }, () => {
					this.onChange(access);
					resolve();
				});
			});
		}

		onChange(access)
		{
			const { onChange } = this.props;
			if (typeof onChange === 'function')
			{
				onChange(access);
			}
		}

		async openCategoryList({ parentWidget })
		{
			const { CategoryListView } = await requireLazy('crm:category-list-view');

			return new Promise((resolve, reject) => {
				CategoryListView.open(
					{
						entityTypeId: this.entityTypeId,
						onSelectCategory: ({ categoryId }) => this.setAccess(categoryId).then(resolve),
						readOnly: true,
						enableSelect: true,
						showCounters: false,
						showTunnels: false,
						currentCategoryId: this.state.access,
					},
					{},
					parentWidget,
				).then((layout) => layout.setListener((eventName) => {
					if (eventName === 'onViewHidden')
					{
						reject();
					}
				})).catch(reject);
			});
		}
	}

	module.exports = { CategoryPermissions };
});
