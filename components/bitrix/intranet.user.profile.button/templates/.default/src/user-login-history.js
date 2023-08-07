import { ajax, Dom, Loc, Tag } from 'main.core';
import { Loader } from 'main.loader';
import Widget from './widget';

export default class UserLoginHistory
{
	#config: Object;
	#widget: Widget;

	constructor(config: Object, widget: Widget)
	{
		this.#config = config;
		this.#widget = widget;
	}

	#isActive(): boolean
	{
		return this.#config.isAvailableUserLoginHistory && this.#config.isConfiguredUserLoginHistory;
	}

	#isAvailable(): boolean
	{
		return this.#config.isAvailableUserLoginHistory;
	}

	#isConfigured(): boolean
	{
		return this.#config.isConfiguredUserLoginHistory;
	}

	handlerLogoutButton()
	{
		if (this.#isActive())
		{
			this.#showLogoutPopup();
		}
	}

	#getMainButton(): ?HTMLElement
	{
		const handlerLogoutButton = () => {
			this.#showLogoutPopup();
		};

		const showConfigureSlider = () => {
			this.#widget.getPopup().close();
			BX.Helper.show('redirect=detail&code=16615982');
		};

		if (this.#isActive())
		{
			return Tag.render`
				<div class="ui-qr-popupcomponentmaker__btn" onclick=\"${handlerLogoutButton}\">${Loc.getMessage('INTRANET_USER_PROFILE_HISTORY_BUTTON_LOGOUT_ALL_DEVICE')}</div>
			`;
		}
		else if(!this.#isConfigured())
		{
			return Tag.render`
				<div class='system-auth-form__settings' onclick="${showConfigureSlider}">${Loc.getMessage('INTRANET_USER_PROFILE_CONFIGURE')}</div>
			`;
		}

		return null;
	}

	#getBottomButton(handler: function): ?HTMLElement
	{
		if (this.#isActive())
		{
			return Tag.render`
				<div class="system-auth-form__item-container">
					<div class="system-auth-form__show-history" onclick="${handler}">
						${Loc.getMessage('INTRANET_USER_PROFILE_HISTORY_BUTTON_SHOW_FULL_LIST')}
					</div>
				</div>
			`;
		}

		return null;
	}

	#getLockIcon(): ?HTMLElement
	{
		const showInfoSlider = () => {
			this.#widget.getPopup().close();
			BX.UI.InfoHelper.show("limit_office_login_history");
		};

		if (!this.#isAvailable())
		{
			return Tag.render`
				<div class="system-auth-form__item-title-logo --lock" onclick="${showInfoSlider}">
					<i></i>
				</div>
			`;
		}

		return null;
	}

	getContainer(): Element
	{
		const showSliderLoginHistory = () => {
			if (this.#isActive())
			{
				this.#widget.getPopup().close();
				BX.SidePanel.Instance.open(this.#config.url, {
					allowChangeHistory: false,
				});
			}
			else if (!this.#isAvailable())
			{
				this.#widget.getPopup().close();
				BX.UI.InfoHelper.show("limit_office_login_history");
			}
		};

		let loginHistoryWidget = Tag.render`
				<div class="system-auth-form__item system-auth-form__scope --vertical">
					<div class="system-auth-form__item-container --center ${this.#isActive() ? '--border' : ''}">
						<div class="system-auth-form__item-logo">
							<div class="system-auth-form__item-logo--image ${this.#isActive() ? '--history' : '--history-gray'}" onclick="${showSliderLoginHistory}">
								<i></i>
							</div>
						</div>
						<div class="system-auth-form__item-container --center">
							<div class="system-auth-form__item-title --sm ${this.#isActive() ? '--link' : ''}" onclick="${showSliderLoginHistory}">${Loc.getMessage('INTRANET_USER_PROFILE_HISTORY_TITLE')}</div>
							${this.#getLockIcon()}
						</div>
						<div class="system-auth-form__item-content">
							${this.#getMainButton()}
						</div>
					</div>
					<div class="system-auth-form__visited">
					</div>
					${this.#getBottomButton(showSliderLoginHistory)}
				</div>
			`;

		const container = loginHistoryWidget.querySelector('.system-auth-form__visited');

		if (this.#isActive())
		{
			const loader = UserLoginHistory.#getLoader();
			loader.show(container);

			ajax.runComponentAction('bitrix:intranet.user.login.history', 'getListLastLogin', {
				mode: 'class',
				data: {
					limit: 1,
				},
			}).then((response) => {
				loader.hide();
				const devices = response.data;
				const keys = Object.keys(devices);
				keys.forEach((key) => {
					const description = UserLoginHistory.#prepareDescriptionLoginHistory(devices[key]['DEVICE_PLATFORM'],
						devices[key]['GEOLOCATION'], devices[key]['BROWSER']);
					const time = UserLoginHistory.#prepareDateTimeForLoginHistory(devices[key]['LOGIN_DATE']);
					const device = Tag.render`
						<div class="system-auth-form__visited-item">
							<div data-hint='${Loc.getMessage('INTRANET_USER_PROFILE_HISTORY_BUTTON_LOGOUT_THIS_DEVICE_DESCRIPTION')}' class="system-auth-form__visited-icon --${devices[key]['DEVICE_TYPE']}" onclick="${showSliderLoginHistory}" data-hint-no-icon></div>
							<script>
								BX.ready(() => {
									BX.UI.Hint.init(document.querySelector(".system-auth-form__visited-icon --${devices[key]['DEVICE_TYPE']}"));
								})
							</script>
							<div class="system-auth-form__visited-text" onclick="${showSliderLoginHistory}">${description}</div>
							<div class="system-auth-form__visited-time" onclick="${showSliderLoginHistory}">${time}</div>
						</div>
					`;
					Dom.append(device, container);
				});
			}).catch(() => {
				loader.hide();
			});
		}

		return loginHistoryWidget;
	}

	#showLogoutPopup(): void
	{
		BX.UI.Dialogs.MessageBox.show({
			message: Loc.getMessage('INTRANET_USER_PROFILE_HISTORY_BUTTON_LOGOUT_ALL_DEVICE_WITHOUT_THIS_MESSAGE'),
			title: Loc.getMessage('INTRANET_USER_PROFILE_HISTORY_BUTTON_LOGOUT_ALL_DEVICE_TITLE'),
			buttons: BX.UI.Dialogs.MessageBoxButtons.YES_CANCEL,
			minWidth: 400,
			popupOptions: {
				contentBackground: 'transparent',
				autoHide: true,
				closeByEsc: true,
				padding: 0,
				background: '',
				events: {
					onShow: () => {
						this.#widget.getPopup().getPopup().setAutoHide(false);
					},
					onPopupClose: () => {
						this.#widget.getPopup().getPopup().setAutoHide(true);
					},
				}
			},
			onYes: (messageBox) => {
				ajax.runComponentAction('bitrix:intranet.user.profile.password', 'logout', {
					mode: 'ajax',
				}).then(() => {
					messageBox.close();
					BX.UI.Notification.Center.notify({
						content: Loc.getMessage('INTRANET_USER_PROFILE_HISTORY_BUTTON_LOGOUT_ALL_DEVICE_WITHOUT_THIS_RESULT'),
						autoHideDelay: 1800,
					});
				}).catch(() => {
					messageBox.close();
					BX.UI.Notification.Center.notify({
						content: Loc.getMessage('INTRANET_USER_PROFILE_HISTORY_BUTTON_LOGOUT_ALL_DEVICE_ERROR'),
						autoHideDelay: 3600,
					});
				});
			},
		});
	}

	static #prepareDescriptionLoginHistory(deviceType: string, geolocation: string, browser: string): string
	{
		const arrayDescription = [];

		if(browser)
		{
			arrayDescription.push(browser);
		}

		if(geolocation)
		{
			arrayDescription.push(geolocation);
		}

		if(deviceType)
		{
			arrayDescription.push(deviceType);
		}

		return arrayDescription.join(', ');
	}

	static #getLoader(): Loader
	{
		return new Loader({
			size: 20,
			mode: 'inline',
		});
	}

	static #prepareDateTimeForLoginHistory(dateTime: string): string
	{
		const format = [
			['-', 'd.m.Y H:i:s'],
			['s', 'sago'],
			['i', 'iago'],
			['H', 'Hago'],
			['d', 'dago'],
			['m', 'mago'],
		];
		return ' - ' + BX.date.format(format, new Date(dateTime), new Date());
	}
}