import { Tag } from 'main.core';
import { Button, AirButtonStyle } from 'ui.buttons';
import { BaseSecondaryTool } from './base-secondary-tool';
import { Menu } from 'main.popup';
import { EventEmitter } from 'main.core.events';
import { Analytics } from '../analytics';

export class ApplicationsInstallerTool extends BaseSecondaryTool
{
	getIconElement(): HTMLElement
	{
		return this.cache.remember('icon', () => {
			if (this.options.mobile.installed)
			{
				return Tag.render`
					<span class="intranet-avatar-widget-secondary-tool-icons__wrapper">
						${this.#getMobileIcon()}
						<div class="intranet-avatar-widget-secondary-tool-icons__seporator"></div>
						${this.#getDesktopIcon()}
					</span>
				`;
			}

			return this.#getDesktopIcon();
		});
	}

	getActionElement(): HTMLElement
	{
		return this.cache.remember('actionElement', () => {
			if (this.options.desktop.installed && this.options.menu)
			{
				const onclick = (element) => {
					this.#getInstallMenu(element.target).toggle();
				};

				return Tag.render`
					<i onclick="${onclick}" class="ui-icon-set --more-m intranet-avatar-widget-item__more"/>
				`;
			}

			const button = (new Button({
				tag: Button.Tag.LINK,
				size: Button.Size.EXTRA_SMALL,
				text: this.options.desktop.buttonName,
				useAirDesign: true,
				style: AirButtonStyle.FILLED,
				noCaps: true,
				link: this.options.desktop.installLink,
				wide: true,
			})).render();

			return Tag.render`
				<span class="intranet-avatar-widget-secondary-tool-application__button-wrapper">
					${button}
				</span>
			`;
		});
	}

	#getMobileIcon(): HTMLElement
	{
		return this.cache.remember('mobileIcon', () => {
			let className = 'ui-icon-set --mobile-selected intranet-avatar-widget-secondary-tool-application__icon';

			if (this.options.mobile.installed)
			{
				className += ' --installed';
			}

			return Tag.render`
				<i class="${className}"/>
			`;
		});
	}

	#getDesktopIcon(): HTMLElement
	{
		return this.cache.remember('desktopIcon', () => {
			let className = 'ui-icon-set intranet-avatar-widget-secondary-tool-application__icon';

			if (this.options.desktop.installed)
			{
				className += ' --screen-selected --installed';
			}
			else
			{
				className += ' --o-screen';
			}

			return Tag.render`
				<i class="${className}"/>
			`;
		});
	}

	#getInstallMenu(bindElement: HTMLElement): Menu
	{
		return this.cache.remember('installMenu', () => {
			const items = this.#getInstallMenuItems();

			if (items.length === 0)
			{
				return null;
			}

			return new Menu({
				bindElement,
				items,
				offsetLeft: 5,
				angle: true,
				fixed: true,
			});
		});
	}

	#getInstallMenuItems(): Array
	{
		const items = [];

		this.options.menu.forEach((item) => {
			if (item.type === 'desktop')
			{
				items.push({
					text: item.title,
					href: this.options.desktop.installLink,
					onclick: () => {
						Analytics.send(Analytics.EVENT_CLICK_INSTALL_DESKTOP_APP);
						this.#getInstallMenu().close();
					},
				});
			}
			else if (item.type === 'mobile')
			{
				items.push({
					text: item.title,
					onclick: () => {
						Analytics.send(Analytics.EVENT_CLICK_INSTALL_MOBILE_APP);
						this.#getInstallMenu().close();
						EventEmitter.emit('BX.Intranet.AvatarWidget.ApplicationInstallerTool:onClick');
					},
				});
			}
		});

		return items;
	}

	onClick(): void
	{}

	getId(): string
	{
		return 'applications-installer';
	}
}
