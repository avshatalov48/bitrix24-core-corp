/**
 * @module crm/entity-detail/component/additional-button-provider
 */
jn.define('crm/entity-detail/component/additional-button-provider', (require, exports, module) => {
	const { CommunicationFloatingButton } = require('crm/communication/floating-button');
	const {
		prepareClientInfo,
		addMultiFieldInfo,
	} = require('crm/entity-detail/component/communication-button/contact-info');
	const { Type } = require('crm/type');
	const { clone, get } = require('utils/object');

	/**
	 * @function additionalButtonProvider
	 * @param {DetailCardComponent} detailCard
	 * @returns {CommunicationFloatingButton[]}
	 */
	const additionalButtonProvider = (detailCard) => {
		const entityTypeId = detailCard.getEntityTypeId();

		if (!Type.existsById(entityTypeId) || detailCard.isNewEntity())
		{
			return [];
		}

		const hasTelegramConnector = get(detailCard.getComponentParams(), 'connectors.telegram', false);
		const isGoToChatAvailable = get(detailCard.getComponentParams(), 'isGoToChatAvailable', false);
		const openLinesAccess = get(detailCard.getComponentParams(), 'permissions.openLinesAccess', false);

		const button = new CommunicationFloatingButton({
			showTelegramConnection: (!hasTelegramConnector && isGoToChatAvailable),
		});
		button.setPermissions({ openLinesAccess });
		const { customEventEmitter } = detailCard;

		customEventEmitter.on('UI.Fields.Client::onUpdate', (eventArgs) => handleClientFieldUpdate(
			eventArgs,
			detailCard,
			button,
		));

		customEventEmitter.on('DetailCard::onTabContentLoaded', (eventArgs) => handleEditorTabLoaded(
			eventArgs,
			detailCard,
			button,
		));

		return [button];
	};

	/**
	 * @function handleEditorTabLoaded
	 * @param {string} tabId
	 * @param {DetailCardComponent} detailCard
	 * @param {CommunicationFloatingButton} button
	 */
	const handleEditorTabLoaded = (tabId, detailCard, button) => {
		if (tabId !== 'main' || !detailCard.hasEntityModel())
		{
			return;
		}

		const value = prepareClientInfo(detailCard);

		button.setValue(value, getOwnerInfo(detailCard));
	};

	/**
	 * @function handleClientFieldUpdate
	 * @param {{uid: string, isEmpty: boolean, canAdd: boolean, isMyCompany: boolean, value: object, compound: ?array, permissions: object}} eventArgs
	 * @param {DetailCardComponent} detailCard
	 * @param {CommunicationFloatingButton} button
	 */
	const handleClientFieldUpdate = (
		{
			uid,
			isEmpty,
			canAdd,
			isMyCompany,
			value,
			compound,
			permissions,
		},
		detailCard,
		button,
	) => {
		if (isMyCompany || (isEmpty && !canAdd))
		{
			return;
		}

		value = clone(value);

		Object.keys(value).forEach((key) => {
			value[key.toUpperCase()] = value[key];
			delete value[key];
		});

		value = addMultiFieldInfo(value, detailCard);

		button.setUid(uid);
		button.setPermissions({ ...button.permissions, ...permissions });
		button.setValue(value, getOwnerInfo(detailCard), compound);
	};

	const getOwnerInfo = (detailCard) => {
		const entityTypeId = detailCard.getEntityTypeId();
		const entityTypeName = Type.resolveNameById(entityTypeId);
		const entityId = detailCard.getEntityId();

		return {
			ownerTypeName: entityTypeName,
			ownerId: entityId,
		};
	};

	module.exports = { additionalButtonProvider };
});
