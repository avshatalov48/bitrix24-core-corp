(() => {
	const require = (ext) => jn.require(ext);

	const { Loc } = require('loc');
	const { SyncPage } = require('calendar/sync-page');

	BX.onViewLoaded(() => {
		const syncInfo = BX.componentParameters.get('syncInfo');

		layout.setTitle({
			text: Loc.getMessage('M_CALENDAR_SYNC_TITLE'),
			useLargeTitleMode: true,
		});
		layout.showComponent(new SyncPage({
			layout,
			syncInfo,
		}));
	});
})();
