/**
 * @module crm/mail/mailbox/connector
 */

jn.define('crm/mail/mailbox/connector', (require, exports, module) => {
	const { Wizard } = require('layout/ui/wizard');
	const { ServicesListStep } = require('crm/mail/mailbox/connector/steps/services-list');
	const { LoginPassword } = require('crm/mail/mailbox/connector/steps/login-password');
	const { Imap } = require('crm/mail/mailbox/connector/steps/imap');
	const { OAuth } = require('crm/mail/mailbox/connector/steps/oauth');
	const { Connected } = require('crm/mail/mailbox/connector/steps/connected');
	const { NotifyManager } = require('notify-manager');
	const AppTheme = require('apptheme');

	class Connector extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			const { parentWidget, successAction } = props;
			this.parentWidget = parentWidget;
			this.successAction = successAction || (() => {});
			this.connectedEmail = false;
			this.stepProps = {};
			this.getStepForId = this.getStepForId.bind(this);
			this.oauthMode = true;
		}

		validateFields(fieldRefs)
		{
			let validate = true;

			Object.values(fieldRefs).forEach((fieldRef) => {
				if (fieldRef && !fieldRef.validate())
				{
					validate = false;
				}
			});

			return validate;
		}

		loadConnectionUrl()
		{
			return BX.ajax.runAction('mail.mailboxconnecting.getConnectionUrl', {});
		}

		connectMailbox(props)
		{
			const {
				login = '',
				password = '',
				server = '',
				port = 993,
				ssl = true,
				storageOauthUid = '',
				useSmtp = 1,
				serverSmtp = '',
				portSmtp = 587,
				sslSmtp = true,
				loginSmtp = '',
				passwordSMTP = '',
			} = props;

			return BX.ajax.runAction('mail.mailboxconnecting.connectMailbox', {
				data: {
					serviceId: this.getMailServiceId(),
					login,
					password,
					server,
					port,
					ssl,
					storageOauthUid,
					useSmtp,
					serverSmtp,
					portSmtp,
					sslSmtp,
					loginSmtp,
					passwordSMTP,
				},
			});
		}

		loadServices()
		{
			return BX.ajax.runAction('mail.mailboxconnecting.getServices', {});
		}

		renderWizard()
		{
			return new Wizard({
				parentLayout: this.currentLayout,
				steps: this.getSteps().map((step) => step.id),
				stepForId: this.getStepForId,
				useProgressBar: true,
				hideProgressBarInLastTab: true,
				isNavigationBarBorderEnabled: true,
			});
		}

		saveMailServiceId(id)
		{
			this.currentMailServiceId = id;
		}

		getMailServiceId()
		{
			return Number(this.currentMailServiceId);
		}

		saveMailServiceKey(key)
		{
			this.currentMailServiceKey = key;
		}

		saveConnectedEmail(email)
		{
			this.connectedEmail = email;
		}

		getConnectedEmail()
		{
			return this.connectedEmail;
		}

		getServices()
		{
			return this.mailServices;
		}

		nextStep()
		{
			this.wizard.moveToNextStep();
		}

		goToStartStep()
		{
			this.wizard.openStepWidget('servicesList');
		}

		goToImap()
		{
			this.wizard.openStepWidget('imap');
		}

		goToLoginPassword()
		{
			this.wizard.openStepWidget('loginPassword');
		}

		goToOauth()
		{
			this.wizard.openStepWidget('oauth');
		}

		goToFinalStep()
		{
			this.wizard.openStepWidget('connected');
		}

		getMailServiceKey()
		{
			return this.currentMailServiceKey;
		}

		onErrorEnter(errors, showErrors = true)
		{
			this.goToStartStep();

			if (showErrors)
			{
				NotifyManager.showErrors(errors);
			}
		}

		onConnectMailbox(email)
		{
			this.saveConnectedEmail(email);
			NotifyManager.hideLoadingIndicatorWithoutFallback();
			this.goToFinalStep();
		}

		getStepForId(stepId)
		{
			const step = this.getSteps().find((step) => step.id === stepId);

			const props = this.stepProps[stepId] || {};
			props.parent = this;
			if (step)
			{
				return new step.component(props);
			}
		}

		getSteps()
		{
			const steps = [];

			steps.push(
				{
					id: 'servicesList',
					component: ServicesListStep,
				},
				{
					id: 'oauth',
					component: OAuth,
				},
				{
					id: 'imap',
					component: Imap,
				},
				{
					id: 'loginPassword',
					component: LoginPassword,
				},
				{
					id: 'connected',
					component: Connected,
				},
			);

			return steps;
		}

		render()
		{
			const wizard = this.renderWizard();
			this.wizard = wizard;

			return View(
				{
					style: {
						backgroundColor: AppTheme.colors.bgSecondary,
					},
				},
				wizard,
			);
		}

		setConnectionUrl(url)
		{
			this.connectionUrl = url;
		}

		async getConnectionUrl()
		{
			if (!this.connectionUrl)
			{
				await this.loadConnectionUrl().then((response) => {
					if (response.data)
					{
						this.setConnectionUrl(response.data);
					}
				});
			}

			return `${this.connectionUrl}?serviceName=${this.getMailServiceKey()}`;
		}

		show()
		{
			NotifyManager.showLoadingIndicator();
			this.loadServices().then(
				(response) => {
					if (response.data)
					{
						this.mailServices = response.data;
						NotifyManager.hideLoadingIndicatorWithoutFallback();
						const parentWidget = this.parentWidget || PageManager;
						parentWidget.openWidget('layout', {
							modal: true,
							backdrop: {
								horizontalSwipeAllowed: false,
								mediumPositionPercent: 90,
							},
						})
							.then((layoutWidget) => {
								this.currentLayout = layoutWidget;
								layoutWidget.showComponent(this);
							}).catch(console.error);
					}
				},
				(response) => {
					NotifyManager.hideLoadingIndicatorWithoutFallback();
					NotifyManager.showErrors(response.errors);
				},
			);
		}
	}

	module.exports = {
		Connector,
	};
});
