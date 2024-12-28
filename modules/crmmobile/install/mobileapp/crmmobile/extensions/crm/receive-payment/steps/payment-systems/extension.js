/**
 * @module crm/receive-payment/steps/payment-systems
 */
jn.define('crm/receive-payment/steps/payment-systems', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { SkipSwitcher } = require('crm/receive-payment/steps/payment-systems/skip-switcher');
	const { PaySystemSettings } = require('crm/receive-payment/steps/payment-systems/paysystem-settings');
	const { WizardStep } = require('layout/ui/wizard/step');
	const { ProgressBarNumber } = require('crm/salescenter/progress-bar-number');
	const { AnalyticsLabel } = require('analytics-label');
	const { MainBlockLayout } = require('crm/receive-payment/steps/payment-systems/main-block-layout');
	const { EventEmitter } = require('event-emitter');

	/**
	 * @class PaymentSystemsStep
	 */
	class PaymentSystemsStep extends WizardStep
	{
		constructor(props)
		{
			super();
			this.props = props;
			this.areAnalyticsSent = false;
			this.paymentSystemList = BX.prop.getArray(this.props, 'paymentSystemList', []);
			this.uid = props.uid || Random.getString();
			this.progressBarNumberRef = null;
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);
			this.customEventEmitter.on('ReceivePayment::onPreparedPaySystems', this.handleUpdatePaySystems.bind(this));
			this.firstLayout = false;
		}

		handleUpdatePaySystems({ paySystems })
		{
			this.paymentSystemList = paySystems;
			this.progressBarNumberRef.setState({ isCompleted: this.hasCashboxes() || this.paymentSystemList.length > 0 });
		}

		onEnterStep()
		{
			super.onEnterStep();

			if (!this.areAnalyticsSent)
			{
				AnalyticsLabel.send({
					event: 'onReceivePaymentPaymentSystemsStepOpen',
					hasPaymentSystems: this.hasPaymentSystems(),
					hasCashboxes: this.hasCashboxes(),
				});

				this.areAnalyticsSent = true;
			}
		}

		get isCashboxEnabled()
		{
			return BX.prop.getBoolean(this.props, 'isCashboxEnabled', false);
		}

		get cashboxList()
		{
			return BX.prop.getArray(this.props, 'cashboxList', []);
		}

		get isNeedToSkipPaymentSystems()
		{
			return BX.prop.getBoolean(this.props, 'isNeedToSkipPaymentSystems', false);
		}

		set isNeedToSkipPaymentSystems(value)
		{
			this.props.isNeedToSkipPaymentSystems = value;
		}

		get isYookassaAvailable()
		{
			return BX.prop.getBoolean(this.props, 'isYookassaAvailable', true);
		}

		hasCashboxes()
		{
			return this.cashboxList.length > 0;
		}

		hasPaymentSystems()
		{
			return this.paymentSystemList.length > 0;
		}

		renderNumberBlock()
		{
			return new ProgressBarNumber({
				number: this.getProgressBarSettings().number.toString(),
				isCompleted: (this.isCashboxEnabled && this.hasCashboxes()) || this.hasPaymentSystems(),
				ref: (ref) => this.progressBarNumberRef = ref,
			});
		}

		getProgressBarSettings()
		{
			return {
				...super.getProgressBarSettings(),
				isEnabled: true,
				title: {
					text: Loc.getMessage('M_RP_PS_PROGRESS_BAR_TITLE'),
				},
			};
		}

		isNeedToSkip()
		{
			return this.isNeedToSkipPaymentSystems || (!this.firstLayout && this.props.resendMessageMode);
		}

		getTitle()
		{
			return Loc.getMessage('M_RP_PS_TITLE');
		}

		createLayout(props)
		{
			this.firstLayout = true;

			return ScrollView(
				{
					style: {
						height: '100%',
					},
				},
				View(
					{
						style: {
							backgroundColor: AppTheme.colors.bgPrimary,
						},
					},
					this.renderPaySystemLayout(),
					this.renderCashboxLayout(),
					this.renderSkipSwitcherLayout(),
				),
			);
		}

		renderSkipSwitcherLayout()
		{
			return new SkipSwitcher({
				value: this.isNeedToSkipPaymentSystems,
				onChangeHandler: (value) => this.isNeedToSkipPaymentSystems = value,
			});
		}

		renderPaySystemLayout()
		{
			const walletSvgs = {
				enabled: 'wallet.svg',
				disabled: 'wallet-disabled.svg',
			};
			const titles = {
				enabled: Loc.getMessage('M_RP_PS_PAY_SYSTEM_WORK'),
				disabled: Loc.getMessage('M_RP_PS_PAY_SYSTEM_NOT_WORK'),
			};
			const description = Loc.getMessage('M_RP_PS_PAY_SYSTEM_DESC');
			const itemList = this.paymentSystemList;

			const onClick = () => {
				AnalyticsLabel.send({
					event: 'onReceivePaymentPaySystemsConfigClick',
				});
				if (this.isYookassaAvailable)
				{
					PaySystemSettings.open({
						uid: this.uid,
					});
				}
				else
				{
					qrauth.open({
						title: Loc.getMessage('M_RP_PS_SETTINGS_TITLE'),
						redirectUrl: '/saleshub/',
						analyticsSection: 'crm',
					});
				}
			};

			return new MainBlockLayout({
				icons: walletSvgs,
				titles,
				description,
				itemList,
				uid: this.uid,
				isFilledList: this.hasPaymentSystems(),
				settingsTitle: Loc.getMessage('M_RP_PS_PAY_SYSTEM_SETTINGS_QRAUTH_TITLE'),
				onClick,
				webRedirect: false,
				additionalStyle: {
					container: {
						paddingTop: 1,
					},
					iconImage: {
						marginTop: this.hasPaymentSystems() ? 16 : 14,
						marginLeft: this.hasPaymentSystems() ? 18 : 17,
					},
				},
			});
		}

		renderCashboxLayout()
		{
			if (!this.isCashboxEnabled)
			{
				return;
			}

			const cashboxSvgs = {
				enabled: 'cashbox.svg',
				disabled: 'cashbox-disabled.svg',
			};
			const titles = {
				enabled: Loc.getMessage('M_RP_PS_CASHBOX_CONNECTED'),
				disabled: Loc.getMessage('M_RP_PS_CASHBOX_NOT_CONNECTED'),
			};
			const description = Loc.getMessage('M_RP_PS_CASHBOX_DESC');
			const itemList = this.cashboxList;

			const onClick = () => {
				AnalyticsLabel.send({
					event: 'onReceivePaymentCashboxConfigClick',
				});
				qrauth.open({
					title: Loc.getMessage('M_RP_PS_CASHBOX_SETTINGS_QRAUTH_TITLE'),
					redirectUrl: '/saleshub/',
					analyticsSection: 'crm',
				});
			};

			return new MainBlockLayout({
				icons: cashboxSvgs,
				titles,
				description,
				itemList,
				isFilledList: this.hasCashboxes(),
				onClick,
				webRedirect: true,
				additionalStyle: {
					container: {
						paddingTop: 2,
					},
					iconImage: {
						marginTop: this.hasCashboxes() ? 16 : 14,
						marginLeft: this.hasCashboxes() ? 18 : 17,
					},
				},
			});
		}

		getNextStepButtonText()
		{
			return Loc.getMessage('M_RP_PS_NEXT_STEP');
		}

		onMoveToNextStep()
		{
			return Promise.resolve();
		}
	}

	module.exports = { PaymentSystemsStep };
});
