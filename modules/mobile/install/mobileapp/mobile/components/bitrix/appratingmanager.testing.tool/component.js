(() => {
	const require = (ext) => jn.require(ext);
	const { AppRatingManagerManualTestingTool } = require('app-rating-manager/manual-testing-tool');

	class AppRatingManagerTestingToolComponent extends LayoutComponent
	{
		render()
		{
			return AppRatingManagerManualTestingTool();
		}
	}

	layout.showComponent(new AppRatingManagerTestingToolComponent());
})();
