/**
 * @module crm/mail/sending-form
 */
jn.define('crm/mail/sending-form', (require, exports, module) => {
	const { WarningBlock } = require('layout/ui/warning-block');
	const { confirmClosing } = require('alert');
	const { Haptics } = require('haptics');
	const { EmailField, EmailType } = require('layout/ui/fields/email');
	const { MailContactField, MailContactType } = require('layout/ui/fields/mail-contact');
	const { showEmailBanner } = require('communication/email-menu');
	const { MenuSelectField, MenuSelectType } = require('layout/ui/fields/menu-select');
	const { FileField } = require('layout/ui/fields/file');
	const { MultipleField } = require('layout/ui/fields/multiple-field');
	const { TextAreaField, TextAreaType } = require('layout/ui/fields/textarea');
	const { WidgetHeaderButton } = require('layout/ui/widget-header-button');
	const { Loc } = require('loc');
	const { NotifyManager } = require('notify-manager');
	const { Type } = require('type');
	const { useCallback } = require('utils/function');
	const { clone } = require('utils/object');
	const { stringify } = require('utils/string');
	const { MessageBody } = require('crm/mail/message/tools/messagebody');
	const { sendMessage, getContactsPromise } = require('crm/mail/message/tools/connector');
	const { ActionPanel } = require('crm/mail/chain/action-panel');
	const AppTheme = require('apptheme');
	const allMarginsWidthInField = 155;

	class SendingForm extends LayoutComponent
	{
		replaceOldSignatureFromString(string, newSignature)
		{
			if (!this.currentSignature)
			{
				return string + newSignature;
			}

			return string.replace(this.getCurrentSignature(), () => {
				return newSignature;
			});
		}

		buildSignature(signature)
		{
			return `\n--\n${stringify(signature)}`;
		}

		setCurrentSignature(signature = false)
		{
			this.currentSignature = signature;
		}

		getCurrentSignature()
		{
			return this.currentSignature;
		}

		getSignature(emailOfSender)
		{
			const signature = this.signatures[emailOfSender];

			if (!signature || signature.trim() === '')
			{
				return '';
			}

			return this.buildSignature(signature);
		}

		/**
		 * If you just need to add a signature to any text,
		 * for example on first launch.
		 *
		 * @param string
		 * @param emailOfSender
		 * @param setCurrentSignature
		 * @returns {string}
		 */
		getStringWithSignature(string, emailOfSender, setCurrentSignature = false)
		{
			const signature = this.getSignature(emailOfSender);

			if (!signature)
			{
				return String(string);
			}

			if (setCurrentSignature)
			{
				this.setCurrentSignature(signature);
			}

			return String(string + signature);
		}

		setCursorBeforeSignature(fieldName = 'message', signature = '')
		{
			const compositeFields = this.state.compositeFields;
			const fullText = compositeFields[fieldName].fields[0].value;
			const ref = this.fieldRefs[fieldName];

			if (fullText)
			{
				const position = fullText.length - signature.length;
				this.saveCursorPosition(fieldName, position);
			}
			else
			{
				this.saveCursorPosition(fieldName);
			}
		}

		placeCursorPosition()
		{
			const {
				fieldName,
				position = 0,
			} = this.cursorPosition;

			if (fieldName !== undefined)
			{
				this.fieldRefs.message.setCursorPositionTo(position, position);
				this.cursorPosition.fieldName = undefined;
			}
		}

		saveCursorPosition(fieldName = 'message', position = 0)
		{
			this.cursorPosition = {
				fieldName,
				position,
			};
		}

		setSignatureInField(keyField, emailOfSender)
		{
			const signature = this.getSignature(emailOfSender);

			const compositeFields = clone(this.state.compositeFields);
			const fullText = compositeFields[keyField].fields[0].value;

			if (signature)
			{
				if (this.getCurrentSignature())
				{
					compositeFields[keyField].fields = [{
						value: this.replaceOldSignatureFromString(fullText, signature),
					}];
				}
				else
				{
					compositeFields[keyField].fields = [{
						value: String(fullText + signature),
					}];
				}

				this.setCurrentSignature(signature);
			}
			else
			{
				compositeFields[keyField].fields = [{
					value: this.replaceOldSignatureFromString(fullText, ''),
				}];
				this.setCurrentSignature();
			}

			this.setState({ compositeFields });
			this.setCursorBeforeSignature('message', signature);
		}

		setSignatures(senderList)
		{
			senderList.forEach((item) => {
				const {
					signature = '',
					email,
				} = item;

				if (email)
				{
					this.signatures[email] = signature;
				}
			});
		}

		setSenders(senderList, selectedEmail)
		{
			this.senders = senderList;
			this.setSignatures(senderList);
			this.constants.compositeFields.from.items = this.buildItemListForSelectField(senderList);
			this.onChangeField('from', selectedEmail, { type: MenuSelectType });
		}

		entityTypeToFiledType(type)
		{
			switch (type)
			{
				case 'contacts':
					return 'contact';
				case 'companies':
					return 'company';
				default:
					return 'email';
			}
		}

		findRecipientByEmail(recipients, email)
		{
			for (const recipient of recipients)
			{
				for (let idInList = 0; idInList < recipient.email.length; idInList++)
				{
					const data = recipient.email[idInList];
					if (data.value === email)
					{
						return {
							recipient,
							idInList,
						};
					}
				}
			}

			return null;
		}

		buildContact(contact, bindingsData = {})
		{
			return contact.map((item) => {
				const {
					value = false,
					customData = {},
					isEmailHidden,
				} = item;

				if (value)
				{
					let augmentedContact = {};

					let additionalData = {};

					const foundRecipient = this.findRecipientByEmail(bindingsData, value);

					if (foundRecipient)
					{
						const {
							recipient,
							idInList,
						} = foundRecipient;

						const {
							typeName = 'email',
							id,
							name,
							email,
						} = recipient;

						additionalData = {
							selectedEmailId: idInList,
							email,
							type: this.entityTypeToFiledType(typeName),
							id,
							name: name || value,
						};
					}

					augmentedContact = {
						customData,
						selectedEmailId: 0,
						email: [
							{
								value,
							},
						],
						id: 0,
						name: value,
						type: 'email',
						isEmailHidden,
						...additionalData,
					};

					return augmentedContact;
				}
			}).filter(Boolean);
		}

		constructor(props)
		{
			super(props);

			let {
				files = [],
				to = [],
				cc = [],
				bcc = [],
				from = [],
			} = props;

			const {
				bindingsData = {},
				isSendFiles,
				subject = '',
				body = '',
				replyMessageBody,
				senders = [],
				clients,
				clientIdsByType,
				widget,
				ownerEntity,
			} = props;

			const clientIdsByTypeForContactField = {
				idsForFilterCompany: clientIdsByType.company.length > 0 ? clientIdsByType.company : [0],
				idsForFilterContact: clientIdsByType.contacts.length > 0 ? clientIdsByType.contacts : [0],
			};

			this.signatures = {};

			if (!isSendFiles)
			{
				this.files = files;
				files = [];
			}

			const galleryInfo = {};
			const galleryValue = files.map((file) => {
				if (file.id && Number.isInteger(parseInt(file.id, 10)))
				{
					galleryInfo[file.id] = clone(file);

					return file.id;
				}

				return clone(file);
			});

			this.senders = senders;
			this.setSignatures(senders);

			this.forwardedFiles = galleryInfo;
			this.layout = widget;
			this.replyMessageBody = replyMessageBody;

			if (this.isEmptyFieldValue(to) && clients && clients.length > 0)
			{
				const client = clients[0];
				if (Array.isArray(client.email) && client.email.length > 0)
				{
					const emailData = client.email[0];
					if (emailData.value)
					{
						to = [{
							value: emailData.value,
						}];
					}
				}
			}

			if (this.isEmptyFieldValue(to) && this.isEmptyFieldValue(cc) && this.isEmptyFieldValue(bcc) && (!clients || clients.length === 0))
			{
				this.emptyRecipients = true;
			}

			to = this.buildContact(to, bindingsData);
			cc = this.buildContact(cc, bindingsData);
			bcc = this.buildContact(bcc, bindingsData);

			if (this.isEmptyFieldValue(from) && senders && senders.length > 0)
			{
				from = senders;
			}

			from = this.buildItemListForSelectField(from);
			const startEmailSender = from.length > 0 ? from[0].value : null;

			this.ownerEntity = ownerEntity;

			this.fieldRefs = {};

			this.cursorPosition = {
				fieldName: undefined,
				position: 0,
			};

			this.recipientsFieldPresetsFromField = {
				companyMode: (clientIdsByType.company.length > 0),
				contactMode: (clientIdsByType.contacts.length > 0),
				userMode: false,
			};

			this.constants = {
				fieldsOutputFormatFull: 'full',
				fieldsOutputFormatLittle: 'little',
				compositeFields: {
					to: {
						config: {
							...clientIdsByTypeForContactField,
							...this.recipientsFieldPresetsFromField,
						},
						testId: 'message-sending-form-to-field',
						ref: (ref) => {
							this.fieldRefs.to = ref;
						},
						required: true,
						isComposite: false,
						type: MailContactType,
						title: Loc.getMessage('MESSAGE_SEND_FIELD_TO'),
						placeholder: Loc.getMessage('MESSAGE_SEND_CONTACT_PLACEHOLDER'),
						collapsible: false,
					},
					cc: {
						config: {
							...clientIdsByTypeForContactField,
							...this.recipientsFieldPresetsFromField,
							userMode: true,
						},
						testId: 'message-sending-form-cc-field',
						ref: (ref) => {
							this.fieldRefs.cc = ref;
						},
						required: false,
						isComposite: false,
						type: MailContactType,
						title: Loc.getMessage('MESSAGE_SEND_FIELD_CC'),
						placeholder: Loc.getMessage('MESSAGE_SEND_CONTACT_PLACEHOLDER'),
						collapsible: true,
					},
					bcc: {
						config: {
							...clientIdsByTypeForContactField,
							...this.recipientsFieldPresetsFromField,
							userMode: true,
						},
						testId: 'message-sending-form-bcc-field',
						ref: (ref) => {
							this.fieldRefs.bcc = ref;
						},
						required: false,
						isComposite: false,
						type: MailContactType,
						title: Loc.getMessage('MESSAGE_SEND_FIELD_BCC'),
						placeholder: Loc.getMessage('MESSAGE_SEND_CONTACT_PLACEHOLDER'),
						collapsible: true,
					},
					from: {
						testId: 'message-sending-form-from-field',
						ref: (ref) => {
							this.fieldRefs.from = ref;
						},
						items: from,
						required: true,
						isShowMoreButton: true,
						showMoreAction: this.expandFormFields.bind(this),
						isComposite: false,
						type: MenuSelectType,
						title: Loc.getMessage('MESSAGE_SEND_FIELD_FROM'),
						placeholder: Loc.getMessage('MESSAGE_SEND_CONTACT_PLACEHOLDER'),
						collapsible: false,
					},
					subject: {
						testId: 'message-sending-form-subject-field',
						requiredErrorMessage: Loc.getMessage('MESSAGE_SEND_FIELD_SUBJECT_ERROR'),
						ref: (ref) => {
							this.fieldRefs.subject = ref;
						},
						required: true,
						isComposite: false,
						type: TextAreaType,
						title: Loc.getMessage('MESSAGE_SEND_FIELD_SUBJECT'),
						placeholder: Loc.getMessage('MESSAGE_SEND_FIELD_SUBJECT_PLACEHOLDER'),
						collapsible: false,
					},
				},
			};

			this.messageIsSent = false;
			this.isChanged = false;

			this.state = {
				files: galleryValue,
				fieldsOutputFormat: this.constants.fieldsOutputFormatLittle,
				compositeFields: {
					to: {
						fields: to,
					},
					cc: {
						fields: cc,
					},
					bcc: {
						fields: bcc,
					},
					from: {
						fields: startEmailSender,
					},
					subject: {
						fields: [{ value: subject }],
					},
					message: {
						fields: [{ value: (body ? `${body} ` : this.getStringWithSignature('', startEmailSender, true)) }],
					},
				},
			};

			this.focusedFieldName = this.getInitialFocusedFieldName();

			this.handleExitClick = this.handleExitClick.bind(this);

			this.bindFileRef = this.bindFileRef.bind(this);
			this.onFileChange = this.onFileChange.bind(this);

			this.bindMessageBodyRef = this.bindMessageBodyRef.bind(this);
			this.onChangeMessageBody = this.onChangeMessageBody.bind(this);

			this.onAttachmentsButton = this.onAttachmentsButton.bind(this);
		}

		buildItemListForSelectField(list)
		{
			const result = [];
			list.forEach((item) => {
				const buildItem = item;

				if (item.hasOwnProperty('value'))
				{
					buildItem.subtitle = item.value;
					buildItem.value = item.value;
					buildItem.id = item.value;
				}
				else if (item.hasOwnProperty('email'))
				{
					buildItem.subtitle = item.email;
					buildItem.value = item.email;
					buildItem.id = item.email;
				}

				if (item.hasOwnProperty('name'))
				{
					buildItem.title = item.name;
				}
				else
				{
					buildItem.subtitle = '';
					buildItem.title = item.email;
				}

				buildItem.icon = '<svg width="18" height="13" viewBox="0 0 18 13" fill="none" xmlns="http://www.w3.org/2000/svg"> <path fill-rule="evenodd" clip-rule="evenodd" d="M1.7386 0.5L8.94327 5.73976L16.1479 0.5H1.7386ZM17.4574 1.80991V2.07942L8.94284 8.7286L0.428223 2.07939V11.8097C0.428223 12.4372 0.976701 12.9444 1.65279 12.9444H16.2328C16.9106 12.9444 17.4574 12.4365 17.4574 11.8097V2.07942L17.4575 2.07939L17.4574 1.80991Z" fill="#6A737F"/> </svg>';
				result.push(buildItem);
			});

			result.push(
				{
					id: 'add-new-mailbox',
					title: Loc.getMessage('MESSAGE_SEND_CONNECT_NEW_MAILBOX'),
					icon: '<svg width="16" height="15" viewBox="0 0 16 15" fill="none" xmlns="http://www.w3.org/2000/svg"> <path fill-rule="evenodd" clip-rule="evenodd" d="M9.25 0H6.75V6.25H0.5V8.75H6.75V15H9.25V8.75H15.5V6.25H9.25V0Z" fill="#6A737F"/> </svg>',
				},
			);

			return result;
		}

		componentDidUpdate(prevProps, prevState)
		{
			this.placeCursorPosition();
			if (this.focusedFieldName)
			{
				const focusedFieldName = this.focusedFieldName;
				this.focusedFieldName = undefined;
				this.setFocusField(focusedFieldName);
			}

			this.refreshSaveButton();
		}

		componentDidMount()
		{
			this.saveButton = new WidgetHeaderButton({
				widget: this.layout,
				text: Loc.getMessage('MESSAGE_SEND_BUTTON_SEND'),
				loadingText: Loc.getMessage('MESSAGE_SEND_BUTTON_SENDING'),
				onClick: () => this.sendEmail(),
				onDisabledClick: () => this.checkSendEmail(),
				disabled: () => this.hasInvalidFields(),
			});

			this.layout.setLeftButtons(this.getLeftButtons());
		}

		getLeftButtons()
		{
			return [
				{
					// type: 'cross',
					svg: {
						content: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.722 6.79175L10.9495 10.5643L9.99907 11.5L9.06666 10.5643L5.29411 6.79175L3.96289 8.12297L10.008 14.1681L16.0532 8.12297L14.722 6.79175Z" fill="#A8ADB4"/></svg>',
					},
					callback: this.handleExitClick,
				},
			];
		}

		handleExitClick()
		{
			if (this.isClosing)
			{
				return;
			}

			if (this.isChanged)
			{
				this.showConfirmExitEntity(
					() => this.sendEmail(),
					() => this.close(),
				);
			}
			else
			{
				this.close();
			}
		}

		showConfirmExitEntity(onSave, onDiscard)
		{
			Haptics.impactLight();

			confirmClosing({
				title: Loc.getMessage('MESSAGE_SEND_EXIT_ALERT_TITLE'),
				description: Loc.getMessage('MESSAGE_SEND_EXIT_ALERT_TEXT'),
				onSave,
				onClose: onDiscard,
			});
		}

		close()
		{
			this.isClosing = true;

			if (this.layout)
			{
				this.layout.back();
				this.layout.close();
			}
		}

		setFocusField(name)
		{
			if (this.fieldRefs[name])
			{
				if (name === 'message')
				{
					this.fieldRefs[name].setCursorPositionTo();
				}
				else
				{
					this.fieldRefs[name].focus();
				}
			}
		}

		refreshSaveButton()
		{
			if (this.saveButton)
			{
				this.saveButton.refresh();
			}
		}

		getInitialFocusedFieldName()
		{
			if (this.isEmptyField('subject'))
			{
				return 'subject';
			}

			return 'message';
		}

		isEmptyField(fieldType)
		{
			const { compositeFields } = this.state;
			const { [fieldType]: fieldValue } = compositeFields;

			if (!Type.isPlainObject(fieldValue) || !fieldValue.hasOwnProperty('fields'))
			{
				throw new Error(`Field ${fieldType} is not found`);
			}

			const { fields } = fieldValue;

			return this.isEmptyFieldValue(fields);
		}

		isEmptyFieldValue(fieldValue)
		{
			if (!Array.isArray(fieldValue))
			{
				return true;
			}

			return fieldValue.every((field) => !field || !Type.isStringFilled(field.value));
		}

		renderMessageBodyInput(props)
		{
			const {
				ref,
				onChangeFieldAction,
				value,
				key,
			} = props;

			let { additionalParameters } = props;

			const showLeftIcon = false;

			if (additionalParameters === undefined)
			{
				additionalParameters = [];
			}

			return View(
				{
					style: {
						minHeight: 100,
						paddingTop: 5,
						paddingBottom: 20,
						paddingLeft: 15,
						paddingRight: 15,
					},
				},
				TextAreaField({
					testId: 'message-sending-form-message-body-field',
					requiredErrorMessage: Loc.getMessage('MESSAGE_SEND_FIELD_MESSAGE_BODY_ERROR'),
					ref,
					showRequired: false,
					required: true,
					showTitle: false,
					onChange: useCallback(onChangeFieldAction, [key]),
					...additionalParameters,
					showLeftIcon,
					value,
					placeholder: Loc.getMessage('MESSAGE_SEND_FIELD_MESSAGE_BODY_PLACEHOLDER'),
				}),
			);
		}

		getOwnerType()
		{
			const {
				ownerType,
			} = this.getOwnerEntity();

			return ownerType;
		}

		getOwnerEntity()
		{
			return this.ownerEntity;
		}

		getReplyMessageBody()
		{
			return this.replyMessageBody;
		}

		expandFormFields()
		{
			this.setState({
				fieldsOutputFormat: this.constants.fieldsOutputFormatFull,
			});
		}

		onChangeField(key, data, item = {})
		{
			if (key === 'from' && data === 'add-new-mailbox')
			{
				showEmailBanner(this.props.parentWidget, (email) => {
					NotifyManager.showLoadingIndicator();
					getContactsPromise(Number(this.getOwnerEntity().ownerId), this.getOwnerEntity().ownerType)
						.then(({ data }) => {
							if (Array.isArray(data.senders))
							{
								this.setSenders(data.senders, email);
							}
						})
						.catch(console.error)
						.finally(() => NotifyManager.hideLoadingIndicatorWithoutFallback());
				});
			}
			else
			{
				if (key === 'from')
				{
					this.setSignatureInField('message', data);
				}

				this.isChanged = true;

				const { type } = item;

				if ((!type || (type !== MenuSelectType && type !== MailContactType)) && !Array.isArray(data))
				{
					data = [{
						value: stringify(data),
					}];
				}

				const compositeFields = clone(this.state.compositeFields);

				compositeFields[key].fields = data;

				this.setState({ compositeFields });
			}
		}

		checkSendEmail()
		{
			const canSendEmail = this.validateFields();

			if (!canSendEmail)
			{
				Haptics.notifyWarning();

				this.focusFirstInvalidField();
			}

			return canSendEmail;
		}

		validateFields()
		{
			if (!this.state.compositeFields.from.fields)
			{
				return false;
			}

			let validate = true;

			Object.values(this.fieldRefs).forEach((fieldRef) => {
				if (fieldRef && !fieldRef.validate())
				{
					validate = false;
				}
			});

			return validate;
		}

		focusFirstInvalidField()
		{
			const inputNames = [
				...Object.keys(this.constants.compositeFields),
				'message',
			];

			const invalidFieldName = inputNames.find((fieldName) => {
				const fieldRef = this.fieldRefs[fieldName];

				return fieldRef && !fieldRef.isValid();
			});

			if (invalidFieldName)
			{
				this.fieldRefs[invalidFieldName].focus();
			}
		}

		hasInvalidFields()
		{
			return Object.values(this.fieldRefs)
				.some((fieldRef) => fieldRef && !fieldRef.isValid());
		}

		sendEmail()
		{
			if (this.messageIsSent)
			{
				return;
			}

			if (!this.checkSendEmail())
			{
				return;
			}

			this.messageIsSent = true;

			NotifyManager.showLoadingIndicator();

			this.waitUploadingFiles()
				.then(() => sendMessage({
					senders: this.senders,
					fileTokens: this.getFileIds(),
					...this.getOwnerEntity(),
					...this.state.compositeFields,
				}))
				.then(() => {
					NotifyManager.hideLoadingIndicator(true);

					setTimeout(() => this.close(), 300);
				})
				.catch((failure) => {
					console.error(failure);

					// some file occurred error during uploading
					if (failure && failure.hasError)
					{
						NotifyManager.hideLoadingIndicatorWithoutFallback();

						return;
					}

					const { errors } = failure || {};

					NotifyManager.showErrors(errors);
				})
				.finally(() => {
					this.messageIsSent = false;
				})
			;
		}

		renderCompositeField(props)
		{
			const {
				testId,
				requiredErrorMessage,
				required,
				key,
				placeholder,
				title,
				isComposite,
				fieldsOutputFormat,
				collapsible,
				config = {},
				fields,
				type,
				onChangeAction,
				showMoreAction,
				isShowMoreButton,
				ref,
				items,
			} = props;

			const showLeftIcon = false;

			if (fieldsOutputFormat === 'little' && collapsible)
			{
				return null;
			}

			let field;

			const hideMode = isShowMoreButton && fieldsOutputFormat === 'little';

			const standardFiledProps = {
				testId,
				showTitle: false,
				requiredErrorMessage,
				ref,
				showRequired: false,
				required,
				placeholder,
				showLeftIcon,
				onChange: useCallback(onChangeAction, [key]),
			};

			if (isComposite)
			{
				let renderField;
				switch (type)
				{
					case EmailType:
						renderField = EmailField;
						break;
					case MenuSelectType:
						renderField = MenuSelectField;
						break;
					default:
						renderField = TextAreaField;
				}
				field = MultipleField({
					addField: {
						content: `
							<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
							<rect width="28" height="28" rx="14" fill="white" fill-opacity="0.01"/>
							<path d="M17.1878 16.6815L17.421 17.8691C17.488 18.2106 17.3065 18.5543 16.9795 18.6733C15.6122 19.1705 14.0705 19.4651 12.4355 19.5003H11.7728C10.1299 19.4649 8.58128 19.1676 7.20917 18.6661C6.89613 18.5517 6.71323 18.2296 6.76649 17.9006C6.81766 17.5845 6.8743 17.2812 6.93213 17.0557C7.12946 16.2864 8.23942 15.7151 9.26073 15.2757C9.52815 15.1607 9.68959 15.0689 9.85269 14.9761C10.012 14.8855 10.173 14.794 10.4358 14.679C10.4656 14.5374 10.4776 14.3927 10.4715 14.2482L10.9239 14.1945C10.9239 14.1945 10.9834 14.3026 10.8879 13.6671C10.8879 13.6671 10.3796 13.5353 10.356 12.5234C10.356 12.5234 9.97377 12.6505 9.95072 12.0373C9.94595 11.9152 9.91438 11.7977 9.88414 11.6852C9.81159 11.4152 9.74664 11.1736 10.0774 10.963L9.83876 10.3267C9.83876 10.3267 9.58774 7.86978 10.6878 8.06868C10.2415 7.36158 14.0056 6.77381 14.2556 8.93889C14.3539 9.59156 14.3539 10.2549 14.2556 10.9076C14.2556 10.9076 14.8179 10.8429 14.4425 11.912C14.4425 11.912 14.2358 12.6815 13.9184 12.5087C13.9184 12.5087 13.9698 13.4811 13.4701 13.646C13.4701 13.646 13.5058 14.1639 13.5058 14.1989L13.9235 14.2613C13.9235 14.2613 13.9108 14.6932 13.9942 14.7399C14.3752 14.9861 14.7929 15.1726 15.2323 15.2929C16.5292 15.6221 17.1878 16.187 17.1878 16.6815Z" fill="#D5D7DB"/>
							<path d="M18.6418 9.04982H20.4311V11.2496H22.7224V13.0532H20.4311V15.3624H18.6418V13.0532H16.46V11.2496H18.6418V9.04982Z" fill="#D5D7DB"/>
							</svg>
						`,
					},
					renderField,
					multiple: true,
					value: fields,
					...standardFiledProps,
				});
			}
			else
			{
				switch (type)
				{
					case EmailType:
					{
						const emailField = EmailField({
							...standardFiledProps,
							...fields[0],
						});

						field = View(
							{
								style: {
									width: hideMode ? '73%' : '100%',
								},
							},
							emailField,
						);
						break;
					}

					case MailContactType:
					{
						const selectField = MailContactField({
							value: fields.map((item) => item.id),
							readOnly: false,
							multiple: true,
							...standardFiledProps,
							config: {
								...config,
								allMarginsWidthInField,
								enableCreation: false,
								entityList: fields.map((item) => ({
									email: item.email,
									selectedEmailId: item.selectedEmailId,
									title: item.name,
									id: item.id,
									type: item.type,
									isEmailHidden: item.isEmailHidden,
								})),
							},
						});

						field = View(
							{
								style: {
									marginTop: 1.3,
									// Indent for tap
									width: '90%',
								},
							},
							selectField,
						);

						break;
					}

					case MenuSelectType:
					{
						const selectField = MenuSelectField({
							value: fields,
							required: true,
							...standardFiledProps,
							config: {
								showCancelButton: true,
								menuTitle: BX.message('MESSAGE_SEND_SELECT_SENDER_TITLE'),
								menuItems: items,
								parentWidget: this.props.parentWidget,
							},
						});

						field = View(
							{
								style: {
									width: hideMode ? '73%' : '100%',
								},
							},
							selectField,
						);
						break;
					}
					default:
						field = TextAreaField({
							...standardFiledProps,
							...fields[0],
						});

						const isIOS = Application.getPlatform() === 'ios';

						field = View(
							{
								style: {
									paddingTop: isIOS ? 1 : 0.5,
								},
							},
							field,
						);
				}
			}

			let moreBtn = null;

			if (hideMode)
			{
				moreBtn = View(
					{
						testId: 'message-sending-form-show-copies-btn',
						style: {
							height: '100%',
							paddingRight: 16,
							justifyContent: 'center',
							right: 0,
							position: 'absolute',
						},
						onClick: showMoreAction,
					},
					View(
						{
							style: {
								borderBottomWidth: 0.5,
								borderBottomColor: AppTheme.colors.bgSeparatorPrimary,
								borderStyle: 'dash',
								borderDashSegmentLength: 2,
								borderDashGapLength: 2,
							},
						},
						Text({
							style: {
								color: AppTheme.colors.base5,
								fontSize: 10,
							},
							text: Loc.getMessage('MESSAGE_SEND_FIELD_SHOW_MORE').toLocaleUpperCase(env.languageId),
						}),
					),
				);
			}

			return View(
				{
					onClick: () => {
						this.setFocusField(key);
					},
					style: {
						paddingLeft: 16,
						alignItems: 'flex-start',
						flexDirection: 'row',
						borderBottomWidth: 0.5,
						borderBottomColor: AppTheme.colors.accentSoftBlue3,
					},
				},
				View(
					{
						style: {
							justifyContent: 'center',
							height: 52,
						},
					},
					Text({
						style: {
							textAlignVertical: 'top',
							color: AppTheme.colors.base5,
							paddingRight: 3,
							fontSize: 16,
						},
						text: `${title}:`,
					}),
				),
				View(
					{
						style: {
							flex: 1,
							minHeight: 52,
						},
					},
					View(
						{
							style: {
								alignItems: 'flex-start',
								flexDirection: 'row',
							},
						},
						View(
							{
								style: {
									flex: 1,
									paddingTop: isComposite ? 1 : 7,
								},
							},
							field,
						),
						moreBtn,
					),
				),
			);
		}

		waitUploadingFiles()
		{
			/** @type {FileField} fileRef */
			const { files: fileRef } = this.fieldRefs;

			if (fileRef)
			{
				return fileRef.getValueWhileReady();
			}

			return Promise.resolve();
		}

		renderAttachments(files)
		{
			return View(
				{
					style: {
						paddingHorizontal: 20,
						display: this.state.files.length === 0 ? 'none' : 'flex',
						paddingBottom: 16,
					},
				},
				FileField({
					testId: 'message-sending-file-field',
					ref: this.bindFileRef,
					showTitle: false,
					showAddButton: false,
					multiple: true,
					value: this.state.files,
					config: {
						fileInfo: files,
						mediaType: 'file',
						parentWidget: this.layout,
						controller: {
							options: this.getOwnerEntity(),
							endpoint: 'crm.FileUploader.MailUploaderController',
						},
					},
					readOnly: false,
					onChange: this.onFileChange,
				}),
			);
		}

		bindFileRef(ref)
		{
			this.fieldRefs.files = ref;
		}

		onFileChange(files)
		{
			this.isChanged = true;

			files = Array.isArray(files) ? files : [];

			this.setState({ files });
		}

		getFiles()
		{
			return this.state.files;
		}

		getFileIds()
		{
			return this.getFiles().map((file) => {
				if (typeof file !== 'object')
				{
					return file;
				}

				return (file.token || file.id);
			});
		}

		// eslint-disable-next-line consistent-return
		renderEmptyRecipientsWarning()
		{
			if (this.emptyRecipients)
			{
				return new WarningBlock({
					title: Loc.getMessage('MESSAGE_SEND_WARNING_EMPTY_RECIPIENT_TITLE'),
					description: Loc.getMessage('MESSAGE_SEND_WARNING_EMPTY_RECIPIENT_TEXT'),
				});
			}
		}

		render()
		{
			const fields = [];
			Object.entries(this.constants.compositeFields).forEach(([key, item]) => {
				if (key !== 'message')
				{
					fields.push(this.renderCompositeField({
						key,
						onChangeAction: useCallback((data) => this.onChangeField(key, data, item), [key]),
						...item,
						...this.state.compositeFields[key],
						fieldsOutputFormat: this.state.fieldsOutputFormat,
					}));
				}
			});

			return View(
				{
					resizableByKeyboard: true,
				},
				this.renderEmptyRecipientsWarning(),
				ScrollView(
					{
						style: {
							backgroundColor: AppTheme.colors.bgContentPrimary,
							flex: 1,
						},
					},
					View(
						{},
						View({
							style: {
								flexDirection: 'row',
								flexWrap: 'wrap',
							},
						}),
						...fields,
						this.renderAttachments(this.forwardedFiles),
						this.renderMessageBodyInput({
							ref: this.bindMessageBodyRef,
							...this.state.compositeFields.message.fields[0],
							key: 'message',
							onChangeFieldAction: this.onChangeMessageBody,
						}),
						new MessageBody({
							borderBeforeFiles: true,
							files: this.files,
							isHiddenField: true,
							content: this.getReplyMessageBody(),
						}),
						new ActionPanel({
							indentStub: true,
						}),
					),
				),
				new ActionPanel({
					withoutStyles: true,
					actions: {
						attachmentsButton: this.onAttachmentsButton,
					},
				}),
			);
		}

		bindMessageBodyRef(ref)
		{
			this.fieldRefs.message = ref;
		}

		onChangeMessageBody(data)
		{
			this.onChangeField('message', data);
		}

		onAttachmentsButton()
		{
			const { files } = this.fieldRefs;

			if (files)
			{
				files.openFilePicker();
			}
		}
	}

	module.exports = { SendingForm };
});
