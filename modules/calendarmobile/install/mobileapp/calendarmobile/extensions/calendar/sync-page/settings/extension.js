/**
 * @module calendar/sync-page/settings
 */
jn.define('calendar/sync-page/settings', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { SyncAjax } = require('calendar/ajax');
	const { BottomSheet } = require('bottom-sheet');
	const { LoadingScreenComponent } = require('layout/ui/loading-screen');
	const { Loc } = require('loc');
	const { TimeAgoFormat } = require('layout/ui/friendly-date/time-ago-format');
	const { Moment } = require('utils/date/moment');
	const { SyncSettingsSection } = require('calendar/sync-page/settings/section');
	const { SyncSettingsMenu } = require('calendar/sync-page/settings/menu');
	const { Color } = require('tokens');

	/**
	 * @class SyncSettings
	 */
	class SyncSettings extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				loading: true,
			};

			this.sections = [];
			this.layoutWidget = null;

			this.onShowMoreMenu = this.showMoreMenu.bind(this);

			void this.loadData();
		}

		show(parentWidget = PageManager)
		{
			const bottomSheet = new BottomSheet({ component: this });

			// eslint-disable-next-line promise/catch-or-return
			bottomSheet.setParentWidget(parentWidget)
				.setBackgroundColor(Color.bgNavigation.toHex())
				.setMediumPositionPercent(70)
				.enableResizeContent()
				.disableContentSwipe()
				.open()
				.then((widget) => {
					this.layoutWidget = widget;
				});
		}

		loadData()
		{
			return new Promise((resolve) => {
				// eslint-disable-next-line promise/catch-or-return
				SyncAjax.getSectionsForProvider(this.props.connectionId, this.props.type).then((response) => {
					if (response.data && response.data.sections)
					{
						this.sections = BX.prop.getArray(response.data, 'sections', []);

						this.setState({ loading: false });
					}

					resolve(response);
				});
			});
		}

		render()
		{
			return View(
				{
					style: {
						flex: 1,
					},
					testId: 'sync_page_settings_container',
				},
				this.renderHeader(),
				!this.state.loading && this.renderSyncInfo(),
				!this.state.loading && this.renderDivider(),
				this.state.loading ? this.renderLoader() : this.renderContent(),
			);
		}

		renderHeader()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						paddingVertical: 15,
						paddingHorizontal: 20,
					},
					clickable: true,
					onClick: () => this.layoutWidget.close(),
					testId: 'sync_page_settings_header',
				},
				this.renderLeftArrow(),
				this.renderTitle(),
			);
		}

		renderLeftArrow()
		{
			return View(
				{
					style: {
						width: 30,
					},
				},
				Image(
					{
						svg: {
							content: icons.leftArrow,
						},
						style: {
							width: 9,
							height: 16,
						},
					},
				),
			);
		}

		renderTitle()
		{
			return Text(
				{
					style: {
						fontSize: 19,
						fontWeight: '400',
					},
					text: Loc.getMessage('M_CALENDAR_SYNC_SETTINGS_TITLE'),
				},
			);
		}

		renderContent()
		{
			return ScrollView(
				{
					style: {
						backgroundColor: Color.bgContentPrimary.toHex(),
						flex: 1,
					},
				},
				View(
					{
						style: {
							backgroundColor: Color.bgContentPrimary.toHex(),
							paddingTop: 12,
							paddingBottom: 20,
							marginBottom: 20,
							borderBottomLeftRadius: 12,
							borderBottomRightRadius: 12,
						},
					},
					this.renderDescription(),
					this.renderSections(),
				),
			);
		}

		renderSyncInfo()
		{
			return View(
				{
					style: {
						backgroundColor: Color.bgContentPrimary.toHex(),
						flexDirection: 'row',
						justifyContent: 'space-between',
						paddingVertical: 12,
						paddingHorizontal: 30,
					},
					testId: 'sync_page_settings_sync_info',
				},
				this.renderIcon(),
				View(
					{
						style: {
							flexDirection: 'column',
							justifyContent: 'center',
							flexGrow: 1,
							marginLeft: 15,
						},
					},
					Text(
						{
							text: this.props.title,
							style: {
								fontSize: 18,
							},
						},
					),
					this.renderSyncDate(),
				),
				this.renderMoreButton(),
			);
		}

		renderDivider()
		{
			return View(
				{
					style: {
						borderBottomWidth: 1,
						borderBottomColor: AppTheme.colors.base6,
					},
				},
			);
		}

		renderDescription()
		{
			return View(
				{
					style: {
						paddingHorizontal: 30,
						marginTop: 30,
						marginBottom: 20,
					},
				},
				Text(
					{
						text: Loc.getMessage('M_CALENDAR_SYNC_SETTING_INFO'),
						style: {
							fontSize: 16,
							color: AppTheme.colors.base3,
						},
					},
				),
			);
		}

		renderSections()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						paddingHorizontal: 30,
					},
					testId: 'sync_page_settings_sections_container',
				},
				...this.sections.map((section, index) => {
					return new SyncSettingsSection({
						section,
						index,
						onChange: (sectionId, status) => this.changeSectionStatus(sectionId, status),
					});
				}),
			);
		}

		renderIcon()
		{
			return View(
				{
					style: {
						width: 66,
						height: 66,
						alignItems: 'center',
						justifyContent: 'center',
						borderRadius: 100,
						backgroundColor: AppTheme.colors.bgNavigation,
						backgroundRepeat: 'no-repeat',
						backgroundPosition: 'center',
						backgroundResizeMode: 'cover',
					},
				},
				Image(
					{
						style: {
							width: this.props.icon.width,
							height: this.props.icon.height,
						},
						svg: {
							content: this.props.icon.svg,
						},
						tintColor: this.props.icon.tintColor,
					},
				),
			);
		}

		renderMoreButton()
		{
			return View(
				{
					style: {
						alignItems: 'center',
						justifyContent: 'center',
						flexGrow: 1,
					},
					onClick: this.onShowMoreMenu,
				},
				Image(
					{
						svg: {
							content: icons.moreMenu,
						},
						style: {
							height: 24,
							width: 25,
						},
					},
				),
			);
		}

		renderSyncDate()
		{
			let offset = this.props.syncOffset;

			if (offset === 0)
			{
				offset = 1;
			}

			if (!offset)
			{
				return null;
			}

			const timestamp = Date.now() - offset * 1000;
			const moment = Moment.createFromTimestamp(timestamp / 1000);
			const time = new TimeAgoFormat().format(moment);

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						marginTop: 3,
					},
				},
				Text(
					{
						text: Loc.getMessage('M_CALENDAR_SYNC_SETTING_UPDATED'),
						style: {
							color: AppTheme.colors.base3,
						},
					},
				),
				Text(
					{
						text: time,
						style: {
							marginLeft: 3,
							color: AppTheme.colors.base3,
						},
					},
				),
			);
		}

		renderLoader()
		{
			return View(
				{
					style: {
						height: device.screen.height - 90,
						width: device.screen.width,
						alignItems: 'center',
						justifyContent: 'center',
					},
				},
				new LoadingScreenComponent({
					backgroundColor: Color.bgContentPrimary.toHex(),
				}),
			);
		}

		changeSectionStatus(sectionId, status)
		{
			// eslint-disable-next-line promise/catch-or-return
			SyncAjax.changeSectionStatus(sectionId, status).then(() => {
				// eslint-disable-next-line no-undef
				include('InAppNotifier');
				// eslint-disable-next-line no-undef
				InAppNotifier.showNotification({
					title: Loc.getMessage('M_CALENDAR_SYNC_SETTINGS_CALENDAR_LIST_UPDATED'),
					backgroundColor: '#E6000000',
				});

				BX.postComponentEvent('Calendar.SyncPage::onSetSectionStatus', [{
					sectionId,
					status,
				}]);
			});
		}

		showMoreMenu()
		{
			const menu = new SyncSettingsMenu({
				layoutWidget: this.layoutWidget,
				onChooseDisable: () => this.deactivateConnection(),
			});

			menu.show();
		}

		deactivateConnection()
		{
			// eslint-disable-next-line promise/catch-or-return
			SyncAjax.deactivateConnection(this.props.connectionId).then((response) => {
				if (response.errors && response.errors.length > 0)
				{
					console.error('error deactivate');
				}
				else
				{
					this.layoutWidget.close();
					this.props.customEventEmitter.emit('Calendar.Sync::onConnectionDisabled', [{ type: this.props.type }]);
				}
			});
		}
	}

	const icons = {
		moreMenu: '<svg width="24" height="25" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 14.5C7.10457 14.5 8 13.6046 8 12.5C8 11.3954 7.10457 10.5 6 10.5C4.89543 10.5 4 11.3954 4 12.5C4 13.6046 4.89543 14.5 6 14.5Z" fill="#A8ADB4"/><path d="M12 14.5C13.1046 14.5 14 13.6046 14 12.5C14 11.3954 13.1046 10.5 12 10.5C10.8954 10.5 10 11.3954 10 12.5C10 13.6046 10.8954 14.5 12 14.5Z" fill="#A8ADB4"/><path d="M20 12.5C20 13.6046 19.1046 14.5 18 14.5C16.8954 14.5 16 13.6046 16 12.5C16 11.3954 16.8954 10.5 18 10.5C19.1046 10.5 20 11.3954 20 12.5Z" fill="#A8ADB4"/></svg>',
		leftArrow: '<svg width="9" height="16" viewBox="0 0 9 16" fill="none" xmlns="http://www.w3.org/2000/svg"> <path fill-rule="evenodd" clip-rule="evenodd" d="M4.3341 9.13027L8.86115 13.6573L7.26368 15.2547L0.00952148 8.0005L7.26368 0.746338L8.86115 2.34381L4.3341 6.87086L3.20009 8.0001L4.3341 9.13027Z" fill="#828B95"/></svg>',
	};

	module.exports = { SyncSettings };
});
