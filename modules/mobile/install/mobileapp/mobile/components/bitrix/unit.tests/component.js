(() => {
	const require = (ext) => jn.require(ext);
	const AppTheme = require('apptheme');

	const { testSuites, report, ConsolePrinter, JnLayoutPrinter } = require('testing');

	class UnitTestDashboard extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			const only = testSuites.filter((suite) => suite.$only);
			const executables = only.length > 0 ? only : testSuites;

			executables
				.filter((suite) => !suite.$skip)
				.forEach((suite) => suite.execute());

			this.jnLayoutPrinter = new JnLayoutPrinter();
			this.consolePrinter = new ConsolePrinter();
			this.consolePrinter.print(report);
		}

		render()
		{
			return ScrollView(
				{
					style: {
						flexDirection: 'column',
					},
				},
				View(
					{
						style: {
							paddingTop: 16,
							flexDirection: 'column',
							flexGrow: 1,
						},
					},
					Button({
						text: 'Run tests',
						style: {
							marginHorizontal: 80,
							marginVertical: 16,
							paddingHorizontal: 20,
							paddingVertical: 10,
							borderWidth: 1,
							borderColor: AppTheme.colors.bgSeparatorPrimary,
							borderRadius: 5,
						},
						onClick()
						{
							console.clear && console.clear();
							this.reload();
						},
					}),
					View(
						{
							style: {
								flexDirection: 'row',
								justifyContent: 'center',
								marginBottom: 16,
							},
						},
						Text({
							text: 'Results duplicates in console',
							style: {
								fontSize: 16,
							},
						}),
					),
					View(
						{},
						this.renderTotals(),
						this.jnLayoutPrinter.print(report),
					),
				),
			);
		}

		renderTotals()
		{
			const assertions = report.totalAssertions;
			const failures = report.totalFailures;
			const isSuccess = failures === 0;

			const stats = isSuccess
				? `Assertions: ${assertions}`
				: `Assertions: ${assertions}, failures: ${failures}`;

			return View(
				{
					style: {
						flexDirection: 'column',
						flexGrow: 1,
					},
				},
				View(
					{
						style: {
							backgroundColor: isSuccess ? AppTheme.colors.accentSoftElementGreen1 : AppTheme.colors.accentMainAlert,
							padding: 12,
							flexDirection: 'row',
							justifyContent: 'space-between',
						},
					},
					View(
						{
							testId: 'UnitTestDashboard_status',
						},
						Text({
							testId: 'UnitTestDashboard_status_text',
							text: isSuccess ? 'SUCCESS' : 'FAILURES',
							style: {
								fontWeight: 'bold',
								fontSize: 18,
							},
						}),
					),
					View(
						{
							testId: 'UnitTestDashboard_statistics',
						},
						Text({
							testId: 'UnitTestDashboard_statistics_text',
							text: stats,
							style: {
								fontSize: 16,
							},
						}),
					),
				),
			);
		}
	}

	BX.onViewLoaded(() => {
		layout.showComponent(new UnitTestDashboard({}));
	});
})();
