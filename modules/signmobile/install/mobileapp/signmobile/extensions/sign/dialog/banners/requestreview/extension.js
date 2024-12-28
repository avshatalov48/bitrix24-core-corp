/**
 * @module sign/dialog/banners/requestreview
 */
jn.define('sign/dialog/banners/requestreview', (require, exports, module) => {
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
	class RequestReview extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			const {
				layoutWidget,
				documentTitle,
				memberId,
				url,
				role,
				initiatedByType,
			} = props;

			this.role = role;
			this.documentTitle = documentTitle;
			this.memberId = memberId;
			this.url = url;
			this.layoutWidget = layoutWidget;
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
			/*
				It is required to reset the cache not only when closing,
				but also when switching to any child banner, since banners called
				from the background should not overlap only each other.
			 */
			SignOpener.clearCacheSomeBannerIsAlreadyOpen();
			this.layoutWidget.close(callback);
		}

		render()
		{
			return BannerTemplate({
				iconPathName: 'request.svg',
				title: Loc.getMessage('SIGN_MOBILE_DIALOG_REQUEST_REVIEW_TITLE'),
				description: Loc.getMessage(
					'SIGN_MOBILE_DIALOG_REQUEST_REVIEW_DESCRIPTION',
					{
						'#DOCUMENT_TITLE#': parser.parse(this.documentTitle).toPlainText(),
						'#COLOR_OF_HIGHLIGHTED_TEXT#': Color.base1.toHex(),
					},
				),
				buttonsView: View(
					{},
					Button({
						text: Loc.getMessage('SIGN_MOBILE_DIALOG_REQUEST_REVIEW_BUTTON_START_SIGNING'),
						size: ButtonSize.XL,
						design: ButtonDesign.FILLED,
						disabled: false,
						badge: false,
						stretched: true,
						onClick: () => {
							this.closeLayout(() => {
								SignOpener.openSigning({
									role: this.role,
									goWithoutConfirmation: true,
									title: this.documentTitle,
									memberId: this.memberId,
									url: this.url,
									initiatedByType: this.initiatedByType,
								});
							});
						},
						style: {
							marginBottom: Indent.L.toNumber(),
						},
					}),
					Button({
						text: Loc.getMessage('SIGN_MOBILE_DIALOG_REQUEST_REVIEW_BUTTON_CLOSE'),
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

	module.exports = { RequestReview };
});