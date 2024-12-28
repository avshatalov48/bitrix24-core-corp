/**
 * @module sign/dialog/banners/signed
 */
jn.define('sign/dialog/banners/signed', (require, exports, module) => {
	const { Loc } = require('loc');
	const {
		ButtonSize,
		ButtonDesign,
		Button,
	} = require('ui-system/form/buttons/button');
	const { BannerTemplate } = require('sign/dialog/banners/template');
	const { Indent, Color } = require('tokens');
	const { downloadFile } = require('sign/download-file');
	const { BBCodeParser } = require('bbcode/parser');
	const parser = new BBCodeParser();

	class Signed extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			const {
				layoutWidget,
				fileDownloadUrl,
				documentTitle,
			} = props;

			this.documentTitle = documentTitle;
			this.fileDownloadUrl = fileDownloadUrl;
			this.layoutWidget = layoutWidget;
		}

		#onDownloadButtonClickHandler = () => {
			return downloadFile(this.fileDownloadUrl);
		};

		closeLayout(callback = {})
		{
			this.layoutWidget.close(callback);
		}

		render()
		{
			return BannerTemplate({
				iconPathName: 'signed.svg',
				title: Loc.getMessage('SIGN_MOBILE_DIALOG_SIGNED_TITLE'),
				description: Loc.getMessage(
					'SIGN_MOBILE_DIALOG_SIGNED_DESCRIPTION_MSGVER_1',
					{
						'#DOCUMENT_TITLE#': parser.parse(this.documentTitle).toPlainText(),
						'#COLOR_OF_HIGHLIGHTED_TEXT#': Color.base1.toHex(),
					},
				),
				buttonsView: View(
					{},
					Button({
						text: Loc.getMessage('SIGN_MOBILE_DIALOG_SIGNED_BUTTON_SAVE_TITLE'),
						size: ButtonSize.XL,
						design: ButtonDesign.FILLED,
						disabled: false,
						badge: false,
						stretched: true,
						onClick: this.#onDownloadButtonClickHandler,
						style: {
							marginBottom: Indent.L.toNumber(),
						},
					}),
					Button({
						text: Loc.getMessage('SIGN_MOBILE_DIALOG_SIGNED_BUTTON_CLOSE'),
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

	module.exports = { Signed };
});