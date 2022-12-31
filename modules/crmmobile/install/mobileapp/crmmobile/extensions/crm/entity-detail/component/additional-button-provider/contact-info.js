/**
 * @module crm/entity-detail/component/communication-button/contact-info
 */
jn.define('crm/entity-detail/component/communication-button/contact-info', (require, exports, module) => {

	const { TypeId, TypeName, Type } = require('crm/type');
	const { EmailType } = require('layout/ui/fields/email');
	const { ImType } = require('layout/ui/fields/im');
	const { PhoneType } = require('layout/ui/fields/phone');
	const { get } = require('utils/object');

	const CLIENTS = [TypeName.Contact, TypeName.Company];
	const CONNECTION_TYPES = [PhoneType, ImType, EmailType];

	/**
	 * @function ButtonProvider
	 */
	const preparationContactInfo = ({ entityModel, entityTypeId, entityId }) => {
		const entityTypeName = Type.resolveNameById(entityTypeId);
		const isDeal = entityTypeId === TypeId.Deal;
		const titleKey = entityTypeId === TypeId.Company ? 'TITLE' : 'FULL_NAME';

		return isDeal
			? getDialClientInfo(entityModel)
			: {
				[entityTypeName]: [
					{
						id: entityId,
						type: entityTypeName,
						title: entityModel[titleKey],
						...getContactClientInfo(entityModel),
					},
				],
			};

	};

	const getDialClientInfo = (entityModel) => CLIENTS.reduce((acc, entityType) => {
		const entityData = get(entityModel, ['CLIENT_INFO', `${entityType}_DATA`], []);
		const entityTypeId = Type.resolveIdByName(entityType);

		return {
			...acc,
			[entityType]: entityData.map(({ id, typeName, title }) => ({
					id,
					type: typeName,
					title,
					...getContactClientInfo(entityModel, `${entityTypeId}_${id}`),
				}),
			),
		};
	}, {});

	const getContactClientInfo = (entityModel, typeId) => {
		const { ID, ENTITY_TYPE_ID } = entityModel;
		const connectionId = typeId || `${ENTITY_TYPE_ID}_${ID}`;

		return CONNECTION_TYPES.reduce((acc, connectionType) => {
			const modelValues = get(entityModel, ['MULTIFIELD_DATA', connectionType.toUpperCase(), connectionId], []);
			const connectionValues = modelValues
				.map((connectionValue) => ({
					value: connectionValue.VALUE,
					complexName: connectionValue.COMPLEX_NAME,
					valueType: connectionValue.VALUE_TYPE,
				}))
				.filter(Boolean);

			return !connectionValues.length ? acc : { ...acc, [connectionType]: connectionValues };

		}, {});
	};

	module.exports = { preparationContactInfo };

});