/**
 * @module sign/dialog/banners/erroraccessdenied
 */
jn.define('sign/dialog/banners/erroraccessdenied', (require, exports, module) => {
	const { Loc } = require('loc');
	const {
		ButtonSize,
		ButtonDesign,
		Button,
	} = require('ui-system/form/buttons/button');
	const { BannerTemplate } = require('sign/dialog/banners/template');

	class ErrorAccessDenied extends LayoutComponent
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
				title: Loc.getMessage('SIGN_MOBILE_DIALOG_ERROR_ACCESS_DENIED_TITLE'),
				description: Loc.getMessage('SIGN_MOBILE_DIALOG_ERROR_ACCESS_DENIED_DESCRIPTION'),
				buttonsView: View(
					{},
					Button({
						text: Loc.getMessage('SIGN_MOBILE_DIALOG_ERROR_ACCESS_DENIED_BUTTON_CLOSE_TITLE'),
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

	module.exports = { ErrorAccessDenied };
});