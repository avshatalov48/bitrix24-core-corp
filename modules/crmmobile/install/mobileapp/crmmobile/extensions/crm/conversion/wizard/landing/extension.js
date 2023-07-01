/**
 * @module crm/conversion/wizard/landing
 */
jn.define('crm/conversion/wizard/landing', (require, exports, module) => {
	const { Loc } = require('loc');
	const { ConversionWizardLandingLayout } = require('crm/conversion/wizard/landing/layout');

	const MEDIUM_POSITION_PERCENT = 65;
	const BACKGROUND_COLOR = '#eef2f4';

	/**
	 * @class ConversionWizardLayout
	 */

	const showLandingWizard = (props) => new Promise((resolve) => {
		const { onFinish } = props;

		PageManager.openWidget(
			'layout',
			{
				titleParams: {
					text: Loc.getMessage('MCRM_CONVERSION_WIZARD_LANDING_TITLE'),
				},
				backdrop: {
					swipeAllowed: true,
					swipeContentAllowed: true,
					forceDismissOnSwipeDown: false,
					hideNavigationBar: false,
					horizontalSwipeAllowed: false,
					mediumPositionPercent: MEDIUM_POSITION_PERCENT,
					navigationBarColor: BACKGROUND_COLOR,
					onlyMediumPosition: false,
					shouldResizeContent: true,
				},
			},
		).then((wizardWidget) => {
			wizardWidget.setRightButtons([
				{
					name: Loc.getMessage('MCRM_CONVERSION_WIZARD_LANDING_RIGHT_TITLE'),
					callback: () => {
						onFinish();
					},
					color: '#0065a3',
				},
			]);
			wizardWidget.showComponent(
				new class extends LayoutComponent
				{
					render()
					{
						return new ConversionWizardLandingLayout({ ...props, wizardWidget });
					}
				}(),
			);

			wizardWidget.enableNavigationBarBorder(false);
			resolve(wizardWidget);
		});
	});

	module.exports = { showLandingWizard, ConversionWizardLandingLayout };
});
