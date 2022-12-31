/**
 * @module crm/entity-tab/type/deal
 */
jn.define('crm/entity-tab/type/deal', (require, exports, module) => {

	const { Alert } = require('alert');
	const { Base: BaseEntityType } = require('crm/entity-tab/type/base');
	const { TypeId, TypeName } = require('crm/type');

	/**
	 * @class Deal
	 */
	class Deal extends BaseEntityType
	{
		/**
		 * @returns {Number}
		 */
		getId()
		{
			return TypeId.Deal;
		}

		/**
		 * @returns {String}
		 */
		getName()
		{
			return TypeName.Deal;
		}

		/**
		 * @returns {Object}
		 */
		getEmptyColumnScreenConfig(data)
		{
			const screenConfig = {
				title: BX.message('M_CRM_ENTITY_TAB_COLUMN_EMPTY_DEAL_TITLE'),
				image: this.getEmptyImage(),
			};

			const { column } = data;
			if (column && column.semantics === 'P')
			{
				screenConfig.description = BX.message('M_CRM_ENTITY_TAB_COLUMN_EMPTY_DESCRIPTION');
			}

			return screenConfig;
		}

		/**
		 * @returns {Object[]}
		 */
		getItemActions(permissions)
		{
			const actions = super.getItemActions(permissions);

			return [
				...actions,
				{
					id: 'exclude',
					title: BX.message('M_CRM_ENTITY_TAB_DEAL_ACTION_EXCLUDE'),
					onClickCallback: this.excludeItem.bind(this),
					onDisableClick: this.showForbiddenActionNotification.bind(this),
					data: {
						svgIcon: '<svg width="24" height="26" viewBox="0 0 24 26" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M1 7.02637C1 6.75022 1.22386 6.52637 1.5 6.52637H18.0202C18.2964 6.52637 18.5202 6.75022 18.5202 7.02637V8.87182C18.5202 9.06899 18.4061 9.2395 18.2403 9.3209C17.9025 9.28291 17.5591 9.2634 17.2111 9.2634C16.7317 9.2634 16.261 9.30044 15.8015 9.37182H1.5C1.22386 9.37182 1 9.14796 1 8.87182V7.02637ZM8.10326 18.3713C8.10326 18.2991 8.1041 18.2272 8.10577 18.1554H1.5C1.22386 18.1554 1 18.3793 1 18.6554V20.5008C1 20.777 1.22386 21.0008 1.5 21.0008H8.48861C8.23796 20.1683 8.10326 19.2855 8.10326 18.3713ZM8.67565 15.1863C9.06947 14.1314 9.65329 13.169 10.3855 12.3409H1.5C1.22386 12.3409 1 12.5647 1 12.8409V14.6863C1 14.9625 1.22386 15.1863 1.5 15.1863H8.67565ZM23.9251 18.4156C23.9251 22.1416 20.9046 25.162 17.1786 25.162C13.4527 25.162 10.4322 22.1416 10.4322 18.4156C10.4322 14.6897 13.4527 11.6692 17.1786 11.6692C20.9046 11.6692 23.9251 14.6897 23.9251 18.4156ZM15.3654 15.1984L13.9616 16.6022L15.7749 18.4154L13.9616 20.2287L15.3654 21.6325L17.1787 19.8192L18.9919 21.6325L20.3957 20.2287L18.5825 18.4154L20.3957 16.6022L18.9919 15.1984L17.1787 17.0116L15.3654 15.1984Z" fill="#767C87"/></svg>',
					},
					isDisabled: !permissions.exclude,
				},
			];
		}

		excludeItem(action, itemId)
		{
			return new Promise((resolve, reject) => {
				Alert.confirm(
					BX.message('M_CRM_ENTITY_TAB_DEAL_ACTION_EXCLUDE'),
					BX.message('M_CRM_ENTITY_TAB_DEAL_ACTION_EXCLUDE_CONFIRMATION'),
					[
						{
							text: BX.message('M_CRM_ENTITY_TAB_DEAL_ACTION_EXCLUDE_CONFIRMATION_OK'),
							type: 'destructive',
							onPress: () => {
								BX.ajax.runComponentAction('bitrix:crm.kanban', 'excludeEntity', {
									mode: 'ajax',
									data: {
										entityType: this.getName(),
										ids: [itemId],
									},
								}).then(() => {
									resolve({
										action: 'delete',
										id: itemId,
									});
								}).catch(({ errors }) => {
									console.error(errors);
									reject();
								});
							},
						},
						{
							type: 'cancel',
							onPress: reject,
						},
					],
				);
			});
		}

		getEmptySearchScreenConfig()
		{
			const config = super.getEmptySearchScreenConfig();
			if (this.params.categoriesCount > 1)
			{
				const entityTypeName = this.getName();
				config.description = BX.message(`M_CRM_ENTITY_TAB_SEARCH_WITH_TWO_OR_MORE_CATEGORIES_EMPTY_${entityTypeName}_DESCRIPTION2`);
			}

			return config;
		}

		getUnsuitableStageScreenConfig(data)
		{
			return {
				title: BX.message(`M_CRM_ENTITY_TAB_COLUMN_USUITABLE_FOR_FILTER_TITLE_${this.getName()}`),
				description: BX.message(`M_CRM_ENTITY_TAB_COLUMN_USUITABLE_FOR_FILTER_DESCRIPTION_${this.getName()}`),
				image: this.getEmptyImage(),
			};
		}
		
		getMenuActions()
		{
			return [
				{
				 	type: UI.Menu.Types.HELPDESK,
				 	data: {
				 		articleCode: '16758628',
				 	},
				},
			];
		}
	}

	module.exports = { Deal };
});
