/**
 * @module sign/dialog/banners/processing
 */
jn.define('sign/dialog/banners/processing', (require, exports, module) => {
	const { Loc } = require('loc');
	const {
		ButtonSize,
		ButtonDesign,
		Button,
	} = require('ui-system/form/buttons/button');
	const { Color } = require('tokens');
	const { BannerTemplate } = require('sign/dialog/banners/template');

	class Processing extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			const {
				layoutWidget,
			} = props;

			this.layoutWidget = layoutWidget;
		}

		closeLayout()
		{
			this.layoutWidget.close();
		}

		render()
		{
			return BannerTemplate({
				iconPathName: 'signed.svg',
				title: Loc.getMessage('SIGN_MOBILE_DIALOG_PROCESSING_TITLE'),
				description: Loc.getMessage(
					'SIGN_MOBILE_DIALOG_PROCESSING_DESCRIPTION',
					{
						'#COLOR_OF_HIGHLIGHTED_TEXT#': Color.base1.toHex(),
					},
				),
				buttonsView: View(
					{},
					Button({
						text: Loc.getMessage('SIGN_MOBILE_DIALOG_PROCESSING_BUTTON_CLOSE'),
						size: ButtonSize.XL,
						design: ButtonDesign.PLAN_ACCENT,
						disabled: false,
						badge: false,
						stretched: true,
						onClick: () => {
							this.closeLayout();
						},
					}),
				),
			});
		}
	}

	module.exports = { Processing };
});