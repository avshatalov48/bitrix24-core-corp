(() => {
	const require = (ext) => jn.require(ext);

	const AppTheme = require('apptheme');
	const { CatalogStoreActivationWizard } = require('catalog/store/activation-wizard');
	const { StatefulList } = require('layout/ui/stateful-list');
	const { ListItemType, ListItemsFactory } = require('catalog/simple-list/items');
	const { EmptyScreen } = require('layout/ui/empty-screen');
	const { Loc } = require('loc');
	const { AnalyticsLabel } = require('analytics-label');
	const { DocumentType } = require('catalog/store/document-type');
	const { confirmDestructiveAction } = require('alert');
	const { ContextMenu } = require('layout/ui/context-menu');

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
				activeTab: this.documentTabs.length > 0 ? this.documentTabs[0].id : null,
			};
			this.layout = props.layout;
			this.statefulList = null;

			this.clickFloatingButtonMenuItemHandler = this.clickFloatingButtonMenuItemHandler.bind(this);

			this.onDetailCardCreate = this.onDetailCardCreateHandler.bind(this);
		}

		createStatefulList()
		{
			const actions = this.state.activeTab === 'shipment'
				? result.actions.realizationDocumentActions
				: result.actions.storeDocumentActions
			;

			return new StatefulList({
				testId: `${COMPONENT_ID}_${this.state.activeTab}`.toUpperCase(),
				actions: actions || {},
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
				itemType: ListItemType.STORE_DOCUMENT,
				itemFactory: ListItemsFactory,
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
				onFloatingButtonClick: this.floatingButtonClickHandler.bind(this),
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
				ref: (ref) => {
					this.statefulList = ref;
				},
			});
		}

		renderEmptyListComponent(customTitle = '', customDescription = '')
		{
			let title = Loc.getMessage(`M_CSDL_EMPTY_LIST_STORE_${this.state.activeTab}_TITLE`.toUpperCase());
			let description = Loc.getMessage(`M_CSDL_EMPTY_LIST_STORE_${this.state.activeTab}_DESCRIPTION`.toUpperCase());
			if (customTitle && typeof customTitle === 'string')
			{
				title = customTitle;
			}

			if (customDescription && typeof customDescription === 'string')
			{
				description = customDescription;
			}

			if (this.layout?.search?.text)
			{
				title = Loc.getMessage('M_CSDL_EMPTY_LIST_STORE_SEARCH_TITLE_MSGVER_1');
				description = Loc.getMessage('M_CSDL_EMPTY_LIST_STORE_SEARCH_DESCRIPTION_MSGVER_1');
			}

			return new EmptyScreen({
				title,
				description,
				styles: styles.emptyScreen.container,
				image: {
					style: styles.emptyScreen.image,
					svg: {
						uri: EmptyScreen.makeLibraryImagePath('inventory-management.svg', 'catalog'),
					},
				},
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
				...this.renderContent(),
			);
		}

		renderContent()
		{
			if (this.documentTabs.length === 0)
			{
				return [
					this.renderEmptyListComponent(
						Loc.getMessage('M_CSDL_NO_RIGHTS_TO_ANY_DOCUMENT_TITLE'),
						Loc.getMessage('M_CSDL_NO_RIGHTS_TO_ANY_DOCUMENT_DESCRIPTION'),
					),
				];
			}

			return [
				TabView({
					testId: `${COMPONENT_ID}_TAB`,
					style: {
						height: 50,
						backgroundColor: AppTheme.realColors.bgNavigation,
					},
					params: {
						styles: {
							tabTitle: {
								underlineColor: AppTheme.colors.accentExtraDarkblue,
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
								analyticsSection: 'inventory',
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
			];
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
				case DocumentType.SalesOrders:
					tabId = 'shipment';
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
				{
					entityId,
					docType: item.docType,
				},
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
				name: componentParams.docType === DocumentType.SalesOrders
					? 'catalog:catalog.realization.document.details'
					: 'catalog:catalog.store.document.details',
				componentParams: {
					payload: componentParams,
				},
				widgetParams: {
					titleParams,
					modal: true,
					leftButtons: [
						{
							// type: 'cross',
							svg: {
								content: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.722 6.79175L10.9495 10.5643L9.99907 11.5L9.06666 10.5643L5.29411 6.79175L3.96289 8.12297L10.008 14.1681L16.0532 8.12297L14.722 6.79175Z" fill="#A8ADB4"/></svg>',
							},
							isCloseButton: true,
						},
					],
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
				actions,
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
						svgIcon: floatingButtonSvgIcons[item.id] || null,
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
							docType: options.parent.data.docType,
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
			if (result.permissions.document[item.data.docType].catalog_store_document_modify !== true)
			{
				return false;
			}

			return !this.hasStatus('Y', item);
		}

		canConductDocumentHandler(actionItemId, itemId, item = {})
		{
			if (result.permissions.document[item.data.docType].catalog_store_document_conduct !== true)
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
							docType: options.parent.data.docType,
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
			if (result.permissions.document[item.data.docType].catalog_store_document_cancel !== true)
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
				errors,
				showErrors,
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

		deleteDocumentHandler(actionItemId, itemId, options)
		{
			return new Promise((resolve, reject) => {
				confirmDestructiveAction({
					title: '',
					description: Loc.getMessage('M_CSDL_DOCUMENT_DELETE_CONFIRMATION'),
					onCancel: () => resolve({}),
					onDestruct: () => {
						BX.ajax.runAction(
							'catalogmobile.StoreDocument.delete',
							{
								data: {
									id: itemId,
									docType: options.parent.data.docType,
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
				});
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
		W: '<svg width="18" height="22" viewBox="0 0 18 22" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M2.72093 0.326172C1.34022 0.326172 0.220932 1.44546 0.220932 2.82617V19.1744C0.220932 20.5552 1.34022 21.6744 2.72093 21.6744H15.279C16.6598 21.6744 17.779 20.5552 17.779 19.1744V7.51772C17.779 6.84547 17.5083 6.20154 17.0279 5.73126L12.2355 1.03971C11.7683 0.582321 11.1405 0.326172 10.4867 0.326172H2.72093ZM2.72093 19.1744V2.82617H10.4867L15.279 7.51772V19.1744H2.72093ZM5.31382 10.2402C5.04655 10.2402 4.9127 9.91709 5.10169 9.7281L8.05572 6.77407C8.44624 6.38354 9.07941 6.38354 9.46993 6.77407L12.424 9.7281C12.613 9.91709 12.4791 10.2402 12.2118 10.2402H9.78559L9.78559 15.2077C9.78559 15.4839 9.56173 15.7077 9.28559 15.7077H8.07876C7.80262 15.7077 7.57876 15.4839 7.57876 15.2077L7.57876 10.2402L5.31382 10.2402Z" fill="#8FBC00"/></svg>',
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
