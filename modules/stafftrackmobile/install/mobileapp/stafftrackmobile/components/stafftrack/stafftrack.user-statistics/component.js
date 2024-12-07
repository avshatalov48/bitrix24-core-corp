(() => {
	const require = (ext) => jn.require(ext);

	const { UserStatistics } = require('stafftrack/user-statistics');

	BX.onViewLoaded(() => {
		const user = BX.componentParameters.get('USER', {});
		const monthCode = BX.componentParameters.get('MONTH_CODE', '');

		layout.showComponent(
			new UserStatistics({
				user,
				monthCode,
				myCheckins: false,
				layoutWidget: layout,
			}),
		);
	});
})();
