(() => {
	const require = (ext) => jn.require(ext);
	const { SendingForm } = require('crm/mail/sending-form');

	const EMPTY_CONTACT = { value: '' };

	function buildContactSetForFields(contacts)
	{
		return contacts.map((item) => ({ value: item.email, isEmailHidden: item.isEmailHidden }));
	}

	function buildContactListForBindings(contacts)
	{
		const bindingsList = [];
		Object.entries(contacts).forEach(([key, item]) => {
			const {
				typeName = 'contacts',
				id = '',
				name = '',
			} = item;

			let {
				email = [],
			} = item;

			if (!Array.isArray(email))
			{
				email = [
					{
						value: email,
					},
				];
			}

			bindingsList.push({
				email,
				typeName,
				id,
				name,
			});
		});

		return bindingsList;
	}

	BX.onViewLoaded(() => {
		const replyMessageBody = BX.componentParameters.get('reply_message_body', null);
		const subject = BX.componentParameters.get('subject', '');
		const body = BX.componentParameters.get('body', '');
		const files = BX.componentParameters.get('files', []);
		const isSendFiles = BX.componentParameters.get('isSendFiles', false);
		const senders = BX.componentParameters.get('senders', []);
		const clients = BX.componentParameters.get('clients', []);
		const clientIdsByType = BX.componentParameters.get('clientIdsByType', {
			contacts: [],
			company: [],
		});

		let to = BX.componentParameters.get('contacts', []);
		let cc = BX.componentParameters.get('cc', []);

		const bindingsData = buildContactListForBindings(clients);

		to = to.length > 0 ? buildContactSetForFields(to) : [EMPTY_CONTACT];
		cc = cc.length > 0 ? buildContactSetForFields(cc) : [EMPTY_CONTACT];

		const sendingForm = new SendingForm({
			bindingsData,
			senders,
			clientIdsByType,
			clients,
			replyMessageBody,
			to,
			cc,
			subject,
			body,
			files,
			isSendFiles,
			ownerEntity: BX.componentParameters.get('owner', {
				inResponseToMessage: 0,
				ownerType: 0,
				ownerId: 0,
			}),
			widget: this.layout,
		});
		layout.showComponent(sendingForm);
	});
})();
