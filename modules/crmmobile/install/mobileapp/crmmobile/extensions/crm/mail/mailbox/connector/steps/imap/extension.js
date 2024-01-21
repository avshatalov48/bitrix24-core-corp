/**
 * @module crm/mail/mailbox/connector/steps/imap
 */

jn.define('crm/mail/mailbox/connector/steps/imap', (require, exports, module) => {
	const { Haptics } = require('haptics');
	const { WizardStep } = require('layout/ui/wizard/step');
	const { Loc } = require('loc');
	const { ProgressBarNumber } = require('crm/salescenter/progress-bar-number');
	const { StringField } = require('layout/ui/fields/string');
	const { EmailField } = require('layout/ui/fields/email');
	const { NumberField } = require('layout/ui/fields/number');
	const { useCallback } = require('utils/function');
	const { clone } = require('utils/object');
	const { stringify } = require('utils/string');
	const { NotifyManager } = require('notify-manager');
	const { Switcher } = require('layout/ui/switcher');
	const { throttle } = require('utils/function');
	const isIOS = Application.getPlatform() === 'ios';
	const AppTheme = require('apptheme');
	/**
	 * @class FieldsLayout
	 */
	class FieldsLayout extends LayoutComponent
	{
		getEmptyFieldsData()
		{
			return {
				login: {
					value: '',
				},
				password: {
					value: '',
				},
				imapPort: {
					value: '',
				},
				smtpPort: {
					value: '',
				},
				server: {
					value: '',
				},
				ssl: {
					value: true,
				},
				sslSmtp: {
					value: true,
				},
				addressSmtp: {
					value: '',
				},
			};
		}

		constructor(props)
		{
			super(props);
			this.fieldRefs = {};
			this.constants = {
				fields: {
					imapAddress: {
						ref: (ref) => this.fieldRefs.imapAddress = ref,
					},
					smtpAddress: {
						ref: (ref) => this.fieldRefs.smtpAddress = ref,
					},
					imapPort: {
						ref: (ref) => this.fieldRefs.imapPort = ref,
					},
					smtpPort: {
						ref: (ref) => this.fieldRefs.smtpPort = ref,
					},
					login: {
						ref: (ref) => this.fieldRefs.login = ref,
					},
					password: {
						ref: (ref) => this.fieldRefs.password = ref,
					},
				},
			};
			this.state = {
				fields: this.getEmptyFieldsData(),
			};
		}

		onChangeField(key, data, convertToString = true)
		{
			const fields = clone(this.state.fields);

			if (convertToString)
			{
				data = stringify(data);
			}

			fields[key].value = data;
			this.setState({ fields });
		}

		render()
		{
			const checkedSslImap = this.state.fields.ssl.value;
			let secureConnectionAction = this.onChangeField.bind(this, 'ssl', !checkedSslImap, false);
			secureConnectionAction = throttle(secureConnectionAction, 500, this);

			const checkedSslSmtp = this.state.fields.sslSmtp.value;
			let secureConnectionSmtpAction = this.onChangeField.bind(this, 'sslSmtp', !checkedSslSmtp, false);
			secureConnectionSmtpAction = throttle(secureConnectionSmtpAction, 500, this);

			return View(
				{},
				View(
					{
						style: {
							borderRadius: 12,
							backgroundColor: AppTheme.colors.bgContentSecondary,
							paddingLeft: 20,
							paddingRight: 15,
							paddingBottom: 37,
							paddingTop: 27,
							marginBottom: 15,
						},
					},
					EmailField({
						testId: 'connecting-mailboxes-connecting-mailbox-imap-smtp-email',
						ref: this.constants.fields.login.ref,
						showLeftIcon: false,
						showRequired: false,
						required: true,
						value: this.state.fields.login.value,
						onChange: useCallback((data) => this.onChangeField('login', data), ['login']),
						title: Loc.getMessage('MAILBOX_CONNECTOR_IMAP_LOGIN_TITLE'),
						placeholder: Loc.getMessage('MAILBOX_CONNECTOR_IMAP_LOGIN_PLACEHOLDER'),
					}),
					StringField({
						testId: 'connecting-mailboxes-connecting-mailbox-imap-smtp-password',
						ref: this.constants.fields.password.ref,
						showRequired: false,
						required: true,
						value: this.state.fields.password.value,
						onChange: useCallback((data) => this.onChangeField('password', data), ['password']),
						title: Loc.getMessage('MAILBOX_CONNECTOR_IMAP_PASSWORD_TITLE'),
						placeholder: Loc.getMessage('MAILBOX_CONNECTOR_IMAP_PASSWORD_PLACEHOLDER'),
						isPassword: true,
					}),
				),
				View(
					{
						style: {
							borderRadius: 12,
							backgroundColor: AppTheme.colors.bgContentSecondary,
							paddingLeft: 20,
							paddingRight: 15,
							paddingBottom: 37,
							paddingTop: 27,
							marginBottom: 15,
						},
					},
					StringField({
						testId: 'connecting-mailboxes-connecting-mailbox-imap-smtp-imap-address',
						ref: this.constants.fields.imapAddress.ref,
						showRequired: false,
						required: true,
						value: this.state.fields.server.value,
						onChange: useCallback((data) => this.onChangeField('server', data), ['server']),
						title: Loc.getMessage('MAILBOX_CONNECTOR_IMAP_LOGIN_ADDRESS_TITLE'),
						placeholder: Loc.getMessage('MAILBOX_CONNECTOR_IMAP_LOGIN_ADDRESS_PLACEHOLDER'),
					}),
					NumberField({
						testId: 'connecting-mailboxes-connecting-mailbox-imap-smtp-imap-port',
						ref: this.constants.fields.imapPort.ref,
						showRequired: false,
						required: true,
						value: this.state.fields.imapPort.value,
						onChange: useCallback((data) => this.onChangeField('imapPort', data), ['imapPort']),
						title: Loc.getMessage('MAILBOX_CONNECTOR_IMAP_PORT_TITLE'),
						placeholder: Loc.getMessage('MAILBOX_CONNECTOR_IMAP_PORT_PLACEHOLDER'),
					}),
					View(
						{
							onClick: secureConnectionAction,
							style: {
								paddingBottom: 10,
							},
						},
						Text({
							style: {
								paddingBottom: 6,
								color: AppTheme.colors.base3,
								fontSize: 10,
								fontWeight: '500',
							},
							text: Loc.getMessage('MAILBOX_CONNECTOR_USE_SECURE_CONNECTION').toLocaleUpperCase(env.languageId),
						}),
						new Switcher({
							testId: 'connecting-mailboxes-connecting-mailbox-imap-smtp-imap-ssl-switcher',
							checked: checkedSslImap,
						}),
					),
				),
				View(
					{
						style: {
							borderRadius: 12,
							backgroundColor: AppTheme.colors.bgContentSecondary,
							paddingLeft: 20,
							paddingRight: 15,
							paddingBottom: 37,
							paddingTop: 27,
						},
					},
					StringField({
						testId: 'connecting-mailboxes-connecting-mailbox-imap-smtp-smtp-address',
						ref: this.constants.fields.smtpAddress.ref,
						showRequired: false,
						required: true,
						value: this.state.fields.addressSmtp.value,
						onChange: useCallback((data) => this.onChangeField('addressSmtp', data), ['addressSmtp']),
						title: Loc.getMessage('MAILBOX_CONNECTOR_SMTP_ADDRESS_TITLE'),
						placeholder: Loc.getMessage('MAILBOX_CONNECTOR_SMTP_ADDRESS_PLACEHOLDER'),
					}),
					NumberField({
						testId: 'connecting-mailboxes-connecting-mailbox-imap-smtp-smtp-port',
						ref: this.constants.fields.smtpPort.ref,
						showRequired: false,
						required: true,
						value: this.state.fields.smtpPort.value,
						onChange: useCallback((data) => this.onChangeField('smtpPort', data), ['smtpPort']),
						title: Loc.getMessage('MAILBOX_CONNECTOR_IMAP_PORT_TITLE'),
						placeholder: Loc.getMessage('MAILBOX_CONNECTOR_SMTP_PORT_PLACEHOLDER'),
					}),
					View(
						{
							onClick: secureConnectionSmtpAction,
							style: {
								paddingBottom: 10,
							},
						},
						Text({
							style: {
								paddingBottom: 6,
								color: AppTheme.colors.base3,
								fontSize: 10,
								fontWeight: '500',
							},
							text: Loc.getMessage('MAILBOX_CONNECTOR_USE_SECURE_CONNECTION').toLocaleUpperCase(env.languageId),
						}),
						new Switcher({
							testId: 'connecting-mailboxes-connecting-mailbox-imap-smtp-smtp-ssl-switcher',
							checked: checkedSslSmtp,
						}),
					),
				),
			);
		}
	}

	/**
	 * @class Imap
	 */
	class Imap extends WizardStep
	{
		checkMailboxCanConnected()
		{
			const canConnected = this.props.parent.validateFields(this.fieldsLayout.fieldRefs);

			if (!canConnected)
			{
				Haptics.notifyWarning();
			}

			return canConnected;
		}

		constructor(props)
		{
			super();
			this.props = props;
		}

		async onMoveToNextStep()
		{
			const response = { finish: false, next: false };

			if (!this.checkMailboxCanConnected())
			{
				return response;
			}

			const {
				login,
				password,
				server,
				addressSmtp,
				imapPort,
				smtpPort,
				ssl,
				sslSmtp,
			} = this.fieldsLayout.state.fields;

			if (login !== '' && password !== '' && imapPort !== '' && smtpPort !== '' && server !== '' && addressSmtp !== '')
			{
				NotifyManager.showLoadingIndicator();
				await this.props.parent.connectMailbox({
					useSmtp: 1,
					login: login.value,
					password: password.value,
					server: server.value,
					port: Number(imapPort.value),
					ssl: ssl.value,
					serverSmtp: addressSmtp.value,
					portSmtp: Number(smtpPort.value),
					sslSmtp: sslSmtp.value,
					loginSmtp: login.value,
					passwordSMTP: password.value,
				}).then(
					({ data }) => {
						this.props.parent.onConnectMailbox(data.email);
					},
				).catch(({ errors }) => {
					NotifyManager.showErrors(errors);
				});
			}

			return response;
		}

		getProgressBarSettings()
		{
			return {
				...super.getProgressBarSettings(),
				isEnabled: true,
				title: {
					text: Loc.getMessage('MAILBOX_CONNECTOR_IMAP_TITLE'),
				},
				number: 2,
				count: 3,
			};
		}

		getTitle()
		{
			return Loc.getMessage('MAILBOX_CONNECTOR_LOGIN_PASSWORD_HEADER_TITLE');
		}

		renderNumberBlock()
		{
			return new ProgressBarNumber({
				number: '2',
			});
		}

		resizableByKeyboard()
		{
			return true;
		}

		createLayout(props)
		{
			this.fieldsLayout = new FieldsLayout();

			return View(
				{
					style: {
						flex: 1,
						paddingBottom: isIOS ? 16 : 0,
					},
				},
				View(
					{
						style: {
							marginLeft: 16,
							marginRight: 16,
							marginTop: 16,
							marginBottom: 20,
							borderRadius: 12,
							flex: 1,
						},
					},
					ScrollView(
						{
							style: {
								flex: 1,
							},
						},
						View(
							{},
							this.fieldsLayout,
						),
					),
				),
			);
		}
	}

	module.exports = { Imap };
});
