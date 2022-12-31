/**
 * @module crm/entity-detail/component/additional-button-provider
 */
jn.define('crm/entity-detail/component/additional-button-provider', (require, exports, module) => {
	const { animateScrollButton } = require('crm/entity-detail/component/communication-button/button-animate');
	const { preparationContactInfo } = require('crm/entity-detail/component/communication-button/contact-info');
	const { CommunicationFloatingButton } = require('crm/communication/floating-button');
	const { TypeId, Type } = require('crm/type');

	/**
	 * @function additionalButtonProvider
	 * @param detailCard
	 * @returns {CommunicationFloatingButton[]}
	 */
	const additionalButtonProvider = (detailCard) => {

		const entityTypeId = detailCard.getEntityTypeId();
		const entityId = detailCard.getEntityId();
		const { customEventEmitter } = detailCard;
		const isShow = [TypeId.Contact, TypeId.Company, TypeId.Deal].includes(entityTypeId);

		if (!isShow || !entityId)
		{
			return [];
		}

		const ownerInfo = {
			ownerId: entityId,
			ownerTypeName: Type.resolveNameById(entityTypeId),
		};
		const button = new CommunicationFloatingButton();

		const handleOnReady = (entityModel) => {

			const contactInfo = preparationContactInfo({
				entityModel,
				entityTypeId,
				entityId,
			});

			button.setValue(contactInfo, ownerInfo);
		};

		customEventEmitter.on('UI.EntityEditor.Model::onReady', handleOnReady);
		customEventEmitter.on('DetailCard::onScroll', (params, tabId) => animateScrollButton(params, button, tabId, detailCard.activeTab));
		customEventEmitter.on('Communication::onUpdate', (updateInfo) => {
			button.setValue(updateInfo, ownerInfo);
		});

		return [button];
	};

	module.exports = { additionalButtonProvider };

});