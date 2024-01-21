/**
 * @module lists/entity-detail
 */
jn.define('lists/entity-detail', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { PureComponent } = require('layout/pure-component');
	const { EventEmitter } = require('event-emitter');
	const { Loc } = require('loc');
	const { DetailTab } = require('lists/entity-detail/detail-tab');
	const { FocusManager } = require('layout/ui/fields/focus-manager');
	const { NotifyManager } = require('notify-manager');

	const EntityDetailTabs = {
		DETAIL_TAB: 'detail',
	};

	class EntityDetail extends PureComponent
	{
		static open(layout = PageManager, props = {})
		{
			layout.openWidget(
				'layout',
				{
					modal: true,
					onReady: (layoutWidget) => {
						const componentProps = props;
						componentProps.layout = layoutWidget;
						layoutWidget.showComponent(new EntityDetail(componentProps));
					},
				},
				layout,
			);
		}

		constructor(props) {
			super(props);

			this.state.iBlock = {
				id: props.iBlockId,
				typeId: props.iBlockTypeId,
				elementName: Loc.getMessage('LISTSMOBILE_EXT_ENTITY_DETAIL_DETAIL_TAB_DEFAULT_TITLE'),
			};
			this.state.entityId = props.entityId || 0;

			// eslint-disable-next-line no-undef
			this.uid = props.uid || Random.getString();
			this.iBlockSectionId = props.iBlockSectionId || 0;
			this.socNetGroupId = props.socNetGroupId || 0;
			this.activeTabId = props.activeTabId || EntityDetailTabs.DETAIL_TAB;

			this.isIBlockLoaded = false;

			this.isLoading = true;
			this.isSaving = false;

			this.tabViewRef = null;
			this.sliderRef = null;
			this.tabRefMap = new Map();

			this.eventMap = new Map();

			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			BX.onViewLoaded(() => {
				this.loadIBlock();
			});
		}

		get layout()
		{
			return this.props.layout || layout || {};
		}

		get styles()
		{
			return {
				component: {
					backgroundColor: AppTheme.colors.bgSecondary,
				},
				tabView: {
					height: 44,
					marginBottom: 10,
				},
				tab: {
					tabTitle: {
						underlineColor: AppTheme.colors.accentMainPrimary,
					},
				},
			};
		}

		componentDidMount()
		{
			super.componentDidMount();

			this.eventMap.set('DetailTab::onAfterEntityLoad', this.handleDetailCardLoaded.bind(this));
			this.customEventEmitter
				.on('DetailTab::onAfterEntityLoad', this.eventMap.get('DetailTab::onAfterEntityLoad'))
			;

			this.setWidgetTitle();
			this.setTopButtons();
		}

		handleDetailCardLoaded()
		{
			this.setIsLoading(false);
		}

		setWidgetTitle()
		{
			this.layout.setTitle({ text: this.state.iBlock.name || '' });
		}

		setTopButtons()
		{
			this.layout.setLeftButtons([
				{
					svg: {
						content: `
							<svg
								width="20"
								height="20"
								viewBox="0 0 20 20"
								fill="none"
								xmlns="http://www.w3.org/2000/svg"
							>
								<path
									fill-rule="evenodd"
									clip-rule="evenodd"
									d="M14.722 6.79175L10.9495 10.5643L9.99907 11.5L9.06666 10.5643L5.29411 6.79175L3.96289 8.12297L10.008 14.1681L16.0532 8.12297L14.722 6.79175Z"
									fill="#A8ADB4"
								/>
							</svg>
						`,
					},
					callback: () => this.layout.close(),
				},
			]);

			this.eventMap.set('right-button-save', this.handleSaveButtonClick.bind(this));
			this.setRightButtons();
		}

		handleSaveButtonClick()
		{
			if (this.isLoading || this.isSaving)
			{
				return;
			}

			this.setIsSaving(true);
			FocusManager.blurFocusedFieldIfHas()
				.then(() => this.validate())
				.then(() => this.getData())
				.then((results) => {
					let payload = {};
					for (const data of results)
					{
						payload = { ...payload, ...data };
					}

					return payload;
				})
				.then((payload) => this.runSave(payload))
				.then((response) => this.processSave(response))
				.catch((data) => {
					if (Array.isArray(data.errors))
					{
						NotifyManager.showErrors(data.errors);
					}
				})
				.finally(() => {
					this.setIsSaving(false);
				})
			;
		}

		validate()
		{
			return (Promise.all(this.tabRefs.map((tab) => (tab.validate ? tab.validate() : {}))));
		}

		getData()
		{
			return Promise.all(this.tabRefs.map((tab) => (tab.getData ? tab.getData() : {})));
		}

		runSave(payload = {})
		{
			const fields = payload;

			fields.IBLOCK_ID = this.state.iBlock.id;
			fields.IBLOCK_TYPE_ID = this.state.iBlock.typeId;
			fields.SOCNET_GROUP_ID = this.socNetGroupId;

			return (
				BX.ajax.runAction(
					'listsmobile.EntityDetails.updateEntity',
					{
						data: {
							id: this.state.entityId,
							fields,
						},
					},
				)
			);
		}

		processSave(response)
		{
			this.isChanged = false;
			this.isEditing = false;
			this.isSaving = false;

			this.isLoading = false;
			this.setTopButtons();

			this.tabRefs.forEach((tab) => (tab.setResult ? tab.setResult(response) : {}));
			if (response.data.id)
			{
				this.setState({ entityId: response.data.id });
			}
		}

		componentWillUnmount()
		{
			super.componentWillUnmount();

			this.customEventEmitter
				.off('DetailTab::onAfterEntityLoad', this.eventMap.get('DetailTab::onAfterEntityLoad'))
			;
		}

		setIsLoading(isLoading = true)
		{
			this.isLoading = isLoading;
			this.setRightButtons();
		}

		setIsSaving(isSaving = true)
		{
			this.isSaving = isSaving;
			this.setRightButtons();
		}

		setRightButtons()
		{
			let buttonTitle = (this.state.entityId <= 0
				? Loc.getMessage('LISTSMOBILE_EXT_ENTITY_DETAIL_CREATE_BUTTON_TITLE')
				: Loc.getMessage('LISTSMOBILE_EXT_ENTITY_DETAIL_CHANGE_BUTTON_TITLE')
			);

			if (this.isSaving)
			{
				buttonTitle = (this.state.entityId <= 0
					? Loc.getMessage('LISTSMOBILE_EXT_ENTITY_DETAIL_CREATE_BUTTON_TITLE_PROCESSING')
					: Loc.getMessage('LISTSMOBILE_EXT_ENTITY_DETAIL_CHANGE_BUTTON_TITLE_PROCESSING')
				);
			}

			this.layout.setRightButtons([
				{
					name: buttonTitle,
					type: 'text',
					color: (
						(this.isLoading || this.isSaving)
							? AppTheme.colors.accentMainPrimary
							: AppTheme.colors.accentMainLinks
					),
					callback: this.eventMap.get('right-button-save'),
				},
			]);
		}

		loadIBlock()
		{
			if (this.isIBlockLoaded)
			{
				return;
			}

			this.setIsLoading(true);

			BX.ajax.runAction('listsmobile.EntityDetails.loadIBlock', {
				data: {
					iBlockId: this.props.iBlockId,
					iBlockTypeId: this.props.iBlockTypeId,
					socNetGroupId: this.props.socNetGroupId,
				},
			})
				.then((response) => {
					const iBlock = response.data.iBlock;
					iBlock.elementName = iBlock.elementName || this.state.iBlock.elementName;
					this.isIBlockLoaded = true;

					this.setState({ iBlock });
					this.setWidgetTitle();

					this.setIsLoading(false);
				})
				.catch(() => {})
			;
		}

		// region render

		render()
		{
			return View(
				{
					resizableByKeyboard: true,
					style: this.styles.component,
				},
				this.renderTabHeader(),
				this.renderTabSlider(),
			);
		}

		renderTabHeader()
		{
			return TabView({
				style: this.styles.tabView,
				params: {
					styles: this.styles.tab,
					items: this.tabItems,
				},
				onTabSelected: this.handleTabSelected.bind(this),
				ref: (ref) => {
					if (ref)
					{
						this.tabViewRef = ref;
					}
				},
			});
		}

		handleTabSelected(tab, changed)
		{
			if (changed)
			{
				this.activeTabId = tab.id;
				if (this.sliderRef)
				{
					const tabPosition = this.tabItems.findIndex((tabItem) => tab.id === tabItem.id);
					if (tabPosition >= 0)
					{
						this.sliderRef.scrollToPage(tabPosition, true);
					}
				}
			}
		}

		renderTabSlider()
		{
			if (this.isIBlockLoaded === true)
			{
				return Slider(
					{
						bounces: true,
						style: { flex: 1 },
						initPage: 0,
						ref: (ref) => {
							if (ref)
							{
								this.sliderRef = ref;
							}
						},
						onPageChange: this.handleSliderPageChange.bind(this),
						onPageWillChange: this.handleSliderPageWillChange.bind(this),
					},
					...this.tabItems.map((tab) => {
						return this.renderTab(tab.id);
					}),
				);
			}

			return null;
		}

		handleSliderPageWillChange(tabPosition, direction)
		{
			const selectedTab = this.tabItems[direction === 'right' ? tabPosition + 1 : tabPosition - 1];
			if (selectedTab && selectedTab.selectable && this.tabRefMap.has(selectedTab.id))
			{
				this.tabRefMap.get(selectedTab.id).load();
			}
		}

		handleSliderPageChange(tabPosition)
		{
			const selectedTab = this.tabItems[tabPosition];
			if (selectedTab && selectedTab.selectable)
			{
				const currentTab = this.tabViewRef.getCurrentItem();
				if (!currentTab || currentTab.id !== selectedTab.id)
				{
					this.tabViewRef.setActiveItem(selectedTab.id);
				}
			}
		}

		renderTab(tabId)
		{
			if (tabId === EntityDetailTabs.DETAIL_TAB)
			{
				if (!this.tabRefMap.has(tabId))
				{
					const detailTab = DetailTab.create({
						uid: this.uid,
						iBlock: this.state.iBlock,
						entityId: this.state.entityId,
						sectionId: this.iBlockSectionId,
						socNetGroupId: this.socNetGroupId,

						layout: this.layout,
					});
					detailTab.load();

					this.tabRefMap.set(tabId, detailTab);
				}

				return this.tabRefMap.get(tabId);
			}

			// eslint-disable-next-line no-undef
			return View({}, Text({ text: Random.getString() }));
		}

		// endregion

		get tabRefs()
		{
			return (
				this.tabItems
					.map(({ id }) => this.tabRefMap.get(id))
					.filter(Boolean)
			);
		}

		get tabItems()
		{
			return [
				{
					id: EntityDetailTabs.DETAIL_TAB,
					title: this.state.iBlock.elementName,
					selectable: true,
					active: true,
				},
				{
					id: 'bp',
					title: Loc.getMessage('LISTSMOBILE_EXT_ENTITY_DETAIL_BP_TAB_DEFAULT_TITLE'),
					selectable: true, // todo
				},
			];
		}
	}

	module.exports = { EntityDetail, EntityDetailTabs };
});
