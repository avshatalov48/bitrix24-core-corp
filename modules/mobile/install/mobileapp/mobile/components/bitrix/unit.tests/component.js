(() => {

	class UnitTestDashboard extends LayoutComponent
	{
		render()
		{
			return ScrollView(
				{},
				View(
					{
						style: {
							alignItems: 'center',
							marginTop: 16,
						}
					},
					Text({
						text: 'See console',
						style: {
							fontSize: 16,
						}
					}),
					Button({
						text: 'Run tests',
						style: {
							marginTop: 16,
							paddingLeft: 20,
							paddingRight: 20,
							paddingTop: 10,
							paddingBottom: 10,
							borderWidth: 1,
							borderColor: '#dadada',
							borderRadius: 5,
						},
						onClick() {
							console.clear();
							this.reload();
						}
					})
				)
			);
		}
	}

	BX.onViewLoaded(() => {
		layout.showComponent(new UnitTestDashboard({}));
	});

})();