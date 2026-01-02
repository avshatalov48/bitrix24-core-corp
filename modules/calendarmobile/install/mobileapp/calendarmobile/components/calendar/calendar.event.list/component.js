(() => {
	const require = (ext) => jn.require(ext);

	const { Color, Indent } = require('tokens');
	const { LoadingScreenComponent } = require('layout/ui/loading-screen');
	const { H2 } = require('ui-system/typography/heading');

	const { CalendarEventListView } = require('calendar/event-list-view');
	const { EventAjax } = require('calendar/ajax');
	const { CalendarType, ViewMode } = require('calendar/enums');

	/**
	 * @class CalendarEventList
	 */
	class CalendarEventList extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.layout = props.layout;
			this.ownerId = props.ownerId;
			this.calType = props.calType;
			this.viewMode = props.viewMode;

			this.state = {
				loading: true,
				error: false,
				errorMessage: null,
				readOnly: false,
				user: {},
				counters: {},
				sharingInfo: {},
				ahaMoments: {},
				settings: {},
				sectionInfo: [],
				additionalSectionInfo: {},
				locationInfo: [],
				categoryInfo: [],
				filterPresets: {},
				syncInfo: {},
				collabInfo: [],
				collabSectionInfo: [],
			};
		}

		componentDidMount()
		{
			super.componentDidMount();
			void this.loadData();
		}

		/**
		 * @private
		 */
		async loadData()
		{
			const { ownerId, calType } = this.props;

			return new Promise((resolve, reject) => {
				// eslint-disable-next-line promise/catch-or-return
				EventAjax.loadMain({ ownerId, calType }).then((response) => {
					if (response.status === 'error')
					{
						const errorMessage = response.errors[0].message;

						this.setState({
							loading: false,
							error: true,
							errorMessage,
						});
					}
					else
					{
						this.setBaseInfo(response.data);
					}

					resolve(response);
				});
			});
		}

		/**
		 * @private
		 * @param {{sharingInfo, ahaMoments, settings, sectionInfo, locationInfo, filterPresets, collabInfo}} data
		 */
		setBaseInfo(data)
		{
			if (data && data.sectionInfo)
			{
				const readOnly = BX.prop.getBoolean(data, 'readOnly', false);
				const user = BX.prop.getArray(data, 'user', []);
				const counters = BX.prop.getObject(data, 'counters', {});
				const sharingInfo = BX.prop.getObject(data, 'sharingInfo', {});
				const ahaMoments = BX.prop.getObject(data, 'ahaMoments', {});
				const settings = BX.prop.getObject(data, 'settings', {});
				const sectionInfo = BX.prop.getArray(data, 'sectionInfo', []);
				const additionalSectionInfo = BX.prop.getObject(data, 'additionalSectionInfo', {});
				const locationInfo = BX.prop.getArray(data, 'locationInfo', []);
				const categoryInfo = BX.prop.getArray(data, 'categoryInfo', {});
				const filterPresets = BX.prop.getObject(data, 'filterPresets', {});
				const syncInfo = BX.prop.getObject(data, 'syncInfo', {});
				const collabInfo = BX.prop.getArray(data, 'collabInfo', []);
				const collabSectionInfo = BX.prop.getArray(data, 'collabSectionInfo', {});

				this.setState({
					loading: false,
					readOnly,
					user,
					counters,
					sharingInfo,
					ahaMoments,
					settings,
					sectionInfo,
					additionalSectionInfo,
					locationInfo,
					categoryInfo,
					filterPresets,
					syncInfo,
					collabInfo,
					collabSectionInfo,
				});
			}
		}

		render()
		{
			return View(
				{
					style: {
						flex: 1,
						backgroundColor: Color.bgContentPrimary.toHex(),
					},
				},
				this.state.error && this.renderErrorContent(),
				!this.state.error && this.renderBaseContent(),
			);
		}

		renderErrorContent()
		{
			return View(
				{
					style: {
						flex: 1,
						alignItems: 'center',
						justifyContent: 'center',
						paddingHorizontal: Indent.XL3.toNumber(),
					},
				},
				H2({
					text: this.state.errorMessage,
					color: Color.base2,
					style: {
						textAlign: 'center',
					},
				}),
			);
		}

		renderBaseContent()
		{
			return View(
				{
					style: {
						flex: 1,
					},
				},
				this.state.loading && this.renderLoader(),
				!this.state.loading && this.renderContent(),
			);
		}

		/**
		 * @private
		 * @returns {CalendarEventListView}
		 */
		renderContent()
		{
			return new CalendarEventListView({
				layout: this.layout,
				ownerId: this.ownerId,
				calType: this.calType,
				viewMode: this.viewMode,
				...this.state,
			});
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
	}

	BX.onViewLoaded(() => {
		const ownerId = BX.componentParameters.get('OWNER_ID', env.userId);
		const calType = BX.componentParameters.get('CAL_TYPE', CalendarType.USER);
		const viewMode = BX.componentParameters.get('VIEW_MODE', ViewMode.BASE);

		layout.enableNavigationBarBorder(false);
		layout.showComponent(new CalendarEventList({
			ownerId,
			calType,
			viewMode,
			layout,
		}));
	});
})();
