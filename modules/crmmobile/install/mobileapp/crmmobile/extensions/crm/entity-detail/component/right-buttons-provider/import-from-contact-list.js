/**
 * @module crm/entity-detail/component/right-buttons-provider/import-from-contact-list
 */
jn.define('crm/entity-detail/component/right-buttons-provider/import-from-contact-list', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { AnalyticsEvent } = require('analytics');
	const { contacts } = require('native/contacts');
	const { NotifyManager } = require('notify-manager');
	const { get, isEmpty, mergeImmutable } = require('utils/object');
	const { stringify } = require('utils/string');
	const { isNil } = require('utils/type');
	const { getEntityMessage } = require('crm/loc');
	const { TypeId } = require('crm/type');

	const addImportButton = (buttons, detailCard) => {
		const { entityTypeId } = detailCard.getComponentParams();

		if (
			detailCard.hasEntityModel()
			&& detailCard.isNewEntity()
			&& (entityTypeId === TypeId.Contact || entityTypeId === TypeId.Company)
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

						const promise = detailCard.reloadWithData([{
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
							detailCard.mergeAnalyticsParams(new AnalyticsEvent().setP3('importButton_true'));
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

		fields.emails = isEmpty(emails) ? [] : prepareEmails(emails);
		fields.phoneNumbers = isEmpty(phoneNumbers) ? [] : preparePhones(phoneNumbers);
		fields.im = isEmpty(im) ? [] : prepareMessengers(im);

		const urlFields = prepareUrlFields(urls, socialProfiles);
		fields.urls = isEmpty(urlFields) ? [] : urlFields;

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
					id: `n${Math.round(Math.random() * 1000)}`,
					value: {
						VALUE: stringify(value).trim(),
						VALUE_TYPE: acceptedTypes.includes(type) ? type.toUpperCase() : 'OTHER',
					},
				}))
				.filter(({ value: { VALUE } }) => VALUE !== '')
		);
	};

	const importContactSvg = `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8 4C6.89543 4 6 4.89543 6 6C7.10457 6 8 6.89543 8 8C8 9.10457 7.10457 10 6 10C7.10457 10 8 10.8954 8 12C8 13.1046 7.10457 14 6 14C7.10457 14 8 14.8954 8 16C8 17.1046 7.10457 18 6 18C6 19.1046 6.89543 20 8 20H17C18.1046 20 19 19.1046 19 18V6C19 4.89543 18.1046 4 17 4H8ZM4 8C4 7.44772 4.44772 7 5 7H6C6.55228 7 7 7.44772 7 8C7 8.55228 6.55228 9 6 9H5C4.44772 9 4 8.55228 4 8ZM5 11C4.44772 11 4 11.4477 4 12C4 12.5523 4.44772 13 5 13H6C6.55228 13 7 12.5523 7 12C7 11.4477 6.55228 11 6 11H5ZM4 16C4 15.4477 4.44772 15 5 15H6C6.55228 15 7 15.4477 7 16C7 16.5523 6.55228 17 6 17H5C4.44772 17 4 16.5523 4 16ZM16.0512 14.3888C16.0901 14.5888 15.9849 14.7902 15.7953 14.8598C15.0063 15.1495 14.1166 15.3212 13.1732 15.3417H12.7915C11.8434 15.3211 10.9497 15.1479 10.1579 14.8556C9.97638 14.7886 9.87035 14.5999 9.90125 14.4072C9.93073 14.2233 9.96334 14.047 9.99664 13.9158C10.1106 13.4671 10.7513 13.1338 11.3409 12.8776C11.4952 12.8105 11.5884 12.7569 11.6826 12.7028L11.6826 12.7028C11.7746 12.65 11.8675 12.5966 12.0192 12.5295C12.0364 12.4469 12.0433 12.3625 12.0398 12.2782L12.301 12.2469C12.301 12.2469 12.3353 12.31 12.2802 11.9393C12.2802 11.9393 11.9867 11.8624 11.9731 11.2722C11.9731 11.2722 11.7525 11.3463 11.7392 10.9887C11.7364 10.9174 11.7182 10.8489 11.7007 10.7833C11.6589 10.6258 11.6214 10.4849 11.8123 10.362L11.6746 9.99093C11.6746 9.99093 11.5296 8.55782 12.1647 8.67384C11.907 8.2614 14.0799 7.91857 14.2242 9.18142C14.281 9.5621 14.281 9.94901 14.2242 10.3297C14.2242 10.3297 14.5488 10.292 14.3321 10.9156C14.3321 10.9156 14.2128 11.3644 14.0296 11.2636C14.0296 11.2636 14.0593 11.8308 13.7708 11.927C13.7708 11.927 13.7914 12.229 13.7914 12.2495L14.0325 12.2859C14.0325 12.2859 14.0252 12.5378 14.0733 12.5651C14.2933 12.7086 14.5344 12.8174 14.7881 12.8876C15.5367 13.0796 15.9169 13.4091 15.9169 13.6975L16.0512 14.3888Z" fill="${AppTheme.colors.base4}"/></svg>`;

	module.exports = { addImportButton };
});
