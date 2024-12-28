/**
 * @module crm/communication/communication-selector
 */
jn.define('crm/communication/communication-selector', (require, exports, module) => {
	const { CommunicationMenu } = require('communication/menu');
	const { PhoneType, EmailType } = require('communication/connection');
	const { Type } = require('crm/type');
	const { getEntityMessage } = require('crm/loc');

	/**
	 * @class CommunicationSelector
	 */
	class CommunicationSelector
	{
		static show({ layout, communications, selectedId, onSelectCallback, ownerInfo, typeId })
		{
			const selector = new CommunicationSelector({
				communications,
				selectedId,
				onSelectCallback,
				ownerInfo,
				typeId,
			});

			return selector.communicationMenu.show(layout);
		}

		static hasActions({ communications, selectedId, typeId })
		{
			const selector = new CommunicationSelector({
				communications,
				selectedId,
				typeId,
			});

			return selector.communicationMenu.getActions().length > 0;
		}

		constructor({ communications, selectedId, onSelectCallback, ownerInfo, typeId })
		{
			this.communications = communications;
			this.selectedId = selectedId;
			this.onSelectCallback = onSelectCallback;

			this.communicationMenu = new CommunicationMenu({
				ownerInfo,
				value: this.getPreparedValues(),
				connections: [PhoneType, EmailType],
				title: getEntityMessage(
					'M_CRM_COMMUNICATION_SELECTOR_TITLE',
					typeId,
				),
				analyticsSection: 'crm',
			});
		}

		getPreparedValues()
		{
			const result = {};

			if (!this.communications)
			{
				return result;
			}

			this.communications.forEach((communication) => {
				const type = Type.resolveNameById(communication.entityTypeId).toLowerCase();
				if (!result[type])
				{
					result[type] = [];
				}

				let phone = [];
				if (Array.isArray(communication.phones) && communication.phones.length > 0)
				{
					phone = this.getValues(communication.phones);
				}

				let email = [];
				if (Array.isArray(communication.emails) && communication.emails.length > 0)
				{
					email = this.getValues(communication.emails);
				}

				result[type].push({
					hidden: false,
					id: communication.entityId,
					title: communication.caption,
					type,
					phone,
					email,
				});
			});

			return result;
		}

		getValues(communications)
		{
			const values = [];

			communications.forEach((communication) => {
				const { value, type, typeLabel, id } = communication;

				if (!this.selectedId)
				{
					this.selectedId = id;
				}

				const isSelected = (id === this.selectedId);

				values.push({
					id,
					value,
					type,
					isSelected,
					showSelectedImage: isSelected,
					complexName: typeLabel,
					onClickCallback: this.onSelectCallback,
				});
			});

			return values;
		}
	}

	module.exports = { CommunicationSelector };
});
