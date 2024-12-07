/**
 * @module crm/entity-detail/component/right-buttons-provider
 */
jn.define('crm/entity-detail/component/right-buttons-provider', (require, exports, module) => {

	const { contacts } = require('native/contacts');
	const { NotifyManager } = require('notify-manager');
	const { get, isEmpty, mergeImmutable } = require('utils/object');
	const { stringify } = require('utils/string');
	const { isNil } = require('utils/type');
	const { getEntityMessage } = require('crm/loc');
	const { TypeId } = require('crm/type');

	/**
	 * @param {*[]} buttons
	 * @param {DetailCardComponent} detailCard
	 * @returns {*[]}
	 */
	const rightButtonsProvider = (buttons, detailCard) => {
		const { entityTypeId } = detailCard.getComponentParams();

		if (
			detailCard.hasEntityModel()
			&& detailCard.isNewEntity()
			&& (entityTypeId === TypeId.Contact || entityTypeId === TypeId.Company)
			&& Application.getApiVersion() >= 45
		)
		{
			buttons = [
				{
					id: 'importContactList',
					badgeCode: 'import_from_contact_list',
					svg: {
						content: importContactSvg,
					},
					callback: getCallback(detailCard),
				},
				...buttons,
			];
		}

		return buttons;
	};

	let callback;
	const getCallback = (detailCard) => {
		if (!callback)
		{
			callback = () => importFromContactList(detailCard);
		}

		return callback;
	};

	/**
	 * @param {DetailCardComponent} detailCard
	 */
	const importFromContactList = (detailCard) => {
		dialogs.showContactList({ singleChoose: true }).then((result) => importSelected(detailCard, result));
	};

	/**
	 * @private
	 * @param {DetailCardComponent} detailCard
	 * @param {Object} result
	 */
	const importSelected = (detailCard, result) => {
		const contact = result[0] || null;
		if (!contact)
		{
			return;
		}

		NotifyManager.showLoadingIndicator();

		contacts
			.getData(contact.id)
			.then(([contactInfo]) => {
				const currentTabData = detailCard.tabRefMap.get('main');
				if (currentTabData)
				{
					const editorResult = get(currentTabData, 'state.result.editor', null);
					const entityData = get(editorResult, 'ENTITY_DATA', null);

					if (editorResult && entityData)
					{
						const newEntityData = mergeImmutable(
							entityData,
							getEntityFields(detailCard.getEntityTypeId(), contactInfo),
						);

						const promise = detailCard.reload([{
							id: 'main',
							result: {
								editor: {
									...editorResult,
									ENTITY_DATA: newEntityData,
								},
							},
						}]);

						promise.then(() => {
							detailCard.markChanged();

							NotifyManager.hideLoadingIndicator(
								true,
								getEntityMessage('M_CRM_ENTITY_ACTION_IMPORT_SUCCESS', detailCard.getEntityTypeId()),
								1000,
							);
						});
					}
				}
			})
			.catch((error) => {
				NotifyManager.hideLoadingIndicator(false);
				console.error(error);
			})
		;
	};

	const getEntityFields = (entityTypeId, contactInfo) => {
		if (entityTypeId === TypeId.Contact)
		{
			return getContactFields(contactInfo);
		}

		if (entityTypeId === TypeId.Company)
		{
			return getCompanyFields(contactInfo);
		}

		return {};
	};

	const getContactFields = (contactInfo) => {
		const {
			firstName,
			secondName,
			middleName,
			jobTitle,
			birthday,
			emails,
			urls,
			phoneNumbers,
			im,
		} = prepareInfo(contactInfo);

		const contactFields = {};

		contactFields.NAME = firstName;
		contactFields.LAST_NAME = secondName;
		contactFields.SECOND_NAME = middleName;
		contactFields.POST = jobTitle;

		if (birthday !== undefined)
		{
			contactFields.BIRTHDATE = birthday;
		}

		contactFields.EMAIL = emails;
		contactFields.WEB = urls;
		contactFields.PHONE = phoneNumbers;
		contactFields.IM = im;

		return contactFields;
	};

	const getCompanyFields = (contactInfo) => {
		const {
			displayName,
			emails,
			urls,
			phoneNumbers,
			im,
		} = prepareInfo(contactInfo);

		const companyFields = {};

		companyFields.TITLE = displayName;
		companyFields.EMAIL = emails;
		companyFields.WEB = urls;
		companyFields.PHONE = phoneNumbers;
		companyFields.IM = im;

		return companyFields;
	};

	const prepareInfo = (contactInfo) => {
		const {
			displayName,
			firstName,
			secondName,
			middleName,
			jobTitle,
			birthday,
			emails,
			urls,
			phoneNumbers,
			socialProfiles,
			im,
		} = contactInfo;

		const fields = {};

		fields.displayName = stringify(displayName).trim();
		fields.firstName = stringify(firstName).trim();
		fields.secondName = stringify(secondName).trim();
		fields.middleName = stringify(middleName).trim();
		fields.jobTitle = stringify(jobTitle).trim();

		if (!isNil(birthday))
		{
			fields.birthday = Math.round(birthday / 1000);
		}

		fields.emails = !isEmpty(emails) ? prepareEmails(emails) : [];
		fields.phoneNumbers = !isEmpty(phoneNumbers) ? preparePhones(phoneNumbers) : [];
		fields.im = !isEmpty(im) ? prepareMessengers(im) : [];

		const urlFields = prepareUrlFields(urls, socialProfiles);
		fields.urls = !isEmpty(urlFields) ? urlFields : [];

		return fields;
	};

	const prepareEmails = (emails) => {
		const acceptedTypes = ['work', 'home'];

		return prepareFmField(emails, acceptedTypes);
	};

	const preparePhones = (phones) => {
		const acceptedTypes = ['mobile', 'home', 'work'];

		phones = phones.map(({ value, type }) => ({
			value: value === 'main' ? 'work' : value,
			type,
		}));

		return prepareFmField(phones, acceptedTypes);
	};

	const prepareMessengers = (messengers) => {
		const acceptedTypes = ['facebook', 'icq', 'jabber', 'msn', 'skype'];

		return prepareFmField(messengers, acceptedTypes);
	};

	const prepareUrlFields = (urls, socialProfiles) => {
		let urlFields = [];

		if (!isEmpty(urls))
		{
			urlFields = prepareUrls(urls);
		}

		if (!isEmpty(socialProfiles))
		{
			urlFields = [...urlFields, ...prepareSocialProfiles(socialProfiles)];
		}

		return urlFields;
	};

	const prepareUrls = (urls) => {
		const acceptedTypes = ['work', 'home'];

		return prepareFmField(urls, acceptedTypes);
	};

	const prepareSocialProfiles = (socialProfiles) => {
		const acceptedTypes = ['facebook', 'twitter'];

		return prepareFmField(socialProfiles, acceptedTypes);
	};

	const prepareFmField = (values, acceptedTypes = []) => {
		return (
			values
				.map(({ value, type }) => ({
					id: 'n' + Math.round(Math.random() * 1000),
					value: {
						VALUE: stringify(value).trim(),
						VALUE_TYPE: acceptedTypes.includes(type) ? type.toUpperCase() : 'OTHER',
					},
				}))
				.filter(({ value: { VALUE } }) => VALUE !== '')
		);
	};

	const importContactSvg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M10.8186 19.5701V12.1473C10.8186 11.4088 11.3967 10.8447 12.1534 10.8447H13.5269C13.575 10.5296 13.5607 10.2586 13.5607 10.2586C13.9834 10.4888 14.2587 9.46391 14.2587 9.46391C14.7586 8.04005 14.0097 8.12614 14.0097 8.12614C14.1407 7.25691 14.1407 6.37348 14.0097 5.50425C13.6767 2.62076 8.6637 3.40355 9.25812 4.34529C7.793 4.08038 8.12732 7.35262 8.12732 7.35262L8.4451 8.19999C8.00463 8.48043 8.09113 8.80227 8.18775 9.16179C8.22803 9.31167 8.27007 9.4681 8.27643 9.6308C8.30713 10.4474 8.81619 10.2782 8.81619 10.2782C8.84756 11.6259 9.52462 11.8014 9.52462 11.8014C9.6518 12.6477 9.57253 12.5037 9.57253 12.5037L8.97002 12.5752C8.97818 12.7677 8.9622 12.9604 8.92245 13.1491C8.57239 13.3022 8.35807 13.4241 8.14587 13.5447C7.92864 13.6683 7.71364 13.7905 7.35748 13.9438C5.99729 14.5289 4.51902 15.2898 4.25622 16.3144C4.13379 16.7916 4.01539 17.5308 3.92383 18.1843C5.91802 19.018 8.23089 19.5169 10.704 19.5701H10.8186Z" fill="#A8ADB4"/><path fill-rule="evenodd" clip-rule="evenodd" d="M17.5727 12.011H13.1119C12.5659 12.011 12.1488 12.3977 12.1488 12.9039V20.0599C12.1488 20.536 12.5659 20.9528 13.1119 20.9528H17.5727C18.1187 20.9528 18.5358 20.536 18.5358 20.0599V12.9039C18.5358 12.3983 18.1187 12.011 17.5727 12.011ZM15.3422 20.3424C15.0067 20.3424 14.7322 20.0745 14.7322 19.7469C14.7322 19.4193 15.0067 19.1515 15.3422 19.1515C15.6778 19.1515 15.9522 19.4193 15.9522 19.7469C15.9522 20.0745 15.6778 20.3424 15.3422 20.3424ZM17.4794 18.7194H13.2052V13.2266H17.4799L17.4794 18.7194Z" fill="#A8ADB4"/></svg>';

	module.exports = { rightButtonsProvider };
});
