/**
 * @module crm/receive-payment/mode-selection
 */
jn.define('crm/receive-payment/mode-selection', (require, exports, module) => {
	const { Loc } = require('loc');
	const { handleErrors } = require('crm/error');
	const { WarningBlock } = require('layout/ui/warning-block');
	const { TypeId } = require('crm/type');
	const { MultiFieldDrawer, MultiFieldType } = require('crm/multi-field-drawer');
	const { EventEmitter } = require('event-emitter');
	const { isEqual } = require('utils/object');
	const { AnalyticsLabel } = require('analytics-label');
	const { NotifyManager } = require('notify-manager');
	const { PlanRestriction } = require('layout/ui/plan-restriction');

	/**
	 * @class ModeSelectionMenu
	 */
	class ModeSelectionMenu
	{
		constructor(props)
		{
			this.entityModel = props.entityModel || {};
			this.uid = props.uid || Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);
			this.contextMenu = null;

			this.entityHasContact = parseInt(this.entityModel.CONTACT_ID, 10) > 0;
			this.contactHasPhone = this.entityModel.CONTACT_HAS_PHONE === 'Y';
			this.hasSmsProviders = true;
			this.isPaymentLimitReached = false;
			this.isOrderLimitReached = false;

			this.handleCacheResult = this.handleCacheResult.bind(this);
			this.handleModeSelectionResponse = this.handleModeSelectionResponse.bind(this);

			this.openReceivePayment = this.openReceivePayment.bind(this);
		}

		open()
		{
			const data = {
				entityId: this.entityModel.ID,
				entityTypeId: this.entityModel.ENTITY_TYPE_ID,
			};

			NotifyManager.showLoadingIndicator();

			new RunActionExecutor('crmmobile.ReceivePayment.ModeSelection.getModeSelectionParams', data)
				.enableJson()
				.setCacheId('receivePaymentModeSelection')
				.setCacheHandler(this.handleCacheResult)
				.setHandler(this.handleModeSelectionResponse)
				.call(true)
			;
		}

		handleCacheResult(cacheResult)
		{
			NotifyManager.hideLoadingIndicatorWithoutFallback();

			if (cacheResult.data)
			{
				this.hasSmsProviders = cacheResult.data.hasSmsProviders;
				this.isPaymentLimitReached = cacheResult.data.isPaymentLimitReached;
				this.isOrderLimitReached = cacheResult.data.isOrderLimitReached;
			}

			this.openMenu();
		}

		handleModeSelectionResponse(response)
		{
			NotifyManager.hideLoadingIndicatorWithoutFallback();

			handleErrors(response)
				.then(() => {
					let isReloadNeeded = false;
					if (response.data)
					{
						const currentParams = {
							entityHasContact: this.entityHasContact,
							contactHasPhone: this.contactHasPhone,
							hasSmsProviders: this.hasSmsProviders,
							isPaymentLimitReached: this.isPaymentLimitReached,
							isOrderLimitReached: this.isOrderLimitReached,
						};

						if (!isEqual(currentParams, response.data))
						{
							isReloadNeeded = true;
						}

						this.entityHasContact = response.data.entityHasContact;
						this.contactHasPhone = response.data.contactHasPhone;
						this.hasSmsProviders = response.data.hasSmsProviders;
						this.isPaymentLimitReached = response.data.isPaymentLimitReached;
						this.isOrderLimitReached = response.data.isOrderLimitReached;
					}

					if (!this.contextMenu)
					{
						this.openMenu();
					}
					else if (isReloadNeeded)
					{
						this.contextMenu.rerender(this.getContextMenuParams());
					}
				})
			;
		}

		openMenu()
		{
			this.contextMenu = new ContextMenu(this.getContextMenuParams());

			this.contextMenu.show();
		}

		getContextMenuParams()
		{
			const paymentIconData = {
				svgIcon: Icons.payment,
			};
			if (this.isPaymentLimitReached || this.isOrderLimitReached)
			{
				paymentIconData.svgIconAfter = {
					type: ContextMenuItem.ImageAfterTypes.LOCK,
				};
			}
			const paymentTitle = Loc.getMessage('MOBILE_RECEIVE_PAYMENT_PAYMENT_MODE');
			const paymentDeliveryTitle = Loc.getMessage('MOBILE_RECEIVE_PAYMENT_PAYMENT_DELIVERY_MODE');
			const deliveryTitle = Loc.getMessage('MOBILE_RECEIVE_PAYMENT_DELIVERY_MODE');

			const actions = [
				{
					id: 'payment',
					title: paymentTitle,
					subTitle: '',
					data: paymentIconData,
					isDisabled: this.areActionsDisabled(),
					onClickCallback: this.onActionClick.bind(this, 'payment'),
				},
				{
					id: 'payment_delivery',
					title: paymentDeliveryTitle,
					subTitle: '',
					data: {
						svgIcon: Icons.paymentDelivery,
						svgIconAfter: {
							type: ContextMenuItem.ImageAfterTypes.WEB,
						},
					},
					isDisabled: true,
					onClickCallback: this.onActionClick.bind(this, 'payment_delivery'),
					onDisableClick: this.onDisableClick.bind(this, paymentDeliveryTitle),
				},
				{
					id: 'delivery',
					title: deliveryTitle,
					subTitle: '',
					data: {
						svgIcon: Icons.delivery,
						svgIconAfter: {
							type: ContextMenuItem.ImageAfterTypes.WEB,
						},
					},
					isDisabled: true,
					onClickCallback: this.onActionClick.bind(this, 'delivery'),
					onDisableClick: this.onDisableClick.bind(this, deliveryTitle),
				},
			];

			const contextMenuParams = {
				actions,
				params: {
					title: Loc.getMessage('MOBILE_RECEIVE_PAYMENT_MODE_MENU_TITLE'),
					showCancelButton: true,
					isCustomIconColor: true,
					helpUrl: helpdesk.getArticleUrl('17567646'),
				},
			};

			if (this.areActionsDisabled())
			{
				contextMenuParams.customSection = {
					layout: View(
						{},
						new WarningBlock(this.getWarningParams()),
					),
					height: 150,
				};
			}

			return contextMenuParams;
		}

		onActionClick(action)
		{
			if (!this.contextMenu)
			{
				return;
			}

			AnalyticsLabel.send({
				event: 'onReceivePaymentScenarioSelect',
				scenario: action,
			});
			if (this.isOrderLimitReached || this.isPaymentLimitReached)
			{
				const restrictionText = this.isOrderLimitReached
					? Loc.getMessage('MOBILE_RECEIVE_PAYMENT_ORDERS_LIMIT')
					: Loc.getMessage('MOBILE_RECEIVE_PAYMENT_LIMIT');
				this.contextMenu.close(() => {
					PlanRestriction.open({
						title: Loc.getMessage('MOBILE_RECEIVE_PAYMENT_MODE_MENU_TITLE'),
						text: restrictionText,
					});
				});
				return;
			}
			if (!this.contactHasPhone)
			{
				this.contextMenu.close(() => {
					const multiFieldDrawer = new MultiFieldDrawer({
						entityTypeId: TypeId.Contact,
						entityId: this.entityModel.CONTACT_ID,
						fields: [MultiFieldType.PHONE],
						onSuccess: this.openReceivePayment.bind(this, action),
						warningBlock: {
							description: Loc.getMessage('MOBILE_RECEIVE_PAYMENT_PHONE_DRAWER_WARNING_TEXT'),
						},
					});

					multiFieldDrawer.show();
				});

				return;
			}

			this.contextMenu.close(() => {
				this.openReceivePayment(action);
				this.emitRecentItemsAction();
			});
		}

		onDisableClick(title)
		{
			if (!this.contextMenu)
			{
				return;
			}
			qrauth.open({
				title,
				redirectUrl: `/crm/deal/details/${this.entityModel.ID}/`,
				layout: this.contextMenu.getActionParentWidget(),
			});
		}

		getBackdropTitle(mode)
		{
			switch (mode)
			{
				case 'payment':
					return Loc.getMessage('MOBILE_RECEIVE_PAYMENT_PAYMENT_MODE_TITLE');
				case 'delivery':
					return Loc.getMessage('MOBILE_RECEIVE_PAYMENT_DELIVERY_MODE_TITLE');
				case 'payment_delivery':
					return Loc.getMessage('MOBILE_RECEIVE_PAYMENT_PAYMENT_DELIVERY_MODE_TITLE');
				default:
					return '';
			}
		}

		guessProductCount()
		{
			const { PRODUCT_ROW_SUMMARY: productSummary } = this.entityModel;
			if (productSummary)
			{
				const { count = 0 } = productSummary;

				return count;
			}

			return 0;
		}

		openReceivePayment(mode)
		{
			ComponentHelper.openLayout({
				name: 'crm:salescenter.receive.payment',
				object: 'layout',
				widgetParams: {
					objectName: 'layout',
					title: this.getBackdropTitle(mode),
					modal: true,
					backgroundColor: '#eef2f4',
					backdrop: {
						swipeAllowed: false,
						forceDismissOnSwipeDown: false,
						horizontalSwipeAllowed: false,
						bounceEnable: true,
						showOnTop: true,
						topPosition: 60,
						navigationBarColor: '#eef2f4',
						helpUrl: helpdesk.getArticleUrl('17567646'),
					},
				},
				componentParams: {
					entityId: this.entityModel.ID,
					entityTypeId: this.entityModel.ENTITY_TYPE_ID,
					mode,
					uid: this.uid,
					productCount: this.guessProductCount(),
				},
			});
		}

		areActionsDisabled()
		{
			return !(this.hasSmsProviders && this.entityHasContact);
		}

		getWarningParams()
		{
			const result = {
				title: '',
				description: '',
			};

			if (!this.entityHasContact)
			{
				result.title = Loc.getMessage('MOBILE_RECEIVE_PAYMENT_NO_CONTACT_TITLE');
				result.description = Loc.getMessage('MOBILE_RECEIVE_PAYMENT_NO_CONTACT_TEXT');
			}
			else if (!this.hasSmsProviders)
			{
				result.title = Loc.getMessage('MOBILE_RECEIVE_PAYMENT_NO_SMS_PROVIDERS_TITLE');
				result.description = Loc.getMessage('MOBILE_RECEIVE_PAYMENT_NO_SMS_PROVIDERS_TEXT');
				result.layout = PageManager;
				result.redirectUrl = '/saleshub/';
			}

			return result;
		}

		emitRecentItemsAction()
		{
			const eventArgs = [{
				actionId: 'receive-payment',
				tabId: 'timeline',
			}];

			this.customEventEmitter.emit('DetailCard.FloatingMenu.Item::onSaveInRecent', eventArgs);
		}
	}

	const Icons = {
		payment: '<svg width="30" height="31" viewBox="0 0 30 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M23.5767 11.9473C23.5165 11.4545 23.2223 11.0282 22.802 10.7705L6.25 10.7717V20.8478L6.25795 21.0348C6.35275 22.1443 7.28319 23.0151 8.41727 23.0151H21.8679L22.0333 23.0076C22.9048 22.9287 23.5881 22.2333 23.5881 21.3896L23.5875 19.5555L17.4554 19.5564L17.3096 19.548C16.688 19.4758 16.2054 18.9475 16.2054 18.3064V14.9503L16.2138 14.8046C16.286 14.1829 16.8144 13.7003 17.4554 13.7003L23.5875 13.6992L23.5881 12.1349L23.5767 11.9473ZM23.5875 18.3605V14.8955L17.4204 14.8958V18.361L23.5875 18.3605ZM19.2536 16.4695C19.2536 15.8711 19.7389 15.3858 20.3372 15.3858C20.9356 15.3858 21.4209 15.8711 21.4209 16.4695C21.4209 17.0678 20.9356 17.5531 20.3372 17.5531C19.7389 17.5531 19.2536 17.0678 19.2536 16.4695ZM21.5649 9.5775L20.4735 5.5L8.1987 8.78902L8.40995 9.5775H21.5649Z" fill="#2FC6F6"/> </svg>',
		paymentDelivery: '<svg width="30" height="31" viewBox="0 0 30 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M23.9187 21.3652C24.712 21.3652 25.355 20.7221 25.355 19.9288V16.338C25.355 16.0694 25.2813 15.8181 25.1533 15.6031C25.0404 15.258 24.8812 14.9278 24.6786 14.6219L22.6877 11.6151C22.067 10.6776 21.0176 10.1139 19.8933 10.1139H17.4387L17.4387 8.6446C17.4387 7.58692 16.5813 6.72949 15.5236 6.72949H6.68269C5.625 6.72949 4.76758 7.58692 4.76758 8.6446V16.2183L4.77249 16.2183C4.76924 16.2578 4.76758 16.2977 4.76758 16.338V19.9288C4.76758 20.7221 5.41065 21.3652 6.20391 21.3652L6.83152 21.3652C6.82633 21.2932 6.82369 21.2204 6.82369 21.1471C6.82369 19.4944 8.16342 18.1547 9.81605 18.1547C11.4687 18.1547 12.8084 19.4944 12.8084 21.1471C12.8084 21.2204 12.8058 21.2932 12.8006 21.3652H16.9842C16.9791 21.2932 16.9764 21.2204 16.9764 21.1471C16.9764 19.4944 18.3161 18.1547 19.9688 18.1547C21.6214 18.1547 22.9611 19.4944 22.9611 21.1471C22.9611 21.2204 22.9585 21.2932 22.9533 21.3652L23.9187 21.3652ZM9.81598 19.2103C10.8854 19.2103 11.7524 20.0773 11.7524 21.1467C11.7524 22.2162 10.8854 23.0832 9.81598 23.0832C8.74652 23.0832 7.87956 22.2162 7.87956 21.1467C7.87956 20.0773 8.74652 19.2103 9.81598 19.2103ZM19.9687 19.2103C21.0381 19.2103 21.9051 20.0773 21.9051 21.1467C21.9051 22.2162 21.0381 23.0832 19.9687 23.0832C18.8992 23.0832 18.0322 22.2162 18.0322 21.1467C18.0322 20.0773 18.8992 19.2103 19.9687 19.2103ZM20.1096 11.5504H18.4128V14.9018H23.106L21.306 12.1919C21.0398 11.7912 20.5907 11.5504 20.1096 11.5504ZM6.86186 12.287C6.86186 10.0019 8.71438 8.14947 10.9995 8.14947C13.2846 8.14947 15.137 10.0019 15.137 12.287C15.137 14.5722 13.2846 16.4246 10.9995 16.4246C8.71438 16.4246 6.86186 14.5722 6.86186 12.287ZM10.4906 9.01489H11.5236V9.6984C11.9836 9.73349 12.3091 9.83717 12.5569 9.95744L12.6603 10.0076L12.3688 11.1437L12.2186 11.0803C12.2002 11.0726 12.1808 11.0642 12.1603 11.0553C11.9552 10.9665 11.6411 10.8306 11.1683 10.8306C10.9365 10.8306 10.8069 10.8808 10.738 10.9348C10.6734 10.9855 10.6489 11.0499 10.6489 11.1197C10.6489 11.1824 10.6759 11.2485 10.804 11.3369C10.9367 11.4284 11.1515 11.5248 11.4773 11.6475C11.9431 11.8121 12.2886 12.0082 12.5172 12.2655C12.7503 12.5279 12.851 12.8412 12.851 13.2143C12.851 13.9445 12.3518 14.5556 11.4852 14.7492V15.5569H10.4522V14.8194C9.9693 14.7799 9.50724 14.6467 9.21801 14.4792L9.12586 14.4258L9.42741 13.2496L9.58614 13.3366C9.89311 13.5049 10.3226 13.6569 10.7923 13.6569C10.9957 13.6569 11.1509 13.6172 11.2502 13.5554C11.3435 13.4974 11.3882 13.4211 11.3882 13.3218C11.3882 13.2252 11.3517 13.1444 11.247 13.0601C11.1348 12.9699 10.9511 12.8822 10.6706 12.7874C10.2536 12.6471 9.88629 12.4746 9.62195 12.2311C9.35196 11.9825 9.19394 11.6636 9.19394 11.2501C9.19394 10.5264 9.68618 9.96873 10.4906 9.77148V9.01489Z" fill="#2FC6F6"/> </svg>',
		delivery: '<svg width="30" height="31" viewBox="0 0 30 31" fill="none" xmlns="http://www.w3.org/2000/svg"> <path fill-rule="evenodd" clip-rule="evenodd" d="M23.6278 20.556C24.395 20.556 25.017 19.934 25.017 19.1668V15.6939C25.017 15.4342 24.9457 15.1911 24.8218 14.9831C24.7126 14.6494 24.5587 14.33 24.3628 14.0341L22.4372 11.126C21.8369 10.2194 20.822 9.67414 19.7346 9.67414H17.3605L17.3605 9.32684C17.3605 8.30388 16.5313 7.47461 15.5083 7.47461H6.9577C5.93474 7.47461 5.10547 8.30388 5.10547 9.32684V15.5781L5.11022 15.5781C5.10707 15.6163 5.10547 15.6549 5.10547 15.6939V19.1668C5.10547 19.934 5.72742 20.556 6.49464 20.556L7.10164 20.556C7.09662 20.4864 7.09407 20.416 7.09407 20.3451C7.09407 18.7467 8.38981 17.451 9.98818 17.451C11.5866 17.451 12.8823 18.7467 12.8823 20.3451C12.8823 20.416 12.8797 20.4864 12.8747 20.556H16.921C16.916 20.4864 16.9135 20.416 16.9135 20.3451C16.9135 18.7467 18.2092 17.451 19.8076 17.451C21.4059 17.451 22.7017 18.7467 22.7017 20.3451C22.7017 20.416 22.6991 20.4864 22.6941 20.556L23.6278 20.556ZM9.98826 18.4717C11.0226 18.4717 11.8611 19.3102 11.8611 20.3445C11.8611 21.3789 11.0226 22.2174 9.98826 22.2174C8.95392 22.2174 8.11542 21.3789 8.11542 20.3445C8.11542 19.3102 8.95392 18.4717 9.98826 18.4717ZM19.8076 18.4717C20.8419 18.4717 21.6804 19.3102 21.6804 20.3445C21.6804 21.3789 20.8419 22.2174 19.8076 22.2174C18.7733 22.2174 17.9348 21.3789 17.9348 20.3445C17.9348 19.3102 18.7733 18.4717 19.8076 18.4717ZM19.9439 11.0635H18.3028V14.3049H22.8419L21.101 11.684C20.8436 11.2964 20.4092 11.0635 19.9439 11.0635Z" fill="#2FC6F6"/> </svg>',
	};

	module.exports = {
		ModeSelectionMenu,
	};
});
