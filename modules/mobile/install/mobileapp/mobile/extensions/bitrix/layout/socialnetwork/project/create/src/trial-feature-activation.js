/**
 * @module layout/socialnetwork/project/create/trial-feature-activation
 */
jn.define('layout/socialnetwork/project/create/trial-feature-activation', (require, exports, module) => {
	const { BottomSheet } = require('bottom-sheet');
	const { Color } = require('tokens');
	const { Loc } = require('loc');
	const { Box } = require('ui-system/layout/box');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');
	const { StatusBlock } = require('ui-system/blocks/status-block');
	const { Button, ButtonSize } = require('ui-system/form/buttons');
	const { makeLibraryImagePath } = require('asset-manager');

	/**
	 * @class TrialFeatureActivation
	 */
	class TrialFeatureActivation extends LayoutComponent
	{
		static open(parentWidget = PageManager)
		{
			void new BottomSheet({
				titleParams: {
					type: 'dialog',
					text: Loc.getMessage('MOBILE_LAYOUT_PROJECT_CREATE_TRIAL_FEATURE_ACTIVATION_TITLE'),
				},
				component: (layout) => new TrialFeatureActivation({ layout }),
			})
				.setParentWidget(parentWidget)
				.setMediumPositionHeight(420)
				.setNavigationBarColor(Color.bgNavigation.toHex())
				.open()
			;
		}

		render()
		{
			return Box(
				{
					backgroundColor: Color.bgContentPrimary,
					footer: BoxFooter(
						{
							safeArea: true,
						},
						Button({
							testId: 'BUTTON_OK',
							text: Loc.getMessage('MOBILE_LAYOUT_PROJECT_CREATE_TRIAL_FEATURE_ACTIVATION_BUTTON_OK'),
							size: ButtonSize.L,
							stretched: true,
							onClick: () => this.props.layout.close(),
						}),
					),
				},
				StatusBlock({
					image: Image({
						style: {
							width: 108,
							height: 108,
						},
						svg: {
							uri: makeLibraryImagePath('project-trial-activation-success.svg', 'graphic'),
						},
					}),
					description: Loc.getMessage('MOBILE_LAYOUT_PROJECT_CREATE_TRIAL_FEATURE_ACTIVATION_DESCRIPTION'),
					descriptionColor: Color.base1,
					testId: 'TRIAL_FEATURE_ACTIVATION',
				}),
			);
		}
	}

	module.exports = { TrialFeatureActivation };
});
