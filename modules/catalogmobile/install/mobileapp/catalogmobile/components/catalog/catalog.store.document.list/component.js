(() => {
	const require = (ext) => jn.require(ext);

	const { CatalogStoreActivationWizard } = require('catalog/store/activation-wizard');
	const { StatefulList } = require('layout/ui/stateful-list');
	const { EmptyScreen } = require('layout/ui/empty-screen');
	const { Loc } = require('loc');
	const { AnalyticsLabel } = require('analytics-label');
	const { DocumentType } = require('catalog/store/document-type');
	const { Alert } = require('alert');
	const { ButtonType } = require('alert/confirm');

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
			this.layout = props.layout;
			this.statefulList = null;

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
						id: 'openDocument',
						title: BX.message('M_CSDL_CONTEXT_MENU_OPEN_DOCUMENT'),
						onClickCallback: (action, itemId, { parentWidget, parent }) => {
							parentWidget.close(() => this.itemDetailOpenHandler(itemId, parent.data));
						},
						onActiveCallback: (actionItemId, itemId, item = {}) => {
							return !this.canEditDocumentHandler(actionItemId, itemId, item);
						},
						data: {
							svgIcon: menuActionSvgIcons.open,
						},
						showArrow: true,
					},
					{
						id: 'editDocument',
						title: BX.message('M_CSDL_CONTEXT_MENU_EDIT_DOCUMENT'),
						onClickCallback: (action, itemId, { parentWidget, parent }) => {
							parentWidget.close(() => this.itemDetailOpenHandler(itemId, parent.data));
						},
						onActiveCallback: this.canEditDocumentHandler.bind(this),
						data: {
							svgIcon: menuActionSvgIcons.edit,
						},
						showArrow: true,
					},
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
				getEmptyListComponent: this.renderEmptyListComponent.bind(this),
				layout: this.layout,
				layoutMenuActions: this.getMenuActions(),
				layoutOptions: {
					useSearch: true,
					useOnViewLoaded: false,
				},
				floatingButtonClickHandler: this.floatingButtonClickHandler.bind(this),
				cacheName: `store.docs.${env.userId}.${this.state.activeTab}`,
				pull: {
					moduleId: PULL_MODULE_ID,
					callback: (data) => {
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
				ref: (ref) => this.statefulList = ref,
			});
		}

		renderEmptyListComponent()
		{
			const params = {
				styles: styles.emptyScreen.container,
				image: {
					style: styles.emptyScreen.image,
					svg: {
						content: emptyStateIcons.list,
					},
				},
			};

			if (this.layout.search.text === '')
			{
				params.title = Loc.getMessage(`M_CSDL_EMPTY_LIST_STORE_${this.state.activeTab}_TITLE`.toUpperCase());
				params.description = Loc.getMessage(`M_CSDL_EMPTY_LIST_STORE_${this.state.activeTab}_DESCRIPTION`.toUpperCase());
			}
			else
			{
				params.title = Loc.getMessage('M_CSDL_EMPTY_LIST_STORE_SEARCH_TITLE');
				params.description = Loc.getMessage('M_CSDL_EMPTY_LIST_STORE_SEARCH_DESCRIPTION');
			}

			return new EmptyScreen(params);
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
			data.params.items.map((item) => {
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
						else
						{
							this.statefulList.reload();
						}
					},
					ref: (ref) => this.tabViewRef = ref,
				}),
				this.createStatefulList(),
			);
		}

		setActiveTabByDocumentType(documentType)
		{
			if (!this.tabViewRef)
			{
				return;
			}

			let tabId = '';
			switch (documentType)
			{
				case DocumentType.Arrival:
				case DocumentType.StoreAdjustment:
					tabId = 'receipt_adjustment';
					break;
				case DocumentType.Moving:
					tabId = 'moving';
					break;
				case DocumentType.Deduct:
					tabId = 'deduct';
					break;
			}

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
				name: 'catalog:catalog.store.document.details',
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
			if (typeIds && typeIds.length > 0)
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
			if (documentTypes.length === 0)
			{
				return [];
			}

			return documentTypes.filter((item) => result.permissions.document[item.id].catalog_store_document_modify);
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
				}));
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
			if (
				params.docType
				&& !this.getDocumentTypesByTab(this.state.activeTab).map((type) => type.id).includes(params.docType)
			)
			{
				this.setActiveTabByDocumentType(params.docType);
			}
			else if (this.statefulList)
			{
				this.statefulList.addToAnimateIds(params.entityId);
				this.statefulList.reload();
			}
		}

		conductDocumentHandler(actionItemId, itemId, options)
		{
			return new Promise((resolve, reject) => {
				BX.ajax.runAction(
					'catalogmobile.StoreDocument.conduct',
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

		canEditDocumentHandler(actionItemId, itemId, item = {})
		{
			if (result.permissions.document[item.data.docType]['catalog_store_document_modify'] !== true)
			{
				return false;
			}

			return !this.hasStatus('Y', item);
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
					'catalogmobile.StoreDocument.cancellation',
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
			const errors = response.errors.length > 0 ? response.errors : [{ message: 'Could not perform action' }];
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
				&& item.data.statuses.includes(status)
			);
		}

		deleteDocumentHandler(actionItemId, itemId)
		{
			return new Promise((resolve, reject) => {
				Alert.confirm(
					'',
					BX.message('M_CSDL_DOCUMENT_DELETE_CONFIRMATION'),
					[
						{
							text: BX.message('M_CSDL_DOCUMENT_DELETE_CONFIRMATION_CANCEL'),
							type: ButtonType.CANCEL,
							onPress: () => resolve({}),
						},
						{
							text: BX.message('M_CSDL_DOCUMENT_DELETE_CONFIRMATION_OK'),
							type: ButtonType.DESTRUCTIVE,
							onPress: () => {
								BX.ajax.runAction(
									'catalogmobile.StoreDocument.delete',
									{
										data: {
											id: itemId,
										},
									},
								)
									.then((response) => {
										if (response.errors.length > 0)
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
									}, (response) => {
										reject({
											errors: response.errors,
											showErrors: true,
										});
									});
							},
						},
					],
				);
			});
		}

		canDeleteDocumentHandler(actionItemId, itemId, item)
		{
			if (result.permissions.document[item.data.docType].catalog_store_document_delete !== true)
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
		open: '<svg width="17" height="21" viewBox="0 0 17 21" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.4234 17.7238C14.4234 17.8825 14.2934 18.01 14.1346 18.01H2.56338C2.40338 18.01 2.27463 17.8825 2.27463 17.7238V2.2875C2.27463 2.13 2.40338 2.00125 2.56338 2.00125H8.05963C8.21963 2.00125 8.34838 2.13 8.34838 2.2875V7.72C8.34838 7.8775 8.47838 8.005 8.63838 8.005H14.1346C14.2934 8.005 14.4234 8.13375 14.4234 8.29125V17.7238ZM10.3734 3.09C10.3734 3.0325 10.4221 2.98375 10.4821 2.98375C10.5109 2.98375 10.5384 2.995 10.5584 3.015L13.3984 5.82125C13.4409 5.8625 13.4409 5.93 13.3984 5.9725C13.3771 5.9925 13.3509 6.00375 13.3209 6.00375H10.4821C10.4221 6.00375 10.3734 5.955 10.3734 5.89625V3.09ZM16.0234 5.585L10.6909 0.31375C10.4884 0.11375 10.2121 0 9.92338 0H1.33463C0.734634 0 0.249634 0.48 0.249634 1.0725V18.94C0.249634 19.5313 0.734634 20.0113 1.33463 20.0113H15.3634C15.9609 20.0113 16.4471 19.5313 16.4471 18.94V6.59625C16.4471 6.21625 16.2946 5.8525 16.0234 5.585ZM12.0359 10.0063H4.65963C4.46088 10.0063 4.29838 10.1663 4.29838 10.3638V11.65C4.29838 11.8463 4.46088 12.0075 4.65963 12.0075H12.0359C12.2359 12.0075 12.3984 11.8463 12.3984 11.65V10.3638C12.3984 10.1663 12.2359 10.0063 12.0359 10.0063ZM4.73338 8.005H5.88963C6.12963 8.005 6.32338 7.8125 6.32338 7.575V6.4325C6.32338 6.195 6.12963 6.00375 5.88963 6.00375H4.73338C4.49338 6.00375 4.29838 6.195 4.29838 6.4325V7.575C4.29838 7.8125 4.49338 8.005 4.73338 8.005ZM12.0359 14.0087H4.65963C4.46088 14.0087 4.29838 14.1675 4.29838 14.365V15.6525C4.29838 15.8488 4.46088 16.01 4.65963 16.01H12.0359C12.2359 16.01 12.3984 15.8488 12.3984 15.6525V14.365C12.3984 14.1675 12.2359 14.0087 12.0359 14.0087Z" fill="#6a737f"/></svg>',
		edit: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M17.0242 3.54235C17.2203 3.34691 17.5378 3.34801 17.7326 3.54479L20.6219 6.46453C20.8156 6.66031 20.8145 6.97592 20.6195 7.17036L9.43665 18.3165L5.84393 14.686L17.0242 3.54235ZM4.1756 19.5286C4.14163 19.6572 4.17803 19.7931 4.27024 19.8877C4.36488 19.9823 4.50078 20.0187 4.62939 19.9823L8.64557 18.9003L5.25791 15.5137L4.1756 19.5286Z" fill="#6a737f"/></svg>',
		conduct: '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.72093 4.32587C7.34022 4.32587 6.22093 5.44515 6.22093 6.82587V23.1741C6.22093 24.5549 7.34022 25.6741 8.72093 25.6741H21.279C22.6598 25.6741 23.779 24.5549 23.779 23.1741V11.5174C23.779 10.8452 23.5083 10.2012 23.0279 9.73096L18.2355 5.03941C17.7683 4.58202 17.1405 4.32587 16.4867 4.32587H8.72093ZM8.72093 23.1741V6.82587H16.4867L21.279 11.5174V23.1741H8.72093ZM13.8534 19.5488C13.6581 19.7441 13.3415 19.7441 13.1463 19.5488L12.2914 18.694C12.2742 18.6767 12.2584 18.6585 12.2442 18.6395L10.6421 17.0374C10.4468 16.8421 10.4468 16.5255 10.6421 16.3302L11.4969 15.4754C11.6922 15.2802 12.0088 15.2802 12.204 15.4754L13.5035 16.7749L17.9005 12.3778C18.0957 12.1826 18.4123 12.1826 18.6076 12.3778L19.4624 13.2327C19.6577 13.4279 19.6577 13.7445 19.4624 13.9398L13.8534 19.5488Z" fill="#525C69"/></svg>',
		cancel: '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.72095 4.32587C7.34024 4.32587 6.22095 5.44515 6.22095 6.82587V23.1741C6.22095 24.5549 7.34024 25.6741 8.72095 25.6741H21.2791C22.6598 25.6741 23.7791 24.5549 23.7791 23.1741V11.5174C23.7791 10.8452 23.5083 10.2012 23.0279 9.73096L18.2356 5.03941C17.7683 4.58202 17.1405 4.32587 16.4867 4.32587H8.72095ZM8.72095 23.1741V6.82587H16.4867L21.2791 11.5174V23.1741H8.72095ZM11.6038 13.9398C11.4085 13.7445 11.4085 13.4279 11.6038 13.2327L12.4586 12.3778C12.6539 12.1826 12.9705 12.1826 13.1657 12.3778L15.1891 14.4012L17.2124 12.3779C17.4077 12.1826 17.7242 12.1826 17.9195 12.3779L18.7743 13.2327C18.9696 13.428 18.9696 13.7445 18.7743 13.9398L16.751 15.9631L18.7747 17.9869C18.97 18.1821 18.97 18.4987 18.7747 18.694L17.9199 19.5488C17.7246 19.7441 17.4081 19.7441 17.2128 19.5488L15.1891 17.5251L13.1653 19.5488C12.9701 19.7441 12.6535 19.7441 12.4582 19.5488L11.6034 18.694C11.4081 18.4987 11.4081 18.1821 11.6034 17.9869L13.6271 15.9631L11.6038 13.9398Z" fill="#525C69"/></svg>',
	};

	const emptyStateIcons = {
		list: '<svg width="163" height="133" viewBox="0 0 163 133" fill="none" xmlns="http://www.w3.org/2000/svg">\n<path fill-rule="evenodd" clip-rule="evenodd" d="M116.909 4.31682C117.898 4.31682 118.699 3.51531 118.699 2.5266C118.699 1.53788 117.898 0.736374 116.909 0.736374C115.92 0.736374 115.119 1.53788 115.119 2.5266C115.119 3.51531 115.92 4.31682 116.909 4.31682ZM28.5703 19.7815C30.7137 19.7815 32.4513 18.0439 32.4513 15.9006C32.4513 13.7572 30.7137 12.0196 28.5703 12.0196C26.427 12.0196 24.6894 13.7572 24.6894 15.9006C24.6894 18.0439 26.427 19.7815 28.5703 19.7815ZM28.5703 17.6176C29.5186 17.6176 30.2874 16.8489 30.2874 15.9006C30.2874 14.9522 29.5186 14.1835 28.5703 14.1835C27.622 14.1835 26.8532 14.9522 26.8532 15.9006C26.8532 16.8489 27.622 17.6176 28.5703 17.6176ZM8.85409 57.6687C11.7409 57.6687 14.0812 55.3284 14.0812 52.4416C14.0812 49.5547 11.7409 47.2145 8.85409 47.2145C5.96725 47.2145 3.627 49.5547 3.627 52.4416C3.627 55.3284 5.96725 57.6687 8.85409 57.6687ZM8.85409 54.9406C10.2343 54.9406 11.3532 53.8218 11.3532 52.4416C11.3532 51.0614 10.2343 49.9425 8.85409 49.9425C7.4739 49.9425 6.35502 51.0614 6.35502 52.4416C6.35502 53.8218 7.4739 54.9406 8.85409 54.9406ZM141.564 123.441C143.62 123.441 145.286 121.775 145.286 119.72C145.286 117.664 143.62 115.998 141.564 115.998C139.509 115.998 137.843 117.664 137.843 119.72C137.843 121.775 139.509 123.441 141.564 123.441ZM141.564 121.504C142.55 121.504 143.349 120.705 143.349 119.72C143.349 118.734 142.55 117.935 141.564 117.935C140.579 117.935 139.779 118.734 139.779 119.72C139.779 120.705 140.579 121.504 141.564 121.504ZM82 133C117.346 133 146 104.346 146 69C146 33.6538 117.346 5 82 5C46.6538 5 18 33.6538 18 69C18 104.346 46.6538 133 82 133ZM17.9693 128.526H5.5276C5.42114 128.526 5.3156 128.522 5.21111 128.514C2.39924 128.449 0.139676 126.086 0.139534 123.18C0.140351 121.764 0.688502 120.407 1.6634 119.406C2.16312 118.893 2.75398 118.495 3.39683 118.23C3.38607 118.086 3.3806 117.94 3.3806 117.793C3.38148 116.299 3.95988 114.866 4.98855 113.81C6.01723 112.755 7.41191 112.162 8.86579 112.163C10.7282 112.165 12.3724 113.122 13.3606 114.583C13.8299 114.412 14.335 114.319 14.8613 114.32C17.186 114.322 19.0954 116.144 19.3168 118.473C21.5443 118.972 23.2113 121.011 23.2093 123.45C23.207 126.26 20.9892 128.536 18.255 128.535C18.1591 128.535 18.0639 128.532 17.9693 128.526ZM149.824 29.5524H158.653C158.72 29.5562 158.788 29.5581 158.856 29.5581C160.797 29.5589 162.37 28.0068 162.372 26.0909C162.373 24.4284 161.19 23.0379 159.61 22.6976C159.453 21.1096 158.098 19.8679 156.448 19.8659C156.074 19.8657 155.716 19.929 155.383 20.0455C154.681 19.0495 153.515 18.3969 152.193 18.3953C151.161 18.3947 150.171 18.7988 149.441 19.5187C148.711 20.2386 148.301 21.2154 148.3 22.2341C148.3 22.3343 148.304 22.4336 148.312 22.5318C147.855 22.713 147.436 22.9841 147.081 23.3338C146.39 24.0161 146.001 24.9418 146 25.9073C146 27.8882 147.604 29.4994 149.599 29.5442C149.673 29.5496 149.748 29.5524 149.824 29.5524Z" fill="#E5F9FF"/>\n<g filter="url(#filter0_d_1_63)">\n<path d="M120.841 56.743C120.841 53.6628 119.44 50.7499 117.033 48.8272L84.9249 23.1717C83.0659 21.6863 80.4236 21.6949 78.5743 23.1924L46.9153 48.828C44.5394 50.7518 43.1592 53.6454 43.1592 56.7025V103.376C43.1592 106.174 45.4274 108.442 48.2254 108.442H115.775C118.573 108.442 120.841 106.174 120.841 103.376V56.743Z" fill="white"/>\n</g>\n<path fill-rule="evenodd" clip-rule="evenodd" d="M116.506 49.4868L84.3978 23.8314C82.8486 22.5935 80.6467 22.6007 79.1056 23.8486L47.4466 49.4842C45.2687 51.2477 44.0035 53.9002 44.0035 56.7025V103.376C44.0035 105.707 45.8937 107.597 48.2253 107.597H115.775C118.106 107.597 119.996 105.707 119.996 103.376V56.743C119.996 53.9195 118.712 51.2493 116.506 49.4868ZM117.033 48.8272C119.44 50.7499 120.841 53.6628 120.841 56.743V103.376C120.841 106.174 118.573 108.442 115.775 108.442H48.2253C45.4274 108.442 43.1592 106.174 43.1592 103.376V56.7025C43.1592 53.6454 44.5394 50.7518 46.9152 48.828L78.5742 23.1924C80.4235 21.6949 83.0658 21.6863 84.9249 23.1717L117.033 48.8272Z" fill="#2FC6F6"/>\n<g opacity="0.4" filter="url(#filter1_d_1_63)">\n<path d="M54.136 65.3792C54.136 62.5813 56.4042 60.3131 59.2022 60.3131H104.798C107.596 60.3131 109.864 62.5813 109.864 65.3792V102.531C109.864 105.329 107.596 107.597 104.798 107.597H59.2022C56.4042 107.597 54.136 105.329 54.136 102.531V65.3792Z" fill="#97E3FB"/>\n</g>\n<g filter="url(#filter2_d_1_63)">\n<path d="M54.136 65.3792C54.136 62.5813 56.4042 60.3131 59.2022 60.3131H104.798C107.596 60.3131 109.864 62.5813 109.864 65.3792V74.6673H54.136V65.3792Z" fill="#E5F9FF"/>\n</g>\n<path fill-rule="evenodd" clip-rule="evenodd" d="M104.798 61.1574H59.2023C56.8706 61.1574 54.9804 63.0476 54.9804 65.3792V73.8229H109.02V65.3792C109.02 63.0476 107.13 61.1574 104.798 61.1574ZM59.2023 60.313C56.4043 60.313 54.1361 62.5813 54.1361 65.3792V74.6672H109.864V65.3792C109.864 62.5813 107.596 60.313 104.798 60.313H59.2023Z" fill="#B8BFC9" fill-opacity="0.6"/>\n<path d="M54.9804 67.068H109.02V67.9123H54.9804V67.068Z" fill="#CDD1D8"/>\n<g filter="url(#filter3_d_1_63)">\n<path d="M87.2093 80.3631C87.2093 79.4305 87.9654 78.6744 88.898 78.6744H98.186C99.1187 78.6744 99.8748 79.4305 99.8748 80.3631V89.6512C99.8748 90.5838 99.1187 91.3399 98.186 91.3399H88.898C87.9654 91.3399 87.2093 90.5838 87.2093 89.6512V80.3631Z" fill="#2FC6F6"/>\n</g>\n<g filter="url(#filter4_d_1_63)">\n<path d="M87.9106 45.1145C87.9106 48.3788 85.2644 51.025 82 51.025C78.7357 51.025 76.0895 48.3788 76.0895 45.1145C76.0895 41.8502 78.7357 39.2039 82 39.2039C85.2644 39.2039 87.9106 41.8502 87.9106 45.1145Z" fill="#97E3FB"/>\n</g>\n<g filter="url(#filter5_d_1_63)">\n<path d="M94.9661 94.3882C94.9661 93.4556 95.7221 92.6995 96.6548 92.6995H105.943C106.875 92.6995 107.632 93.4556 107.632 94.3882V103.676C107.632 104.609 106.875 105.365 105.943 105.365H96.6548C95.7221 105.365 94.9661 104.609 94.9661 103.676V94.3882Z" fill="#2FC6F6"/>\n</g>\n<g filter="url(#filter6_d_1_63)">\n<path d="M79.7674 94.3882C79.7674 93.4556 80.5235 92.6995 81.4562 92.6995H90.7442C91.6768 92.6995 92.4329 93.4556 92.4329 94.3882V103.676C92.4329 104.609 91.6768 105.365 90.7442 105.365H81.4562C80.5235 105.365 79.7674 104.609 79.7674 103.676V94.3882Z" fill="#2FC6F6"/>\n</g>\n<defs>\n<filter id="filter0_d_1_63" x="30.0657" y="11.5455" width="103.868" height="112.565" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">\n<feFlood flood-opacity="0" result="BackgroundImageFix"/>\n<feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>\n<feOffset dy="2.57558"/>\n<feGaussianBlur stdDeviation="6.54673"/>\n<feComposite in2="hardAlpha" operator="out"/>\n<feColorMatrix type="matrix" values="0 0 0 0 0.392157 0 0 0 0 0.427451 0 0 0 0 0.482353 0 0 0 0.16 0"/>\n<feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_1_63"/>\n<feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_1_63" result="shape"/>\n</filter>\n<filter id="filter1_d_1_63" x="46.7187" y="56.6044" width="70.5627" height="62.1191" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">\n<feFlood flood-opacity="0" result="BackgroundImageFix"/>\n<feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>\n<feOffset dy="3.70866"/>\n<feGaussianBlur stdDeviation="3.70866"/>\n<feComposite in2="hardAlpha" operator="out"/>\n<feColorMatrix type="matrix" values="0 0 0 0 0.392157 0 0 0 0 0.427451 0 0 0 0 0.482353 0 0 0 0.1 0"/>\n<feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_1_63"/>\n<feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_1_63" result="shape"/>\n</filter>\n<filter id="filter2_d_1_63" x="51.136" y="58.3131" width="61.7281" height="20.3542" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">\n<feFlood flood-opacity="0" result="BackgroundImageFix"/>\n<feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>\n<feOffset dy="1"/>\n<feGaussianBlur stdDeviation="1.5"/>\n<feComposite in2="hardAlpha" operator="out"/>\n<feColorMatrix type="matrix" values="0 0 0 0 0.392157 0 0 0 0 0.427451 0 0 0 0 0.482353 0 0 0 0.1 0"/>\n<feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_1_63"/>\n<feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_1_63" result="shape"/>\n</filter>\n<filter id="filter3_d_1_63" x="79.792" y="74.9658" width="27.5001" height="27.5001" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">\n<feFlood flood-opacity="0" result="BackgroundImageFix"/>\n<feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>\n<feOffset dy="3.70866"/>\n<feGaussianBlur stdDeviation="3.70866"/>\n<feComposite in2="hardAlpha" operator="out"/>\n<feColorMatrix type="matrix" values="0 0 0 0 0.392157 0 0 0 0 0.427451 0 0 0 0 0.482353 0 0 0 0.1 0"/>\n<feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_1_63"/>\n<feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_1_63" result="shape"/>\n</filter>\n<filter id="filter4_d_1_63" x="68.6722" y="35.4953" width="26.6558" height="26.6558" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">\n<feFlood flood-opacity="0" result="BackgroundImageFix"/>\n<feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>\n<feOffset dy="3.70866"/>\n<feGaussianBlur stdDeviation="3.70866"/>\n<feComposite in2="hardAlpha" operator="out"/>\n<feColorMatrix type="matrix" values="0 0 0 0 0.392157 0 0 0 0 0.427451 0 0 0 0 0.482353 0 0 0 0.1 0"/>\n<feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_1_63"/>\n<feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_1_63" result="shape"/>\n</filter>\n<filter id="filter5_d_1_63" x="87.5487" y="88.9908" width="27.5001" height="27.5001" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">\n<feFlood flood-opacity="0" result="BackgroundImageFix"/>\n<feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>\n<feOffset dy="3.70866"/>\n<feGaussianBlur stdDeviation="3.70866"/>\n<feComposite in2="hardAlpha" operator="out"/>\n<feColorMatrix type="matrix" values="0 0 0 0 0.392157 0 0 0 0 0.427451 0 0 0 0 0.482353 0 0 0 0.1 0"/>\n<feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_1_63"/>\n<feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_1_63" result="shape"/>\n</filter>\n<filter id="filter6_d_1_63" x="72.3501" y="88.9908" width="27.5001" height="27.5001" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">\n<feFlood flood-opacity="0" result="BackgroundImageFix"/>\n<feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>\n<feOffset dy="3.70866"/>\n<feGaussianBlur stdDeviation="3.70866"/>\n<feComposite in2="hardAlpha" operator="out"/>\n<feColorMatrix type="matrix" values="0 0 0 0 0.392157 0 0 0 0 0.427451 0 0 0 0 0.482353 0 0 0 0.1 0"/>\n<feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_1_63"/>\n<feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_1_63" result="shape"/>\n</filter>\n</defs>\n</svg>\n',
		filter: '',
	};

	const styles = {
		emptyScreen: {
			container: {
				paddingHorizontal: 20,
			},
			image: {
				width: 163,
				height: 133,
			},
		},
	};

	BX.onViewLoaded(() => {
		layout.enableNavigationBarBorder(false);
		layout.showComponent(new CatalogStoreDocumentList({ layout }));

		AnalyticsLabel.send({
			event: 'showInventoryManagement',
		});
	});
})();
