/**
 * @module crm/conversion/menu
 */
jn.define('crm/conversion/menu', (require, exports, module) => {
	const { Loc } = require('loc');
	const { get } = require('utils/object');
	const { getEntityMessage } = require('crm/loc');
	const { NotifyManager } = require('notify-manager');
	const { TypeName, TypeId, Type } = require('crm/type');
	const { getActionToChangeStage } = require('crm/entity-actions');
	const { CrmElementSelector } = require('crm/selector/entity/element');

	const SELECTOR_ENTITIES = [TypeName.Company, TypeName.Contact];
	const ACTION = 'crmmobile.Conversion.getConversionMenuItems';

	/**
	 * @class ConversionMenu
	 */
	class ConversionMenu
	{
		constructor(props)
		{
			this.props = props;
			this.menu = null;
			this.permissions = null;
			this.isReturnCustomer = false;
		}

		getMenu()
		{
			return this.fetchMenuItems().then((items) => {
				if (items.length === 0)
				{
					return null;
				}

				const { entityTypeId } = this.props;
				const actions = this.getActionsItems(items);
				this.menu = new ContextMenu({
					testId: 'CONVERSION',
					actions,
					params: {
						title: getEntityMessage('MCRM_CONVERSION_MENU_TITLE', entityTypeId),
						helpUrl: helpdesk.getArticleUrl('17596834'),
					},
					layoutWidget: PageManager,
				});

				return this.menu;
			});
		}

		fetchMenuItems()
		{
			const { entityTypeId, entityId } = this.props;

			return BX.ajax.runAction(ACTION, {
				json: { entityTypeId, entityId },
			})
				.then(({ data }) => {
					const { currentItemId, items, permissions, isReturnCustomer } = data;
					this.isReturnCustomer = isReturnCustomer;
					this.permissions = permissions;
					if (currentItemId > 0 && Array.isArray(items))
					{
						return items;
					}

					return [];
				}).catch(console.error);
		}

		getActionsItems(items)
		{
			const menuItems = [];
			items.forEach(({ name, phrase, entityTypeIds }) => {
				menuItems.push({
					id: name,
					sectionCode: 'entities',
					title: phrase,
					onClickCallback: () => this.onClickCallback(entityTypeIds),
				});
			});

			const { entityTypeId } = this.props;
			const isLead = entityTypeId === TypeId.Lead;
			const supportedEntities = this.getSupportedEntities();

			if (isLead && supportedEntities.length > 0 && !this.isReturnCustomer)
			{
				menuItems.push({
					id: 'custom',
					sectionCode: 'custom',
					title: Loc.getMessage('MCRM_CONVERSION_MENU_ITEM_CUSTOM'),
					showArrow: true,
					onClickCallback: () => {
						this.menu.close(() => {
							NotifyManager.showLoadingIndicator();
							setTimeout(() => this.openCrmElementSelector(supportedEntities), 250);
						});
					},
				});
			}

			return menuItems;
		}

		onClickCallback(entityTypeIds)
		{
			this.menu.close(() => {
				const { executeConversion } = this.props;
				if (!entityTypeIds.includes(TypeId.Deal))
				{
					return executeConversion({ entities: entityTypeIds });
				}

				this.openCategoryList(entityTypeIds);
			});
		}

		openCategoryList(entities)
		{
			const { executeConversion } = this.props;
			const { onAction } = getActionToChangeStage();
			onAction({
				entityTypeId: TypeId.Deal,
				title: Loc.getMessage('MCRM_CONVERSION_MENU_CATEGORY_TITLE'),
			}).then((categoryId) => {
				executeConversion({ entities, categoryId });
			});
		}

		openCrmElementSelector(supportedEntities)
		{
			const { executeConversion } = this.props;
			let selectedEntity = [];

			const selector = CrmElementSelector.make({
				entityIds: supportedEntities,
				allowMultipleSelection: true,
				createOptions: {
					enableCreation: false,
				},
				selectOptions: {
					canUnselectLast: true,
					singleEntityByType: true,
				},
				widgetParams: {
					backdrop: {
						horizontalSwipeAllowed: false,
						mediumPositionPercent: 70,
					},
					title: Loc.getMessage('MCRM_CONVERSION_MENU_SELECTOR_TITLE'),
				},
				provider: {
					entities: supportedEntities.map((id) => ({
						id,
						dynamicLoad: true,
						dynamicSearch: true,
						options: {},
						searchable: true,
					})),
					useRawResult: true,
				},
				events: {
					onViewRemoved: () => {
						executeConversion(this.prepareSelectedEntities(selectedEntity));
					},
					onClose: (currentEntities) => {
						selectedEntity = currentEntities;
					},
				},
			});

			selector.show();
		}

		prepareSelectedEntities(currentEntities)
		{
			const entityIds = {};
			const entities = [];

			currentEntities.forEach(({ type, hidden, id: entityId }) => {
				const entityTypeId = type ? Type.resolveIdByName(type) : null;
				if (!entityTypeId || hidden)
				{
					return;
				}

				if (!entities.includes(entityTypeId))
				{
					entities.push(entityTypeId);
				}

				if (entityIds[type])
				{
					entityIds[type].push(entityId);
				}
				else
				{
					entityIds[type] = [entityId];
				}
			});

			return { entityIds, entities, close: entities.length === 0 };
		}

		getSupportedEntities()
		{
			if (!this.permissions)
			{
				return SELECTOR_ENTITIES;
			}

			return SELECTOR_ENTITIES.filter((entityTypeName) => {
				const entityPermission = this.permissions[Type.resolveIdByName(entityTypeName)];

				return entityPermission.read && entityPermission.write;
			});
		}

		checkPermission(entityTypeName, permissionType)
		{
			const { permissions } = this.props;

			return get(permissions, [entityTypeName.toLowerCase(), permissionType], false);
		}

		static create(props)
		{
			const conversionMenu = new ConversionMenu(props);

			return conversionMenu.getMenu();
		}
	}

	module.exports = { ConversionMenu };
});
