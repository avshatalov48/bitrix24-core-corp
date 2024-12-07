/**
 * @module sign/dialog/banners/external
 */
jn.define('sign/dialog/banners/external', (require, exports, module) => {
	const { Loc } = require('loc');
	const {
		ButtonSize,
		ButtonDesign,
		Button,
	} = require('ui-system/form/buttons/button');
	const { BannerTemplate } = require('sign/dialog/banners/template');
	const { Indent, Color } = require('tokens');
	const { NotifyManager } = require('notify-manager');
	const { getExternalUrl } = require('sign/connector');
	const { BBCodeParser } = require('bbcode/parser');
	const parser = new BBCodeParser();
	class External extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			const {
				layoutWidget,
				memberId,
				url = '',
				documentTitle,
			} = props;

			this.documentTitle = documentTitle;
			this.memberId = memberId;
			this.url = url;
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
				title: Loc.getMessage('SIGN_MOBILE_DIALOG_EXTERNAL_TITLE'),
				description: Loc.getMessage(
					'SIGN_MOBILE_DIALOG_EXTERNAL_DESCRIPTION',
					{
						'#DOCUMENT_TITLE#': parser.parse(this.documentTitle).toPlainText(),
						'#COLOR_OF_HIGHLIGHTED_TEXT#': Color.base1.toHex(),
					},
				),
				buttonsView: View(
					{},
					Button({
						text: Loc.getMessage('SIGN_MOBILE_DIALOG_EXTERNAL_BUTTON_CONFIRM'),
						size: ButtonSize.XL,
						design: ButtonDesign.FILLED,
						disabled: false,
						badge: false,
						stretched: true,
						onClick: () => {
							NotifyManager.showLoadingIndicator();
							getExternalUrl(this.memberId)
								.then((externalResponse) => {
									NotifyManager.hideLoadingIndicatorWithoutFallback();
									const url = externalResponse?.data?.url;
									if (Application.canOpenUrl(url))
									{
										Application.openUrl(url);
										this.closeLayout();
									}
									else
									{
										NotifyManager.showErrors([{
											message: Loc.getMessage('SIGN_MOBILE_DIALOG_EXTERNAL_DOCUMENT_UNKNOWN_ERROR_TEXT'),
										}]);
									}
								}).catch(() => {
									NotifyManager.showErrors([{
										message: Loc.getMessage('SIGN_MOBILE_DIALOG_EXTERNAL_DOCUMENT_UNKNOWN_ERROR_TEXT'),
									}]);
								});
						},
						style: {
							marginBottom: Indent.L.toNumber(),
						},
					}),
					Button({
						text: Loc.getMessage('SIGN_MOBILE_DIALOG_EXTERNAL_BUTTON_CLOSE'),
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

	module.exports = { External };
});
