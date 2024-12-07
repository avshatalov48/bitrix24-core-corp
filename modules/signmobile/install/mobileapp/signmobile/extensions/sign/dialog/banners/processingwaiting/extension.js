/**
 * @module sign/dialog/banners/processingwaiting
 */
jn.define('sign/dialog/banners/processingwaiting', (require, exports, module) => {
	const { Loc } = require('loc');
	const {
		ButtonSize,
		ButtonDesign,
		Button,
	} = require('ui-system/form/buttons/button');
	const { BannerTemplate } = require('sign/dialog/banners/template');
	const { BBCodeParser } = require('bbcode/parser');
	const { Color } = require('tokens');
	const parser = new BBCodeParser();

	class ProcessingWaiting extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			const {
				documentTitle,
				layoutWidget,
			} = props;

			this.documentTitle = documentTitle;
			this.layoutWidget = layoutWidget;
		}

		closeLayout()
		{
			this.layoutWidget.close();
		}

		render()
		{
			return BannerTemplate({
				iconPathName: 'request.svg',
				title: Loc.getMessage('SIGN_MOBILE_DIALOG_PROCESSING_WAITING_TITLE'),
				description: Loc.getMessage(
					'SIGN_MOBILE_DIALOG_PROCESSING_WAITING_DESCRIPTION',
					{
						'#DOCUMENT_TITLE#': parser.parse(this.documentTitle).toPlainText(),
						'#COLOR_OF_HIGHLIGHTED_TEXT#': Color.base1.toHex(),
					},
				),
				buttonsView: View(
					{},
					Button({
						text: Loc.getMessage('SIGN_MOBILE_DIALOG_PROCESSING_WAITING_BUTTON_CLOSE_TITLE'),
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

	module.exports = { ProcessingWaiting };
});