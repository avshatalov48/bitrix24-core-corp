(() => {
	const require = (ext) => jn.require(ext);

	const AppTheme = require('apptheme');
	const { SyncPage } = require('calendar/sync-page');
	const { Loc } = require('loc');
	const { SyncAjax } = require('calendar/ajax');
	const { CalendarLoader } = require('calendar/layout/ui/loader');
	const { Color } = require('tokens');

	class CalendarSyncDetail extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.layout = props.layout;
			let syncInfo;

			if (props.syncInfo)
			{
				syncInfo = Object.keys(props.syncInfo).sort().reduce((object, key) => {
					object[key] = props.syncInfo[key];
					return object;
				}, {});
			}

			this.state = {
				loading: !syncInfo,
				syncInfo,
			};

			if (this.state.loading)
			{
				this.loadData();
			}
		}

		componentDidMount()
		{
			this.layout.setTitle({
				text: Loc.getMessage('M_CALENDAR_SYNC_TITLE'),
				useLargeTitleMode: true,
			});
		}

		loadData()
		{
			return new Promise((resolve) => {
				// eslint-disable-next-line promise/catch-or-return
				SyncAjax.getSyncInfo().then((response) => {
					if (response.data && response.data.syncInfo)
					{
						const syncInfo = BX.prop.getObject(response.data, 'syncInfo', {});

						this.setState({
							loading: false,
							syncInfo,
						});
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
						backgroundColor: Color.bgNavigation.toHex(),
					},
				},
				this.state.loading ? CalendarLoader() : this.renderContent(),
			);
		}

		renderContent()
		{
			return new SyncPage({
				syncInfo: this.state.syncInfo,
			});
		}
	}

	BX.onViewLoaded(() => {
		layout.showComponent(new CalendarSyncDetail({
			layout,
			syncInfo: BX.componentParameters.get('syncInfo'),
		}));
	});
})();
