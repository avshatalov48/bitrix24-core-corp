(() => {
	const require = (ext) => jn.require(ext);
	const { SendingForm } = require('crm/mail/sending-form');

	const EMPTY_CONTACT = { value: '' };

	function buildContactSetForFields(contacts)
	{
		return contacts.map((item) => ({ value: item.email }));
	}

	function buildContactMapForBindings(contacts)
	{
		const bindingsMap = {};
		Object.entries(contacts).forEach(([key, item]) => {
			const {
				email = '',
				typeName = 'contacts',
				id = '',
				name = '',
			} = item;

			bindingsMap[item.email] = {
				email,
				typeName,
				id,
				name,
			};
		});
		return bindingsMap;
	}

	BX.onViewLoaded(() => {
		const replyMessageBody = BX.componentParameters.get('reply_message_body', null);
		const subject = BX.componentParameters.get('subject', '');
		const body = BX.componentParameters.get('body', '');
		const files = BX.componentParameters.get('files', []);
		const isSendFiles = BX.componentParameters.get('isSendFiles', false);
		const senders = BX.componentParameters.get('senders', []);
		const clients = BX.componentParameters.get('clients', []);

		let to = BX.componentParameters.get('contacts', []);
		let cc = BX.componentParameters.get('cc', []);

		const bindingsData = {
			...buildContactMapForBindings(clients),
			...buildContactMapForBindings(to),
			...buildContactMapForBindings(cc),
		};

		to = to.length > 0 ? buildContactSetForFields(to) : [EMPTY_CONTACT];
		cc = cc.length > 0 ? buildContactSetForFields(cc) : [EMPTY_CONTACT];

		const sendingForm = new SendingForm({
			bindingsData,
			senders,
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
