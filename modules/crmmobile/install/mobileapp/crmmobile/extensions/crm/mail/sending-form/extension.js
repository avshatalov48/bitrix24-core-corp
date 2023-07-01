/**
 * @module crm/mail/sending-form
 */
jn.define('crm/mail/sending-form', (require, exports, module) => {
	const { Alert } = require('alert');
	const { Haptics } = require('haptics');
	const { EmailField, EmailType } = require('layout/ui/fields/email');
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
	const { sendMessage } = require('crm/mail/message/tools/connector');
	const { ActionPanel } = require('crm/mail/chain/action-panel');

	const EMPTY_VALUE = { value: '' };

	class SendingForm extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			let {
				files = [],
				to = [EMPTY_VALUE],
				from = [EMPTY_VALUE],
			} = props;

			const {
				bindingsData = {},
				isSendFiles,
				cc = [EMPTY_VALUE],
				bcc = [EMPTY_VALUE],
				subject = '',
				body = '',
				replyMessageBody,
				senders = [],
				clients,
				widget,
				ownerEntity,
			} = props;

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

			this.bindingsData = bindingsData;
			this.senders = senders;
			this.forwardedFiles = galleryInfo;
			this.layout = widget;
			this.replyMessageBody = replyMessageBody;

			if (this.isEmptyFieldValue(to) && clients && clients.length > 0)
			{
				to = [{
					value: stringify(clients[0].email),
				}];
			}

			if (this.isEmptyFieldValue(from) && senders && senders.length > 0)
			{
				from = [{
					value: stringify(senders[0].email),
				}];
			}

			this.ownerEntity = ownerEntity;

			this.fieldRefs = {};

			this.constants = {
				fieldsOutputFormatFull: 'full',
				fieldsOutputFormatLittle: 'little',
				compositeFields: {
					to: {
						testId: 'message-sending-form-to-field',
						ref: (ref) => this.fieldRefs.to = ref,
						required: true,
						isComposite: true,
						type: EmailType,
						title: Loc.getMessage('MESSAGE_SEND_FIELD_TO'),
						placeholder: Loc.getMessage('MESSAGE_SEND_CONTACT_PLACEHOLDER'),
						collapsible: false,
					},
					cc: {
						testId: 'message-sending-form-cc-field',
						ref: (ref) => this.fieldRefs.cc = ref,
						required: false,
						isComposite: true,
						type: EmailType,
						title: Loc.getMessage('MESSAGE_SEND_FIELD_CC'),
						placeholder: Loc.getMessage('MESSAGE_SEND_CONTACT_PLACEHOLDER'),
						collapsible: true,
					},
					bcc: {
						testId: 'message-sending-form-bcc-field',
						ref: (ref) => this.fieldRefs.bcc = ref,
						required: false,
						isComposite: true,
						type: EmailType,
						title: Loc.getMessage('MESSAGE_SEND_FIELD_BCC'),
						placeholder: Loc.getMessage('MESSAGE_SEND_CONTACT_PLACEHOLDER'),
						collapsible: true,
					},
					from: {
						testId: 'message-sending-form-from-field',
						ref: (ref) => this.fieldRefs.from = ref,
						required: true,
						isShowMoreButton: true,
						showMoreAction: this.expandFormFields.bind(this),
						isComposite: false,
						type: EmailType,
						title: Loc.getMessage('MESSAGE_SEND_FIELD_FROM'),
						placeholder: Loc.getMessage('MESSAGE_SEND_CONTACT_PLACEHOLDER'),
						collapsible: false,
					},
					subject: {
						testId: 'message-sending-form-subject-field',
						requiredErrorMessage: Loc.getMessage('MESSAGE_SEND_FIELD_SUBJECT_ERROR'),
						ref: (ref) => this.fieldRefs.subject = ref,
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

			this.fieldObjects = {};

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
						fields: from,
					},
					subject: {
						fields: [{ value: subject }],
					},
					message: {
						fields: [{ value: body }],
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

		componentDidUpdate(prevProps, prevState)
		{
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

			Alert.confirm(
				Loc.getMessage('MESSAGE_SEND_EXIT_ALERT_TITLE'),
				Loc.getMessage('MESSAGE_SEND_EXIT_ALERT_TEXT'),
				[
					{
						text: Loc.getMessage('MESSAGE_SEND_EXIT_ALERT_SAVE'),
						type: 'default',
						onPress: onSave,
					},
					{
						text: Loc.getMessage('MESSAGE_SEND_EXIT_ALERT_DISCARD'),
						type: 'destructive',
						onPress: onDiscard,
					},
					{
						text: Loc.getMessage('MESSAGE_SEND_EXIT_ALERT_CANCEL'),
						type: 'cancel',
					},
				],
			);
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
			if (this.fieldObjects[name])
			{
				// @todo fix in the mobile app: setTimeout to fix a bug (170691) with the keyboard on IOS app.
				setTimeout(() => this.fieldObjects[name].focus(), 500);
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
			if (this.isEmptyField('to'))
			{
				return 'to';
			}

			if (this.isEmptyField('from'))
			{
				return 'from';
			}

			if (this.isEmptyField('subject'))
			{
				return 'subject';
			}

			return 'messageBodyInput';
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

			this.fieldObjects.messageBodyInput = TextAreaField({
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
			});

			return View(
				{
					style: {
						paddingTop: 5,
						paddingBottom: 20,
						paddingLeft: 15,
						paddingRight: 15,
					},
				},
				this.fieldObjects.messageBodyInput,
			);
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
			this.focusedFieldName = 'cc';

			this.setState({
				fieldsOutputFormat: this.constants.fieldsOutputFormatFull,
			});
		}

		onChangeField(key, data)
		{
			this.isChanged = true;

			if (!Array.isArray(data))
			{
				data = [{
					value: stringify(data),
				}];
			}

			const compositeFields = clone(this.state.compositeFields);

			compositeFields[key].fields = data;

			this.setState({ compositeFields });
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
				'messageBodyInput',
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
					bindingsData: this.bindingsData,
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
				fields,
				type,
				onChangeAction,
				showMoreAction,
				isShowMoreButton,
				ref,
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
					default:
						renderField = TextAreaField;
				}
				field = MultipleField({
					renderField,
					multiple: true,
					value: fields,
					...standardFiledProps,
				});
				this.fieldObjects[key] = field;
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

						this.fieldObjects[key] = emailField;

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
					default:
						field = TextAreaField({
							...standardFiledProps,
							...fields[0],
						});
						this.fieldObjects[key] = field;

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
								borderBottomColor: '#bdc1c6',
								borderStyle: 'dash',
								borderDashSegmentLength: 2,
								borderDashGapLength: 2,
							},
						},
						Text({
							style: {
								color: '#bdc1c6',
								fontSize: 10,
							},
							text: Loc.getMessage('MESSAGE_SEND_FIELD_SHOW_MORE').toLocaleUpperCase(env.languageId),
						}),
					),
				);
			}

			return View(
				{
					style: {
						paddingLeft: 16,
						alignItems: 'flex-start',
						flexDirection: 'row',
						borderBottomWidth: 0.5,
						borderBottomColor: '#f0f2fb',
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
							color: '#bdc1c6',
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

		render()
		{
			const fields = [];
			Object.entries(this.constants.compositeFields).forEach(([key, value]) => {
				if (key !== 'message')
				{
					fields.push(this.renderCompositeField({
						key,
						onChangeAction: useCallback((data) => this.onChangeField(key, data), [key]),
						...value,
						...this.state.compositeFields[key],
						fieldsOutputFormat: this.state.fieldsOutputFormat,
					}));
				}
			});

			return View(
				{
					resizableByKeyboard: true,
				},
				ScrollView(
					{
						style: {
							backgroundColor: '#ffffff',
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
			this.fieldRefs.messageBodyInput = ref;
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

	module.exports = {
		SendingForm,
	};
});
