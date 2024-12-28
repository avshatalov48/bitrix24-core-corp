/**
 * @module sign/dialog/banners/responsemobile
 */
jn.define('sign/dialog/banners/responsemobile', (require, exports, module) => {
	const { Loc } = require('loc');
	const {
		ButtonSize,
		ButtonDesign,
		Button,
	} = require('ui-system/form/buttons/button');
	const { BannerTemplate } = require('sign/dialog/banners/template');
	const { Indent, Color } = require('tokens');
	const { Processing } = require('sign/dialog/banners/processing');
	const { NotifyManager } = require('notify-manager');
	const { signingAccept } = require('sign/connector');
	const { BBCodeParser } = require('bbcode/parser');
	const parser = new BBCodeParser();
	class ResponseMobile extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			const {
				layoutWidget,
				memberId,
				url = '',
				documentTitle,
				initiatedByType,
			} = props;

			this.documentTitle = documentTitle;
			this.memberId = memberId;
			this.url = url;
			this.layoutWidget = layoutWidget;
			this.initiatedByType = initiatedByType;
		}

		closeLayout(callback = {})
		{
			this.layoutWidget.close(callback);
		}

		render()
		{
			return BannerTemplate({
				iconPathName: 'response.svg',
				title: Loc.getMessage('SIGN_MOBILE_DIALOG_RESPONSE_MOBILE_TITLE'),
				description: Loc.getMessage(
					'SIGN_MOBILE_DIALOG_RESPONSE_MOBILE_DESCRIPTION',
					{
						'#DOCUMENT_TITLE#': parser.parse(this.documentTitle).toPlainText(),
						'#COLOR_OF_HIGHLIGHTED_TEXT#': Color.base1.toHex(),
					},
				),
				buttonsView: View(
					{},
					Button({
						text: Loc.getMessage('SIGN_MOBILE_DIALOG_RESPONSE_MOBILE_BUTTON_CONFIRM'),
						size: ButtonSize.XL,
						design: ButtonDesign.FILLED,
						disabled: false,
						badge: false,
						stretched: true,
						onClick: () => {
							NotifyManager.showLoadingIndicator();
							signingAccept(this.memberId).then(() => {
								NotifyManager.hideLoadingIndicatorWithoutFallback();
								this.layoutWidget.showComponent(new Processing({
									layoutWidget: this.layoutWidget,
									documentTitle: this.documentTitle,
									memberId: this.memberId,
									initiatedByType: this.initiatedByType,
								}));
							}).catch(() => {
								NotifyManager.showErrors([{
									message: Loc.getMessage('SIGN_MOBILE_DIALOG_RESPONSE_MOBILE_DOCUMENT_UNKNOWN_ERROR_TEXT'),
								}]);
							});
						},
						style: {
							marginBottom: Indent.L.toNumber(),
						},
					}),
					Button({
						text: Loc.getMessage('SIGN_MOBILE_DIALOG_RESPONSE_MOBILE_BUTTON_CLOSE'),
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

	module.exports = { ResponseMobile };
});
