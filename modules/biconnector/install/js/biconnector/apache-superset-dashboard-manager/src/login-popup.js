import { Loc, Tag } from 'main.core';
import { Popup } from 'main.popup';
import { Button } from 'ui.buttons';
import 'ui.icon-set.main';
import 'ui.design-tokens';
import './css/main.css';
import type { SourceDashboardInfo } from './dashboard-manager';

type Props = {
	login: string,
	password: string,
	link: string,
	additionalParams?: Object,
	sourceDashboard?: SourceDashboardInfo,
	onOpen?: () => void,
	onClose?: () => void,
};

export class LoginPopup extends Popup
{
	constructor(props: Props)
	{
		const popupContent = LoginPopup.getPopupContent(props.login, props.password, props.sourceDashboard);

		const loginPopupParams = {
			content: popupContent,
			overlay: true,
			width: 620,
			className: 'dashboard-login-popup',
			closeIcon: true,
			closeByEsc: true,
			contentBackground: 'transparent',
			titleBar: Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_LOGIN_POPUP_TITLE'),
			events: {
				onClose: () => {
					if (props.onClose)
					{
						props.onClose();
					}
				},
			},
			disableScroll: true,
			buttons: [
				new Button({
					text: Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_LOGIN_POPUP_AUTH_BTN'),
					color: Button.Color.PRIMARY,
					onclick: () => {
						if (props.onOpen)
						{
							props.onOpen();
						}
						window.open(props.link, '_blank');
					},
				}),
				new Button({
					text: Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_LOGIN_POPUP_CANCEL_BTN'),
					color: Button.Color.LIGHT,
					onclick: () => {
						this.close();
					},
				}),
			],
		};

		super({
			...loginPopupParams,
			...props.additionalParams,
		});
	}

	static getPopupContent(login: string, password: string): HTMLElement
	{
		const copyLoginBtn = Tag.render`<span 
			class="ui-icon-set --paste" 
			id="dashboard-copy-login-btn"
			style="--ui-icon-set__icon-size: 21px; --ui-icon-set__icon-color: var(--ui-color-palette-gray-60)"
			></span>`;

		const copyPasswdBtn = Tag.render`<span 
			class="ui-icon-set --paste" 
			id="dashboard-copy-passwd-btn"
			style="--ui-icon-set__icon-size: 21px; --ui-icon-set__icon-color: var(--ui-color-palette-gray-60)"
			></span>`;

		BX.clipboard.bindCopyClick(copyLoginBtn, {
			text: login,
		});

		BX.clipboard.bindCopyClick(copyPasswdBtn, {
			text: password,
		});

		return Tag.render`
			<div>
				<div class="dashboard-login-popup-info">
					<div class="dashboard-login-popup-info-desc">
						${Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_LOGIN_POPUP_DESCRIPTION')}
					</div>
					<div class="dashboard-login-popup-info-title">
						<svg class="dashboard-login-popup-info-icon" width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path fill-rule="evenodd" clip-rule="evenodd" d="M8.9849 0.00012207C13.9469 0.00012207 17.9695 4.02242 17.9695 8.98498C17.9695 13.9468 13.9469 17.9694 8.9849 17.9694C4.02266 17.9694 0 13.9468 0 8.98498C0 4.02242 4.02266 0.00012207 8.9849 0.00012207ZM10.2118 4.08379C10.2118 4.8272 9.60328 5.42985 8.85267 5.42985C8.10205 5.42985 7.49356 4.8272 7.49356 4.08379C7.49356 3.34039 8.10205 2.73774 8.85267 2.73774C9.60328 2.73774 10.2118 3.34039 10.2118 4.08379ZM6.15315 6.68282H9.32948V6.68509H10.2063V12.6612H11.5615V13.853L10.2063 13.853H7.74132L6.15315 13.853V12.6612H7.74132V7.89359H6.15315V6.68282Z" fill="#559BE6"/>
						</svg>
						${Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_LOGIN_POPUP_DATA_TITLE')}
					</div>
					<div class="dashboard-login-popup-info-data">
						<div class="dashboard-login-popup-info-data-row">
							<span class="dashboard-login-popup-info-data-row-title">${Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_LOGIN_POPUP_DATA_LOGIN')}:</span> 
							<span class="dashboard-login-popup-info-data-row-val">${login}</span>
							${BX.util.htmlspecialchars(copyLoginBtn)}
						 </div>
						<div class="dashboard-login-popup-info-data-row">
							<span class="dashboard-login-popup-info-data-row-title">${Loc.getMessage('SUPERSET_DASHBOARD_DETAIL_LOGIN_POPUP_DATA_PASSWORD')}:</span> 
							<span class="dashboard-login-popup-info-data-row-val">${password}</span>
							${BX.util.htmlspecialchars(copyPasswdBtn)}
						</div>
					</div>
				</div>
			</div>
		`;
	}
}
