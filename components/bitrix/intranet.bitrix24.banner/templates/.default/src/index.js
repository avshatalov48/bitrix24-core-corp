import { Menu } from 'main.popup';
import { Loc } from 'main.core';

export type installersForLinuxType = {
	deb: string,
	rpm: string,
};

export class Bitrix24Banner
{
	#menuLinux: ?Menu;
	static #instance: ?this;
	#installersForLinux: installersForLinuxType;

	static getInstance()
	{
		if (!this.#instance)
		{
			this.#instance = new this;
		}
		return this.#instance;
	}

	showMenuForLinux(event, target, links: installersForLinuxType): void
	{
		event.preventDefault();
		this.#installersForLinux = links;
		this.#menuLinux = (this.#menuLinux || new Menu({
			className: 'system-auth-form__popup',
			bindElement: target,
			items: [
				{
					text: Loc.getMessage('B24_BANNER_DOWNLOAD_LINUX_DEB'),
					href: this.#installersForLinux.deb,
					onclick: (element) => {
						element.close();
					}
				},
				{
					text: Loc.getMessage('B24_BANNER_DOWNLOAD_LINUX_RPM'),
					href: this.#installersForLinux.rpm,
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