(() => {
	const require = (ext) => jn.require(ext);

	const { CalendarEventListView } = require('calendar/event-list-view');
	const { EventAjax } = require('calendar/ajax');
	const { CalendarLoader } = require('calendar/layout/ui/loader');
	const { Search } = require('calendar/event-list-view/search');
	const { Color } = require('tokens');

	/**
	 * @class CalendarEventList
	 */
	class CalendarEventList extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.layout = props.layout;

			this.state = {
				loading: true,
				sharingInfo: {},
				ahaMoments: {},
				settings: {},
				sectionInfo: [],
				locationInfo: [],
				filterPresets: {},
				syncInfo: {},
			};

			this.searchRef = null;
		}

		componentDidMount()
		{
			super.componentDidMount();
			this.loadData();
		}

		/**
		 * @private
		 */
		loadData()
		{
			return new Promise((resolve) => {
				EventAjax.loadMain().then((response) => {
					this.setBaseInfo(response.data);
					resolve(response);
				});
			})
		}

		/**
		 * @private
		 * @param {{sharingInfo, ahaMoments, settings, sectionInfo, locationInfo, filterPresets}} data
		 */
		setBaseInfo(data)
		{
			if (data && data.sharingInfo)
			{
				const sharingInfo = BX.prop.getObject(data, 'sharingInfo', {});
				const ahaMoments = BX.prop.getObject(data, 'ahaMoments', {});
				const settings = BX.prop.getObject(data, 'settings', {});
				const sectionInfo = BX.prop.getArray(data, 'sectionInfo', []);
				const locationInfo = BX.prop.getArray(data, 'locationInfo', []);
				const filterPresets = BX.prop.getObject(data, 'filterPresets', {});
				const syncInfo = BX.prop.getObject(data, 'syncInfo', {});

				this.setState({
					loading: false,
					sharingInfo,
					ahaMoments,
					settings,
					sectionInfo,
					locationInfo,
					filterPresets,
					syncInfo,
				});
			}
		}

		render()
		{
			return View(
				{
					style: {
						backgroundColor: Color.bgContentPrimary.toHex(),
					},
				},
				this.state.loading ? CalendarLoader() : this.renderContent(),
				!this.state.loading && this.renderSearch(),
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
				sharingInfo: this.state.sharingInfo,
				ahaMoments: this.state.ahaMoments,
				settings: this.state.settings,
				sectionInfo: this.state.sectionInfo,
				locationInfo: this.state.locationInfo,
				filterPresets: this.state.filterPresets,
				syncInfo: this.state.syncInfo,
			});
		}

		renderSearch()
		{
			return new Search({
				layout: this.layout,
				presets: this.state.filterPresets,
				ref: (ref) => {
					this.searchRef = ref;
				},
			});
		}
	}

	BX.onViewLoaded(() => {
		layout.enableNavigationBarBorder(false);
		layout.showComponent(new CalendarEventList({ layout }));
	});
})();
