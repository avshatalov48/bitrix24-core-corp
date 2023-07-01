/**
 * @module crm/entity-document/base-document
 */
jn.define('crm/entity-document/base-document', (require, exports, module) => {
	const { CrmProductTabShimmer } = require('layout/ui/detail-card/tabs/shimmer/crm-product');
	const { Loc } = require('loc');
	const { EntityDocumentProductGrid } = require('crm/entity-document/product/product-grid');
	const { EventEmitter } = require('event-emitter');
	const { handleErrors } = require('crm/error');
	const { Feature } = require('feature');

	const PRODUCTS_FOR_LOADER_COUNT = 4;

	/**
	 * @class BaseDocument
	 */
	class BaseDocument extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.document = props.document;
			this.documentType = props.document.TYPE;
			this.layoutWidget = props.layoutWidget;
			this.entityId = parseInt(props.entityModel.ID, 10);
			this.entityTypeId = props.entityModel.ENTITY_TYPE_ID;
			this.grid = {};
			this.shipment = null;
			this.payment = null;
			this.state = {
				isLoading: true,
			};
			this.uid = props.uid;
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);
			this.loadProducts();
		}

		componentDidMount()
		{
			super.componentDidMount();
			this.layoutWidget.setTitle({
				text: this.getDocumentTitle(),
			});
			this.layoutWidget.setRightButtons([
				{
					name: Loc.getMessage('M_CRM_ENTITY_DOCUMENT_SEND'),
					type: 'text',
					color: '#2066b0',
					callback: () => this.openSendMessageStep(),
				},
			]);
		}

		static open(props)
		{
			const parentWidget = props.parentWidget || PageManager;
			const widgetParams = {
				backdrop: {
					swipeAllowed: true,
					forceDismissOnSwipeDown: false,
					horizontalSwipeAllowed: false,
					showOnTop: true,
					navigationBarColor: '#eef2f4',
				},
			};

			parentWidget.openWidget('layout', widgetParams)
				.then((layoutWidget) => {
					layoutWidget.enableNavigationBarBorder(false);
					layoutWidget.setTitle({
						text: ' ',
					});
					layoutWidget.showComponent(new this({
						...props,
						layoutWidget,
					}));
				});
		}

		loadProducts()
		{
			const action = this.getLoadDocumentDataAction();
			return new Promise(() => {
				const data = {
					entityId: this.entityId,
					entityTypeId: this.entityTypeId,
					documentId: parseInt(this.document.ID, 10),
				};

				BX.ajax.runAction(action, { data })
					.then((response) => {
						this.grid = response.data.grid || {};
						this.shipment = response.data.shipment || null;
						this.payment = response.data.payment || null;
					})
					.catch(handleErrors)
					.finally(() => this.setState({ isLoading: false }));
			});
		}

		getDocumentTitle()
		{
			return Loc.getMessage('M_CRM_ENTITY_DOCUMENT_DEFAULT_TITLE');
		}

		openSendMessageStep()
		{
			this.layoutWidget.close(() => {
				if (!Feature.isReceivePaymentSupported())
				{
					Feature.showDefaultUnsupportedWidget();

					return;
				}

				const backdropParams = {
					swipeAllowed: false,
					forceDismissOnSwipeDown: false,
					horizontalSwipeAllowed: false,
					bounceEnable: true,
					showOnTop: true,
					topPosition: 60,
					navigationBarColor: '#eef2f4',
					helpUrl: helpdesk.getArticleUrl('17567646'),
				};

				const componentParams = {
					entityId: this.entityId,
					entityTypeId: this.entityTypeId,
					mode: this.document.TYPE.toLowerCase(),
					uid: this.uid,
					resendMessageMode: true,
					document: this.document,
				};

				ComponentHelper.openLayout({
					name: 'crm:salescenter.receive.payment',
					object: 'layout',
					widgetParams: {
						objectName: 'layout',
						title: Loc.getMessage('M_CRM_ENTITY_DOCUMENT_RESEND_LINK'),
						modal: true,
						backgroundColor: '#eef2f4',
						backdrop: backdropParams,
					},
					componentParams,
				});
			});
		}

		getEntityTypeId()
		{
			return TypeId.OrderPayment;
		}

		getLoadDocumentDataAction()
		{
			return 'crmmobile.Document.Payment.getDocumentData';
		}

		renderProducts()
		{
			return View(
				{
					style: {
						flexGrow: 1,
					},
				},
				new EntityDocumentProductGrid({
					...this.grid,
					showFloatingButton: false,
					showSummaryAmount: false,
					showSummaryTax: false,
					discountCaption: Loc.getMessage('M_CRM_ENTITY_DOCUMENT_SUMMARY_TOTAL_DISCOUNT'),
					totalSumCaption: Loc.getMessage('M_CRM_ENTITY_DOCUMENT_SUMMARY_TOTAL'),
					additionalSummary: this.renderAdditionalSummary(),
					uid: this.uid,
				}),
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
					text: Loc.getMessage('M_CRM_ENTITY_DOCUMENT_SUMMARY_TITLE').toLocaleUpperCase(env.languageId),
				}),
				paymentData ? this.renderSummaryBlock(paymentData) : null,
				deliveryData ? this.renderSummaryBlock(deliveryData) : null,
				this.renderSeparator(),
			);
		}

		/**
		 *
		 * @param {{title: string, subtitle: string, badge: Object, money: string}} data
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
					}),
					data.subtitle && Text({
						style: styles.summaryBlockText,
						text: data.subtitle,
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
						style: styles.summaryBlockText,
						text: data.money,
					}),
				),
			);
		}

		renderBadge(badgeData)
		{
			const { text, color, backgroundColor } = badgeData;

			return View(
				{
					style: styles.badge(backgroundColor),
				},
				Text({
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
			return null;
		}

		/**
		 * @returns {Object}
		 */
		getSummaryDeliveryData()
		{
			return null;
		}

		render()
		{
			return View(
				{
					style: {
						backgroundColor: '#eef2f4',
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
	}

	const styles = {
		summary: {
			title: {
				fontSize: 10,
				color: '#333333',
				marginBottom: 14,
			},
			separator: {
				borderBottomWidth: 1,
				borderBottomColor: '#DFE0E3',
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
			color: '#959CA4',
			fontSize: 14,
		},
		badge: (color) => ({
			backgroundColor: color,
			height: 18,
			borderRadius: 10.5,
			paddingHorizontal: 8,
			marginVertical: 4,
			fontWeight: 700,
			justifyContent: 'center',
			flexShrink: 1,
		}),
		badgeText: (color) => ({
			color,
			fontSize: 8,
			fontWeight: '700',
			textAlign: 'center',
		}),
	};

	module.exports = {
		BaseDocument,
	};
});
