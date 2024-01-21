/**
 * @module crm/mail/mailbox/connector/steps/login-password
 */

jn.define('crm/mail/mailbox/connector/steps/login-password', (require, exports, module) => {
	const { Haptics } = require('haptics');
	const { WizardStep } = require('layout/ui/wizard/step');
	const { Loc } = require('loc');
	const { ProgressBarNumber } = require('crm/salescenter/progress-bar-number');
	const { StringField } = require('layout/ui/fields/string');
	const { EmailField } = require('layout/ui/fields/email');
	const { useCallback } = require('utils/function');
	const { clone } = require('utils/object');
	const { stringify } = require('utils/string');
	const { NotifyManager } = require('notify-manager');
	const { getServiceInfo } = require('crm/mail/mailbox/connector/steps/services-list');
	const AppTheme = require('apptheme');

	/**
	 * @class FieldsLayout
	 */
	class FieldsLayout extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.fieldRefs = {};
			this.constants = {
				fields: {
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

		getEmptyFieldsData()
		{
			return {
				login: {
					value: '',
				},
				password: {
					value: '',
				},
			};
		}

		onChangeField(key, data)
		{
			const fields = clone(this.state.fields);
			fields[key].value = stringify(data);
			this.setState({ fields });
		}

		render()
		{
			return View(
				{},
				EmailField({
					testId: 'connecting-mailboxes-connecting-mailbox-login-password-email',
					ref: this.constants.fields.login.ref,
					showLeftIcon: false,
					showRequired: false,
					required: true,
					value: this.state.fields.login.value,
					onChange: useCallback((data) => this.onChangeField('login', data), ['login']),
					title: Loc.getMessage('MAILBOX_CONNECTOR_LOGIN_PASSWORD_LOGIN_TITLE'),
					placeholder: Loc.getMessage('MAILBOX_CONNECTOR_LOGIN_PASSWORD_LOGIN_PLACEHOLDER'),
				}),
				StringField({
					testId: 'connecting-mailboxes-connecting-mailbox-login-password-password',
					ref: this.constants.fields.password.ref,
					showRequired: false,
					required: true,
					value: this.state.fields.password.value,
					onChange: useCallback((data) => this.onChangeField('password', data), ['password']),
					title: Loc.getMessage('MAILBOX_CONNECTOR_LOGIN_PASSWORD_PASSWORD_TITLE'),
					placeholder: Loc.getMessage('MAILBOX_CONNECTOR_LOGIN_PASSWORD_PASSWORD_PLACEHOLDER'),
					isPassword: true,
				}),
			);
		}
	}

	/**
	 * @class LoginPassword
	 */
	class LoginPassword extends WizardStep
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
			} = this.fieldsLayout.state.fields;

			if (login !== '' && password !== '')
			{
				NotifyManager.showLoadingIndicator();
				await this.props.parent.connectMailbox({
					login: login.value,
					password: password.value,
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
					text: Loc.getMessage('MAILBOX_CONNECTOR_LOGIN_PASSWORD_TITLE_1'),
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

		titleBlock()
		{
			const serviceKey = this.props.parent.getMailServiceKey();

			if (!serviceKey)
			{
				return null;
			}

			return View(
				{
					style: {
						marginBottom: 20,
						flexDirection: 'row',
					},
				},
				View(
					{
						style: {
							marginRight: 10,
							width: 32,
							height: 32,
						},
					},
					Image({
						svg: {
							content: getServiceInfo(serviceKey).svgContent,
						},
						style: {
							width: 32,
							height: 32,
						},
					}),
				),
				Text({
					style: {
						textAlign: 'center',
						color: AppTheme.colors.base2,
						fontSize: 18,
						fontWeight: '400',
					},
					text: Loc.getMessage('MAILBOX_CONNECTOR_LOGIN_PASSWORD_TITLE_2'),
				}),
			);
		}

		createLayout(props)
		{
			this.fieldsLayout = new FieldsLayout();

			return View(
				{},
				View(
					{
						style: {},
					},
					View(
						{
							style: {
								margin: 16,
								borderRadius: 12,
								backgroundColor: AppTheme.colors.bgContentPrimary,
								paddingLeft: 15,
								paddingRight: 15,
								paddingBottom: 37,
								paddingTop: 27,
							},
						},
						this.titleBlock(),
						View(
							{
								style: {
									paddingLeft: 5,
								},
							},
							this.fieldsLayout,
						),
					),
				),
			);
		}
	}

	module.exports = { LoginPassword };
});
