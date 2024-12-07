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
	const { NotifyManager } = require('notify-manager');
	const { Filesystem, utils } = require('native/filesystem');
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

		downloadFile()
		{
			NotifyManager.showLoadingIndicator();
			Filesystem.downloadFile(this.fileDownloadUrl)
				.then((localPath) => {
					NotifyManager.hideLoadingIndicatorWithoutFallback();
					this.closeLayout(() => {
						utils.saveFile(localPath).catch(() => dialogs.showSharingDialog({ uri: localPath }));
					});
				})
				.catch(() => {
					NotifyManager.showErrors([{
						message: Loc.getMessage('SIGN_MOBILE_DIALOG_SIGNED_SAVE_FILE_ERROR_TEXT'),
					}]);
				})
			;
		}

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
					'SIGN_MOBILE_DIALOG_SIGNED_DESCRIPTION',
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
						onClick: () => {
							this.downloadFile();
						},
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