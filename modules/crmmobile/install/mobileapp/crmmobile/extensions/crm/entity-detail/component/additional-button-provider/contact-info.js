/**
 * @module crm/entity-detail/component/communication-button/contact-info
 */
jn.define('crm/entity-detail/component/communication-button/contact-info', (require, exports, module) => {
	const { TypeId, TypeName, Type } = require('crm/type');
	const { clone, get, isEmpty } = require('utils/object');
	const { SelectorProcessing, TYPE_ADVANCED_INFO } = require('crm/selector/utils/processing');

	const CLIENTS = [TypeName.Contact, TypeName.Company];

	/**
	 * @function ButtonProvider
	 */
	const prepareClientInfo = (detailCard) => {
		const { entityModel } = detailCard;
		const communicationClientInfo = getCommunicationClientInfo(entityModel);

		return addMultiFieldInfo(communicationClientInfo, detailCard);
	};

	const getCommunicationClientInfo = (entityModel) => {
		const clientsInfo = {};

		CLIENTS.forEach((entityTypeName) => {
			const entityTypeId = Type.resolveIdByName(entityTypeName);
			const modelClientInfo = get(entityModel, ['CLIENT_INFO', `${entityTypeName}_DATA`], []);
			const entityData = modelClientInfo.map(({ id, typeName, title, advancedInfo = {}, hidden = true }) => {
				let connectionValues = {};
				const multiFields = get(advancedInfo, 'multiFields', []);

				const modelMultiFieldData = get(entityModel, ['MULTIFIELD_DATA'], []);
				if (Array.isArray(multiFields) && multiFields.length > 0)
				{
					multiFields.forEach(({ TYPE_ID, ...connectionData }) => {
						const connectionType = TYPE_ID.toLowerCase();
						if (TYPE_ADVANCED_INFO.includes(TYPE_ID))
						{
							const connectionValue = SelectorProcessing.prepareValue(connectionData);
							if (Array.isArray(connectionValues[connectionType]))
							{
								connectionValues[connectionType].push(connectionValue);
							}
							else
							{
								connectionValues[connectionType] = [connectionValue];
							}
						}
					});
				}
				else if (modelMultiFieldData)
				{
					connectionValues = SelectorProcessing.getMultiFieldClientInfo(modelMultiFieldData, `${entityTypeId}_${id}`);
				}

				return {
					id,
					type: typeName,
					title,
					hidden,
					...connectionValues,
				};
			});

			if (entityData.length > 0)
			{
				clientsInfo[entityTypeName] = entityData;
			}
		});

		return clientsInfo;
	};

	const addMultiFieldInfo = (value, detailCard) => {
		value = clone(value);

		const entityTypeId = detailCard.getEntityTypeId();
		const entityTypeName = Type.resolveNameById(entityTypeId);
		const entityId = detailCard.getEntityId();
		const { entityModel } = detailCard;

		const modelMultiFieldData = get(entityModel, ['MULTIFIELD_DATA'], []);
		const entityInfo = SelectorProcessing.getMultiFieldClientInfo(modelMultiFieldData, `${entityTypeId}_${entityId}`);

		if (!isEmpty(entityInfo))
		{
			const titleKey = entityTypeId === TypeId.Contact ? 'FULL_NAME' : 'TITLE';
			const multiFieldInfo = [
				{
					id: entityId,
					type: entityTypeName,
					title: entityModel[titleKey],
					...entityInfo,
				},
			];

			if (entityTypeName in value)
			{
				value[entityTypeName] = multiFieldInfo;
			}
			else
			{
				value = {
					[entityTypeName]: multiFieldInfo,
					...value,
				};
			}
		}

		return value;
	};

	module.exports = { prepareClientInfo, addMultiFieldInfo };
});
