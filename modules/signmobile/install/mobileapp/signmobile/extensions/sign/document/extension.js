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
	const { InitiatedByType } = require('sign/type/initiated-by-type');
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
				initiatedByType,
			} = props;

			this.layout = widget;
			this.url = url;
			this.memberId = memberId;
			this.hideButtons = hideButtons;
			this.isGoskey = isGoskey;
			this.isExternal = isExternal;
			this.title = title;
			this.initiatedByType = initiatedByType;
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
			const rejectTitleCode  = InitiatedByType.isInitiatedByEmployee(this.initiatedByType)
				? 'SIGN_MOBILE_DOCUMENT_CONFIRM_SIGNING_REJECT_BY_EMPLOYEE_TITLE'
				: 'SIGN_MOBILE_DOCUMENT_CONFIRM_SIGNING_REJECT_TITLE'
			;

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
									initiatedByType: this.initiatedByType,
								}));
							},
							style: {
								marginBottom: Indent.L.toNumber(),
							},
						}),
						Button({
							text: Loc.getMessage(rejectTitleCode),
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

								const { titleCode, descriptionCode, confirmTitleCode } = InitiatedByType.isInitiatedByEmployee(this.initiatedByType)
									? {
										titleCode: 'SIGN_MOBILE_DOCUMENT_CONFIRM_SIGNING_ALERT_BY_EMPLOYEE_TITLE',
										descriptionCode: 'SIGN_MOBILE_DOCUMENT_CONFIRM_SIGNING_ALERT_BY_EMPLOYEE_DESCRIPTION',
										confirmTitleCode: 'SIGN_MOBILE_DOCUMENT_CONFIRM_SIGNING_ALERT_BY_EMPLOYEE_REJECT_BUTTON_TITLE',
									}
									: {
										titleCode: 'SIGN_MOBILE_DOCUMENT_CONFIRM_SIGNING_ALERT_TITLE',
										descriptionCode: 'SIGN_MOBILE_DOCUMENT_CONFIRM_SIGNING_ALERT_DESCRIPTION',
										confirmTitleCode: 'SIGN_MOBILE_DOCUMENT_CONFIRM_SIGNING_ALERT_REJECT_BUTTON_TITLE',
									}
								;

								showConfirm({
									title: Loc.getMessage(titleCode),
									description: Loc.getMessage(descriptionCode),
									confirmTitle: Loc.getMessage(confirmTitleCode),
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
												initiatedByType: this.initiatedByType,
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
