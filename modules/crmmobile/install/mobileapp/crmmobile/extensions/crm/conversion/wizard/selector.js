/**
 * @module crm/conversion/wizard/selector
 */
jn.define('crm/conversion/wizard/selector', (require, exports, module) => {
	const { Loc } = require('loc');
	const { get, isEmpty } = require('utils/object');
	const { TypeName, Type, TypeId } = require('crm/type');
	const { NotifyManager } = require('notify-manager');
	const { CrmElementSelector } = require('crm/selector/entity/element');

	const SELECTOR_ENTITIES = [TypeName.Company, TypeName.Contact];

	/**
	 * @class ConversionSelector
	 */
	class ConversionSelector
	{
		constructor(props)
		{
			this.props = props;
		}

		openCrmElementSelector(supportedEntities)
		{
			const { layoutWidget } = this.props;
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
						this.closeSelector(selectedEntity);
					},
					onClose: (currentEntities) => {
						selectedEntity = currentEntities;
					},
				},
			});

			selector.show({}, layoutWidget);
		}

		closeSelector(selectedEntity)
		{
			const { onChange } = this.props;
			const { close, ...restParams } = this.prepareSelectedEntities(selectedEntity);
			if (close)
			{
				NotifyManager.hideLoadingIndicatorWithoutFallback();

				return;
			}

			onChange(restParams);
		}

		prepareSelectedEntities(currentEntities)
		{
			const entityIds = {};
			const entityTypeIds = [];

			currentEntities.forEach(({ type, hidden, id: entityId }) => {
				const entityTypeId = type ? Type.resolveIdByName(type) : null;
				if (!entityTypeId || hidden)
				{
					return;
				}

				if (!entityTypeIds.includes(entityTypeId))
				{
					entityTypeIds.push(entityTypeId);
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

			return { entityIds, entityTypeIds, close: entityTypeIds.length === 0 };
		}

		checkPermission(entityTypeName, permissionType)
		{
			const { permissions } = this.props;

			return get(permissions, [entityTypeName.toLowerCase(), permissionType], false);
		}

		static hasConversionSelectorMenuButton({ entityTypeId, isReturnCustomer, permissions })
		{
			const isLead = entityTypeId === TypeId.Lead;
			const supportedEntities = ConversionSelector.getSupportedEntities(permissions);

			return isLead && supportedEntities.length > 0 && !isReturnCustomer;
		}

		static getSupportedEntities(permissions)
		{
			if (isEmpty(permissions))
			{
				return SELECTOR_ENTITIES;
			}

			return SELECTOR_ENTITIES.filter((entityTypeName) => {
				const entityPermission = permissions[Type.resolveIdByName(entityTypeName)];

				return entityPermission.read && entityPermission.write;
			});
		}

		static open(props)
		{
			const conversionSelector = new ConversionSelector(props);
			const supportedEntities = ConversionSelector.getSupportedEntities();

			conversionSelector.openCrmElementSelector(supportedEntities);
		}
	}

	module.exports = { ConversionSelector };
});
