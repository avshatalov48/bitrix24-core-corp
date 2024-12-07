/**
 * @module crm/entity-document/payment-document
 */
jn.define('crm/entity-document/payment-document', (require, exports, module) => {
	const { CrmProductTabShimmer } = require('layout/ui/detail-card/tabs/shimmer/crm-product');
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { EntityDocumentProductGrid } = require('crm/entity-document/product/product-grid');
	const { EventEmitter } = require('event-emitter');
	const { handleErrors } = require('crm/error');
	const { Feature } = require('feature');
	const { TypeId } = require('crm/type');
	const { Moment } = require('utils/date');
	const { date, shortTime } = require('utils/date/formats');
	const { PureComponent } = require('layout/pure-component');
	const {
		FieldManagerService,
		FieldNameResponsible,
		FieldNameClient,
	} = require('crm/terminal/services/field-manager');
	const { PaymentPayOpener } = require('crm/terminal/entity/payment-pay-opener');
	const { getFormattedNumber } = require('utils/phone');
	const { InfoHelper } = require('layout/ui/info-helper');
	const { MultiFieldDrawer, MultiFieldType } = require('crm/multi-field-drawer');

	const PRODUCTS_FOR_LOADER_COUNT = 4;

	/**
	 * @class PaymentDocument
	 */
	class PaymentDocument extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				isLoading: true,
			};

			this.grid = {};
			this.entity = null;
			/** @type {Payment|null} */
			this.payment = null;
			/** @type {Array<Check>} */
			this.checks = [];
			this.shipment = null;

			this.uid = props.uid;
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);
			this.isTerminalToolEnabled = props.isTerminalToolEnabled ?? true;
			this.resendParams = props.resendParams ?? {};

			this.loadDocumentData();
		}

		static open(props, layout = PageManager, callbacks = {})
		{
			const { onOpen } = callbacks;

			const widgetParams = {
				backgroundColor: AppTheme.colors.bgPrimary,
				backdrop: {
					swipeAllowed: true,
					forceDismissOnSwipeDown: false,
					horizontalSwipeAllowed: false,
					showOnTop: true,
					navigationBarColor: AppTheme.colors.bgSecondary,
				},
			};

			layout.openWidget('layout', widgetParams)
				.then((layoutWidget) => {
					layoutWidget.enableNavigationBarBorder(false);
					layoutWidget.setTitle({
						text: ' ',
					});

					if (typeof onOpen === 'function')
					{
						onOpen(layoutWidget);
					}

					layoutWidget.showComponent(new this({
						...props,
						layoutWidget,
					}));
				})
				.catch(console.error);
		}

		loadDocumentData()
		{
			return new Promise(() => {
				const data = {
					entityId: this.entityId,
					entityTypeId: this.entityTypeId,
					documentId: this.id,
				};

				BX.ajax.runAction('crmmobile.Document.Payment.getDocumentData', { data })
					.then((response) => {
						this.grid = response.data.grid || {};
						this.shipment = response.data.shipment || null;
						this.payment = response.data.payment || null;
						this.checks = response.data.checks || [];

						this.entity = response.data.entity || null;

						this.layoutWidget.setTitle({
							text: Loc.getMessage('M_CRM_ENTITY_DOCUMENT_PAYMENT_TITLE', {
								'#DATE#': Moment.createFromTimestamp(this.payment.date).format(String(date())),
								'#ACCOUNT_NUMBER#': this.payment.accountNumber,
							}),
						});

						const rightButtons = [];

						if (this.payment.isTerminalPayment)
						{
							if (!this.payment.isPaid)
							{
								rightButtons.push({
									name: Loc.getMessage('M_CRM_ENTITY_DOCUMENT_RECEIVE_PAYMENT'),
									type: 'text',
									color: AppTheme.colors.accentMainLinks,
									callback: () => this.openTerminalPaymentPay(),
								});
							}
						}
						else
						{
							rightButtons.push({
								name: Loc.getMessage('M_CRM_ENTITY_DOCUMENT_SEND'),
								type: 'text',
								color: AppTheme.colors.accentMainLinks,
								callback: () => this.onClickSendMessageButton(),
							});
						}

						if (rightButtons.length > 0)
						{
							this.layoutWidget.setRightButtons(rightButtons);
						}

						this.fieldManagerService = new FieldManagerService(
							this.payment.fields,
							{
								renderIfEmpty: false,
							},
						);
					})
					.catch(handleErrors)
					.finally(() => this.setState({ isLoading: false }));
			});
		}

		openTerminalPaymentPay()
		{
			if (!this.isTerminalToolEnabled)
			{
				InfoHelper.openByCode('limit_crm_terminal_off', this.layoutWidget);

				return;
			}

			this.layoutWidget.close(() => {
				PaymentPayOpener.open({
					id: this.id,
					uid: this.uid,
					isStatusVisible: true,
				});
			});
		}

		onClickSendMessageButton()
		{
			this.layoutWidget.close(() => {
				if (!Feature.isReceivePaymentSupported())
				{
					Feature.showDefaultUnsupportedWidget();

					return;
				}

				if (this.resendParams.entityHasContact && !this.resendParams.contactHasPhone)
				{
					const multiFieldDrawer = new MultiFieldDrawer({
						entityTypeId: TypeId.Contact,
						entityId: this.resendParams.contactId,
						fields: [MultiFieldType.PHONE],
						onSuccess: this.openSendMessageStep.bind(this),
						warningBlock: {
							description: Loc.getMessage('M_CRM_ENTITY_DOCUMENT_PAYMENT_PHONE_DRAWER_WARNING_TEXT'),
						},
					});

					multiFieldDrawer.show();
				}
				else
				{
					this.openSendMessageStep();
				}
			});
		}

		openSendMessageStep()
		{
			const backdropParams = {
				swipeAllowed: false,
				forceDismissOnSwipeDown: false,
				horizontalSwipeAllowed: false,
				bounceEnable: true,
				showOnTop: true,
				topPosition: 60,
				navigationBarColor: AppTheme.colors.bgSecondary,
				helpUrl: helpdesk.getArticleUrl('17567646'),
			};

			const componentParams = {
				entityId: this.entityId,
				entityTypeId: this.entityTypeId,
				mode: 'payment',
				uid: this.uid,
				resendMessageMode: true,
				paymentId: this.id,
				entityHasContact: this.resendParams.entityHasContact,
			};

			ComponentHelper.openLayout({
				name: 'crm:salescenter.receive.payment',
				object: 'layout',
				widgetParams: {
					objectName: 'layout',
					title: Loc.getMessage('M_CRM_ENTITY_DOCUMENT_RESEND_LINK'),
					modal: true,
					backgroundColor: AppTheme.colors.bgPrimary,
					backdrop: backdropParams,
				},
				componentParams,
			});
		}

		getEntityTypeId()
		{
			return TypeId.OrderPayment;
		}

		renderProducts()
		{
			if (this.entity === null)
			{
				this.grid.products = [];
			}

			return View(
				{
					style: {
						flexGrow: 1,
					},
				},
				new EntityDocumentProductGrid({
					...this.grid,
					summaryComponents: {
						summary: true,
						amount: false,
						discount: this.grid.products.length > 0,
						taxes: false,
					},
					showEmptyScreen: false,
					showFloatingButton: false,
					discountCaption: Loc.getMessage('M_CRM_ENTITY_DOCUMENT_SUMMARY_TOTAL_DISCOUNT'),
					totalSumCaption: Loc.getMessage('M_CRM_ENTITY_DOCUMENT_SUMMARY_TOTAL'),
					additionalTopContent: this.renderAdditionalTopContent(),
					additionalBottomContent: this.renderAdditionalBottomContent(),
					additionalSummary: this.renderAdditionalSummary(),
					additionalSummaryBottom: this.renderAdditionalSummaryBottom(),
					uid: this.uid,
				}),
			);
		}

		renderAdditionalTopContent()
		{
			return View(
				{
					style: {
						backgroundColor: AppTheme.colors.bgPrimary,
					},
				},
				View(
					{
						style: {
							padding: 16,
							paddingTop: 0,
							paddingBottom: 0,
							backgroundColor: AppTheme.colors.bgContentPrimary,
							borderRadius: 12,
							marginBottom: 12,
						},
					},
					this.fieldManagerService.renderField(FieldNameClient, {
						testId: 'PaymentDocumentFieldClient',
						readOnly: true,
						config: {
							deepMergeStyles: {
								element: {
									title: {
										fontSize: 16,
									},
								},
							},
							showClientType: false,
							parentWidget: this.layoutWidget,
						},
					}),
				),
			);
		}

		renderAdditionalBottomContent()
		{
			if (!this.payment.isTerminalPayment)
			{
				return null;
			}

			const terminalCheckPhrase = Loc.getMessage(
				'M_CRM_ENTITY_DOCUMENT_TERMINAL_CHECK',
				{
					'#DATE#': Moment.createFromTimestamp(this.payment.date).format(`${date()} ${shortTime()}`),
				},
			);

			return View(
				{
					style: {
						backgroundColor: AppTheme.colors.bgPrimary,
					},
				},
				View(
					{
						style: {
							padding: 16,
							paddingTop: 0,
							paddingBottom: 12,
							backgroundColor: AppTheme.colors.bgContentPrimary,
							borderRadius: 12,
						},
					},
					this.fieldManagerService.renderField(
						FieldNameResponsible,
						{
							title: Loc.getMessage('M_CRM_ENTITY_DOCUMENT_TERMINAL'),
							showSubtitle: false,
							testId: 'PaymentDocumentFieldResponsible',
							renderIfEmpty: true,
							readOnly: false,
							showEditIcon: false,
							showRequired: false,
							config: {
								showSubtitle: false,
								parentWidget: this.layoutWidget,
							},
						},
					),
					this.payment.isPaid && this.payment.slipLink && BBCodeText({
						testId: 'PaymentDocumentSlipLink',
						style: {
							flexShrink: 2,
							color: AppTheme.colors.base4,
							fontSize: 15,
						},
						value: `[C type=dot textColor=${AppTheme.colors.base4} lineColor=${AppTheme.colors.base4}][COLOR=${AppTheme.colors.base4}][URL="${currentDomain + this.payment.slipLink}"]${terminalCheckPhrase}[/URL][/COLOR][/C]`,
						linksUnderline: false,
					}),
					this.payment.isPaid && this.payment.phoneNumber && View(
						{
							style: {
								paddingTop: 6,
							},
						},
						Text({
							text: Loc.getMessage('M_CRM_ENTITY_DOCUMENT_TERMINAL_CHECK_PHONE', {
								'#PHONE_NUMBER#': getFormattedNumber(this.payment.phoneNumber),
							}),
							testId: 'PaymentDocumentCheckPhone',
							style: {
								color: AppTheme.colors.base3,
								fontSize: 14,
								fontWeight: '400',
							},
						}),
					),
				),
			);
		}

		renderAdditionalSummary()
		{
			const paymentData = this.getSummaryPaymentData();
			const deliveryData = this.getSummaryDeliveryData();

			return View(
				{
					style: {
						padding: 4,
					},
				},
				Text({
					style: styles.summary.title,
					text: Loc.getMessage(
						'M_CRM_ENTITY_DOCUMENT_SUMMARY_TITLE_MSGVER_1',
						{
							'#DATE#': Moment.createFromTimestamp(this.payment.date).format(String(date())),
						},
					).toLocaleUpperCase(env.languageId),
				}),
				paymentData ? this.renderSummaryBlock(paymentData) : null,
				deliveryData ? this.renderSummaryBlock(deliveryData) : null,
				this.renderSeparator(),
			);
		}

		renderAdditionalSummaryBottom()
		{
			if (this.checks.length === 0)
			{
				return null;
			}

			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'flex-end',
						marginTop: 20,
					},
				},
				View(
					{
						style: {
							flexDirection: 'column',
						},
					},
					...this.checks.map((check, index) => {
						if (!check.url)
						{
							return null;
						}

						const checkText = Loc.getMessage(
							'M_CRM_ENTITY_DOCUMENT_PAYMENT_CHECK',
							{
								'#DATE#': Moment.createFromTimestamp(check.date).format(`${date()}, ${shortTime()}`),
							},
						);

						return BBCodeText({
							style: {
								flexShrink: 2,
								color: AppTheme.colors.base3,
								fontSize: 16,
								marginTop: index > 0 ? 10 : 0,
							},
							value: `[C type=dot textColor=${AppTheme.colors.base3} lineColor=${AppTheme.colors.base3}][COLOR=${AppTheme.colors.base3}][URL="${check.url}"]${checkText}[/URL][/COLOR][/C]`,
							linksUnderline: false,
						});
					}),
				),
			);
		}

		/**
		 *
		 * @param {{title: string, titleTestId: string, subtitle: string, subtitleTestId, badge: Object, money: string, moneyTestId: string}} data
		 * @returns {*}
		 */
		renderSummaryBlock(data)
		{
			return View(
				{
					style: styles.summaryBlock,
				},
				View(
					{
						style: {
							flexDirection: 'column',
							alignItems: 'flex-start',
							flexShrink: 2,
						},
					},
					Text({
						style: styles.summaryBlockText,
						text: data.title,
						testId: data.titleTestId,
					}),
					data.subtitle && Text({
						style: styles.summaryBlockText,
						text: data.subtitle,
						testId: data.subtitleTestId,
					}),
					this.renderBadge(data.badge),
				),
				View(
					{
						style: {
							marginLeft: 20,
						},
					},
					Text({
						testId: data.moneyTestId,
						style: styles.summaryBlockText,
						text: data.money,
					}),
				),
			);
		}

		renderBadge(badgeData)
		{
			const { testId, text, color, backgroundColor } = badgeData;

			return View(
				{
					style: styles.badge(backgroundColor),
				},
				Text({
					testId,
					style: styles.badgeText(color),
					text: text.toUpperCase(),
					ellipsize: 'end',
				}),
			);
		}

		renderSeparator()
		{
			return View({
				style: styles.summary.separator,
			});
		}

		/**
		 * @returns {Object}
		 */
		getSummaryPaymentData()
		{
			let productsPrice = 0;
			let productsCurrency = this.payment.currency;
			if (this.grid)
			{
				productsPrice = this.grid.summary.totalProductCost;
				productsCurrency = this.grid.summary.currency;
			}
			const money = Money.create({
				amount: productsPrice,
				currency: productsCurrency,
			});

			const title = this.entity === null
				? Loc.getMessage('M_CRM_ENTITY_DOCUMENT_SUMMARY_AMOUNT')
				: Loc.getMessage(
					'M_CRM_ENTITY_DOCUMENT_SUMMARY_PRODUCTS_TITLE',
					{
						'#AMOUNT#': this.grid.products.length,
					},
				);

			let subtitle = '';
			if (this.payment.isPaid)
			{
				const datePaid = Moment.createFromTimestamp(this.payment.datePaid).format(`${date()}, ${shortTime()} `);
				subtitle = `${this.payment.paymentSystemName} ${datePaid}`;
			}

			let badgeColor = AppTheme.colors.accentSoftOrange1;
			let badgeTextColor = AppTheme.colors.accentExtraBrown;

			let badgeText = Loc.getMessage('M_CRM_ENTITY_DOCUMENT_STAGE_NOT_PAID');

			if (this.payment.isPaid)
			{
				badgeColor = AppTheme.colors.accentSoftGreen1;
				badgeTextColor = AppTheme.colors.accentSoftElementGreen1;
				badgeText = Loc.getMessage('M_CRM_ENTITY_DOCUMENT_STAGE_PAID');
			}

			return {
				title,
				titleTestId: 'PaymentDocumentPaymentSummaryTitle',
				subtitle,
				subtitleTestId: 'PaymentDocumentPaymentSummarySubtitle',
				badge: {
					testId: 'PaymentDocumentPaymentSummaryBadge',
					text: badgeText,
					color: badgeTextColor,
					backgroundColor: badgeColor,
				},
				money: money.formatted,
				moneyTestId: 'PaymentDocumentPaymentMoney',
			};
		}

		/**
		 * @returns {Object}
		 */
		getSummaryDeliveryData()
		{
			const shipment = this.shipment;
			if (!shipment)
			{
				return null;
			}
			const shipmentMoney = Money.create({
				amount: shipment.BASE_PRICE_DELIVERY,
				currency: shipment.CURRENCY,
			});

			let badgeText = Loc.getMessage('M_CRM_ENTITY_DOCUMENT_SHIPMENT_WAITING');
			let bagdeColor = AppTheme.colors.base6;
			let bagdeTextColor = AppTheme.colors.base3;

			if (shipment.DEDUCTED === 'Y')
			{
				bagdeColor = AppTheme.colors.accentSoftGreen2;
				bagdeTextColor = AppTheme.colors.accentSoftElementGreen1;
				badgeText = Loc.getMessage('M_CRM_ENTITY_DOCUMENT_SHIPMENT_DONE');
			}

			return {
				title: Loc.getMessage('M_CRM_ENTITY_DOCUMENT_SUMMARY_DELIVERY_TITLE'),
				titleTestId: 'PaymentDocumentDeliverySummaryTitle',
				subtitle: shipment.DELIVERY_NAME,
				subtitleTestId: 'PaymentDocumentDeliverySummarySubtitle',
				badge: {
					testId: 'PaymentDocumentDeliverySummaryBadge',
					text: badgeText,
					color: bagdeTextColor,
					backgroundColor: bagdeColor,
				},
				money: shipmentMoney.formatted,
				moneyTestId: 'PaymentDocumentDeliveryMoney',
			};
		}

		render()
		{
			return View(
				{
					style: {
						backgroundColor: AppTheme.colors.bgPrimary,
					},
				},
				this.state.isLoading ? this.renderLoader() : this.renderProducts(),
			);
		}

		renderLoader()
		{
			return new CrmProductTabShimmer({
				animating: true,
				productCount: PRODUCTS_FOR_LOADER_COUNT,
			});
		}

		/**
		 * @returns {number}
		 */
		get id()
		{
			return BX.prop.getInteger(this.props, 'id', 0);
		}

		/**
		 * @returns {number}
		 */
		get entityId()
		{
			return BX.prop.getInteger(this.entity, 'id', 0);
		}

		/**
		 * @returns {number}
		 */
		get entityTypeId()
		{
			return BX.prop.getInteger(this.entity, 'typeId', 0);
		}

		/**
		 * @returns {Object}
		 */
		get layoutWidget()
		{
			return this.props.layoutWidget || PageManager;
		}
	}

	const styles = {
		summary: {
			title: {
				fontSize: 10,
				color: AppTheme.colors.base4,
				marginBottom: 14,
				fontWeight: '400',
				flexShrink: 2,
			},
			separator: {
				borderBottomWidth: 1,
				borderBottomColor: AppTheme.colors.base6,
				marginTop: -2,
				marginBottom: 5,
			},
		},
		summaryBlock: {
			flexDirection: 'row',
			justifyContent: 'space-between',
			marginBottom: 10,
		},
		summaryBlockText: {
			flexShrink: 2,
			color: AppTheme.colors.base4,
			fontSize: 14,
		},
		badge: (color) => ({
			backgroundColor: color,
			height: 21,
			borderRadius: 10.5,
			paddingHorizontal: 8,
			paddingVertical: 1,
			marginVertical: 4,
			justifyContent: 'center',
			flexShrink: 1,
		}),
		badgeText: (color) => ({
			color,
			fontSize: 9,
			fontWeight: '700',
			textAlign: 'center',
		}),
	};

	module.exports = {
		PaymentDocument,
	};
});
