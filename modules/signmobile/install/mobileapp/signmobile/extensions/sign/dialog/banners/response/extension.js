/**
 * @module sign/dialog/banners/response
 */
jn.define('sign/dialog/banners/response', (require, exports, module) => {
	const { Loc } = require('loc');
	const {
		ButtonSize,
		ButtonDesign,
		Button,
	} = require('ui-system/form/buttons/button');
	const { BannerTemplate } = require('sign/dialog/banners/template');
	const { SignOpener } = require('sign/opener');
	const { Indent, Color } = require('tokens');
	const { confirmationAccept, confirmationPostpone } = require('sign/connector');
	const { Processing } = require('sign/dialog/banners/processing');
	const { SignedByAssignee } = require('sign/dialog/banners/signedbyassignee');
	const { NotifyManager } = require('notify-manager');
	const { BBCodeParser } = require('bbcode/parser');
	const parser = new BBCodeParser();

	const ROLE_ASSIGNEE = 'assignee';

	class Response extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			const {
				layoutWidget,
				documentTitle,
				memberId,
				role,
				initiatedByType,
			} = props;

			this.documentTitle = documentTitle;
			this.memberId = memberId;
			this.layoutWidget = layoutWidget;
			this.role = role;
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
				iconPathName: 'response.svg',
				title: Loc.getMessage('SIGN_MOBILE_DIALOG_RESPONSE_TITLE'),
				description: Loc.getMessage(
					'SIGN_MOBILE_DIALOG_RESPONSE_DESCRIPTION',
					{
						'#DOCUMENT_TITLE#': parser.parse(this.documentTitle).toPlainText(),
						'#COLOR_OF_HIGHLIGHTED_TEXT#': Color.base1.toHex(),
					},
				),
				buttonsView: View(
					{},
					Button({
						text: Loc.getMessage('SIGN_MOBILE_DIALOG_RESPONSE_BUTTON_CONFIRM'),
						size: ButtonSize.XL,
						design: ButtonDesign.FILLED,
						disabled: false,
						badge: false,
						stretched: true,
						onClick: () => {
							NotifyManager.showLoadingIndicator();
							confirmationAccept(this.memberId).then(() => {
								NotifyManager.hideLoadingIndicatorWithoutFallback();
								if (this.role === ROLE_ASSIGNEE)
								{
									this.layoutWidget.showComponent(new SignedByAssignee({
										layoutWidget: this.layoutWidget,
										documentTitle: this.documentTitle,
										initiatedByType: this.initiatedByType,
									}));
								}
								else
								{
									this.layoutWidget.showComponent(new Processing({
										layoutWidget: this.layoutWidget,
										documentTitle: this.documentTitle,
										memberId: this.memberId,
										initiatedByType: this.initiatedByType,
									}));
								}
							}).catch(() => {
								NotifyManager.showErrors([{
									message: Loc.getMessage('SIGN_MOBILE_DIALOG_RESPONSE_DOCUMENT_UNKNOWN_ERROR_TEXT'),
								}]);
							});
						},
						style: {
							marginBottom: Indent.L.toNumber(),
						},
					}),
					Button({
						text: Loc.getMessage('SIGN_MOBILE_DIALOG_RESPONSE_BUTTON_CLOSE'),
						size: ButtonSize.XL,
						design: ButtonDesign.PLAN_ACCENT,
						disabled: false,
						badge: false,
						stretched: true,
						onClick: () => {
							NotifyManager.showLoadingIndicator();
							confirmationPostpone(this.memberId).then(() => {
								NotifyManager.hideLoadingIndicatorWithoutFallback();
								this.closeLayout();
							}).catch(() => {
								NotifyManager.hideLoadingIndicatorWithoutFallback();
								this.closeLayout();
							});
						},
					}),
				),
			});
		}
	}

	module.exports = { Response };
});