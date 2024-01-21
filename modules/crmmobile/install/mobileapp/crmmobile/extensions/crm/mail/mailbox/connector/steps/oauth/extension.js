/**
 * @module crm/mail/mailbox/connector/steps/oauth
 */

jn.define('crm/mail/mailbox/connector/steps/oauth', (require, exports, module) => {
	const { WizardStep } = require('layout/ui/wizard/step');
	const { ProgressBarNumber } = require('crm/salescenter/progress-bar-number');
	const { Loc } = require('loc');
	const { getParameterByName } = require('utils/url');
	const AppTheme = require('apptheme');

	class OAuth extends WizardStep
	{
		constructor(props)
		{
			super();
			this.props = props;
		}

		isNextStepEnabled()
		{
			return false;
		}

		getTitle()
		{
			return Loc.getMessage('MAILBOX_CONNECTOR_OAUTH_HEADER_TITLE');
		}

		renderNumberBlock()
		{
			return new ProgressBarNumber({
				number: '2',
			});
		}

		getProgressBarSettings()
		{
			return {
				...super.getProgressBarSettings(),
				isEnabled: true,
				title: {
					text: Loc.getMessage('MAILBOX_CONNECTOR_OAUTH_TITLE'),
				},
				number: 2,
				count: 3,
			};
		}

		startOAuth()
		{
			this.props.parent.getConnectionUrl().then(
				(response) => {
					const { OAuthSession } = jn.require('native/oauth');
					const session = new OAuthSession(response);
					session.start()
						.then(async ({ url }) => {
							const storageOauthUid = getParameterByName(url, 'storedUid');
							const login = getParameterByName(url, 'email');
							if (storageOauthUid && storageOauthUid !== '' && login && login !== '')
							{
								await this.props.parent.connectMailbox(
									{
										useSmtp: 0,
										storageOauthUid,
										login,
									},
								).then(
									({ data }) => {
										this.props.parent.onConnectMailbox(data.email);
									},
								).catch(({ errors }) => {
									this.props.parent.onErrorEnter(errors);
								});
							}
							else
							{
								const error = getParameterByName(url, 'error');
								let errors = [];
								if (error)
								{
									errors = [
										{
											message: error,
										},
									];
								}
								this.props.parent.onErrorEnter(errors);
							}
						})
						.catch(({ errors }) => {
							this.props.parent.onErrorEnter(errors, false);
						});
				},
			).catch(() => {
				this.props.parent.goToStartStep();
			});
		}

		createLayout(props)
		{
			this.startOAuth();

			return View(
				{},
				ScrollView(
					{
						style: {
							height: '100%',
						},
					},
					View(
						{
							style: {
								paddingTop: 200,
								alignItems: 'center',
							},
						},
						View(
							{
								justifyContent: 'center',
								flexDirection: 'row',
							},
							Loader({
								style: {
									width: 50,
									height: 50,
								},
								tintColor: '#00aeff',
								animating: true,
								size: 'large',
							}),
						),
					),
				),
			);
		}
	}

	module.exports = { OAuth };
});
