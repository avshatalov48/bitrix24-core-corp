/**
 * @module crm/timeline/item/ui/body/blocks/ecommerce-documents-list
 */
jn.define('crm/timeline/item/ui/body/blocks/ecommerce-documents-list', (require, exports, module) => {
	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');

	/**
	 * @class TimelineItemBodyEcommerceDocumentsList
	 */
	class TimelineItemBodyEcommerceDocumentsList extends TimelineItemBodyBlock
	{
		get summaryOptions()
		{
			return BX.prop.getObject(this.props, 'summaryOptions', {});
		}

		get orders()
		{
			return BX.prop.getArray(this.summaryOptions, 'ORDERS', []);
		}

		get documents()
		{
			return BX.prop.getArray(this.summaryOptions, 'DOCUMENTS', []);
		}

		get isWithOrdersMode()
		{
			return BX.prop.getBoolean(this.props, 'isWithOrdersMode', false);
		}

		getFormattedMoneyWithCurrency(amount, currency)
		{
			return Money.create({ amount, currency }).formatted;
		}

		render()
		{
			return View(
				{},
				this.renderDocumentsBlock(),
				this.renderHorizontalLine(3, 11),
				this.renderSumOfDeal(),
				this.renderPaid(),
				this.renderHorizontalLine(7, 11),
				this.renderTotalPayedByDeal(),
			);
		}

		renderSumOfDeal()
		{
			return this.renderAmount(
				Loc.getMessage('CRM_TIMELINE_DOCUMENT_LIST_SUM_OF_DEAL'),
				this.summaryOptions.ENTITY_AMOUNT,
			);
		}

		renderPaid()
		{
			return this.renderAmount(
				Loc.getMessage('CRM_TIMELINE_DOCUMENT_LIST_PAID'),
				this.summaryOptions.PAID_AMOUNT,
			);
		}

		renderTotalPayedByDeal()
		{
			return this.renderAmount(
				Loc.getMessage('CRM_TIMELINE_DOCUMENT_LIST_TOTAL_PAID_BY_DEAL'),
				this.summaryOptions.TOTAL_AMOUNT,
				'700',
			);
		}

		renderAmount(title, amount, AmountFontWeight = '400')
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'space-between',
						marginBottom: 3,
					},
				},
				Text({
					style: {
						fontSize: 14,
						color: AppTheme.colors.base3,
					},
					text: title,
				}),
				Text({
					style: {
						fontSize: 14,
						color: AppTheme.colors.base1,
						fontWeight: AmountFontWeight,
					},
					text: this.getFormattedMoneyWithCurrency(
						amount,
						this.summaryOptions.CURRENCY_ID,
					),
				}),
			);
		}

		renderHorizontalLine(marginTop, marginBottom)
		{
			return View({
				style: {
					width: '100%',
					backgroundColor: AppTheme.colors.base6,
					height: 1,
					marginTop,
					marginBottom,
				},
			});
		}

		renderDocumentsBlock()
		{
			let documentViews = [];
			if (this.isWithOrdersMode)
			{
				this.orders.forEach((order) => {
					const currentOrderDocumentViews = this.getDocumentViews(order.ID);
					if (currentOrderDocumentViews.length === 0)
					{
						return;
					}

					documentViews.push(
						View(
							{
								style: {
									flexDirection: 'row',
									justifyContent: 'space-between',
									marginBottom: 13,
								},
							},
							Text({
								style: {
									color: AppTheme.colors.base1,
								},
								text: order.TITLE,
							}),
							Text({
								style: {
									color: AppTheme.colors.base1,
								},
								text: jnComponent.convertHtmlEntities(order.PRICE_FORMAT),
							}),
						),
					);
					documentViews = [...documentViews, ...currentOrderDocumentViews];
				});
			}
			else
			{
				documentViews = this.getDocumentViews();
			}

			return View(
				{},
				...documentViews,
			);
		}

		getDocumentViews(orderId = null)
		{
			const documentViews = [];
			this.documents.forEach((document) => {
				if (orderId && document.ORDER_ID !== orderId)
				{
					return;
				}
				let documentDescription;
				let documentSum;
				let labelText;
				let onClick;
				if (document.TYPE === 'SHIPMENT_DOCUMENT' && document.DEDUCTED === 'Y')
				{
					documentDescription = Loc.getMessage(
						'CRM_TIMELINE_DOCUMENT_LIST_SHIPMENT_DOCUMENT_DESCRIPTION',
						{
							'#DATE#': document.FORMATTED_DATE,
							'#ACCOUNT_NUMBER#': document.ACCOUNT_NUMBER,
						},
					);
					documentSum = Loc.getMessage(
						'CRM_TIMELINE_DOCUMENT_LIST_SHIPMENT_DOCUMENT_SUM',
						{
							'#SUM#': this.getFormattedMoneyWithCurrency(document.SUM, this.summaryOptions.CURRENCY_ID),
						},
					);
					labelText = Loc.getMessage('CRM_TIMELINE_DOCUMENT_LIST_SHIPMENT_DOCUMENT_LABEL');
					onClick = () => {
						this.timelineScopeEventBus.emit('EntityRealizationDocument::Click', [{
							id: document.ID,
							title: Loc.getMessage(
								'CRM_TIMELINE_DOCUMENT_LIST_SHIPMENT_DOCUMENT_SHORT_DESCRIPTION',
								{
									'#ACCOUNT_NUMBER#': document.ACCOUNT_NUMBER,
								},
							),
						}]);
					};
				}
				else if (document.TYPE === 'SHIPMENT' && document.DEDUCTED === 'Y')
				{
					documentDescription = Loc.getMessage(
						'CRM_TIMELINE_DOCUMENT_LIST_SHIPMENT_DESCRIPTION',
						{
							'#DATE#': document.FORMATTED_DATE,
							'#ACCOUNT_NUMBER#': document.ACCOUNT_NUMBER,
						},
					);
					documentSum = Loc.getMessage(
						'CRM_TIMELINE_DOCUMENT_LIST_SHIPMENT_SUM',
						{
							'#DELIVERY_NAME#': document.DELIVERY_NAME,
							'#SUM#': this.getFormattedMoneyWithCurrency(document.SUM, document.CURRENCY),
						},
					);
					labelText = Loc.getMessage('CRM_TIMELINE_DOCUMENT_LIST_SHIPMENT_LABEL');
					onClick = () => {
						qrauth.open({
							title: documentDescription,
							redirectUrl: `/crm/deal/details/${this.props.ownerId}/`,
							hintText: Loc.getMessage('CRM_TIMELINE_DOCUMENT_LIST_SHIPMENT_QRAUTH_HINT_MSGVER_1'),
							analyticsSection: 'crm',
						});
					};
				}
				else if (document.TYPE === 'PAYMENT' && document.PAID === 'Y')
				{
					documentDescription = Loc.getMessage(
						'CRM_TIMELINE_DOCUMENT_LIST_PAYMENT_DESCRIPTION',
						{
							'#DATE#': document.FORMATTED_DATE,
							'#ACCOUNT_NUMBER#': document.ACCOUNT_NUMBER,
						},
					);
					documentSum = Loc.getMessage(
						'CRM_TIMELINE_DOCUMENT_LIST_PAYMENT_SUM',
						{
							'#SUM#': this.getFormattedMoneyWithCurrency(document.SUM, document.CURRENCY),
						},
					);
					labelText = Loc.getMessage('CRM_TIMELINE_DOCUMENT_LIST_PAYMENT_LABEL');
					onClick = () => {
						this.timelineScopeEventBus.emit('EntityPaymentDocument::Click', [{
							ID: document.ID,
							TYPE: 'PAYMENT',
							FORMATTED_DATE: document.FORMATTED_DATE,
							ACCOUNT_NUMBER: document.ACCOUNT_NUMBER,
						}]);
					};
				}
				else
				{
					return;
				}
				documentViews.push(
					View(
						{
							style: {
								width: '100%',
								flexDirection: 'row',
								justifyContent: 'space-between',
								marginBottom: 13,
							},
						},
						View(
							{
								style: {
									flexShrink: 1,
								},
								onClick,
							},
							Text({
								style: {
									color: AppTheme.colors.accentMainLinks,
									fontSize: 14,
									lineHeight: 16,
									width: '100%',
								},
								ellipsize: 'end',
								text: documentDescription,
							}),
							Text({
								style: {
									color: AppTheme.colors.accentMainLinks,
									fontSize: 14,
									lineHeight: 16,
									width: '100%',
								},
								ellipsize: 'end',
								text: `(${documentSum})`,
							}),
						),
						View(
							{
								style: {
									backgroundColor: AppTheme.colors.accentSoftGreen3,
									borderRadius: 50,
									justifyContent: 'center',
									alignItems: 'center',
									paddingHorizontal: 8,
									height: 21,
									marginLeft: 10,
								},
							},
							Text({
								style: {
									color: AppTheme.colors.accentSoftElementGreen1,
									fontSize: 9,
									fontWeight: '700',
								},
								text: labelText.toLocaleUpperCase(env.languageId),
							}),
						),
					),
				);
			});

			return documentViews;
		}
	}

	module.exports = { TimelineItemBodyEcommerceDocumentsList };
});
