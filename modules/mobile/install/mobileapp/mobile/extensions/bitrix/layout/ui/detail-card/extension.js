(() => {
	/**
	 * @class DetailCardComponent
	 */
	class DetailCardComponent extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			/** @type {Map<string,LazyLoadWrapper>} */
			this.lazyLoadWrapperMap = new Map();
			/** @type {BaseTab[]} */
			this.tabs = this.initializeTabs(props.tabs);
			this.activeTab = props.activeTab;

			this.menu = null;
			this.menuActionsProvider = null;

			this.itemActions = [];
			this.componentParams = null;

			this.layout = null;

			this.sliderRef = null;
			this.tabViewRef = null;

			/** @type {ToolPanel} */
			this.toolPanelRef = null;
			/** @type {BottomPadding} */
			this.bottomPaddingRef = null;
			/** @type {ActionsPanel} */
			this.actionsPanelRef = null;

			this.entityModel = null;
			this.isChanged = false;
			this.isEditing = false;

			this.handleTabClick = this.handleTabClick.bind(this);
			this.handleTabChange = this.handleTabChange.bind(this);
			this.handleTabEdit = this.handleTabEdit.bind(this);
			this.handleTabPreloadRequest = this.handleTabPreloadRequest.bind(this);
			this.handleEntityModelReady = this.handleEntityModelReady.bind(this);

			/** @type {Function} */
			this.checkToolbarPanelDebounced = Utils.debounce(this.checkToolbarPanel, 50, this);

			BX.addCustomEvent('DetailCard::onTabClick', this.handleTabClick);
			BX.addCustomEvent('DetailCard::onTabChange', this.handleTabChange);
			BX.addCustomEvent('DetailCard::onTabEdit', this.handleTabEdit);
			BX.addCustomEvent('DetailCard::onTabPreloadRequest', this.handleTabPreloadRequest);
			BX.addCustomEvent('DetailCard::onEntityModelReady', this.handleEntityModelReady);
		}

		componentDidMount()
		{
			this.preloadTab(this.activeTab);
			this.checkToolbarPanel();
		}

		checkToolbarPanel()
		{
			if (this.isToolPanelVisible())
			{
				this.showToolPanel();
			}
			else if (this.isActionsPanelVisible())
			{
				this.showActionsPanel();
			}
			else
			{
				this.hideToolbars();
			}
		}

		showToolPanel()
		{
			if (this.toolPanelRef)
			{
				this.toolPanelRef.show();
			}

			if (this.bottomPaddingRef)
			{
				this.bottomPaddingRef.show();
			}

			if (this.actionsPanelRef)
			{
				this.actionsPanelRef.hide();
			}
		}

		showActionsPanel()
		{
			if (this.actionsPanelRef)
			{
				this.actionsPanelRef.setModel(this.entityModel);
				this.actionsPanelRef.show();
			}

			if (this.bottomPaddingRef)
			{
				this.bottomPaddingRef.show();
			}

			if (this.toolPanelRef)
			{
				this.toolPanelRef.hide();
			}
		}

		hideToolbars()
		{
			if (this.toolPanelRef)
			{
				this.toolPanelRef.hide();
			}

			if (this.bottomPaddingRef)
			{
				this.bottomPaddingRef.hide();
			}

			if (this.actionsPanelRef)
			{
				this.actionsPanelRef.hide();
			}
		}

		initializeTabs(tabs)
		{
			return tabs.map((tabOptions) => TabFactory.create(tabOptions.type, tabOptions));
		}

		preloadTab(tabId)
		{
			const tabWrapper = this.lazyLoadWrapperMap.get(tabId);
			if (tabWrapper)
			{
				tabWrapper.fetch();
			}
		}

		showTab(tabId)
		{
			this.activeTab = tabId;

			this.preloadTab(tabId);
			Keyboard.dismiss();

			if (this.sliderRef)
			{
				const tabPosition = this.tabs.findIndex((tab) => tab.id === tabId);
				this.sliderRef.scrollToPage(tabPosition);
			}
		}

		renderTabHeader()
		{
			return TabView({
				style: {
					height: 44,
					backgroundColor: '#f5f7f8'
				},
				params: {
					styles: {
						tabTitle: {
							underlineColor: '#2899f0'
						}
					},
					items: this.tabs.map((tab) => {
						return {
							id: tab.id,
							title: tab.title,
							selectable: tab.selectable
						};
					})
				},
				onTabSelected: this.handleTabClick,
				ref: (ref) => this.tabViewRef = ref
			});
		}

		renderTabSlider()
		{
			return Slider(
				{
					bounces: false,
					style: {
						flex: 1
					},
					ref: (ref) => this.sliderRef = ref,
					onPageWillChange: this.handleSliderPageWillChange.bind(this),
					onPageChange: this.handleSliderPageChange.bind(this)
				},
				...this.getEachTabContent()
			);
		}

		renderBottomPadding()
		{
			return new BottomPadding({
				ref: (ref) => this.bottomPaddingRef = ref
			});
		}

		renderToolPanel()
		{
			return new ToolPanel({
				ref: (ref) => this.toolPanelRef = ref,
				onSave: this.handleSave.bind(this),
				onCancel: this.handleCancel.bind(this)
			});
		}

		renderActionsPanel()
		{
			return new ActionsPanel({
				ref: (ref) => this.actionsPanelRef = ref,
				actions: this.itemActions,
				onActionStart: this.handleOnActionStart.bind(this),
				onActionSuccess: this.handleActionSuccess.bind(this),
				onActionFailure: this.handleActionFailure.bind(this)
			});
		}

		handleOnActionStart(action)
		{
			NotifyManager.showLoadingIndicator(true);

			if (action.id)
			{
				AnalyticsLabel.send({
					event: action.id,
					entity: 'store-document',
					type: this.entityModel.DOC_TYPE
				});
			}
		}

		handleActionSuccess(action, data)
		{
			this.reload(data.load);
			this.emitDetailUpdate();

			if (action.id)
			{
				AnalyticsLabel.send({
					event: action.id + '-success',
					entity: 'store-document',
					type: this.entityModel.DOC_TYPE
				});
			}
		}

		handleActionFailure(action, {errors, showErrors})
		{
			NotifyManager.hideLoadingIndicator(false);
			NotifyManager.showErrors(showErrors ? errors : []);
		}

		isActionsPanelVisible()
		{
			return (
				this.hasEntityModel()
				&& !this.isNewEntity()
				&& !this.isToolPanelVisible()
				&& this.itemActions.filter((action) => action.onActiveCallback(this.entityModel)).length > 0
			);
		}

		isToolPanelVisible()
		{
			return this.isNewEntity() || this.isChanged || this.isEditing;
		}

		reload(tabsData)
		{
			this.entityModel = null;

			if (!Array.isArray(tabsData))
			{
				return Promise.resolve();
			}

			const results = [];
			for (const tabData of tabsData)
			{
				const tabWrapper = this.lazyLoadWrapperMap.get(tabData.id);
				if (tabWrapper)
				{
					results.push(tabWrapper.setResult(tabData.result));
				}
			}

			return (
				Promise.all(results)
					.then(() => NotifyManager.hideLoadingIndicator())
					.catch(() => NotifyManager.hideLoadingIndicator(false))
			);
		}

		hasEntityModel()
		{
			return this.entityModel !== null;
		}

		isNewEntity()
		{
			if (!this.hasEntityModel())
			{
				return false;
			}

			return (
				!BX.type.isNumber(Number(this.entityModel.ID))
				|| Number(this.entityModel.ID) <= 0
			);
		}

		getEachTabContent()
		{
			return this.tabs.map((tab) => {
				const tabId = tab.id;

				return new LazyLoadWrapper({
					ref: (ref) => this.lazyLoadWrapperMap.set(tab.id, ref),
					endpoint: `${this.props.endpoint}.load`,
					payload: {
						tabId: tab.id,
						parameters: {
							...tab.payload,
							...this.getComponentParams()
						}
					},
					renderContent: tab.render.bind(tab),
					onContentLoaded: this.handleTabContentLoaded.bind(this, tabId),
				});
			});
		}

		handleSliderPageWillChange(tabPosition, direction)
		{
			if (direction === 'right')
			{
				tabPosition++;
			}
			else
			{
				tabPosition--;
			}

			const tab = this.tabs[tabPosition];
			if (tab && tab.selectable)
			{
				this.preloadTab(tab.id);
			}
		}

		handleSliderPageChange(tabPosition)
		{
			const tab = this.tabs[tabPosition];
			if (tab && this.tabViewRef)
			{
				this.tabViewRef.setActiveItem(tab.id);
			}
		}

		handleTabClick(tab, changed)
		{
			if (changed)
			{
				this.showTab(tab.id);
			}
			else if (tab.selectable === false)
			{
				this.showTab(this.activeTab);
				this.openBackDropDemo(tab.id);
			}
			else
			{
				// ToDo this.scrollTop()?
			}
		}

		handleTabChange()
		{
			if (!this.isChanged)
			{
				this.isChanged = true;
				this.checkToolbarPanel();
			}
		}

		handleTabEdit(tab, isEditing)
		{
			if (this.isEditing !== isEditing)
			{
				this.isEditing = isEditing;

				if (this.isEditing)
				{
					this.checkToolbarPanel();
				}
				else
				{
					// delay toolbar render when just changed focus from one field to another
					this.checkToolbarPanelDebounced();
				}
			}
		}

		handleEntityModelReady(entityModel)
		{
			if (this.hasEntityModel())
			{
				return;
			}

			this.entityModel = entityModel;

			if (this.getComponentParams().hasOwnProperty('id') && this.isNewEntity())
			{
				this.layout.back();
				NotifyManager.showError(BX.message('DETAIL_CARD_RECORD_NOT_FOUND'));

				return;
			}

			if (this.isNewEntity())
			{
				this.isEditing = true;

				AnalyticsLabel.send({
					event: 'tryingToCreate',
					entity: 'store-document',
					type: this.entityModel.DOC_TYPE
				});
			}

			this.checkToolbarPanel();
		}

		handleSave()
		{
			return (
				this.validate()
					.then((result) => {
						result && NotifyManager.showLoadingIndicator(true);

						return result;
					})
					.then(this.getData.bind(this))
					.then(this.save.bind(this))
					.then(() => NotifyManager.hideLoadingIndicator())
					.catch(() => NotifyManager.hideLoadingIndicator(false))
			);
		}

		handleTabPreloadRequest(tabId)
		{
			this.preloadTab(tabId);
		}

		handleTabContentLoaded(tabId)
		{
			BX.postComponentEvent('DetailCard::onTabContentLoaded', [tabId]);
		}

		showConfirmWindow(callback)
		{
			navigator.notification.confirm(
				BX.message('DETAIL_CARD_DISCARD_CHANGES_CONFIRMATION'),
				callback,
				'',
				[
					BX.message('DETAIL_CARD_DISCARD_CHANGES_CONFIRMATION_OK'),
					BX.message('DETAIL_CARD_DISCARD_CHANGES_CONFIRMATION_CANCEL')
				]
			);
		}

		handleCancelNewDocument()
		{
			return new Promise((resolve) => {
				if (!this.isChanged)
				{
					this.layout.back();
					resolve();
				}
				else
				{
					const OK = 1;

					this.showConfirmWindow((index) => {
						if (index === OK)
						{
							this.layout.back();
						}

						resolve();
					});
				}
			});
		}

		handleCancelExistingDocument()
		{
			return new Promise((resolve) => {
				if (!this.isChanged)
				{
					Keyboard.dismiss();
					this.isEditing = false;
					this.checkToolbarPanel();
					resolve();
				}
				else
				{
					const OK = 1;

					this.showConfirmWindow((index) => {
						if (index === OK)
						{
							NotifyManager.showLoadingIndicator(true);

							Promise
								.all([
									new Promise((resolve) => {
										this.isChanged = false;
										this.isEditing = false;
										this.checkToolbarPanel();
										resolve();
									}),
									...[...this.lazyLoadWrapperMap].map((wrapper) => wrapper[1].refreshResult())
								])
								.then(() => {
									NotifyManager.hideLoadingIndicatorWithoutFallback();
									resolve();
								})
								.catch(() => NotifyManager.hideLoadingIndicator(false))
						}
						else
						{
							resolve();
						}
					});
				}
			});
		}

		handleCancel()
		{
			if (this.isNewEntity())
			{
				return this.handleCancelNewDocument();
			}

			return this.handleCancelExistingDocument();
		}

		validate()
		{
			return (
				Promise
					.all(this.tabs.map(tab => tab.validate()))
					.then((validationResults) => {
						let showTabWithErrors = null;
						const errors = [];

						validationResults.forEach((validationResult, index) => {
							if (showTabWithErrors !== null)
							{
								return;
							}

							const hasErrors = (
								validationResult === false
								|| (Array.isArray(validationResult) && validationResult.length)
							);

							if (!hasErrors)
							{
								return;
							}

							if (Array.isArray(validationResult))
							{
								errors.push(...validationResult);
							}

							if (showTabWithErrors === null)
							{
								showTabWithErrors = index;
							}
						});

						if (showTabWithErrors !== null)
						{
							this.showTab(this.tabs[showTabWithErrors].id);

							if (errors.length)
							{
								NotifyManager.showErrors(errors);
							}

							return false;
						}

						return true;
					})
			);
		}

		getData(isValid)
		{
			if (!isValid)
			{
				return false;
			}

			return (
				Promise
					.all(this.tabs.map((tab) => tab.getData()))
					.then((getDataResults) => {
						let payload = {};

						for (const getDataResult of getDataResults)
						{
							payload = {...payload, ...getDataResult};
						}

						return payload;
					})
			);
		}

		getSaveEndpoint()
		{
			let endpoint = this.props.endpoint;
			endpoint += '.';
			endpoint += this.isNewEntity() ? 'add' : 'update';

			return endpoint;
		}

		save(payload)
		{
			if (payload === false)
			{
				return false;
			}

			return BX.ajax.runAction(this.getSaveEndpoint(), {
				json: {
					parameters: this.getComponentParams(),
					data: payload
				},
				analyticsLabel: {
					event: 'save',
					entity: 'store-document',
					type: this.entityModel.DOC_TYPE
				}
			})
				.then((response) => {
					return this.processSave(response);
				})
				.catch((response) => {
					return this.processSave(response);
				});
		}

		processSave(response)
		{
			if (response.errors.length)
			{
				if (this.areSaveErrorsCritical(response.errors))
				{
					NotifyManager.showErrors(response.errors);
					return;
				}
				else
				{
					NotifyManager.showErrors(response.errors, BX.message('DETAIL_CARD_RECORD_SAVE_SUCCESS_WITH_ERRORS'));
				}
			}

			this.isChanged = false;
			this.isEditing = false;
			this.checkToolbarPanel();

			this.emitDetailUpdate();

			this.layout.setTitle({text: response.data.title}, true);
			if (response.data.hasOwnProperty('params'))
			{
				this.setComponentParams(response.data.params);
			}

			this.reload(response.data.load)
				.then(() => NotifyManager.hideLoadingIndicator())
				.catch(() => NotifyManager.hideLoadingIndicator(false))
			;
		}

		openBackDropDemo(tabId)
		{
			const tab = this.tabs.find((tab) => tab.id === tabId);
			if (tab && tab.desktopUrl)
			{
				qrauth.open({
					title: tab.title,
					redirectUrl: tab.desktopUrl
				})
			}
		}

		emitDetailUpdate()
		{
			BX.postComponentEvent('DetailCard::onUpdate');
		}

		render()
		{
			return View(
				{
					resizableByKeyboard: true,
					safeArea: {
						bottom: true,
						top: true,
						left: true,
						right: true
					}
				},
				this.renderTabHeader(),
				this.renderTabSlider(),
				this.renderBottomPadding(),
				this.renderToolPanel(),
				this.renderActionsPanel()
			);
		}

		renderTo(layout)
		{
			this.layout = layout;

			BX.onViewLoaded(() => {
				this.layout.enableNavigationBarBorder(false);

				this.layout.setBackButtonHandler(() => this.handleCancelNewDocument() && true);
				this.layout.setLeftButtons([
					{
						type: 'back',
						callback: () => this.handleCancelNewDocument()
					}
				]);
				this.layout.setRightButtons([
					{
						type: 'more',
						badgeCode: 'access_more',
						callback: this.showMenu.bind(this)
					}
				]);

				this.layout.showComponent(this);
			});

			return this;
		}

		getComponentParams()
		{
			return this.componentParams || BX.componentParameters.get('payload', {});
		}

		setComponentParams(componentParams)
		{
			this.componentParams = componentParams;

			return this;
		}

		showMenu()
		{
			if (!this.menu)
			{
				this.createMenu();
			}

			this.menu.show();
		}

		createMenu()
		{
			this.menu = new UI.Menu(() => {
				return this.menuActionsProvider(
					this.entityModel,
					{
						onActionStart: this.handleOnActionStart.bind(this),
						onActionSuccess: this.handleActionSuccess.bind(this),
						onActionFailure: this.handleActionFailure.bind(this),
					}
				);
			});
		}

		setMenuActionsProvider(provider)
		{
			this.menuActionsProvider = provider;

			return this;
		}

		setItemActions(actions)
		{
			this.itemActions = actions;

			return this;
		}

		areSaveErrorsCritical(errors)
		{
			for (let error of errors)
			{
				if (
					(
						!error.hasOwnProperty('customData')
						|| error.customData === null
					)
					|| (
						error.customData.hasOwnProperty('NON_CRITICAL')
						&& error.customData['NON_CRITICAL'] !== true
					)
				)
				{
					return true;
				}
			}

			return false;
		}

		static create(result)
		{
			return new DetailCardComponent(result);
		}
	}

	this.UI = this.UI || {};
	this.UI.DetailCardComponent = DetailCardComponent;
})();
