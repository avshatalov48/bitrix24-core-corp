/**
 * @module sign/dialog/banners/error
 */
jn.define('sign/dialog/banners/error', (require, exports, module) => {
	const { Loc } = require('loc');
	const {
		ButtonSize,
		ButtonDesign,
		Button,
	} = require('ui-system/form/buttons/button');
	const { BannerTemplate } = require('sign/dialog/banners/template');

	class Error extends LayoutComponent
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
				iconPathName: 'error.svg',
				title: Loc.getMessage('SIGN_MOBILE_DIALOG_ERROR_TITLE'),
				description: Loc.getMessage('SIGN_MOBILE_DIALOG_ERROR_DESCRIPTION'),
				buttonsView: View(
					{},
					Button({
						text: Loc.getMessage('SIGN_MOBILE_DIALOG_ERROR_BUTTON_CLOSE_TITLE'),
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

	module.exports = { Error };
});