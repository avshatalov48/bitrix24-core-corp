/**
 * @module sign/dialog/banners/gotowebeditor
 */
jn.define('sign/dialog/banners/gotowebeditor', (require, exports, module) => {
	const { Loc } = require('loc');
	const {
		ButtonSize,
		ButtonDesign,
		Button,
	} = require('ui-system/form/buttons/button');
	const { BannerTemplate } = require('sign/dialog/banners/template');
	const { Indent } = require('tokens');

	class GoToWebEditor extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			const {
				layoutWidget,
				memberId,
			} = props;

			this.memberId = memberId;
			this.layoutWidget = layoutWidget;
		}

		closeLayout(callback = {})
		{
			this.layoutWidget.close(callback);
		}

		render()
		{
			return BannerTemplate({
				iconPathName: 'go-to-web.svg',
				title: Loc.getMessage('SIGN_MOBILE_DIALOG_GO_TO_WEB_EDITOR_TITLE'),
				description: Loc.getMessage('SIGN_MOBILE_DIALOG_GO_TO_WEB_EDITOR_DESCRIPTION'),
				buttonsView: View(
					{},
					Button({
						text: Loc.getMessage('SIGN_MOBILE_DIALOG_GO_TO_WEB_EDITOR_BUTTON_QRCODE_AUTH_TITLE'),
						size: ButtonSize.XL,
						design: ButtonDesign.FILLED,
						disabled: false,
						badge: false,
						stretched: true,
						onClick: () => {
							this.closeLayout(() => {
								qrauth.open({
									title: Loc.getMessage('SIGN_MOBILE_DIALOG_GO_TO_WEB_EDITOR_QRCODE_AUTH_TITLE'),
									redirectUrl: `/sign/link/member/${this.memberId}/`,
									showHint: true,
									hintText: Loc.getMessage('SIGN_MOBILE_DIALOG_GO_TO_WEB_EDITOR_QRCODE_AUTH_HINT_TEXT'),
								});
							});
						},
						style: {
							marginBottom: Indent.L.toNumber(),
						},
					}),
					Button({
						text: Loc.getMessage('SIGN_MOBILE_DIALOG_GO_TO_WEB_EDITOR_BUTTON_CLOSE_TITLE'),
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

	module.exports = { GoToWebEditor };
});