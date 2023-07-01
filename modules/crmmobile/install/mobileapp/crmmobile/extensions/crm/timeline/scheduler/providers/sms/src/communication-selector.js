/**
 * @module crm/timeline/scheduler/providers/sms/communication-selector
 */
jn.define('crm/timeline/scheduler/providers/sms/communication-selector', (require, exports, module) => {
	const { CommunicationMenu } = require('communication/menu');
	const { PhoneType } = require('communication/connection');
	const { getEntityMessage } = require('crm/loc');

	/**
	 * @class CommunicationSelector
	 */
	class CommunicationSelector
	{
		static show({ layout, communications, selectedPhone, onPhoneSelectCallback, ownerInfo, typeId })
		{
			const self = new CommunicationSelector({
				communications,
				selectedPhone,
				onPhoneSelectCallback,
				ownerInfo,
				typeId,
			});

			self.communicationMenu.show(layout);
		}

		constructor({ communications, selectedPhone, onPhoneSelectCallback, ownerInfo, typeId })
		{
			this.communications = communications;
			this.selectedPhone = selectedPhone;
			this.onPhoneSelectCallback = onPhoneSelectCallback;

			this.communicationMenu = new CommunicationMenu({
				ownerInfo,
				value: this.getPreparedPhones(),
				connections: [PhoneType],
				title: getEntityMessage(
					'M_CRM_TIMELINE_SCHEDULER_SMS_CONTACTS_SELECTOR_TITLE',
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
				const { value, type, typeLabel } = phone;
				const isSelected = (value === this.selectedPhone);

				result.push({
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
