/**
 * @module crm/mail/message/tools/connector
 */
jn.define('crm/mail/message/tools/connector', (require, exports, module) => {

	function buildSender(email, senders)
	{
		let name = email;

		Object.entries(senders).some(([key, value]) => {
			if (value.email === email && value.name)
			{
				name = value.name;
				return true;
			}
		});

		return `${name} <${email}>`;
	}

	function buildContactData(contact, bindingsData = {})
	{
		return contact.fields.map((item) => {
			if (item.value)
			{
				let augmentedContact = {};

				if (bindingsData[item.value])
				{
					const {
						typeName,
						id,
						name,
					} = bindingsData[item.value];

					augmentedContact = {
						email: item.value,
						entityType: typeName,
						entityId: id,
						name,
					};
				}
				else
				{
					augmentedContact = {
						email: item.value,
						entityType: 'contacts',
					};
				}
				return JSON.stringify(augmentedContact);
			}
		}).filter(Boolean);
	}

	function buildFieldValue(data)
	{
		if (data && data.fields[0] && data.fields[0].value)
		{
			return data.fields[0].value;
		}

		return '';
	}

	/**
	 * @function sendMessage
	 */
	function sendMessage(props)
	{
		const {
			senders,
			bindingsData,
			fileTokens,
			message,
			from,
			to,
			ownerType,
			ownerId,
			inResponseToMessage,
		} = props;

		const subject = buildFieldValue(props.subject);

		const HTML_CONTENT_TYPE = 3;
		const CRM_ACTIVITY_STORAGE_TYPE = 3;

		const data = {
			fileTokens,
			from: buildSender(buildFieldValue(from), senders),
			to: buildContactData(to, bindingsData),
			subject,
			ownerType,
			ownerId,
			ownerRcpt: 'Y',
			storageTypeID: CRM_ACTIVITY_STORAGE_TYPE,
			message: buildFieldValue(message),
			content_type: HTML_CONTENT_TYPE,
			bindings: [{
				entityType: ownerType,
				entityId: ownerId,
			}],
		};

		const cc = buildContactData(props.cc, bindingsData);
		const bcc = buildContactData(props.bcc, bindingsData);

		if (cc)
		{
			data.cc = cc;
		}

		if (bcc)
		{
			data.bcc = bcc;
		}

		if (inResponseToMessage)
		{
			data.REPLIED_ID = inResponseToMessage;
		}

		return BX.ajax.runAction('crm.api.mail.message.sendMessage', {
			data: {
				data,
			},
		});
	}

	/**
	 * @function deleteMessage
	 */
	function deleteMessage(props)
	{
		const {
			id,
			ownerType,
			ownerId,
			successAction,
			failureAction,
		} = props;

		let {
			excludeFromCrm = false,
			markAsSpam = false,
		} = props;

		excludeFromCrm = excludeFromCrm === true ? 'Y' : 'N';

		markAsSpam = markAsSpam === true ? 'Y' : 'N';

		const data = {
			OWNER_ID: ownerId,
			OWNER_TYPE: ownerType,
			IS_SKIP: excludeFromCrm,
			IS_SPAM: markAsSpam,
			ITEM_ID: id,
		};

		return BX.ajax.runAction('crm.api.mail.message.deleteMessage', {
			data: {
				data,
			},
		}).then((response) => {
			if (Number(id) === Number(response.data.DELETED_ITEM_ID))
			{
				successAction();
			}
			else
			{
				failureAction();
			}
		}).catch((response) => {
			failureAction();
		});
	}

	/**
	 * @function fetchCanUseMail
	 * @returns {Promise}
	 */
	function fetchCanUseMail()
	{
		return BX.ajax.runAction('crm.api.mail.message.canUseMail');
	}

	/**
	 * @function getContactDeal
	 */
	function getContactsPromise(ownerId, ownerTypeName, uploadClients = true, uploadSenders = true)
	{
		if (uploadClients === false && uploadSenders === false)
		{
			return Promise.resolve({ data: {} });
		}

		return BX.ajax.runAction('crm.api.mail.message.getEntityContacts', {
			data: {
				ownerId,
				ownerTypeName,
				uploadClients: (uploadClients ? 1 : 0),
				uploadSenders: (uploadClients ? 1 : 0),
			},
		});
	}

	/**
	 * @function getChainPromise
	 */
	function getChainPromise(id)
	{
		return BX.ajax.runAction('crm.api.mail.message.getChain', {
			data: {
				id,
			},
		});
	}

	/**
	 * @function getFilesDataPromise
	 */
	function getFilesDataPromise(id)
	{
		return BX.ajax.runAction('crm.api.mail.message.getMessageFilesLinkMessages', {
			data: {
				id,
			},
		});
	}

	/**
	 * @function getBodyPromise
	 */
	function getBodyPromise(id)
	{
		return BX.ajax.runAction('crm.api.mail.message.getMessageBody', {
			data: {
				id,
			},
		});
	}

	module.exports = {
		getContactsPromise,
		getBodyPromise,
		getFilesDataPromise,
		getChainPromise,
		sendMessage,
		deleteMessage,
		fetchCanUseMail,
	};
});
