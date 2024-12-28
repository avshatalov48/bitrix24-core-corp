/**
 * @module crm/terminal/payment-pay
 */
jn.define('crm/terminal/payment-pay', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Alert } = require('alert');
	const AppTheme = require('apptheme');
	const { Random } = require('utils/random');
	const { Haptics } = require('haptics');
	const { withPressed } = require('utils/color');
	const { EventEmitter } = require('event-emitter');
	const { PureComponent } = require('layout/pure-component');
	const { PaymentButtonFactory } = require('crm/terminal/payment-pay/components/payment-button/factory');
	const { PaymentButton } = require('crm/terminal/payment-pay/components/payment-button/button');
	const { PaymentResultSuccess } = require('crm/terminal/payment-pay/components/payment-result/success');
	const { PaymentResultFailure } = require('crm/terminal/payment-pay/components/payment-result/failure');
	const { Oauth } = require('crm/payment-system/creation/actions/oauth');
	const { Before } = require('crm/payment-system/creation/actions/before');
	const { PaymentSystemService } = require('crm/terminal/services/payment-system');
	const { PaymentService } = require('crm/terminal/services/payment');
	const { ProductList } = require('crm/terminal/product-list');
	const { WarningBlock } = require('layout/ui/warning-block');
	const { ContextMenu } = require('layout/ui/context-menu');

	const {
		FieldManagerService,
		FieldNameSum,
		FieldNamePhone,
		FieldNameClient,
		FieldNameStatus,
	} = require('crm/terminal/services/field-manager');
	const { AnalyticsLabel } = require('analytics-label');
	const FISCALIZATION_ERROR_CODE = 'fiscalization_enabled';

	/**
	 * @class PaymentPay
	 */
	class PaymentPay extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.randomUid = Random.getString(10);
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			this.fieldManagerService = new FieldManagerService(this.payment.fields);
			this.paymentSystemService = new PaymentSystemService();
			this.paymentService = new PaymentService();
			this.psCreationOauthAction = (new Oauth())
				.setContext('terminal')
				.setHelpArticleId('17584326')
				.setLayout(this.layout);
			this.psCreationBeforeAction = new Before();

			/**
			 * @type {TerminalPaymentMethod}
			 */
			const paymentMethod = {
				type: null,
				paymentSystem: null,
			};

			this.state = {
				step: 'view',
				qrCode: null,
				paymentMethod,
				paymentSystems: this.payment.terminalPaymentSystems,
			};

			this.pullUnsubscribe = null;

			this.onPaymentMethodSelected = this.onPaymentMethodSelectedHandler.bind(this);
			this.onBackToPaymentMethods = this.onBackToPaymentMethodsHandler.bind(this);

			this.isPhoneConfirmed = props.isPhoneConfirmed ?? true;
			this.connectedSiteId = props.connectedSiteId ?? 0;
			this.phoneConfirmationWarningContexMenu = null;
		}

		render()
		{
			return View(
				{
					style: styles.container,
				},
				this.renderContent(),
			);
		}

		renderContent()
		{
			if (this.isStep(Steps.success))
			{
				return this.renderSuccess();
			}

			if (this.isStep(Steps.failure))
			{
				return this.renderFailure();
			}

			return this.renderPayment();
		}

		renderPayment()
		{
			return View(
				{
					style: styles.paymentContainer(this.isStep(Steps.loading)),
				},
				this.renderPaymentFields(),
				this.renderPaymentContent(),
			);
		}

		renderPaymentFields()
		{
			return View(
				{
					style: styles.fieldsContainer,
				},
				this.fieldManagerService.renderField(FieldNameSum, {
					testId: 'TerminalPaymentPayFieldSum',
					readOnly: true,
					config: {
						styles: {
							moneyValueWrapper: {
								flex: 1,
							},
						},
					},
					renderAdditionalRightContent: () => {
						if (
							!(
								this.payment.hasEntityBinding
								&& this.payment.productsCnt > 0
							)
						)
						{
							return;
						}

						return View(
							{
								style: {
									flex: 1,
									alignItems: 'flex-end',
									alignSelf: 'flex-end',
								},
								onClick: () => {
									ProductList.open({
										id: this.payment.id,
										uid: this.uid,
										productsCnt: this.payment.productsCnt,
									}, this.layout);
								},
							},
							Text({
								style: {
									fontSize: 13,
									color: AppTheme.colors.accentMainLinks,
								},
								text: Loc.getMessage(
									'M_CRM_TL_PAYMENT_PAY_PRODUCTS_CNT',
									{
										'#CNT#': this.payment.productsCnt,
									},
								),
							}),
						);
					},
				}),
				this.fieldManagerService.renderField(FieldNamePhone, {
					testId: 'TerminalPaymentPayFieldPhone',
					readOnly: true,
				}),
				this.fieldManagerService.renderField(FieldNameClient, {
					testId: 'TerminalPaymentPayFieldClient',
					readOnly: true,
					config: {
						parentWidget: this.layout,
					},
				}),
				this.isStatusVisible && this.fieldManagerService.renderField(FieldNameStatus, {
					testId: 'TerminalPaymentPayFieldStatus',
					readOnly: true,
				}),
			);
		}

		renderPaymentContent()
		{
			if (this.isStep(Steps.loading))
			{
				return this.renderPaymentLoader();
			}

			if (this.isStep(Steps.payment))
			{
				return this.renderPaymentQr();
			}

			return ScrollView(
				{
					style: styles.paymentMethodsContainer,
					showsVerticalScrollIndicator: false,
				},
				this.renderPaymentMethods(),
			);
		}

		renderPaymentLoader()
		{
			return View(
				{
					style: styles.loaderContainer,
				},
				Loader({
					style: styles.loader,
					tintColor: AppTheme.colors.accentBrandBlue,
					animating: true,
					size: 'large',
				}),
				View(
					{
						style: styles.loaderBottomContainer,
					},
					Text({
						style: styles.loaderBottomText,
						text: Loc.getMessage('M_CRM_TL_PAYMENT_PAY_PLEASE_WAIT'),
					}),
				),
			);
		}

		renderPaymentQr()
		{
			return View(
				{
					style: styles.paymentQrContainer,
				},
				Text({
					id: 'TerminalPaymentPayScanQrText',
					style: styles.paymentQrText,
					text: Loc.getMessage('M_CRM_TL_PAYMENT_PAY_SCAN_QR'),
				}),
				View(
					{
						style: styles.paymentQrImageContainer,
					},
					Image({
						testId: 'TerminalPaymentPayQrCode',
						style: styles.paymentQrImage,
						base64: this.state.qrCode,
					}),
				),
				View(
					{
						testId: 'TerminalPaymentPayBackToPaymentMethodsButton',
						style: styles.backToPaymentMethodsButton,
						onClick: this.onBackToPaymentMethods,
					},
					View(
						{
							style: styles.backToPaymentMethodsButtonIconContainer,
						},
						Image({
							style: styles.backToPaymentMethodsButtonIcon,
							svg: {
								content: SvgIcons.backArrow,
							},
						}),
					),
					Text({
						style: styles.backToPaymentMethodsButtonText,
						text: Loc.getMessage('M_CRM_TL_PAYMENT_DETAILS_BACK_TO_PAYMENT_METHOD'),
					}),
				),
			);
		}

		renderPaymentMethods()
		{
			return View(
				{},
				this.state.paymentSystems.length === 0
				&& !this.payment.isLinkPaymentEnabled
				&& Text(
					{
						style: {
							color: AppTheme.colors.base3,
							fontSize: 15,
							textAlign: 'center',
							lineHeightMultiple: 1.2,
						},
						text: Loc.getMessage('M_CRM_TL_PAYMENT_PAY_NO_PAY_METHODS'),
					},
				),
				...this.state.paymentSystems.map((paymentSystem, index) => {
					return View(
						{
							style: styles.paymentMethodContainer(index === 0),
						},
						PaymentButtonFactory.createByPaymentSystem(
							paymentSystem,
							{
								paymentMethod: {
									type: PaymentMethodTypes.paymentSystem,
									paymentSystem,
								},
								uid: this.uid,
							},
						),
					);
				}),
				this.payment.isLinkPaymentEnabled && View(
					{
						style: styles.paymentMethodContainer(this.state.paymentSystems.length === 0),
					},
					this.renderLinkPaymentButton(),
				),
			);
		}

		renderLinkPaymentButton()
		{
			return new PaymentButton({
				testId: 'TerminalPaymentPayLinkPaymentButton',
				paymentMethod: {
					type: PaymentMethodTypes.paymentLink,
					paymentSystem: null,
				},
				uid: this.uid,
				text: Loc.getMessage('M_CRM_TL_PAYMENT_PAY_VIA_QR_PAYMENT_LINK'),
				iconUri: this.getImagePath('link-payment'),
				styles: {
					iconContainer: {
						marginRight: 10,
						width: 18,
						height: 19,
					},
					icon: {
						width: 17,
						height: 17,
					},
				},
			});
		}

		openPhoneConfirmationWarning()
		{
			if (this.phoneConfirmationWarningContexMenu === null)
			{
				const menuProps = {
					customSection: {
						layout: View(
							{
								style: styles.warningBlockContainer,
							},
							new WarningBlock({
								title: Loc.getMessage('M_CRM_TL_PAYMENT_PAY_PHONE_CONFIRMATION_WARNING_TITLE'),
								description: Loc.getMessage('M_CRM_TL_PAYMENT_PAY_PHONE_CONFIRMATION_WARNING_DESCRIPTION'),
								onClickCallback: () => {
									qrauth.open({
										title: Loc.getMessage('M_CRM_TL_PAYMENT_PAY_PHONE_CONFIRMATION_TITLE'),
										redirectUrl: `/shop/stores/?force_verify_site_id=${this.connectedSiteId}`,
										layout: this.phoneConfirmationWarningContexMenu.getActionParentWidget(),
										analyticsSection: 'crm',
									});
								},
							}),
						),
						height: 180,
					},
					params: {
						title: Loc.getMessage('M_CRM_TL_PAYMENT_PAY_PAYMENT_LINK_NOT_CONNECTED'),
						showCancelButton: true,
					},
				};

				this.phoneConfirmationWarningContexMenu = new ContextMenu(menuProps);
			}

			this.phoneConfirmationWarningContexMenu.show(this.layout);
		}

		renderSuccess()
		{
			return new PaymentResultSuccess({
				text: Loc.getMessage(
					'M_CRM_TL_PAYMENT_PAY_SUCCESS_PAYMENT_PAID',
					{
						'#NUMBER#': this.payment.accountNumber,
					},
				),
				actions: [
					{
						testId: 'TerminalPaymentPaySuccessBackToList',
						name: Loc.getMessage('M_CRM_TL_PAYMENT_PAY_SUCCESS_BACK_TO_LIST'),
						action: () => {
							this.layout.close();
						},
					},
				],
			});
		}

		renderFailure()
		{
			return new PaymentResultFailure({
				text: Loc.getMessage('M_CRM_TL_PAYMENT_PAY_FAILURE_ERROR_TEXT'),
				actions: [
					{
						testId: 'TerminalPaymentPayFailureGetNewQr',
						name: Loc.getMessage('M_CRM_TL_PAYMENT_PAY_FAILURE_GET_NEW_QR_GET_NEW_QR'),
						action: () => {
							AnalyticsLabel.send({ event: 'terminal-payment-pay-failure-retry' });

							if (this.state.paymentMethod.type === PaymentMethodTypes.paymentSystem)
							{
								this.payWithPaymentSystem();
							}
							else if (this.state.paymentMethod.type === PaymentMethodTypes.paymentLink)
							{
								this.payWithPaymentLink();
							}
						},
						type: 'primary',
					},
					{
						testId: 'TerminalPaymentPayFailureChooseOtherPaymentMethod',
						name: Loc.getMessage('M_CRM_TL_PAYMENT_PAY_FAILURE_CHOOSE_OTHER_PAYMENT_METHOD'),
						action: () => {
							AnalyticsLabel.send({ event: 'terminal-payment-pay-failure-choose-other-method' });
							this.setStep(Steps.view);
						},
					},
				],
			});
		}

		isStep(step)
		{
			return this.state.step === step;
		}

		setStep(step, callback = null)
		{
			this.setState({ step }, callback);
		}

		/**
		 * @param {TerminalPaymentMethod} paymentMethod
		 */
		onPaymentMethodSelectedHandler(paymentMethod)
		{
			this.setState({ paymentMethod }, () => {
				if (paymentMethod.type === PaymentMethodTypes.paymentSystem)
				{
					AnalyticsLabel.send({
						event: 'terminal-payment-pay-payment-system-clicked',
						handler: paymentMethod.paymentSystem.handler,
						type: paymentMethod.paymentSystem.type,
					});
					this.payWithPaymentSystem();
				}
				else if (paymentMethod.type === PaymentMethodTypes.paymentLink)
				{
					AnalyticsLabel.send({ event: 'terminal-payment-pay-link-payment-clicked' });
					this.payWithPaymentLink();
				}
			});
		}

		onBackToPaymentMethodsHandler()
		{
			AnalyticsLabel.send({ event: 'terminal-payment-pay-pick-payment-method' });
			this.setStep(Steps.view);
		}

		getActionProviderData(name)
		{
			const providerData = BX.prop.getObject(this.psCreationActionProviders, name, null);
			if (providerData === null)
			{
				return null;
			}

			return BX.prop.getObject(
				providerData,
				this.state.paymentMethod.paymentSystem.handler,
				null,
			);
		}

		payWithPaymentSystem()
		{
			Promise.resolve()
				.then(() => this.psCreationOauthAction.run(this.getActionProviderData('oauth')))
				.then(() => {
					this.setStep(Steps.loading);

					return Promise.resolve();
				})
				.then(() => this.psCreationBeforeAction.run(this.getActionProviderData('before')))
				.then(() => this.createPaymentSystem())
				.then(() => this.initiatePayment())
				.then(() => this.setStep(Steps.payment))
				.catch((response) => {
					if (response.isError || response.errors)
					{
						this.showError(response.errors || []);
						Haptics.notifyFailure();
					}

					this.setStep(Steps.view);
				});
		}

		createPaymentSystem()
		{
			return new Promise((resolve, reject) => {
				if (this.state.paymentMethod.paymentSystem.connected === true)
				{
					resolve();

					return;
				}

				const handler = this.state.paymentMethod.paymentSystem.handler;
				const type = this.state.paymentMethod.paymentSystem.type;

				this.paymentSystemService
					.create({ handler, type })
					.then((paymentSystemId) => {
						const paymentSystem = this.state.paymentSystems.find((item) => {
							return item.handler === handler && item.type === type;
						});

						if (!paymentSystem)
						{
							reject({
								errors: {
									message: Loc.getMessage('M_CRM_TL_PAYMENT_PAY_PAYMENT_SYSTEM_CREATION_ERROR_MESSAGE'),
								},
							});

							return;
						}

						paymentSystem.connected = true;
						paymentSystem.id = paymentSystemId;

						const paymentMethod = this.state.paymentMethod;
						paymentMethod.paymentSystem.id = paymentSystemId;
						paymentMethod.paymentSystem.connected = true;

						this.setState(
							{
								paymentSystems: this.state.paymentSystems,
								paymentMethod,
							},
							() => {
								resolve();
							},
						);
					})
					.catch(() => {
						reject({
							errors: {
								message: Loc.getMessage('M_CRM_TL_PAYMENT_PAY_PAYMENT_SYSTEM_CREATION_ERROR_MESSAGE'),
							},
						});
					});
			});
		}

		initiatePayment()
		{
			return new Promise((resolve, reject) => {
				this.paymentService
					.initiate({
						paymentId: this.payment.id,
						paymentSystemId: this.state.paymentMethod.paymentSystem.id,
						accessCode: this.payment.accessCode,
					})
					.then((qrCode) => {
						this.setState({ qrCode });
						Haptics.notifySuccess();
						resolve();
					})
					.catch((errors) => {
						reject({ isError: true, errors });
					});
			});
		}

		payWithPaymentLink()
		{
			if (!this.isPhoneConfirmed && this.connectedSiteId > 0)
			{
				this.openPhoneConfirmationWarning();

				return;
			}

			this.setStep(Steps.loading);

			this.paymentService
				.getLink(this.payment.id)
				.then((qrCode) => {
					this.setState({ qrCode });
					Haptics.notifySuccess();
					this.setStep(Steps.payment);
				})
				.catch((response) => {
					const { connectedSiteId, isPhoneConfirmed } = response.data;

					if (isPhoneConfirmed !== null)
					{
						this.isPhoneConfirmed = isPhoneConfirmed;
					}

					if (
						connectedSiteId
						&& connectedSiteId > 0
						&& connectedSiteId !== this.connectedSiteId
					)
					{
						this.connectedSiteId = connectedSiteId;
					}

					Haptics.notifyFailure();
					this.setStep(Steps.view);

					if (!this.isPhoneConfirmed && this.connectedSiteId > 0)
					{
						this.openPhoneConfirmationWarning();

						return;
					}

					this.showError();
				});
		}

		showError(errors = [])
		{
			const errorText = errors.map((error) => error.message).join('\n');
			const hasFiscalizationError = errors.some((error) => error.code === FISCALIZATION_ERROR_CODE);

			if (hasFiscalizationError)
			{
				Alert.confirm(
					Loc.getMessage('M_CRM_TL_PAYMENT_PAY_DEFAULT_ERROR_TITLE'),
					errorText || Loc.getMessage('M_CRM_TL_PAYMENT_PAY_DEFAULT_ERROR_MESSAGE'),
					[
						{
							text: Loc.getMessage('M_CRM_TL_PAYMENT_PAY_DEFAULT_ERROR_BUTTON_CONFIRM_TITLE'),
							type: 'default',
						},
						{
							text: Loc.getMessage('M_CRM_TL_PAYMENT_PAY_DEFAULT_ERROR_BUTTON_HELP_TITLE'),
							type: 'default',
							onPress: () => helpdesk.openHelpArticle('17886650', 'helpdesk'),
						},
					],
				);
			}
			else
			{
				Alert.alert(
					Loc.getMessage('M_CRM_TL_PAYMENT_PAY_DEFAULT_ERROR_TITLE'),
					errorText || Loc.getMessage('M_CRM_TL_PAYMENT_PAY_DEFAULT_ERROR_MESSAGE'),
				);
			}

			this.setStep(Steps.view);
		}

		componentDidMount()
		{
			if (this.layout)
			{
				this.layout.enableNavigationBarBorder(false);
				this.layout.setBottomSheetHeight(this.getHeight());
				this.layout.setTitle({
					text: this.payment.name,
				});
			}

			this.customEventEmitter.on(
				'TerminalPayment::onPaymentMethodSelected',
				this.onPaymentMethodSelected,
			);
			this.pullSubscribe();
		}

		componentWillUnmount()
		{
			this.customEventEmitter.off(
				'TerminalPayment::onPaymentMethodSelected',
				this.onPaymentMethodSelected,
			);
			if (this.pullUnsubscribe)
			{
				this.pullUnsubscribe();
			}
		}

		pullSubscribe()
		{
			this.pullUnsubscribe = BX.PULL.subscribe({
				moduleId: this.pullConfig.moduleId,
				callback: (data) => {
					const command = BX.prop.getString(data, 'command', '');
					const params = BX.prop.getObject(data, 'params', {});
					const { eventName, paymentId } = params;

					if (
						!(
							command === this.pullConfig.command
							&& paymentId === this.payment.id
						)
					)
					{
						return;
					}

					if (eventName === this.pullConfig.events.success)
					{
						this.setStep(Steps.success);
						Haptics.notifySuccess();
					}

					if (eventName === this.pullConfig.events.failure)
					{
						this.setStep(Steps.failure);
						Haptics.notifyFailure();
					}
				},
			});
		}

		get layout()
		{
			return this.props.layout || {};
		}

		get payment()
		{
			return this.props.payment || {};
		}

		get psCreationActionProviders()
		{
			return this.props.psCreationActionProviders || {};
		}

		get isStatusVisible()
		{
			if (this.isStep(Steps.payment))
			{
				return false;
			}

			return BX.prop.getBoolean(this.props, 'isStatusVisible', false);
		}

		get pullConfig()
		{
			return this.props.pullConfig || {};
		}

		get uid()
		{
			return this.props.uid || this.randomUid;
		}

		getImagePath(image)
		{
			return `${currentDomain}/bitrix/mobileapp/crmmobile/extensions/crm/terminal/payment-pay/images/${image}.png`;
		}

		getHeight()
		{
			const buttonsCount = this.payment.terminalPaymentSystems.length + 1;

			const result = TITLE_HEIGHT
				+ FIELDS_HEIGHT
				+ PAYMENT_METHODS_CONTAINER_MARGIN_TOP
				+ PAYMENT_METHODS_CONTAINER_MARGIN_BOTTOM
				+ buttonsCount * PaymentButton.getHeight()
				+ (buttonsCount - 1) * PAYMENT_BUTTON_MARGIN_TOP;

			if (result < PaymentPay.getMinHeight())
			{
				return PaymentPay.getMinHeight();
			}

			return result;
		}

		static getMinHeight()
		{
			return MIN_HEIGHT;
		}
	}

	const MIN_HEIGHT = 630;
	const TITLE_HEIGHT = 55;
	const FIELDS_HEIGHT = 245;
	const PAYMENT_METHODS_CONTAINER_MARGIN_TOP = 50;
	const PAYMENT_METHODS_CONTAINER_MARGIN_BOTTOM = 25;
	const PAYMENT_BUTTON_MARGIN_TOP = 16;

	const Steps = {
		view: 'view',
		loading: 'loading',
		payment: 'payment',
		success: 'success',
		failure: 'failure',
	};

	const PaymentMethodTypes = {
		paymentSystem: 'paymentSystem',
		paymentLink: 'paymentLink',
	};

	const SvgIcons = {
		backArrow: '<svg width="10" height="15" viewBox="0 0 10 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M9.63067 12.9271L5.10362 8.40008L3.96961 7.26991L5.10362 6.14067L9.63067 1.61362L8.0332 0.0161552L0.779042 7.27031L8.0332 14.5245L9.63067 12.9271Z" fill="#525C69"/></svg>',
	};

	const styles = {
		container: {
			flex: 1,
			backgroundColor: AppTheme.colors.bgSecondary,
		},
		paymentContainer: (isLoading) => ({
			flex: 1,
			opacity: isLoading ? 0.3 : 1,
		}),
		fieldsContainer: {
			backgroundColor: AppTheme.colors.bgContentPrimary,
			borderRadius: 12,
			paddingTop: 14,
			paddingBottom: 8,
			paddingHorizontal: 16,
		},
		loaderContainer: {
			marginTop: 116,
			backgroundColor: AppTheme.colors.bgSecondary,
			alignItems: 'center',
		},
		loader: {
			width: 90,
			height: 90,
		},
		loaderBottomContainer: {
			marginTop: 34,
		},
		loaderBottomText: {
			fontWeight: '400',
			fontSize: 18,
			color: AppTheme.colors.base1,
		},
		paymentQrContainer: {
			flex: 1,
			justifyContent: 'space-evenly',
			alignItems: 'center',
		},
		paymentQrText: {
			marginHorizontal: 30,
			fontWeight: '700',
			fontSize: 18,
			color: AppTheme.colors.base1,
			textAlign: 'center',
		},
		paymentQrImageContainer: {
			width: 218,
			height: 218,
			backgroundColor: AppTheme.colors.bgContentPrimary,
			borderRadius: 12,
			flexDirection: 'row',
			justifyContent: 'center',
			alignItems: 'center',
		},
		paymentQrImage: {
			width: 190,
			height: 190,
			alignSelf: 'center',
		},
		backToPaymentMethodsButton: {
			flexDirection: 'row',
			alignItems: 'center',
			justifyContent: 'center',
			paddingHorizontal: 14,
			paddingTop: 9,
			paddingBottom: 9,
			borderRadius: 6,
			borderWidth: 1,
			borderColor: AppTheme.colors.base3,
			backgroundColor: withPressed(AppTheme.colors.bgPrimary),
			height: 42,
		},
		backToPaymentMethodsButtonIconContainer: {
			width: 28,
			height: 28,
			justifyContent: 'center',
			alignItems: 'center',
		},
		backToPaymentMethodsButtonIcon: {
			width: 10,
			height: 15,
		},
		backToPaymentMethodsButtonText: {
			color: AppTheme.colors.base1,
			fontSize: 17,
			fontWeight: '400',
		},
		paymentMethodsContainer: {
			flex: 1,
			marginTop: PAYMENT_METHODS_CONTAINER_MARGIN_TOP,
			marginBottom: PAYMENT_METHODS_CONTAINER_MARGIN_BOTTOM,
			marginHorizontal: 38,
		},
		warningBlockContainer: {
			margin: 10,
		},
		paymentMethodContainer: (isFirst) => {
			return {
				marginTop: isFirst ? 0 : PAYMENT_BUTTON_MARGIN_TOP,
			};
		},
	};

	module.exports = { PaymentPay };
});
