/**
 * @module sign/dialog/banners/request
 */
jn.define('sign/dialog/banners/request', (require, exports, module) => {
	const { Loc } = require('loc');
	const {
		ButtonSize,
		ButtonDesign,
		Button,
	} = require('ui-system/form/buttons/button');
	const { BannerTemplate } = require('sign/dialog/banners/template');
	const { Indent, Color } = require('tokens');
	const { SignOpener } = require('sign/opener');
	const { BBCodeParser } = require('bbcode/parser');
	const parser = new BBCodeParser();
	class Request extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			const {
				layoutWidget,
				documentTitle,
				memberId,
				role,
				url,
				isGoskey,
				isExternal,
				initiatedByType,
			} = props;

			this.documentTitle = documentTitle;
			this.memberId = memberId;
			this.url = url;
			this.role = role;
			this.layoutWidget = layoutWidget;
			this.isGoskey = isGoskey;
			this.isExternal = isExternal;
			this.initiatedByType = initiatedByType;
		}

		componentDidMount()
		{
			this.layoutWidget.setListener((eventName) => {
				if (eventName === 'onViewHidden' || eventName === 'onViewRemoved')
				{
					SignOpener.clearCacheSomeBannerIsAlreadyOpen();
				}
			});
		}

		closeLayout(callback = {})
		{
			this.layoutWidget.close(callback);
		}

		render()
		{
			return BannerTemplate({
				iconPathName: 'request.svg',
				title: Loc.getMessage('SIGN_MOBILE_DIALOG_REQUEST_TITLE'),
				description: Loc.getMessage(
					'SIGN_MOBILE_DIALOG_REQUEST_DESCRIPTION_MSGVER_1',
					{
						'#DOCUMENT_TITLE#': parser.parse(this.documentTitle).toPlainText(),
						'#COLOR_OF_HIGHLIGHTED_TEXT#': Color.base1.toHex(),
					},
				),
				buttonsView: View(
					{},
					Button({
						text: Loc.getMessage('SIGN_MOBILE_DIALOG_REQUEST_BUTTON_START_SIGNING'),
						size: ButtonSize.XL,
						design: ButtonDesign.FILLED,
						disabled: false,
						badge: false,
						stretched: true,
						onClick: () => {
							this.closeLayout(() => {
								SignOpener.openSigning({
									goWithoutConfirmation: true,
									title: this.documentTitle,
									memberId: this.memberId,
									role: this.role,
									url: this.url,
									isGoskey: this.isGoskey,
									isExternal: this.isExternal,
									initiatedByType: this.initiatedByType,
								});
							});
						},
						style: {
							marginBottom: Indent.L.toNumber(),
						},
					}),
					Button({
						text: Loc.getMessage('SIGN_MOBILE_DIALOG_REQUEST_BUTTON_CLOSE'),
						size: ButtonSize.XL,
						design: ButtonDesign.PLAN_ACCENT,
						disabled: false,
						badge: false,
						stretched: true,
						onClick: () => {
							this.closeLayout(() => {
								SignOpener.clearCacheSomeBannerIsAlreadyOpen();
							});
						},
					}),
				),
			});
		}
	}

	module.exports = { Request };
});
