/**
 * @module crm/receive-payment/mode-selection
 */
jn.define('crm/receive-payment/mode-selection', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { ContextMenu } = require('layout/ui/context-menu');
	const { handleErrors } = require('crm/error');
	const { InfoHelper } = require('layout/ui/info-helper');
	const { TypeId } = require('crm/type');
	const { MultiFieldDrawer, MultiFieldType } = require('crm/multi-field-drawer');
	const { EventEmitter } = require('event-emitter');
	const { isEqual } = require('utils/object');
	const { AnalyticsLabel } = require('analytics-label');
	const { AnalyticsEvent } = require('analytics');
	const { NotifyManager } = require('notify-manager');
	const { PlanRestriction } = require('layout/ui/plan-restriction');
	const { ImageAfterTypes } = require('layout/ui/context-menu/item');
	const { WarningBlock } = require('layout/ui/warning-block');
	const { PaymentCreate } = require('crm/terminal/entity/payment-create');

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
			this.isPaymentLimitReached = false;
			this.isOrderLimitReached = false;
			this.isTerminalAvailable = false;
			this.isTerminalToolEnabled = this.entityModel.IS_TERMINAL_TOOL_ENABLED;
			this.isPhoneConfirmed = this.entityModel.IS_PHONE_CONFIRMED;
			this.connectedSiteId = this.entityModel.CONNECTED_SITE_ID;

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
				this.isPaymentLimitReached = cacheResult.data.isPaymentLimitReached;
				this.isOrderLimitReached = cacheResult.data.isOrderLimitReached;
				this.isTerminalAvailable = cacheResult.data.isTerminalAvailable;
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
							isPaymentLimitReached: this.isPaymentLimitReached,
							isOrderLimitReached: this.isOrderLimitReached,
							isTerminalAvailable: this.isTerminalAvailable,
						};

						if (!isEqual(currentParams, response.data))
						{
							isReloadNeeded = true;
						}

						this.entityHasContact = response.data.entityHasContact;
						this.contactHasPhone = response.data.contactHasPhone;
						this.isPaymentLimitReached = response.data.isPaymentLimitReached;
						this.isOrderLimitReached = response.data.isOrderLimitReached;
						this.isTerminalAvailable = response.data.isTerminalAvailable;
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
			const contextMenuParams = {
				actions: [],
				params: {
					title: Loc.getMessage('MOBILE_RECEIVE_PAYMENT_MODE_MENU_TITLE'),
					showCancelButton: true,
					isCustomIconColor: true,
					helpUrl: helpdesk.getArticleUrl('17567646'),
				},
			};

			const isNeedPhoneConfirmation = (!this.isPhoneConfirmed && this.connectedSiteId > 0);

			if (isNeedPhoneConfirmation)
			{
				contextMenuParams.customSection = {
					layout: View(
						{
							style: { marginHorizontal: 10 },
						},
						new WarningBlock({
							title: Loc.getMessage('MOBILE_RECEIVE_PAYMENT_PHONE_NOT_CONFIRMED_WARNING_TITLE_MSGVER_1'),
							description: Loc.getMessage('MOBILE_RECEIVE_PAYMENT_PHONE_NOT_CONFIRMED_WARNING_TEXT_MSGVER_1'),
							onClickCallback: this.onDisableClick.bind(
								this,
								Loc.getMessage('MOBILE_RECEIVE_PAYMENT_PHONE_CONFIRMATION_WARNING_TITLE_MSGVER_1'),
								`/shop/stores/?force_verify_site_id=${this.connectedSiteId}`,
							),
						}),
					),
					height: 160,
				};
			}

			contextMenuParams.actions.push({
				id: 'payment',
				title: Loc.getMessage('MOBILE_RECEIVE_PAYMENT_PAYMENT_MODE_MSGVER_2'),
				subTitle: '',
				data: {
					svgIcon: Icons.payment,
					svgIconAfter: this.isPaymentOrOrderLimitReached() ? { type: ImageAfterTypes.LOCK } : null,
				},
				isDisabled: isNeedPhoneConfirmation,
				onClickCallback: this.onActionClick.bind(this, 'payment'),
			});

			if (this.isTerminalAvailable)
			{
				contextMenuParams.actions.push({
					id: 'terminal_payment',
					title: Loc.getMessage('MOBILE_RECEIVE_PAYMENT_TERMINAL_PAYMENT'),
					subTitle: '',
					data: {
						svgIcon: Icons.terminalPayment,
						svgIconAfter: this.isPaymentOrOrderLimitReached() ? { type: ImageAfterTypes.LOCK } : null,
					},
					isDisabled: false,
					onClickCallback: this.onActionClick.bind(this, 'terminal_payment'),
				});
			}

			contextMenuParams.actions.push({
				id: 'payment_delivery',
				title: Loc.getMessage('MOBILE_RECEIVE_PAYMENT_PAYMENT_DELIVERY_MODE'),
				subTitle: '',
				data: {
					svgIcon: Icons.paymentDelivery,
					svgIconAfter: isNeedPhoneConfirmation ? null : { type: ImageAfterTypes.WEB },
				},
				isDisabled: true,
				onClickCallback: this.onActionClick.bind(this, 'payment_delivery'),
				onDisableClick: isNeedPhoneConfirmation
					? null
					: this.onDisableClick.bind(
						this,
						Loc.getMessage('MOBILE_RECEIVE_PAYMENT_PAYMENT_DELIVERY_MODE'),
					),
			});

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

			const analytics = new AnalyticsEvent();
			analytics
				.setTool('crm')
				.setCategory('payments')
				.setEvent('payment_create_click')
				.setType(action)
				.setSection('crm')
				.setSubSection('mobile')
			;
			analytics.send();

			if (action === 'terminal_payment' && !this.isTerminalToolEnabled)
			{
				InfoHelper.openByCode('limit_crm_terminal_off');

				return;
			}

			if (this.isPaymentOrOrderLimitReached())
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

			if (action === 'payment' && (this.entityHasContact && !this.contactHasPhone))
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

		onDisableClick(title, redirectUrl = `/crm/deal/details/${this.entityModel.ID}/`)
		{
			if (!this.contextMenu)
			{
				return;
			}
			qrauth.open({
				title,
				redirectUrl,
				layout: this.contextMenu.getActionParentWidget(),
				analyticsSection: 'crm',
			});
		}

		getBackdropTitle(action)
		{
			switch (action)
			{
				case 'payment':
					return Loc.getMessage('MOBILE_RECEIVE_PAYMENT_PAYMENT_MODE_TITLE');
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

		openReceivePayment(action)
		{
			if (action === 'payment')
			{
				this.openReceivePaymentByLink(action);
			}
			else if (action === 'terminal_payment')
			{
				this.openReceivePaymentByTerminal();
			}
		}

		openReceivePaymentByLink(action)
		{
			ComponentHelper.openLayout({
				name: 'crm:salescenter.receive.payment',
				object: 'layout',
				widgetParams: {
					objectName: 'layout',
					title: this.getBackdropTitle(action),
					modal: true,
					backgroundColor: AppTheme.colors.bgSecondary,
					backdrop: {
						swipeAllowed: false,
						forceDismissOnSwipeDown: false,
						horizontalSwipeAllowed: false,
						bounceEnable: true,
						showOnTop: true,
						topPosition: 60,
						navigationBarColor: AppTheme.colors.bgSecondary,
						helpUrl: helpdesk.getArticleUrl('17567646'),
					},
				},
				componentParams: {
					entityHasContact: this.entityHasContact,
					entityId: this.entityModel.ID,
					entityTypeId: this.entityModel.ENTITY_TYPE_ID,
					mode: action,
					uid: this.uid,
					productCount: this.guessProductCount(),
				},
			});
		}

		openReceivePaymentByTerminal()
		{
			PaymentCreate.open({
				componentParams: {
					entityId: this.entityModel.ID,
					entityTypeId: this.entityModel.ENTITY_TYPE_ID,
					uid: this.uid,
				},
			});
		}

		emitRecentItemsAction()
		{
			const eventArgs = [
				{
					actionId: 'receive-payment',
					tabId: 'timeline',
				},
			];

			this.customEventEmitter.emit('DetailCard.FloatingMenu.Item::onSaveInRecent', eventArgs);
		}

		isPaymentOrOrderLimitReached()
		{
			return this.isPaymentLimitReached || this.isOrderLimitReached;
		}
	}

	const Icons = {
		payment: '<svg width="30" height="31" viewBox="0 0 30 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M23.5767 11.9473C23.5165 11.4545 23.2223 11.0282 22.802 10.7705L6.25 10.7717V20.8478L6.25795 21.0348C6.35275 22.1443 7.28319 23.0151 8.41727 23.0151H21.8679L22.0333 23.0076C22.9048 22.9287 23.5881 22.2333 23.5881 21.3896L23.5875 19.5555L17.4554 19.5564L17.3096 19.548C16.688 19.4758 16.2054 18.9475 16.2054 18.3064V14.9503L16.2138 14.8046C16.286 14.1829 16.8144 13.7003 17.4554 13.7003L23.5875 13.6992L23.5881 12.1349L23.5767 11.9473ZM23.5875 18.3605V14.8955L17.4204 14.8958V18.361L23.5875 18.3605ZM19.2536 16.4695C19.2536 15.8711 19.7389 15.3858 20.3372 15.3858C20.9356 15.3858 21.4209 15.8711 21.4209 16.4695C21.4209 17.0678 20.9356 17.5531 20.3372 17.5531C19.7389 17.5531 19.2536 17.0678 19.2536 16.4695ZM21.5649 9.5775L20.4735 5.5L8.1987 8.78902L8.40995 9.5775H21.5649Z" fill="#2FC6F6"/> </svg>',
		terminalPayment: '<svg width="13" height="18" viewBox="0 0 13 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.3432 7.0343L4.93557 5.674L5.58742 5.03872L6.3432 5.76373L8.17271 4.00385L8.82456 4.63913L6.3432 7.0343Z" fill="#2FC6F6"/><path fill-rule="evenodd" clip-rule="evenodd" d="M12.6472 1.72604C12.6472 1.02142 12.0752 0.442383 11.3636 0.442383H2.25949C1.55486 0.442383 0.97583 1.01445 0.97583 1.72604V8.46475C0.97583 8.53609 1.16081 8.82653 1.34251 9.11182C1.51789 9.38718 1.6902 9.65773 1.6902 9.72188V16.6971C1.6902 17.4018 2.26227 17.9808 2.97386 17.9808H10.6491C11.3537 17.9808 11.9327 17.4087 11.9327 16.6971L11.9329 9.78345C11.9329 9.71279 12.1182 9.42264 12.3001 9.13796C12.4753 8.86379 12.6472 8.59469 12.6472 8.53163L12.6472 1.72604ZM2.85945 1.95207H10.7636C10.938 1.95207 11.0776 2.09159 11.0776 2.266C11.0776 2.44041 10.938 2.57994 10.7636 2.57994H2.85945C2.68504 2.57994 2.54552 2.44041 2.54552 2.266C2.54552 2.09159 2.68504 1.95207 2.85945 1.95207ZM2.54566 3.66824C2.54566 3.49383 2.68519 3.3543 2.8596 3.3543H10.7638C10.9382 3.3543 11.0777 3.49383 11.0777 3.66824V7.45916C11.0777 7.63357 10.9382 7.7731 10.7638 7.7731H2.8596C2.68519 7.7731 2.54566 7.63357 2.54566 7.45916V3.66824ZM9.99916 11.1116H9.08597C8.76329 11.1116 8.50152 10.8498 8.50152 10.5271C8.50152 10.2045 8.76331 9.94269 9.08597 9.94269H9.99916C10.3218 9.94269 10.5836 10.2045 10.5836 10.5271C10.5836 10.8498 10.3218 11.1116 9.99916 11.1116ZM6.35857 11.1117H7.27176C7.59443 11.1117 7.85621 10.8499 7.85621 10.5273C7.85621 10.2046 7.59444 9.94281 7.27176 9.94281H6.35857C6.0359 9.94281 5.77412 10.2046 5.77412 10.5273C5.77412 10.8499 6.03589 11.1117 6.35857 11.1117ZM4.54436 11.1118H3.63116C3.30849 11.1118 3.04672 10.85 3.04672 10.5274C3.04672 10.2047 3.3085 9.94294 3.63116 9.94294H4.54436C4.86704 9.94294 5.1288 10.2047 5.1288 10.5274C5.1288 10.85 4.86702 11.1118 4.54436 11.1118ZM3.63116 13.2487H4.54436C4.86702 13.2487 5.1288 12.9869 5.1288 12.6643C5.1288 12.3416 4.86704 12.0798 4.54436 12.0798H3.63116C3.3085 12.0798 3.04672 12.3416 3.04672 12.6643C3.04672 12.9869 3.30849 13.2487 3.63116 13.2487ZM3.63116 15.3795H4.54436C4.86702 15.3795 5.1288 15.1177 5.1288 14.795C5.1288 14.4724 4.86704 14.2106 4.54436 14.2106H3.63116C3.3085 14.2106 3.04672 14.4724 3.04672 14.795C3.04672 15.1177 3.30849 15.3795 3.63116 15.3795ZM6.35857 15.3793H7.27176C7.59443 15.3793 7.85621 15.1176 7.85621 14.7949C7.85621 14.4722 7.59444 14.2105 7.27176 14.2105H6.35857C6.0359 14.2105 5.77412 14.4722 5.77412 14.7949C5.77412 15.1176 6.03589 15.3793 6.35857 15.3793ZM7.85621 12.6641C7.85621 12.9868 7.59443 13.2486 7.27176 13.2486H6.35857C6.03589 13.2486 5.77412 12.9868 5.77412 12.6641C5.77412 12.3415 6.0359 12.0797 6.35857 12.0797H7.27176C7.59444 12.0797 7.85621 12.3415 7.85621 12.6641ZM9.99916 15.3792H9.08597C8.76329 15.3792 8.50152 15.1174 8.50152 14.7948C8.50152 14.4721 8.76331 14.2103 9.08597 14.2103H9.99916C10.3218 14.2103 10.5836 14.4721 10.5836 14.7948C10.5836 15.1174 10.3218 15.3792 9.99916 15.3792ZM9.08597 13.2485H9.99916C10.3218 13.2485 10.5836 12.9867 10.5836 12.664C10.5836 12.3413 10.3218 12.0796 9.99916 12.0796H9.08597C8.76331 12.0796 8.50152 12.3413 8.50152 12.664C8.50152 12.9867 8.76329 13.2485 9.08597 13.2485Z" fill="#2FC6F6"/></svg>',
		paymentDelivery: '<svg width="30" height="31" viewBox="0 0 30 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M23.9187 21.3652C24.712 21.3652 25.355 20.7221 25.355 19.9288V16.338C25.355 16.0694 25.2813 15.8181 25.1533 15.6031C25.0404 15.258 24.8812 14.9278 24.6786 14.6219L22.6877 11.6151C22.067 10.6776 21.0176 10.1139 19.8933 10.1139H17.4387L17.4387 8.6446C17.4387 7.58692 16.5813 6.72949 15.5236 6.72949H6.68269C5.625 6.72949 4.76758 7.58692 4.76758 8.6446V16.2183L4.77249 16.2183C4.76924 16.2578 4.76758 16.2977 4.76758 16.338V19.9288C4.76758 20.7221 5.41065 21.3652 6.20391 21.3652L6.83152 21.3652C6.82633 21.2932 6.82369 21.2204 6.82369 21.1471C6.82369 19.4944 8.16342 18.1547 9.81605 18.1547C11.4687 18.1547 12.8084 19.4944 12.8084 21.1471C12.8084 21.2204 12.8058 21.2932 12.8006 21.3652H16.9842C16.9791 21.2932 16.9764 21.2204 16.9764 21.1471C16.9764 19.4944 18.3161 18.1547 19.9688 18.1547C21.6214 18.1547 22.9611 19.4944 22.9611 21.1471C22.9611 21.2204 22.9585 21.2932 22.9533 21.3652L23.9187 21.3652ZM9.81598 19.2103C10.8854 19.2103 11.7524 20.0773 11.7524 21.1467C11.7524 22.2162 10.8854 23.0832 9.81598 23.0832C8.74652 23.0832 7.87956 22.2162 7.87956 21.1467C7.87956 20.0773 8.74652 19.2103 9.81598 19.2103ZM19.9687 19.2103C21.0381 19.2103 21.9051 20.0773 21.9051 21.1467C21.9051 22.2162 21.0381 23.0832 19.9687 23.0832C18.8992 23.0832 18.0322 22.2162 18.0322 21.1467C18.0322 20.0773 18.8992 19.2103 19.9687 19.2103ZM20.1096 11.5504H18.4128V14.9018H23.106L21.306 12.1919C21.0398 11.7912 20.5907 11.5504 20.1096 11.5504ZM6.86186 12.287C6.86186 10.0019 8.71438 8.14947 10.9995 8.14947C13.2846 8.14947 15.137 10.0019 15.137 12.287C15.137 14.5722 13.2846 16.4246 10.9995 16.4246C8.71438 16.4246 6.86186 14.5722 6.86186 12.287ZM10.4906 9.01489H11.5236V9.6984C11.9836 9.73349 12.3091 9.83717 12.5569 9.95744L12.6603 10.0076L12.3688 11.1437L12.2186 11.0803C12.2002 11.0726 12.1808 11.0642 12.1603 11.0553C11.9552 10.9665 11.6411 10.8306 11.1683 10.8306C10.9365 10.8306 10.8069 10.8808 10.738 10.9348C10.6734 10.9855 10.6489 11.0499 10.6489 11.1197C10.6489 11.1824 10.6759 11.2485 10.804 11.3369C10.9367 11.4284 11.1515 11.5248 11.4773 11.6475C11.9431 11.8121 12.2886 12.0082 12.5172 12.2655C12.7503 12.5279 12.851 12.8412 12.851 13.2143C12.851 13.9445 12.3518 14.5556 11.4852 14.7492V15.5569H10.4522V14.8194C9.9693 14.7799 9.50724 14.6467 9.21801 14.4792L9.12586 14.4258L9.42741 13.2496L9.58614 13.3366C9.89311 13.5049 10.3226 13.6569 10.7923 13.6569C10.9957 13.6569 11.1509 13.6172 11.2502 13.5554C11.3435 13.4974 11.3882 13.4211 11.3882 13.3218C11.3882 13.2252 11.3517 13.1444 11.247 13.0601C11.1348 12.9699 10.9511 12.8822 10.6706 12.7874C10.2536 12.6471 9.88629 12.4746 9.62195 12.2311C9.35196 11.9825 9.19394 11.6636 9.19394 11.2501C9.19394 10.5264 9.68618 9.96873 10.4906 9.77148V9.01489Z" fill="#2FC6F6"/> </svg>',
	};

	module.exports = {
		ModeSelectionMenu,
	};
});
