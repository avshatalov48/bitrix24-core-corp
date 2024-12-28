/**
 * @module intranet/user-list/src/more-menu
 */
jn.define('intranet/user-list/src/more-menu', (require, exports, module) => {

	const { BaseListMoreMenu } = require('layout/ui/list/base-more-menu');
	const { Loc } = require('loc');

	const iconPrefix = `${currentDomain}/bitrix/mobileapp/intranetmobile/extensions/intranet/user-list/images/more-menu-`;

	/**
	 * @class UserListMoreMenu
	 */
	class UserListMoreMenu extends BaseListMoreMenu
	{
		get icons()
		{
			return {
				companyStructure: `${iconPrefix}company.svg`,
			};
		}

		/**
		 * @param {String} selectedSorting
		 * @param {String} order
		 * @param {Object} callbacks
		 */
		constructor(
			selectedSorting,
			order,
			callbacks = {},
		)
		{
			super([], null, selectedSorting, callbacks);

			this.order = order;

			this.onSelectSorting = callbacks.onSelectSorting;
		}

		/**
		 * @public
		 * @param {String} order
		 */
		setOrder(order)
		{
			this.order = order;
		}

		/**
		 * @private
		 * @returns {String}
		 */
		getOrder()
		{
			return this.order;
		}

		/**
		 * @public
		 * @returns {{svg: {content: string}, callback: ((function(): void)|*), type: string}}
		 */
		getMenuButton()
		{
			return {
				type: 'more',
				id: 'user-list-more',
				testId: 'user-list-more',
				callback: this.openMoreMenu,
			};
		}

		/**
		 * @private
		 * @returns {Array}
		 */
		getMenuItems()
		{
			return [
				this.createMenuItem({
					id: 'companyStructure',
					title: Loc.getMessage('MOBILE_USERS_MORE_MENU_COMPANY_STRUCTURE'),
					checked: false,
					showCheckedIcon: false,
				}),
				{
					type: UI.Menu.Types.HELPDESK,
					data: { articleCode: '20955260' },
				},
			];
		}

		/**
		 * @private
		 * @param event
		 * @param item
		 */
		onMenuItemSelected(event, item)
		{
			if (item.id === 'companyStructure')
			{
				this.openCompanyStructure();
			}
		}

		openCompanyStructure()
		{
			qrauth.open({
				title: Loc.getMessage('MOBILE_USERS_MORE_MENU_COMPANY_STRUCTURE'),
				showHint: true,
				hintText: Loc.getMessage('MOBILE_USERS_MORE_MENU_COMPANY_STRUCTURE_HINT_TEXT'),
				redirectUrl: '/company/vis_structure.php',
				layout,
				analyticsSection: 'visualStructure',
			});
		}
	}

	module.exports = { UserListMoreMenu };
});
