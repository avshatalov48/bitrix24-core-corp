/**
 * @module more-menu/block/company/demo-banner
 */
jn.define('more-menu/block/company/demo-banner', (require, exports, module) => {
	const { Indent, Color } = require('tokens');
	const { Loc } = require('loc');
	const { Text3, Text4 } = require('ui-system/typography/text');
	const { Icon, IconView } = require('ui-system/blocks/icon');
	const { PropTypes } = require('utils/validation');
	const { Button, ButtonSize, ButtonDesign } = require('ui-system/form/buttons/button');
	const { Card, CardDesign } = require('ui-system/layout/card');
	const { makeLibraryImagePath } = require('asset-manager');
	const { createTestIdGenerator } = require('utils/test');

	/**
	 * @class DemoBanner
	 */
	class DemoBanner extends LayoutComponent
	{
		/**
		 * @param props
		 * @param {object} props.license
		 * @param {object} props.layout
		 * @param {string} props.testId
		 */
		constructor(props)
		{
			super(props);

			this.getTestId = createTestIdGenerator({
				prefix: props.testId,
			});
		}

		render()
		{
			const { license = {} } = this.props;

			if (!license?.isDemoTrialAvailable)
			{
				return View({});
			}

			return Card(
				{
					testId: this.getTestId('wrapper'),
					design: CardDesign.PRIMARY,
					border: true,
					style: {
						marginLeft: Indent.M.toNumber(),
						borderColor: Color.accentSoftBlue1.toHex(),
						flexDirection: 'row',
						alignItems: 'center',
						backgroundImage: makeLibraryImagePath('demo-banner-background.png', 'more-menu'),
						backgroundResizeMode: 'cover',
					},
					onClick: this.onButtonClick,
				},
				View(
					{},
					View(
						{
							style: {
								flexDirection: 'row',
								alignItems: 'center',
								marginBottom: Indent.S.toNumber(),
							},
						},
						Text3({
							testId: this.getTestId('title'),
							text: Loc.getMessage('MORE_MENU_COMPANY_DEMO_TITLE'),
							color: Color.base1,
							numberOfLines: 1,
							ellipsize: 'end',
						}),
						IconView({
							testId: this.getTestId('chevron'),
							size: 20,
							icon: Icon.CHEVRON_TO_THE_RIGHT,
							color: Color.base1,
							resizeMode: 'cover',
							style: {
								marginTop: 2,
								width: 10,
								height: 16,
								marginLeft: Indent.XS.toNumber(),
							},
						}),
					),
					Text4({
						testId: this.getTestId('description'),
						text: Loc.getMessage('MORE_MENU_COMPANY_DEMO_DESCRIPTION'),
						color: Color.base1,
						numberOfLines: 1,
						ellipsize: 'end',
						style: {
							opacity: 0.6,
						},
					}),
					Button({
						testId: this.getTestId('demo-button'),
						size: ButtonSize.M,
						design: ButtonDesign.FILLED,
						style: {
							marginTop: Indent.XL2.toNumber(),
						},
						onClick: this.onButtonClick,
						text: Loc.getMessage('MORE_MENU_COMPANY_DEMO_BUTTON'),
					}),
				),
				Image({
					testId: this.getTestId('demo-image'),
					style: {
						width: 68,
						height: 84,
						marginLeft: 21,
					},
					resizeMode: 'contain',
					uri: makeLibraryImagePath('demo-banner-icon.png', 'more-menu'),
				}),
			);
		}

		onButtonClick = async () => {
			const { layout } = this.props;
			const { PlanRestriction } = await requireLazy('layout/ui/plan-restriction');

			PlanRestriction.open(
				{},
				layout,
			);
		};
	}

	DemoBanner.propTypes = {
		license: PropTypes.object,
		layout: PropTypes.object.isRequired,
		testId: PropTypes.string.isRequired,
	};

	module.exports = {
		DemoBanner,
	};
});
