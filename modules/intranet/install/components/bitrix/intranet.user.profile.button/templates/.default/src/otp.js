import {Loc, Tag, Text, Uri} from "main.core";
import {Menu} from "main.popup";
import {BaseEvent, EventEmitter} from "main.core.events";
import Options from "./options";

export class Otp
{
	isSingle: boolean;
	config;

	constructor(isSingle: boolean = false, config)
	{
		this.isSingle = isSingle;
		this.config = config;
	}

	getContainer(): HTMLElement
	{
		const isInstalled = this.config.IS_ACTIVE === 'Y';
		const onclick = () => {
			EventEmitter.emit(EventEmitter.GLOBAL_TARGET, Options.eventNameSpace + ':onOpen');
			if (String(this.config.URL).length > 0)
			{
				Uri.addParam(this.config.URL, {page: 'otpConnected'});
				BX.SidePanel.Instance.open(Uri.addParam(this.config.URL, {page: 'otpConnected'}), {width: 1100});
			}
			else
			{
				console.error('Otp page is not defined. Check the component params');
			}
		};
		const button = isInstalled ?
			Tag.render`<div class="ui-qr-popupcomponentmaker__btn" style="margin-top: auto" onclick="${onclick}">${Loc.getMessage('INTRANET_USER_PROFILE_TURNED_ON')}</div>`
			:
			Tag.render`<div class="ui-qr-popupcomponentmaker__btn" style="margin-top: auto" onclick="${onclick}">${Loc.getMessage('INTRANET_USER_PROFILE_TURN_ON')}</div>`;
		const onclickHelp = () => {
			top.BX.Helper.show('redirect=detail&code=17728602');
			EventEmitter.emit(EventEmitter.GLOBAL_TARGET, Options.eventNameSpace + ':onOpen');
		};
		if (this.isSingle !== true)
		{
			return Tag.render`
				<div class="system-auth-form__item system-auth-form__scope --padding-bottom-10 ${isInstalled ? ' --active' : ''}">
					<div class="system-auth-form__item-logo">
						<div class="system-auth-form__item-logo--image --authentication"></div>
					</div>
					<div class="system-auth-form__item-container --flex --column --flex-start">
						<div class="system-auth-form__item-title --without-margin --block">
							${Loc.getMessage('INTRANET_USER_PROFILE_OTP_MESSAGE')}
							<span class="system-auth-form__icon-help --inline" onclick="${onclickHelp}"></span>
						</div>
						${isInstalled ?
							Tag.render`
								<div class="system-auth-form__item-title --link-dotted" onclick="${onclick}">${Loc.getMessage('INTRANET_USER_PROFILE_CONFIGURE')}</div>
							` : ''
						}
						<div class="system-auth-form__item-content --margin-top-auto --center --center-force">
							${button}
						</div>
					</div>
					${isInstalled ? '' : `
						<div class="system-auth-form__item-new">
							<div class="system-auth-form__item-new--title">${Loc.getMessage('INTRANET_USER_PROFILE_OTP_TITLE')}</div>
						</div>`
					}
				</div>
			`;
		}

		let menuPopup = null;
		const popupClick = (event: MouseEvent) => {
			event.stopPropagation();
			const items = [
				{
					text: Loc.getMessage('INTRANET_USER_PROFILE_CONFIGURE'),
					onclick: () => {
						menuPopup.close();
						onclick();
					}
				}
			];
			menuPopup = (menuPopup || (new Menu(`menu-otp-${Text.getRandom()}`, event.target, items, {
				className: 'system-auth-form__popup',
				angle: true,
				offsetLeft: 10,
				autoHide: true,
				events: {
					onShow: (popup) => {
						EventEmitter.emit(
							EventEmitter.GLOBAL_TARGET,
							Options.eventNameSpace + ':showOtpMenu',
							new BaseEvent({
								data: {
									popup: popup.target,
								}
							})
						);
					},
				}
			})));
			menuPopup.toggle();
		};
		return Tag.render`
				<div class="system-auth-form__item system-auth-form__scope --padding-sm-all ${isInstalled ? ' --active' : ''} --vertical --center">
					<div class="system-auth-form__item-logo --margin-bottom --center system-auth-form__item-container --flex">
						<div class="system-auth-form__item-logo--image --authentication"></div>
					</div>
					<div class="system-auth-form__item-container --flex">
						<div class="system-auth-form__item-title --light --center --s">
							${Loc.getMessage('INTRANET_USER_PROFILE_OTP_MESSAGE')}
							 <span class="system-auth-form__icon-help" onclick="${onclickHelp}"></span> 
						</div>
					</div>
					${isInstalled ? Tag.render`<div class="system-auth-form__config --absolute" onclick="${popupClick}"></div>` : ''}
					<div class="system-auth-form__item-container --flex --column --space-around">
						<div class="system-auth-form__item-content --flex --display-flex">
							${button}
						</div>
					</div>
					${isInstalled ? '' : `
						<div class="system-auth-form__item-new system-auth-form__item-new-icon --ssl">
							<div class="system-auth-form__item-new--title">${Loc.getMessage('INTRANET_USER_PROFILE_OTP_TITLE')}</div>
						</div>`
					}
				</div>
		`;
	}
}