(() => {
	const { StatefulList } = jn.require('layout/ui/stateful-list');
	const { AnalyticsLabel } = jn.require('analytics-label');

	const COMPONENT_ID = 'CATALOG_STORE_LIST';
	const PULL_MODULE_ID = 'catalog';
	const PULL_COMMAND = 'CATALOG_DOCUMENTS_LIST_UPDATED';

	/**
	 * @class CatalogStoreDocumentList
	 */
	class CatalogStoreDocumentList extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.detailNavigation = new DetailCardNavigation(result.detailNavigation);

			this.documentTabs = result.documentTabs || [];
			this.floatingButtonMenu = null;
			this.statuses = result.statuses;
			this.state = {
				activeTab: this.documentTabs[0].id,
			};

			this.clickFloatingButtonMenuItemHandler = this.clickFloatingButtonMenuItemHandler.bind(this);

			this.onDetailCardCreate = this.onDetailCardCreateHandler.bind(this);
		}

		createStatefulList()
		{
			return new StatefulList({
				testId: `${COMPONENT_ID}_${this.state.activeTab}`.toUpperCase(),
				actions: result.actions || {},
				actionParams: {
					loadItems: {
						documentTypes: this.getDocumentTypeIdsByTab(this.state.activeTab),
					},
				},
				itemLayoutOptions: {
					useConnectsBlock: false,
					useItemMenu: true,
					useStatusBlock: true,
				},
				isShowFloatingButton: this.isShowFloatingButton(),
				itemDetailOpenHandler: this.itemDetailOpenHandler.bind(this),
				itemActions: [
					{
						id: 'conductDocument',
						title: BX.message('M_CSDL_CONTEXT_MENU_CONDUCT_DOCUMENT'),
						showActionLoader: true,
						onClickCallback: this.conductDocumentHandler.bind(this),
						onActiveCallback: this.canConductDocumentHandler.bind(this),
						data: {
							svgIcon: menuActionSvgIcons.conduct,
						},
					},
					{
						id: 'cancelDocument',
						title: BX.message('M_CSDL_CONTEXT_MENU_CANCELLATION_DOCUMENT'),
						showActionLoader: true,
						onClickCallback: this.cancelDocumentHandler.bind(this),
						onActiveCallback: this.canCancelDocumentHandler.bind(this),
						data: {
							svgIcon: menuActionSvgIcons.cancel,
						},
					},
					{
						id: 'deleteDocument',
						title: BX.message('M_CSDL_CONTEXT_MENU_DELETE_DOCUMENT'),
						type: 'delete',
						showActionLoader: true,
						onClickCallback: this.deleteDocumentHandler.bind(this),
						onActiveCallback: this.canDeleteDocumentHandler.bind(this),
					},
				],
				itemParams: {
					statuses: this.statuses,
				},
				emptyListText: BX.message('M_CSDL_EMPTY_LIST_TEXT'),
				emptySearchText: BX.message('M_CSDL_EMPTY_SEARCH_TEXT'),
				layout: layout,
				layoutMenuActions: this.getMenuActions(),
				layoutOptions: {
					useSearch: true,
					useOnViewLoaded: false,
				},
				floatingButtonClickHandler: this.floatingButtonClickHandler.bind(this),
				cacheName: 'store.docs.' + env.userId + '.' + this.state.activeTab,
				pull: {
					moduleId: PULL_MODULE_ID,
					callback: data => {
						return new Promise((resolve, reject) => {
							if (data.command === PULL_COMMAND)
							{
								this.preparePullData(data);
								resolve(data);
								return;
							}
							reject();
						});
					},
					notificationUpdateText: BX.message('M_CSDL_PULL_NOTIFICATION_UPDATE'),
					notificationAddText: BX.message('M_CSDL_PULL_NOTIFICATION_ADD'),
				},
				onDetailCardCreateHandler: this.onDetailCardCreate,
				onDetailCardUpdateHandler: (params) => {
					if (this.statefulList)
					{
						this.statefulList.updateItems([params.entityId]);
					}
				},
				ref: ref => this.statefulList = ref,
			});
		}

		getMenuActions()
		{
			return [
				{
					type: UI.Menu.Types.DESKTOP,
					showHint: false,
					data: {
						qrUrl: '/shop/documents/receipt_adjustment/',
					},
				},
				{
					type: UI.Menu.Types.HELPDESK,
					data: {
						articleCode: '14721730',
					},
				},
			];
		}

		preparePullData(data)
		{
			data.params.items.map(item => {
				item.data = item.mobileData;
				delete item.mobileData;
			});
		}

		render()
		{
			return View(
				{
					testId: COMPONENT_ID,
					resizableByKeyboard: true,
				},
				TabView({
					testId: `${COMPONENT_ID}_TAB`,
					style: {
						height: 44,
						backgroundColor: '#f5f7f8',
					},
					params: {
						styles: {
							tabTitle: {
								underlineColor: '#2899f0',
							},
						},
						items: this.documentTabs,
					},
					onTabSelected: (tab, changed) => {
						if (changed)
						{
							this.setState({
								activeTab: tab.id,
							}, () => {
								this.statefulList.reload();
							});
						}
						else if (tab.selectable === false)
						{
							qrauth.open({
								title: tab.title || BX.message('M_CSDL_TO_LOGIN_ON_DESKTOP_MSGVER_1'),
								redirectUrl: this.getDesktopPageLink(tab.id),
							});
						}
					},
					ref: ref => this.tabViewRef = ref,
				}),
				this.createStatefulList(),
			);
		}

		getTabIdByDocumentType(documentType)
		{
			let tabId = '';
			switch (documentType) {
				case 'A':
				case 'S':
					tabId = 'receipt_adjustment';
					break;
				case 'M':
					tabId = 'moving';
					break;
				case 'D':
					tabId = 'deduct';
					break;
			}

			return tabId;
		}

		setActiveTabByDocumentType(documentType)
		{
			if (!this.tabViewRef)
			{
				return;
			}

			const tabId = this.getTabIdByDocumentType(documentType);

			if (tabId)
			{
				this.tabViewRef.setActiveItem(tabId);
			}
		}

		getTab(tabId)
		{
			return this.documentTabs.find((tab) => tab.id === tabId);
		}

		getDocumentTypesByTab(tabId)
		{
			const tab = this.getTab(tabId);
			if (tab)
			{
				return tab.documentTypes;
			}

			return [];
		}

		getDocumentTypeIdsByTab(tabId)
		{
			const types = this.getDocumentTypesByTab(tabId);
			if (types)
			{
				return types.map((type) => type.id);
			}

			return [];
		}

		getDesktopPageLink(tabId)
		{
			const tab = this.getTab(tabId);
			if (tab)
			{
				return tab.link;
			}

			return null;
		}

		itemDetailOpenHandler(entityId, item)
		{
			this.itemDetailOpen(
				{ entityId },
				{
					...this.detailNavigation.getTitleParamsByType(item.docType),
					text: item.name,
				},
			);
		}

		newItemOpenHandler(docType)
		{
			this.itemDetailOpen(
				{ docType },
				{
					...this.detailNavigation.getTitleParamsByType(docType),
					text: BX.message('M_CSDL_DETAIL_CARD_NEW_DOCUMENT'),
				},
			);
		}

		itemDetailOpen(componentParams, titleParams)
		{
			componentParams = (componentParams || {});
			titleParams = (titleParams || {});

			ComponentHelper.openLayout({
				name: 'catalog.store.document.details',
				componentParams: {
					payload: componentParams,
				},
				widgetParams: {
					titleParams: titleParams,
					modal: true,
					leftButtons: [{
						// type: 'cross',
						svg: {
							content: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.722 6.79175L10.9495 10.5643L9.99907 11.5L9.06666 10.5643L5.29411 6.79175L3.96289 8.12297L10.008 14.1681L16.0532 8.12297L14.722 6.79175Z" fill="#A8ADB4"/></svg>',
						},
						isCloseButton: true,
					}],
				},
			});
		}

		floatingButtonClickHandler()
		{
			const tabId = this.state.activeTab;
			this.floatingMenu = this.getFloatingButtonMenu();
			if (this.floatingMenu)
			{
				this.floatingMenu.show();
				return;
			}

			const typeIds = this.getDocumentTypeIdsByTab(tabId);
			if (typeIds && typeIds.length)
			{
				this.newItemOpenHandler(typeIds[0]);
			}
		}

		getFloatingButtonMenu()
		{
			if (!this.floatingButtonMenu)
			{
				this.floatingButtonMenu = this.createFloatingMenu();
			}

			return this.floatingButtonMenu;
		}

		createFloatingMenu()
		{
			const actions = this.getFloatingMenuActions();
			if (actions.length === 0)
			{
				return null;
			}

			return new ContextMenu({
				testId: COMPONENT_ID,
				actions: actions,
				params: {
					showCancelButton: true,
					showActionLoader: false,
					title: BX.message('M_CSDL_CONTEXT_MENU_CREATE_DOCUMENT'),
					isCustomIconColor: true,
				},
			});
		}

		isShowFloatingButton()
		{
			return this.getModifiableDocumentTypes().length > 0;
		}

		getModifiableDocumentTypes()
		{
			const documentTypes = result.floatingMenuTypes;
			if (documentTypes.length < 1)
			{
				return [];
			}

			return documentTypes.filter(item => result.permissions.document[item.id]['catalog_store_document_modify']);
		}

		getFloatingMenuActions()
		{
			return this.getModifiableDocumentTypes()
				.map((item) => ({
					id: item.id,
					title: item.title,
					data: {
						svgIcon: (floatingButtonSvgIcons[item.id] ? floatingButtonSvgIcons[item.id] : null),
					},
					onClickCallback: this.clickFloatingButtonMenuItemHandler,
				}),
			);
		}

		clickFloatingButtonMenuItemHandler(actionItemId)
		{
			if (this.floatingMenu)
			{
				this.floatingMenu.close(() => {
					this.newItemOpenHandler(actionItemId);
				});
			}

			return Promise.resolve({ closeMenu: false });
		}

		onDetailCardCreateHandler(params)
		{
			if (!params.docType || !this.statefulList)
			{
				return;
			}

			const tabId = this.getTabIdByDocumentType(params.docType);

			if (tabId === this.state.activeTab)
			{
				this.statefulList.reload();
			}
			else
			{
				this.setActiveTabByDocumentType(params.docType);
			}
		}

		conductDocumentHandler(actionItemId, itemId, options)
		{
			return new Promise((resolve, reject) => {
				BX.ajax.runAction(
					'mobile.catalog.storeDocument.conduct',
					{
						data: {
							id: itemId,
						},
					},
				)
					.then((response) => {
						resolve({
							action: 'update',
							id: itemId,
							params: {
								data: response.data.item,
							},
						});
					}, (response) => {
						reject(this.processActionResponseErrors(
							response,
							options,
						));
					});
			});
		}

		canConductDocumentHandler(actionItemId, itemId, item = {})
		{
			if (result.permissions.document[item.data.docType]['catalog_store_document_conduct'] !== true)
			{
				return false;
			}

			return !this.hasStatus('Y', item);
		}

		cancelDocumentHandler(actionItemId, itemId, options)
		{
			return new Promise((resolve, reject) => {
				BX.ajax.runAction(
					'mobile.catalog.storeDocument.cancellation',
					{
						data: {
							id: itemId,
						},
					},
				)
					.then((response) => {
						resolve({
							action: 'update',
							id: itemId,
							params: {
								data: response.data.item,
							},
						});
					}, (response) => {
						reject(this.processActionResponseErrors(
							response,
							options,
						));
					});
			});
		}

		canCancelDocumentHandler(actionItemId, itemId, item)
		{
			if (result.permissions.document[item.data.docType]['catalog_store_document_cancel'] !== true)
			{
				return false;
			}

			return this.hasStatus('Y', item);
		}

		processActionResponseErrors(response, options)
		{
			const errors = response.errors.length ? response.errors : [{ message: 'Could not perform action' }];
			const showErrors = !CatalogStoreActivationWizard.hasStoreControlDisabledError(errors);

			return {
				errors: errors,
				showErrors: showErrors,
				callback: () => {
					if (!showErrors)
					{
						CatalogStoreActivationWizard.open();
					}
				},
			};
		}

		/**
		 * @param {String} status
		 * @param item
		 * @returns {boolean}
		 */
		hasStatus(status, item = {})
		{
			return (
				item.data
				&& item.data.statuses
				&& Array.isArray(item.data.statuses)
				&& item.data.statuses.indexOf(status) !== -1
			);
		}

		deleteDocumentHandler(actionItemId, itemId)
		{
			return new Promise((resolve, reject) => {
				const OK = 1;
				navigator.notification.confirm(
					BX.message('M_CSDL_DOCUMENT_DELETE_CONFIRMATION'),
					index => {
						if (index === OK)
						{
							BX.ajax.runAction(
								'mobile.catalog.storeDocument.delete',
								{
									data: {
										id: itemId,
									},
								},
							)
								.then(response => {
									if (response.errors.length)
									{
										reject({
											errors: response.errors,
											showErrors: true,
										});
									}
									resolve({
										action: 'delete',
										id: itemId,
									});
								}, response => {
									reject({
										errors: response.errors,
										showErrors: true,
									});
								});
						}
						else
						{
							resolve({});
						}
					},
					'',
					[
						BX.message('M_CSDL_DOCUMENT_DELETE_CONFIRMATION_OK'),
						BX.message('M_CSDL_DOCUMENT_DELETE_CONFIRMATION_CANCEL'),
					],
				);
			});
		}

		canDeleteDocumentHandler(actionItemId, itemId, item)
		{
			if (result.permissions.document[item.data.docType]['catalog_store_document_delete'] !== true)
			{
				return false;
			}

			return !this.hasStatus('Y', item);
		}
	}

	// @todo change XXX to id of the sale action
	const floatingButtonSvgIcons = {
		A: '<svg width="18" height="22" viewBox="0 0 18 22" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0 2.5C0 1.11929 1.11929 0 2.5 0H10.2657C10.9196 0 11.5474 0.256149 12.0146 0.71354L16.807 5.40509C17.2874 5.87537 17.5581 6.51929 17.5581 7.19155V18.8483C17.5581 20.229 16.4388 21.3483 15.0581 21.3483H2.5C1.11929 21.3483 0 20.229 0 18.8483V2.5ZM2.5 2.5V18.8483H15.0581V7.19155L10.2657 2.5H2.5Z" fill="#4793E0"/><path d="M12.124 12.8008C12.3129 12.6119 12.1791 12.2887 11.9118 12.2887H9.64688L9.64688 6.4663C9.64688 6.19015 9.42303 5.9663 9.14688 5.9663H7.94006C7.66391 5.9663 7.44006 6.19015 7.44006 6.4663V12.2887H5.01381C4.74654 12.2887 4.61269 12.6119 4.80168 12.8008L7.75571 15.7549C8.14623 16.1454 8.7794 16.1454 9.16992 15.7549L12.124 12.8008Z" fill="#4793E0"/></svg>',
		S: '<svg width="18" height="22" viewBox="0 0 18 22" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0 2.5C0 1.11929 1.11929 0 2.5 0H10.2657C10.9196 0 11.5474 0.256149 12.0146 0.71354L16.807 5.40509C17.2874 5.87537 17.5581 6.51929 17.5581 7.19155V18.8483C17.5581 20.229 16.4388 21.3483 15.0581 21.3483H2.5C1.11929 21.3483 0 20.229 0 18.8483V2.5ZM2.5 2.5V18.8483H15.0581V7.19155L10.2657 2.5H2.5Z" fill="#00ACE3"/><path d="M4.56836 17.1055C4.29222 17.1055 4.06836 16.8816 4.06836 16.6055V15.3986C4.06836 15.1225 4.29222 14.8986 4.56836 14.8986H12.5162C12.7923 14.8986 13.0162 15.1225 13.0162 15.3986V16.6055C13.0162 16.8816 12.7923 17.1055 12.5162 17.1055H4.56836Z" fill="#00ACE3"/><path d="M12.1237 10.4154C12.3127 10.2264 12.1788 9.90325 11.9115 9.90325H9.64661V6.18574C9.64661 5.9096 9.42275 5.68574 9.14661 5.68574H7.93978C7.66364 5.68574 7.43978 5.9096 7.43978 6.18574V9.90325L5.01353 9.90325C4.74626 9.90325 4.61241 10.2264 4.8014 10.4154L7.75543 13.3694C8.14596 13.7599 8.77912 13.7599 9.16964 13.3694L12.1237 10.4154Z" fill="#00ACE3"/></svg>',
		D: '<svg width="18" height="22" viewBox="0 0 18 22" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0 2.5C0 1.11929 1.11929 0 2.5 0H10.2657C10.9196 0 11.5474 0.256149 12.0146 0.71354L16.807 5.40509C17.2874 5.87537 17.5581 6.51929 17.5581 7.19155V18.8483C17.5581 20.229 16.4388 21.3483 15.0581 21.3483H2.5C1.11929 21.3483 0 20.229 0 18.8483V2.5ZM2.5 2.5V18.8483H15.0581V7.19155L10.2657 2.5H2.5Z" fill="#F78500"/><path d="M4.56836 17.1055C4.29222 17.1055 4.06836 16.8816 4.06836 16.6055V15.3986C4.06836 15.1225 4.29222 14.8986 4.56836 14.8986H12.5162C12.7923 14.8986 13.0162 15.1225 13.0162 15.3986V16.6055C13.0162 16.8816 12.7923 17.1055 12.5162 17.1055H4.56836Z" fill="#F78500"/><path d="M4.80136 8.93248C4.61237 9.12147 4.74622 9.44461 5.01349 9.44461L7.27844 9.44461L7.27844 13.1621C7.27844 13.4383 7.50229 13.6621 7.77843 13.6621H8.98526C9.26141 13.6621 9.48526 13.4383 9.48526 13.1621V9.44461L11.9115 9.44461C12.1788 9.44461 12.3126 9.12147 12.1236 8.93248L9.16961 5.97844C8.77909 5.58792 8.14592 5.58792 7.7554 5.97844L4.80136 8.93248Z" fill="#F78500"/></svg>',
		M: '<svg width="18" height="22" viewBox="0 0 18 22" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0 2.5C0 1.11929 1.11929 0 2.5 0H10.2657C10.9196 0 11.5474 0.256149 12.0146 0.71354L16.807 5.40509C17.2874 5.87537 17.5581 6.51929 17.5581 7.19155V18.8483C17.5581 20.229 16.4388 21.3483 15.0581 21.3483H2.5C1.11929 21.3483 0 20.229 0 18.8483V2.5ZM2.5 2.5V18.8483H15.0581V7.19155L10.2657 2.5H2.5Z" fill="#17CDC4"/><path d="M9.87947 5.21053C9.69048 5.02154 9.36734 5.15539 9.36734 5.42266L9.36734 7.40314H4.92017C4.64403 7.40314 4.42017 7.627 4.42017 7.90314L4.42017 8.89997C4.42017 9.17612 4.64403 9.39997 4.92017 9.39997H9.36734V11.5264C9.36734 11.7937 9.69048 11.9276 9.87947 11.7386L12.4364 9.18165C12.8269 8.79113 12.8269 8.15796 12.4364 7.76744L9.87947 5.21053Z" fill="#17CDC4"/><path d="M6.80412 17.5014C6.99311 17.6904 7.31625 17.5565 7.31625 17.2893V15.3088H11.7634C12.0396 15.3088 12.2634 15.0849 12.2634 14.8088L12.2634 13.8119C12.2634 13.5358 12.0396 13.3119 11.7634 13.3119H7.31625V11.1855C7.31625 10.9182 6.99311 10.7844 6.80412 10.9733L4.24721 13.5303C3.85668 13.9208 3.85668 14.554 4.24721 14.9445L6.80412 17.5014Z" fill="#17CDC4"/></svg>',
		XXX: '<svg width="18" height="22" viewBox="0 0 18 22" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0 2.5C0 1.11929 1.11929 0 2.5 0H10.2657C10.9196 0 11.5474 0.256149 12.0146 0.71354L16.807 5.40509C17.2874 5.87537 17.5581 6.51929 17.5581 7.19155V18.8483C17.5581 20.229 16.4388 21.3483 15.0581 21.3483H2.5C1.11929 21.3483 0 20.229 0 18.8483V2.5ZM2.5 2.5V18.8483H15.0581V7.19155L10.2657 2.5H2.5Z" fill="#9DCF00"/><path d="M4.88218 9.402C4.69319 9.59099 4.82704 9.91413 5.09431 9.91413L7.35925 9.91413L7.35925 14.8816C7.35925 15.1578 7.58311 15.3816 7.85925 15.3816L9.06608 15.3816C9.34222 15.3816 9.56608 15.1578 9.56608 14.8816L9.56608 9.91413L11.9923 9.91413C12.2596 9.91413 12.3935 9.59099 12.2045 9.402L9.25043 6.44797C8.8599 6.05744 8.22674 6.05744 7.83621 6.44797L4.88218 9.402Z" fill="#9DCF00"/></svg>',
	};

	const menuActionSvgIcons = {
		conduct: '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.72093 4.32587C7.34022 4.32587 6.22093 5.44515 6.22093 6.82587V23.1741C6.22093 24.5549 7.34022 25.6741 8.72093 25.6741H21.279C22.6598 25.6741 23.779 24.5549 23.779 23.1741V11.5174C23.779 10.8452 23.5083 10.2012 23.0279 9.73096L18.2355 5.03941C17.7683 4.58202 17.1405 4.32587 16.4867 4.32587H8.72093ZM8.72093 23.1741V6.82587H16.4867L21.279 11.5174V23.1741H8.72093ZM13.8534 19.5488C13.6581 19.7441 13.3415 19.7441 13.1463 19.5488L12.2914 18.694C12.2742 18.6767 12.2584 18.6585 12.2442 18.6395L10.6421 17.0374C10.4468 16.8421 10.4468 16.5255 10.6421 16.3302L11.4969 15.4754C11.6922 15.2802 12.0088 15.2802 12.204 15.4754L13.5035 16.7749L17.9005 12.3778C18.0957 12.1826 18.4123 12.1826 18.6076 12.3778L19.4624 13.2327C19.6577 13.4279 19.6577 13.7445 19.4624 13.9398L13.8534 19.5488Z" fill="#525C69"/></svg>',
		cancel: '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.72095 4.32587C7.34024 4.32587 6.22095 5.44515 6.22095 6.82587V23.1741C6.22095 24.5549 7.34024 25.6741 8.72095 25.6741H21.2791C22.6598 25.6741 23.7791 24.5549 23.7791 23.1741V11.5174C23.7791 10.8452 23.5083 10.2012 23.0279 9.73096L18.2356 5.03941C17.7683 4.58202 17.1405 4.32587 16.4867 4.32587H8.72095ZM8.72095 23.1741V6.82587H16.4867L21.2791 11.5174V23.1741H8.72095ZM11.6038 13.9398C11.4085 13.7445 11.4085 13.4279 11.6038 13.2327L12.4586 12.3778C12.6539 12.1826 12.9705 12.1826 13.1657 12.3778L15.1891 14.4012L17.2124 12.3779C17.4077 12.1826 17.7242 12.1826 17.9195 12.3779L18.7743 13.2327C18.9696 13.428 18.9696 13.7445 18.7743 13.9398L16.751 15.9631L18.7747 17.9869C18.97 18.1821 18.97 18.4987 18.7747 18.694L17.9199 19.5488C17.7246 19.7441 17.4081 19.7441 17.2128 19.5488L15.1891 17.5251L13.1653 19.5488C12.9701 19.7441 12.6535 19.7441 12.4582 19.5488L11.6034 18.694C11.4081 18.4987 11.4081 18.1821 11.6034 17.9869L13.6271 15.9631L11.6038 13.9398Z" fill="#525C69"/></svg>',
	};

	BX.onViewLoaded(() => {
		layout.enableNavigationBarBorder(false);
		layout.showComponent(new CatalogStoreDocumentList());

		AnalyticsLabel.send({
			event: 'showInventoryManagement',
		});
	});

})();
