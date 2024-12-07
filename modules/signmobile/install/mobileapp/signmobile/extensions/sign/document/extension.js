/**
 * @module sign/document
 */
jn.define('sign/document', (require, exports, module) => {
	const { Card } = require('ui-system/layout/card');
	const {
		ButtonSize,
		ButtonDesign,
		Button,
	} = require('ui-system/form/buttons/button');
	const { Indent, Color } = require('tokens');
	const { Loc } = require('loc');
	const { ResponseMobile } = require('sign/dialog/banners/responsemobile');
	const { RefusedJustNow } = require('sign/dialog/banners/refusedjustnow');
	const { External } = require('sign/dialog/banners/external');
	const { NotifyManager } = require('notify-manager');
	const { showConfirm } = require('sign/dialog/banners/template');
	const { rejectConfirmation } = require('sign/connector');
	const IS_IOS = Application.getPlatform() === 'ios';
	const URL_GOSKEY_APP_INSTALL = 'https://www.gosuslugi.ru/select-app-goskey';

	/**
	 * @class SignDocument
	 */
	class SignDocument extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			const {
				url,
				widget,
				memberId,
				hideButtons,
				title,
				isGoskey,
				isExternal,
			} = props;

			this.layout = widget;
			this.url = url;
			this.memberId = memberId;
			this.hideButtons = hideButtons;
			this.isGoskey = isGoskey;
			this.isExternal = isExternal;
			this.title = title;
		}

		clearHeader()
		{
			this.layout.setTitle({
				text: '',
			});
			this.layout.setRightButtons([]);
			this.layout.setLeftButtons([]);
		}

		render()
		{
			return View(
				{
					style: {
						backgroundColor: Color.bgPrimary.toHex(),
						flex: 1,
					},
				},
				WebView({
					style: {
						flex: 1,
					},
					scrollDisabled: true,
					zoomEnabled: true,
					data: {
						url: this.url,
					},
				}),
				View(
					{
						style: {
							paddingBottom: IS_IOS ? device.screen.safeArea.bottom : (!this.hideButtons ? Indent.L.toNumber() : 0),
						},
					},
					!this.hideButtons && Card(
						{},
						Button({
							text: Loc.getMessage('SIGN_MOBILE_DOCUMENT_CONFIRM_SIGNING_BUTTON_TITLE'),
							size: ButtonSize.XL,
							design: ButtonDesign.FILLED,
							disabled: false,
							badge: false,
							stretched: true,
							onClick: () => {
								if (this.isGoskey)
								{
									// TODO add helpdesk article to popup
									// helpdesk.openHelpArticle('19740842', 'install_app');

									if (Application.canOpenUrl(URL_GOSKEY_APP_INSTALL))
									{
										Application.openUrl(URL_GOSKEY_APP_INSTALL);
									}

									return;
								}

								this.clearHeader();

								if (this.isExternal)
								{
									this.layout.showComponent(new External({
										documentTitle: this.title,
										memberId: this.memberId,
										layoutWidget: this.layout,
									}));

									return;
								}

								this.layout.showComponent(new ResponseMobile({
									documentTitle: this.title,
									memberId: this.memberId,
									layoutWidget: this.layout,
								}));
							},
							style: {
								marginBottom: Indent.L.toNumber(),
							},
						}),
						Button({
							text: Loc.getMessage('SIGN_MOBILE_DOCUMENT_CONFIRM_SIGNING_REJECT_TITLE'),
							size: ButtonSize.XL,
							design: ButtonDesign.PLAN_ACCENT,
							disabled: false,
							badge: false,
							stretched: true,
							onClick: () => {
								if (this.isGoskey)
								{
									// TODO add helpdesk article to popup
									// helpdesk.openHelpArticle('19740842', 'install_app');

									if (Application.canOpenUrl(URL_GOSKEY_APP_INSTALL))
									{
										Application.openUrl(URL_GOSKEY_APP_INSTALL);
									}

									return;
								}

								showConfirm({
									title: Loc.getMessage('SIGN_MOBILE_DOCUMENT_CONFIRM_SIGNING_ALERT_TITLE'),
									description: Loc.getMessage('SIGN_MOBILE_DOCUMENT_CONFIRM_SIGNING_ALERT_DESCRIPTION'),
									confirmTitle: Loc.getMessage(
										'SIGN_MOBILE_DOCUMENT_CONFIRM_SIGNING_ALERT_REJECT_BUTTON_TITLE'),
									cancelTitle: Loc.getMessage(
										'SIGN_MOBILE_DOCUMENT_CONFIRM_SIGNING_ALERT_CANCEL_BUTTON_TITLE'),
									onConfirm: () => {
										NotifyManager.showLoadingIndicator();
										rejectConfirmation(this.memberId).then(({ data }) => {
											NotifyManager.hideLoadingIndicatorWithoutFallback();
											this.clearHeader();
											this.layout.showComponent(new RefusedJustNow({
												documentTitle: this.title,
												memberId: this.memberId,
												layoutWidget: this.layout,
											}));
										}).catch(() => {
											NotifyManager.showErrors([
												{
													message: Loc.getMessage('SIGN_MOBILE_DOCUMENT_UNKNOWN_ERROR_TEXT'),
												},
											]);
										});
									},
								});
							},
						}),
					),
				),
			);
		}
	}

	module.exports = { SignDocument };
});
