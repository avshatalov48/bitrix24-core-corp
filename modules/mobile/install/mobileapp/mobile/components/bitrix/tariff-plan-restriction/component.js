// call this via PlanRestriction.openComponent
(() => {
	const require = (ext) => jn.require(ext);
	const { PlanRestriction } = require('layout/ui/plan-restriction');

	BX.onViewLoaded(() => {
		layout.showComponent(
			new PlanRestriction({
				layout,
				text: BX.componentParameters.get('text'),
				isPromo: BX.componentParameters.get('isPromo'),
				planId: BX.componentParameters.get('planId'),
				featureId: BX.componentParameters.get('featureId'),
				analyticsData: BX.componentParameters.get('analyticsData'),
			}),
		);
	});
})();
