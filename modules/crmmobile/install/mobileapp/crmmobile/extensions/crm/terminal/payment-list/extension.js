/**
 * @module crm/terminal/payment-list
 */
jn.define('crm/terminal/payment-list', (require, exports, module) => {
	const { Alert } = require('alert');
	const { EventEmitter } = require('event-emitter');
	const { Loc } = require('loc');
	const { PureComponent } = require('layout/pure-component');
	const { EmptyScreen } = require('layout/ui/empty-screen');
	const { StatefulList } = require('layout/ui/stateful-list');
	const { PaymentDetails } = require('crm/terminal/payment-details');
	const { PaymentCreate } = require('crm/terminal/payment-create');
	const { PaymentPay } = require('crm/terminal/payment-pay');
	const { PaymentService } = require('crm/terminal/services/payment');
	const { AnalyticsLabel } = require('analytics-label');

	/**
	 * @class PaymentList
	 */
	class PaymentList extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.paymentService = new PaymentService();

			this.uid = Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			this.lastAddedPaymentId = null;

			this.currencyId = null;
			this.defaultCountry = null;
			this.psCreationActionProviders = null;
			this.createPaymentFields = null;
			this.pullConfig = null;

			this.onPaymentCreated = this.onPaymentCreatedHandler.bind(this);
			this.onUpperContextClose = Promise.resolve();
		}

		render()
		{
			return this.createStatefulList();
		}

		createStatefulList()
		{
			return new StatefulList({
				testId: 'TerminalPaymentList',
				actions: {
					loadItems: 'crmmobile.Terminal.loadPayments',
				},
				actionParams: {
					loadItems: {},
				},
				itemLayoutOptions: {
					useConnectsBlock: false,
					useItemMenu: true,
					useStatusBlock: true,
				},
				isShowFloatingButton: true,
				itemDetailOpenHandler: this.handlePaymentDetailOpen.bind(this),
				itemActions: this.getItemActions(),
				itemParams: {},
				getEmptyListComponent: this.renderEmptyListComponent.bind(this),
				layout,
				layoutMenuActions: this.getMenuActions(),
				layoutOptions: {
					useSearch: false,
					useOnViewLoaded: false,
				},
				floatingButtonClickHandler: this.createPaymentHandler.bind(this),
				cacheName: `crm.terminal.list.${env.userId}`,
				itemType: 'Terminal',
				pull: {
					moduleId: 'crm',
					callback: (data) => {
						return new Promise((resolve, reject) => {
							if (data.command === this.pullConfig.list.command)
							{
								this.onUpperContextClose.then(() => {
									this.preparePullData(data);
									resolve(data);
								});
							}
							else
							{
								reject();
							}
						});
					},
					notificationAddText: Loc.getMessage('M_CRM_TL_PAYMENT_LIST_NEW_PAYMENTS_NOTIFICATION'),
				},
				ref: (ref) => this.statefulList = ref,
			});
		}

		renderEmptyListComponent()
		{
			return new EmptyScreen({
				styles: styles.emptyScreen.common,
				image: {
					uri: EmptyScreen.makeLibraryImagePath('terminal.png'),
					style: styles.emptyScreen.image,
				},
				title: Loc.getMessage('M_CRM_TL_PAYMENT_LIST_EMPTY_SCREEN_TITLE_V2'),
				description: () => {
					return View(
						{
							style: styles.emptyScreen.description.container,
						},
						Text({
							text: Loc.getMessage('M_CRM_TL_PAYMENT_LIST_EMPTY_SCREEN_DESCRIPTION'),
							style: styles.emptyScreen.description.text,
						}),
					);
				},
			});
		}

		getItemActions()
		{
			return [
				{
					id: 'view',
					title: Loc.getMessage('M_CRM_TL_PAYMENT_LIST_VIEW_PAYMENT'),
					onClickCallback: (action, itemId, { parentWidget, parent }) => {
						parentWidget.close(() => this.handlePaymentDetailOpen(itemId, parent.data));
					},
					data: {
						svgIcon: SvgIcons.viewPayment,
					},
				},
				{
					id: 'delete',
					title: Loc.getMessage('M_CRM_TL_PAYMENT_LIST_DELETE_PAYMENT'),
					onClickCallback: this.deletePaymentHandler.bind(this),
					onDisableClick: this.showDeleteForbiddenNotification.bind(this),
					showActionLoader: true,
					type: 'delete',
				},
			];
		}

		handlePaymentDetailOpen(entityId, item)
		{
			if (item.isPaid)
			{
				this.openPaymentDetails(entityId, item);
			}
			else
			{
				this.openPaymentPay(entityId, item);
			}
		}

		openPaymentPay(entityId, item)
		{
			AnalyticsLabel.send({ event: 'terminal-list-open-pay' });

			this.layout.openWidget('layout', {
				modal: true,
				titleParams: {
					text: item.name,
				},
				backgroundColor: '#eef2f4',
				backdrop: {
					onlyMediumPosition: false,
					mediumPositionHeight: PaymentPay.getHeight(),
					navigationBarColor: '#EEF2F4',
					swipeAllowed: true,
					swipeContentAllowed: false,
					horizontalSwipeAllowed: false,
				},
				onReady: (layout) => {
					this.setOnUpperContextClose(layout);

					this.paymentService
						.get(entityId)
						.then((payment) => {
							layout.showComponent(new PaymentPay({
								layout,
								payment,
								isStatusVisible: true,
								...this.getPaymentPayProps(),
							}));
						});
				},
			});
		}

		openPaymentDetails(entityId, item)
		{
			AnalyticsLabel.send({ event: 'terminal-list-open-details' });

			this.layout.openWidget('layout', {
				modal: true,
				titleParams: {
					text: item.name,
				},
				backgroundColor: '#eef2f4',
				backdrop: {
					onlyMediumPosition: false,
					mediumPositionHeight: 500,
					navigationBarColor: '#EEF2F4',
					swipeAllowed: true,
					swipeContentAllowed: false,
					horizontalSwipeAllowed: false,
				},
				onReady: (layout) => {
					this.setOnUpperContextClose(layout);

					this.paymentService
						.get(entityId)
						.then((payment) => {
							layout.showComponent(new PaymentDetails({ layout, payment }));
						});
				},
			});
		}

		createPaymentHandler()
		{
			AnalyticsLabel.send({ event: 'terminal-new-payment-create' });

			this.layout.openWidget('layout', {
				modal: true,
				titleParams: {
					text: Loc.getMessage('M_CRM_TL_PAYMENT_LIST_NEW_PAYMENT'),
				},
				backgroundColor: '#eef2f4',
				backdrop: {
					onlyMediumPosition: false,
					shouldResizeContent: true,
					navigationBarColor: '#EEF2F4',
					swipeAllowed: true,
					swipeContentAllowed: false,
					horizontalSwipeAllowed: false,
				},
				onReady: (layout) => {
					this.setOnUpperContextClose(layout, () => {
						if (this.lastAddedPaymentId === null)
						{
							return;
						}

						this.statefulList.updateItems([this.lastAddedPaymentId]);
						this.lastAddedPaymentId = null;
					});

					layout.showComponent(new PaymentCreate({
						layout,
						uid: this.uid,
						currencyId: this.currencyId,
						defaultCountry: this.defaultCountry,
						fields: this.createPaymentFields,
						paymentPayProps: this.getPaymentPayProps(),
					}));
				},
			});
		}

		deletePaymentHandler(actionItemId, itemId)
		{
			AnalyticsLabel.send({ event: 'terminal-list-delete' });

			return new Promise((resolve, reject) => {
				Alert.confirm(
					'',
					Loc.getMessage('M_CRM_TL_PAYMENT_LIST_DELETE_CONFIRMATION'),
					[
						{
							text: Loc.getMessage('M_CRM_TL_PAYMENT_LIST_DELETE_CONFIRMATION_OK'),
							onPress: () => this.paymentService
								.delete(itemId)
								.then(() => resolve({ action: 'delete', id: itemId }))
								.catch(() => {
									reject({
										errors: [{
											message: Loc.getMessage('M_CRM_TL_PAYMENT_LIST_DELETE_ERROR'),
										}],
									});
								}),
						},
						{
							type: 'cancel',
							onPress: reject,
						},
					],
				);
			});
		}

		getPaymentPayProps()
		{
			return {
				uid: this.uid,
				psCreationActionProviders: this.psCreationActionProviders,
				pullConfig: this.pullConfig.payment,
			};
		}

		showDeleteForbiddenNotification(actionItemId, itemId, { parent })
		{
			const { isPaid, permissions } = parent.data;

			let title = '';
			let text = '';

			if (!permissions.delete)
			{
				title = Loc.getMessage('M_CRM_TL_PAYMENT_LIST_PAID_DOCUMENT_DELETE_WARNING_ACCESS_TITLE');
				text = Loc.getMessage('M_CRM_TL_PAYMENT_LIST_PAID_DOCUMENT_DELETE_WARNING_ACCESS_TEXT');
			}
			else if (isPaid)
			{
				title = Loc.getMessage('M_CRM_TL_PAYMENT_LIST_PAID_DOCUMENT_DELETE_WARNING_TITLE');
				text = Loc.getMessage('M_CRM_TL_PAYMENT_LIST_PAID_DOCUMENT_DELETE_WARNING_TEXT');
			}

			if (title && text)
			{
				Notify.showUniqueMessage(text, title, { time: 3 });
			}
		}

		componentDidMount()
		{
			this.customEventEmitter.on('TerminalPayment::onCreated', this.onPaymentCreated);
			this.initialize().then(() => this.statefulList.reload());
		}

		initialize()
		{
			return new Promise((resolve) => {
				BX.ajax.runAction('crmmobile.Terminal.initialize')
					.then((response) => {
						const {
							currencyId,
							defaultCountry,
							createPaymentFields,
							psCreationActionProviders,
							pullConfig,
						} = response.data;

						this.currencyId = currencyId || null;
						this.defaultCountry = defaultCountry || null;
						this.createPaymentFields = createPaymentFields || [];
						this.psCreationActionProviders = psCreationActionProviders || {};
						this.pullConfig = pullConfig || {};

						resolve();
					});

				AnalyticsLabel.send({ event: 'terminal-start' });
			});
		}

		getMenuActions()
		{
			return [
				{
					type: UI.Menu.Types.HELPDESK,
					data: {
						articleCode: '17399046',
					},
				},
			];
		}

		setOnUpperContextClose(layout, after = () => {})
		{
			this.onUpperContextClose = new Promise((resolve) => {
				layout.setListener((eventName) => {
					if (eventName === 'onViewHidden')
					{
						resolve();
					}

					after();
				});
			});
		}

		preparePullData(data)
		{
			data.params.items.map((item) => {
				item.data = item.mobileData;
				delete item.mobileData;
				return item;
			});
		}

		onPaymentCreatedHandler(paymentId)
		{
			this.statefulList.reload();
			this.lastAddedPaymentId = paymentId;
		}

		get layout()
		{
			return this.props.layout || {};
		}
	}

	const SvgIcons = {
		viewPayment: '<svg width="17" height="21" viewBox="0 0 17 21" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.4234 17.7238C14.4234 17.8825 14.2934 18.01 14.1346 18.01H2.56338C2.40338 18.01 2.27463 17.8825 2.27463 17.7238V2.2875C2.27463 2.13 2.40338 2.00125 2.56338 2.00125H8.05963C8.21963 2.00125 8.34838 2.13 8.34838 2.2875V7.72C8.34838 7.8775 8.47838 8.005 8.63838 8.005H14.1346C14.2934 8.005 14.4234 8.13375 14.4234 8.29125V17.7238ZM10.3734 3.09C10.3734 3.0325 10.4221 2.98375 10.4821 2.98375C10.5109 2.98375 10.5384 2.995 10.5584 3.015L13.3984 5.82125C13.4409 5.8625 13.4409 5.93 13.3984 5.9725C13.3771 5.9925 13.3509 6.00375 13.3209 6.00375H10.4821C10.4221 6.00375 10.3734 5.955 10.3734 5.89625V3.09ZM16.0234 5.585L10.6909 0.31375C10.4884 0.11375 10.2121 0 9.92338 0H1.33463C0.734634 0 0.249634 0.48 0.249634 1.0725V18.94C0.249634 19.5313 0.734634 20.0113 1.33463 20.0113H15.3634C15.9609 20.0113 16.4471 19.5313 16.4471 18.94V6.59625C16.4471 6.21625 16.2946 5.8525 16.0234 5.585ZM12.0359 10.0063H4.65963C4.46088 10.0063 4.29838 10.1663 4.29838 10.3638V11.65C4.29838 11.8463 4.46088 12.0075 4.65963 12.0075H12.0359C12.2359 12.0075 12.3984 11.8463 12.3984 11.65V10.3638C12.3984 10.1663 12.2359 10.0063 12.0359 10.0063ZM4.73338 8.005H5.88963C6.12963 8.005 6.32338 7.8125 6.32338 7.575V6.4325C6.32338 6.195 6.12963 6.00375 5.88963 6.00375H4.73338C4.49338 6.00375 4.29838 6.195 4.29838 6.4325V7.575C4.29838 7.8125 4.49338 8.005 4.73338 8.005ZM12.0359 14.0087H4.65963C4.46088 14.0087 4.29838 14.1675 4.29838 14.365V15.6525C4.29838 15.8488 4.46088 16.01 4.65963 16.01H12.0359C12.2359 16.01 12.3984 15.8488 12.3984 15.6525V14.365C12.3984 14.1675 12.2359 14.0087 12.0359 14.0087Z" fill="#6a737f"/></svg>',
	};

	const styles = {
		emptyScreen: {
			common: {
				container: {
					paddingHorizontal: 20,
				},
				icon: {
					marginBottom: 50,
				},
			},
			image: {
				width: 218,
				height: 178,
			},
			description: {
				container: {
					flexDirection: 'column',
					justifyContent: 'center',
					alignItems: 'center',
				},
				text: {
					color: '#525C69',
					fontSize: 15,
					textAlign: 'center',
					lineHeightMultiple: 1.2,
				},
			},
		},
	};

	module.exports = { PaymentList };
});
