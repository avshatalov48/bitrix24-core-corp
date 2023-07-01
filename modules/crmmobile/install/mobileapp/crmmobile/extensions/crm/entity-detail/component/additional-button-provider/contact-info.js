/**
 * @module crm/entity-detail/component/communication-button/contact-info
 */
jn.define('crm/entity-detail/component/communication-button/contact-info', (require, exports, module) => {
	const { TypeId, TypeName, Type } = require('crm/type');
	const { EmailType } = require('layout/ui/fields/email');
	const { ImType } = require('layout/ui/fields/im');
	const { PhoneType } = require('layout/ui/fields/phone');
	const { clone, get, isEmpty } = require('utils/object');

	const CLIENTS = [TypeName.Contact, TypeName.Company];
	const CONNECTION_TYPES = [PhoneType, EmailType, ImType];

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
			const entityData = modelClientInfo.map(({ id, typeName, title, advancedInfo = {} }) => {
				let connectionValues = {};
				const multiFields = get(advancedInfo, 'multiFields', []);

				if (Array.isArray(multiFields) && multiFields.length > 0)
				{
					multiFields.forEach(({ TYPE_ID, ...connectionData }) => {
						const connectionType = TYPE_ID.toLowerCase();
						if (CONNECTION_TYPES.includes(connectionType))
						{
							const connectionValue = prepareConnectionData(connectionData);
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
				else
				{
					connectionValues = getMultiFieldClientInfo(entityModel, `${entityTypeId}_${id}`);
				}

				return {
					id,
					type: typeName,
					title,
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

		const entityInfo = getMultiFieldClientInfo(entityModel);

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

			if (value.hasOwnProperty(entityTypeName))
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

	const getMultiFieldClientInfo = (entityModel, typeId) => {
		const { ENTITY_TYPE_ID, ID } = entityModel;
		const connectionId = typeId || `${ENTITY_TYPE_ID}_${ID}`;
		const contactsInfo = {};

		CONNECTION_TYPES.forEach((connectionType) => {
			const modelValues = get(entityModel, ['MULTIFIELD_DATA', connectionType.toUpperCase(), connectionId], []);
			const connectionValues = modelValues
				.map((connectionValue) => prepareConnectionData(connectionValue))
				.filter(Boolean);

			if (connectionValues.length > 0)
			{
				contactsInfo[connectionType] = connectionValues;
			}
		});

		return contactsInfo;
	};

	const prepareConnectionData = (data) => ({
		value: data.VALUE,
		complexName: data.COMPLEX_NAME,
		valueType: data.VALUE_TYPE,
	});

	module.exports = { prepareClientInfo, addMultiFieldInfo };
});
