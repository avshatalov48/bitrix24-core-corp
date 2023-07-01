/**
 * @module crm/communication/communication-selector
 */
jn.define('crm/communication/communication-selector', (require, exports, module) => {
	const { CommunicationMenu } = require('communication/menu');
	const { PhoneType } = require('communication/connection');
	const { getEntityMessage } = require('crm/loc');

	/**
	 * @class CommunicationSelector
	 */
	class CommunicationSelector
	{
		static show({ layout, communications, selectedPhoneId, onPhoneSelectCallback, ownerInfo, typeId })
		{
			const selector = new CommunicationSelector({
				communications,
				selectedPhoneId,
				onPhoneSelectCallback,
				ownerInfo,
				typeId,
			});

			return selector.communicationMenu.show(layout);
		}

		static hasActions({ communications, selectedPhoneId, typeId })
		{
			const selector = new CommunicationSelector({
				communications,
				selectedPhoneId,
				typeId,
			});

			return selector.communicationMenu.getActions().length > 0;
		}

		constructor({ communications, selectedPhoneId, onPhoneSelectCallback, ownerInfo, typeId })
		{
			this.communications = communications;
			this.selectedPhoneId = selectedPhoneId;
			this.onPhoneSelectCallback = onPhoneSelectCallback;

			this.communicationMenu = new CommunicationMenu({
				ownerInfo,
				value: this.getPreparedPhones(),
				connections: [PhoneType],
				title: getEntityMessage(
					'M_CRM_COMMUNICATION_SELECTOR_TITLE',
					typeId,
				),
			});
		}

		getPreparedPhones()
		{
			const result = {};

			if (!this.communications)
			{
				return result;
			}

			this.communications.forEach((communication) => {
				if (Array.isArray(communication.phones) && communication.phones.length > 0)
				{
					const type = communication.entityTypeName.toLowerCase();
					if (!result[type])
					{
						result[type] = [];
					}

					const phone = this.getCommunicationPhones(communication.phones);

					result[type].push({
						hidden: false,
						id: communication.entityId,
						title: communication.caption,
						type,
						phone,
					});
				}
			});

			return result;
		}

		getCommunicationPhones(phones)
		{
			const result = [];

			phones.forEach((phone) => {
				const { value, type, typeLabel, id } = phone;

				if (!this.selectedPhoneId)
				{
					this.selectedPhoneId = id;
				}

				const isSelected = (id === this.selectedPhoneId);

				result.push({
					id,
					value,
					type,
					isSelected,
					showSelectedImage: isSelected,
					complexName: typeLabel,
					onClickCallback: this.onPhoneSelectCallback,
				});
			});

			return result;
		}
	}

	module.exports = { CommunicationSelector };
});
