/**
 * @module crm/terminal/payment-create
 */
jn.define('crm/terminal/payment-create', (require, exports, module) => {
	const { Alert } = require('alert');
	const { Loc } = require('loc');
	const { PureComponent } = require('layout/pure-component');
	const { EventEmitter } = require('event-emitter');
	const { Haptics } = require('haptics');
	const { mergeImmutable } = require('utils/object');
	const { PaymentPay } = require('crm/terminal/payment-pay');
	const { PaymentService } = require('crm/terminal/services/payment');
	const { FindsClientService } = require('crm/terminal/services/finds-client');
	const { DuplicatesPanel } = require('crm/duplicates/panel');
	const {
		FieldManagerService,
		FieldNameSum,
		FieldNamePhone,
		FieldNameClientName,
	} = require('crm/terminal/services/field-manager');
	const { AnalyticsLabel } = require('analytics-label');

	/**
	 * @class PaymentCreate
	 */
	class PaymentCreate extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.fieldManagerService = new FieldManagerService(this.fields);
			this.paymentService = new PaymentService();
			this.findsClientService = new FindsClientService();

			/** @type {TerminalPayment|null} */
			this.payment = null;
			/** @type {TerminalClientProps|null} */
			this.client = null;

			this.state = {
				step: Steps.create,
				isSumValid: false,
				phone: {
					phoneNumber: '',
				},
				sum: {
					amount: 0,
					currency: this.currencyId,
				},
				name: '',
			};

			this.randomUid = Random.getString(10);
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			this.sumFieldRef = null;
			this.phoneFieldRef = null;

			this.onChangePhone = this.onChangePhoneHandler.bind(this);
			this.onPhoneFieldRef = this.phoneFieldRefHandler.bind(this);
			this.onSumFieldRef = this.sumFieldRefHandler.bind(this);
			this.onChangeSum = this.onChangeSumHandler.bind(this);
			this.onChangeName = this.onChangeNameHandler.bind(this);
			this.onContinue = this.onContinueHandler.bind(this);
			this.onCancel = this.onCancelHandler.bind(this);
		}

		render()
		{
			return View(
				{
					resizableByKeyboard: true,
					style: styles.container,
					onClick: () => Keyboard.dismiss(),
					onPan: () => Keyboard.dismiss(),
				},
				...this.renderContent(),
			);
		}

		renderContent()
		{
			if (this.state.step === Steps.created)
			{
				return [
					this.renderPaymentPay(),
				];
			}

			return [
				this.renderPaymentCreate(),
				this.renderBottomPanel(),
			];
		}

		renderPaymentPay()
		{
			return new PaymentPay({
				payment: this.payment,
				layout: this.layout,
				isStatusVisible: false,
				...this.paymentPayProps,
			});
		}

		renderPaymentCreate()
		{
			const fields = [
				{
					name: FieldNameSum,
					data: {
						testId: 'TerminalPaymentCreateFieldSum',
						value: this.state.sum,
						required: true,
						onChange: this.onChangeSum,
						ref: this.onSumFieldRef,
					},
				},
				{
					name: FieldNamePhone,
					data: {
						testId: 'TerminalPaymentCreateFieldPhone',
						value: this.state.phone,
						onChange: this.onChangePhone,
						ref: this.onPhoneFieldRef,
						config: {
							countryCode: this.defaultCountry,
						},
					},
				},
				{
					name: FieldNameClientName,
					data: {
						testId: 'TerminalPaymentCreateFieldName',
						value: this.state.name,
						onChange: this.onChangeName,
					},
				},
			];

			return View(
				{
					style: styles.paymentCreateContainer,
				},
				...fields.map((field, index) => {
					const data = field.data;
					if (index > 0)
					{
						if (!data.config)
						{
							data.config = {};
						}

						data.config.deepMergeStyles = {
							externalWrapper: {
								marginTop: 10,
							},
						};
					}

					return this.fieldManagerService.renderField(
						field.name,
						mergeImmutable(defaultFieldData, data),
					);
				}),
			);
		}

		renderBottomPanel()
		{
			return new UI.BottomToolbar({
				renderContent: () => View(
					{
						style: styles.bottomPanel.container,
					},
					View(
						{
							testId: 'TerminalPaymentCreateCancelButton',
							style: styles.bottomPanel.cancelButton.container,
							onClick: this.onCancel,
						},
						Text({
							style: styles.bottomPanel.cancelButton.text,
							text: Loc.getMessage('M_CRM_TL_PAYMENT_CREATE_CANCEL'),
						}),
					),
					View(
						{
							testId: 'TerminalPaymentCreateContinueButton',
							style: styles.bottomPanel.continueButton.container,
							onClick: this.onContinue,
						},
						Text({
							style: styles.bottomPanel.continueButton.text(this.state.isSumValid),
							text: Loc.getMessage('M_CRM_TL_PAYMENT_CREATE_CONTINUE'),
						}),
					),
				),
			});
		}

		componentDidMount()
		{
			if (this.layout)
			{
				this.layout.enableNavigationBarBorder(false);
			}

			if (this.sumFieldRef)
			{
				this.sumFieldRef.focus();
			}
		}

		onChangePhoneHandler(value)
		{
			this.setState({ phone: value });
			this.client = null;

			if (!(value.phoneNumber && value.phoneNumber.length >= minPhoneSearchLength))
			{
				return;
			}

			this.findsClientService
				.findClient(value.phoneNumber)
				.then((duplicates) => {
					if (this.state.step !== Steps.create)
					{
						return;
					}

					this.openDuplicatesPanel({ ENTITIES: duplicates });
				});
		}

		openDuplicatesPanel(duplicates)
		{
			const panel = new DuplicatesPanel({
				uid: this.uid,
				entityType: 'CONTACT',
				isAllowed: true,
				isAllowedAnyEntityType: true,
				duplicates,
				shouldCloseOnEntityOpen: false,
				useDuplicateForEntityDetails: false,
				onUseContact: (id, entityTypeId, params) => {
					Haptics.impactLight();
					this.setState({ name: params.title });
					this.client = { id, entityTypeId };
					panel.close(() => {
						this.phoneFieldRef.focus();
					});
				},
			});

			panel.open(this.layout);
		}

		sumFieldRefHandler(ref)
		{
			this.sumFieldRef = ref;
		}

		phoneFieldRefHandler(ref)
		{
			this.phoneFieldRef = ref;
		}

		onChangeSumHandler(value)
		{
			this.setState({ sum: value }, () => {
				const isSumValid = this.state.sum.amount > 0;

				if (isSumValid !== this.state.isSumValid)
				{
					this.setState({ isSumValid });
				}
			});
		}

		onChangeNameHandler(value)
		{
			this.setState({ name: value });
			this.client = null;
		}

		onContinueHandler()
		{
			const isActive = this.state.isSumValid;

			AnalyticsLabel.send({
				event: 'terminal-new-payment-continue',
				isActive: isActive ? 'Y' : 'N',
			});

			if (!isActive)
			{
				Haptics.notifyWarning();
				return;
			}

			Notify.showIndicatorLoading();

			this.setState({ step: Steps.creating }, () => {
				this.paymentService.create({
					sum: this.state.sum.amount,
					currency: this.state.sum.currency,
					phoneNumber: this.state.phone.phoneNumber,
					client: this.client,
					clientName: this.client ? null : this.state.name,
				})
					.then((payment) => {
						Haptics.notifySuccess();
						AnalyticsLabel.send({ event: 'terminal-new-payment-created' });
						Keyboard.dismiss();

						this.layout.setTitle({ text: payment.name });
						this.payment = payment;
						this.setState({
							step: Steps.created,
						});

						this.customEventEmitter.emit('TerminalPayment::onCreated', [this.payment.id]);
					})
					.catch(() => {
						Haptics.notifyFailure();
						this.setState({
							step: Steps.create,
						});
						Alert.alert(
							Loc.getMessage('M_CRM_TL_PAYMENT_CREATE_ERROR_TITLE'),
							Loc.getMessage('M_CRM_TL_PAYMENT_CREATE_ERROR_TEXT'),
						);
					})
					.finally(() => {
						Notify.hideCurrentIndicator();
					});
			});
		}

		onCancelHandler()
		{
			AnalyticsLabel.send({ event: 'terminal-new-payment-cancel' });
			this.layout.close();
		}

		get layout()
		{
			return this.props.layout || {};
		}

		get uid()
		{
			return this.props.uid || this.randomUid;
		}

		get fields()
		{
			return this.props.fields || [];
		}

		get currencyId()
		{
			return BX.prop.getString(this.props, 'currencyId', null);
		}

		get defaultCountry()
		{
			return BX.prop.getString(this.props, 'defaultCountry', null);
		}

		get paymentPayProps()
		{
			return this.props.paymentPayProps || {};
		}
	}

	const Steps = {
		create: 'create',
		creating: 'creating',
		created: 'created',
	};

	const defaultFieldData = {
		readOnly: false,
		showBorder: true,
		hasSolidBorderContainer: true,
		config: {
			styles: {
				externalWrapperBorderColor: '#D5D7DB',
			},
			deepMergeStyles: {
				externalWrapper: {
					paddingVertical: 0,
					marginHorizontal: 16,
				},
			},
		},
	};

	const minPhoneSearchLength = 5;

	const styles = {
		container: {
			backgroundColor: '#EEF2F4',
		},
		paymentCreateContainer: {
			backgroundColor: '#FFFFFF',
			borderRadius: 12,
			paddingVertical: 18,
		},
		bottomPanel: {
			container: {
				flex: 1,
				flexDirection: 'row',
				height: 52,
			},
			cancelButton: {
				container: {
					justifyContent: 'center',
					alignContent: 'center',
					flexBasis: '50%',
				},
				text: {
					fontSize: 18,
					fontWeight: '500',
					color: '#525C69',
					textAlign: 'center',
				},
			},
			continueButton: {
				container: {
					justifyContent: 'center',
					alignContent: 'center',
					flexBasis: '50%',
					borderLeftWidth: 1,
					borderLeftColor: '#EEF2F4',
				},
				text: (isValid) => {
					return {
						fontSize: 18,
						fontWeight: '500',
						color: '#0B66C3',
						opacity: isValid ? 1 : 0.3,
						textAlign: 'center',
					};
				},
			},
		},
	};

	module.exports = { PaymentCreate };
});
