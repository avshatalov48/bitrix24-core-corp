import { Menu } from 'main.popup';
import { Loc } from 'main.core';

export class Bitrix24Banner
{
	#menuLinux: ?Menu;
	static #instance: ?this;
	#typesInstallersForLinux = {
		'DEB': {
			text: Loc.getMessage('B24_BANNER_DOWNLOAD_LINUX_DEB'),
			href: 'https://dl.bitrix24.com/b24/bitrix24_desktop.deb',
		},
		'RPM': {
			text: Loc.getMessage('B24_BANNER_DOWNLOAD_LINUX_RPM'),
			href: 'https://dl.bitrix24.com/b24/bitrix24_desktop.rpm',
		},
	};

	static getInstance()
	{
		if (!this.#instance)
		{
			this.#instance = new this;
		}
		return this.#instance;
	}

	showMenuForLinux(event, target): void
	{
		event.preventDefault();
		this.#menuLinux = (this.#menuLinux || new Menu({
			className: 'system-auth-form__popup',
			bindElement: target,
			items: [
				{
					text: this.#typesInstallersForLinux.DEB.text,
					href: this.#typesInstallersForLinux.DEB.href,
					onclick: (element) => {
						element.close();
					}
				},
				{
					text: this.#typesInstallersForLinux.RPM.text,
					href: this.#typesInstallersForLinux.RPM.href,
					onclick: (element) => {
						element.close();
					}
				},

			],
			angle: true,
			offsetLeft: 40,
		}));
		this.#menuLinux.toggle();
	}
}