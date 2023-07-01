import { Cache, Tag, Loc, ajax } from 'main.core';
import { Popup } from 'main.popup';
import { Button } from 'ui.buttons';
import { NotifyManager } from '../index';
import { DateTimeFormat } from 'main.date';
import { LicenseNotificationPopupOptions } from "../types/options";

export class LicenseNotificationPopup
{
	#cache = new Cache.MemoryCache();

	constructor(options: LicenseNotificationPopupOptions)
	{
		this.setOptions(options);
	}

	setOptions(options: LicenseNotificationPopupOptions): void
	{
		this.#cache.set('options', options);
	}

	getOptions(): ?LicenseNotificationPopupOptions
	{
		return this.#cache.get('options', null);
	}

	show(): void
	{
		this.#getPopup().show();
	}

	close(): void
	{
		this.#getPopup().close();
	}

	#getPopup(): Popup
	{
		return this.#cache.remember('popup', () => {
			return new Popup({
				className: 'bitrix24-notify-popup',
				width: 800,
				padding: 0,
				content: this.#getContent(),
				contentBackground: "transparent",
				overlay: true,
				closeIcon: true,
				titleBar: false,
				buttons: this.#getButtons(),
				events: {
					onShow: () => {
						ajax.runComponentAction(NotifyManager.componentName, 'setLicenseNotifyConfig', {
							mode: 'class',
							data: {
								type: this.getOptions().type,
							}
						});
					},
				}
			});
		});
	}

	#getContent(): HTMLDivElement
	{
		return this.#cache.remember('popup-content', () => {
			return Tag.render`
				<div class="bitrix24-notify-popup-inner">
					<div class="bitrix24-notify-popup-title">
						${
							this.getOptions().isExpired ?
							Loc.getMessage('INTRANET_NOTIFY_PANEL_LICENSE_NOTIFICATION_TITLE_EXPIRED', {'#DAY_MONTH#': this.#getDate()}) :
							Loc.getMessage('INTRANET_NOTIFY_PANEL_LICENSE_NOTIFICATION_TITLE', {'#DAY_MONTH#': this.#getDate()})
						}
					</div>
					<div class="bitrix24-notify-popup-block">
						${
							this.getOptions().isExpired ? 
							Loc.getMessage('INTRANET_NOTIFY_PANEL_LICENSE_NOTIFICATION_EXPIRED_DESCRIPTION_1', {'#DAY_MONTH#': this.#getDate()}) :
							Loc.getMessage('INTRANET_NOTIFY_PANEL_LICENSE_NOTIFICATION_DESCRIPTION_1')
						}
					</div>
					${this.getOptions().isPortalWithPartner ? this.#getDescriptionWithPartner() : null}
				</div>
			`;
		});
	}

	#getDate(): string
	{
		return this.#cache.remember('date', () => {
			const format = DateTimeFormat.getFormat('DAY_MONTH_FORMAT');

			if (this.getOptions().isExpired)
			{
				return DateTimeFormat.format(format, Number(this.getOptions().blockDate));
			}

			return DateTimeFormat.format(format, Number(this.getOptions().expireDate));
		});
	}

	#getDescriptionWithPartner(): HTMLDivElement
	{
		return this.#cache.remember('description-partner', () => {
			return Tag.render`
				<div class="bitrix24-notify-popup-block">
					${Loc.getMessage('INTRANET_NOTIFY_PANEL_LICENSE_NOTIFICATION_DESCRIPTION_2')}
				</div>
			`;
		});
	}

	//region Buttons
	#getButtons(): Array<Button>
	{
		return [
			this.#getRenewalButton(),
			this.getOptions().isAdmin ? this.#getPartnerButton() : null,
			this.#getMoreInformationButton(),
		];
	}

	#getPartnerButton(): ?Button
	{
		return this.#cache.remember('partner-button', () => {
			if (!this.getOptions().isPortalWithPartner)
			{
				return null;
			}

			return new Button({
				color: Button.Color.LIGHT_BORDER,
				text: Loc.getMessage('INTRANET_NOTIFY_PANEL_LICENSE_NOTIFICATION_BUTTON_PARTNER'),
				round: true,
				onclick: () => {
					window.open(this.getOptions().urlBuyWithPartner, '_self');
				}
			});
		});
	}

	#getRenewalButton(): Button
	{
		return this.#cache.remember('renewal-button', () => {
			return new Button({
				color: Button.Color.SUCCESS,
				text: Loc.getMessage('INTRANET_NOTIFY_PANEL_LICENSE_NOTIFICATION_BUTTON_RENEW_LICENSE'),
				round: true,
				onclick: () => {
					window.open(this.getOptions().urlDefaultBuy, '_blank');
				}
			});
		});
	}

	#getMoreInformationButton(): Button
	{
		return this.#cache.remember('more-button', () => {
			return new Button({
				color: Button.Color.LIGHT_BORDER,
				text: Loc.getMessage('INTRANET_NOTIFY_PANEL_LICENSE_NOTIFICATION_BUTTON_MORE'),
				round: true,
				onclick: () => {
					window.open(this.getOptions().urlArticle, '_blank');
				}
			});
		});
	}
	//endregion
}
