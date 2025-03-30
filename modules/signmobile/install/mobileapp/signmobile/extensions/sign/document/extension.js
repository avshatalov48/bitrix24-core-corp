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
	const { ResponseReviewMobile } = require('sign/dialog/banners/responsereviewmobile');
	const { RefusedJustNow } = require('sign/dialog/banners/refusedjustnow');
	const { InitiatedByType } = require('sign/type/initiated-by-type');
	const { External } = require('sign/dialog/banners/external');
	const { NotifyManager } = require('notify-manager');
	const { showConfirm } = require('sign/dialog/banners/template');
	const { rejectConfirmation } = require('sign/connector');
	const { MemberRole } = require('sign/type/member-role');
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
				title,
				isGoskey,
				isExternal,
				initiatedByType,
				role,
			} = props;

			this.layout = widget;
			const appendSymbol = url.indexOf('?') !== -1 ? '&' : '?';
			this.url = url + appendSymbol + 'hide_reviewer_buttons=1';
			this.memberId = memberId;
			this.isGoskey = isGoskey;
			this.isExternal = isExternal;
			this.title = title;
			this.initiatedByType = initiatedByType;
			this.role = role;
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
							paddingBottom: IS_IOS ? device.screen.safeArea.bottom : 0,
						},
					},
					MemberRole.isReviewerRole(this.role)
						? this.renderReviewButtons()
						: this.renderSignButtons()
					,
				),
			);
		}

		renderSignButtons()
		{
			return Card(
				{},
				Button({
					text: Loc.getMessage('SIGN_MOBILE_DOCUMENT_CONFIRM_SIGNING_BUTTON_TITLE'),
					size: ButtonSize.XL,
					design: ButtonDesign.FILLED,
					disabled: false,
					badge: false,
					stretched: true,
					onClick: this.#onSignButtonClickHandler,
					style: {
						marginBottom: Indent.L.toNumber(),
					},
				}),
				this.renderRejectButton(),
			);
		}

		renderReviewButtons()
		{
			return Card(
				{},
				Button({
					text: Loc.getMessage('SIGN_MOBILE_DOCUMENT_CONFIRM_REVIEW_BUTTON_TITLE'),
					size: ButtonSize.XL,
					design: ButtonDesign.FILLED,
					disabled: false,
					badge: false,
					stretched: true,
					onClick: this.#onReviewButtonClickHandler,
					style: {
						marginBottom: Indent.L.toNumber(),
					},
				}),
				this.renderRejectButton(),
			);
		}

		renderRejectButton()
		{
			const { titleCode } = this.#getRejectButtonConfig();

			return Button({
				text: Loc.getMessage(titleCode),
				size: ButtonSize.XL,
				design: ButtonDesign.PLAN_ACCENT,
				disabled: false,
				badge: false,
				stretched: true,
				onClick: this.#onRejectButtonClickHandler,
			});
		}

		#onSignButtonClickHandler = () => {
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
		};

		#onReviewButtonClickHandler = () => {
			this.clearHeader();

			this.layout.showComponent(new ResponseReviewMobile({
				documentTitle: this.title,
				memberId: this.memberId,
				layoutWidget: this.layout,
				initiatedByType: this.initiatedByType,
			}));
		};

		#onRejectButtonClickHandler = () => {
			if (this.isGoskey && !MemberRole.isReviewerRole(this.role))
			{
				// TODO add helpdesk article to popup
				// helpdesk.openHelpArticle('19740842', 'install_app');

				if (Application.canOpenUrl(URL_GOSKEY_APP_INSTALL))
				{
					Application.openUrl(URL_GOSKEY_APP_INSTALL);
				}

				return;
			}

			const { titleCode, confirmTitleCode, descriptionCode, cancelTitleCode } = this.#getRejectButtonConfig();

			showConfirm({
				title: Loc.getMessage(titleCode),
				description: Loc.getMessage(descriptionCode),
				confirmTitle: Loc.getMessage(confirmTitleCode),
				cancelTitle: Loc.getMessage(cancelTitleCode),
				onConfirm: this.#onConfirmRejectButtonClickHandler,
			});
		};

		#onConfirmRejectButtonClickHandler = () => {
			NotifyManager.showLoadingIndicator();
			rejectConfirmation(this.memberId).then(() => {
				NotifyManager.hideLoadingIndicatorWithoutFallback();
				this.clearHeader();
				this.layout.showComponent(new RefusedJustNow({
					role: this.role,
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
		};

		#getRejectButtonConfig() {
			const isEmployee = InitiatedByType.isInitiatedByEmployee(this.initiatedByType);
			const isReviewer = MemberRole.isReviewerRole(this.role);
			const isSigner = MemberRole.isSignerRole(this.role);

			const titleCode = isEmployee || isReviewer
				? 'SIGN_MOBILE_DOCUMENT_CONFIRM_SIGNING_REJECT_BY_EMPLOYEE_TITLE'
				: 'SIGN_MOBILE_DOCUMENT_CONFIRM_SIGNING_REJECT_TITLE';

			const confirmTitleCode = isEmployee || isReviewer
				? 'SIGN_MOBILE_DOCUMENT_CONFIRM_SIGNING_ALERT_BY_EMPLOYEE_REJECT_BUTTON_TITLE'
				: 'SIGN_MOBILE_DOCUMENT_CONFIRM_SIGNING_ALERT_REJECT_BUTTON_TITLE';

			let descriptionCode = isEmployee && isSigner
				? 'SIGN_MOBILE_DOCUMENT_CONFIRM_SIGNING_ALERT_BY_EMPLOYEE_DESCRIPTION'
				: isReviewer
					? 'SIGN_MOBILE_DOCUMENT_CONFIRM_SIGNING_ALERT_BY_REVIEWER_DESCRIPTION'
					: 'SIGN_MOBILE_DOCUMENT_CONFIRM_SIGNING_ALERT_DESCRIPTION';

			const cancelTitleCode = isReviewer
				? 'SIGN_MOBILE_DOCUMENT_CONFIRM_SIGNING_ALERT_CANCEL_BY_REVIEWER_BUTTON_TITLE'
				: 'SIGN_MOBILE_DOCUMENT_CONFIRM_SIGNING_ALERT_CANCEL_BUTTON_TITLE'
			;

			return { titleCode, confirmTitleCode, descriptionCode, cancelTitleCode };
		}
	}

	module.exports = { SignDocument };
});
