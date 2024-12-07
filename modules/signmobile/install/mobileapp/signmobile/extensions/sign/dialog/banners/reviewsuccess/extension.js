/**
 * @module sign/dialog/banners/reviewsuccess
 */
jn.define('sign/dialog/banners/reviewsuccess', (require, exports, module) => {
	const { Loc } = require('loc');
	const {
		ButtonSize,
		ButtonDesign,
		Button,
	} = require('ui-system/form/buttons/button');
	const { BannerTemplate } = require('sign/dialog/banners/template');
	const { Color } = require('tokens');
	const { BBCodeParser } = require('bbcode/parser');
	const parser = new BBCodeParser();

	class ReviewSuccess extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			const {
				layoutWidget,
				documentTitle,
			} = props;

			this.layoutWidget = layoutWidget;
			this.documentTitle = documentTitle;
		}

		closeLayout()
		{
			this.layoutWidget.close();
		}

		render()
		{
			return BannerTemplate({
				iconPathName: 'signed.svg',
				title: Loc.getMessage('SIGN_MOBILE_DIALOG_REVIEW_SUCCESS_TITLE'),
				description: Loc.getMessage(
					'SIGN_MOBILE_DIALOG_REVIEW_SUCCESS_DESCRIPTION',
					{
						'#DOCUMENT_TITLE#': parser.parse(this.documentTitle).toPlainText(),
						'#COLOR_OF_HIGHLIGHTED_TEXT#': Color.base1.toHex(),
					},
				),
				buttonsView: View(
					{},
					Button({
						text: Loc.getMessage('SIGN_MOBILE_DIALOG_REVIEW_SUCCESS_BUTTON_CLOSE'),
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

	module.exports = { ReviewSuccess };
});