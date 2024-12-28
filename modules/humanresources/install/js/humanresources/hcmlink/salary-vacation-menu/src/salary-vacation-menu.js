import { ajax, Dom, Type, Event, Loc } from 'main.core';
import { BaseCache, MemoryCache } from 'main.core.cache';
import { Menu } from 'main.popup';

type Settings = {
	show: boolean,
	disabled: ?boolean,
	options?: Array<Option>,
};

type Option = {
	appId: number,
	id: number,
	code: string,
	title: string,
	options: { bx24_width?: number },
};

type Mode = 'profile-menu' | 'ui-btn';

export class SalaryVacationMenu
{
	#settings: Settings | null = null;
	#cache: BaseCache<any> = new MemoryCache();
	#mode: Mode;

	#button: HTMLElement | null = null;

	constructor(mode: Mode = 'profile-menu')
	{
		this.#mode = mode;
	}

	async load(): Promise<void>
	{
		if (Type.isObject(this.#settings))
		{
			return;
		}

		this.#settings = await ajax.runAction('humanresources.hcmlink.placement.loadSalaryVacation')
			.then((response) => {
				return response.data;
			})
			.catch(() => {
				return {
					show: false
				};
			})
		;
	}

	isHidden(): boolean
	{
		return this.#settings?.show !== true;
	}

	isDisabled(): boolean
	{
		return this.#settings?.disabled === true;
	}

	show(bindElement: HTMLElement): void
	{
		if (this.isHidden() || this.isDisabled())
		{
			return;
		}

		this.#getMenu().getPopupWindow().setBindElement(bindElement);
		this.#getMenu().show();
		this.#getMenu().getPopupWindow().adjustPosition();
	}

	#getMenu(): Menu
	{
		return this.#cache.remember('hcmLinkSalaryVacationMenu', () => {
			const items = this.#settings.options.map(
				(option: Option) => {
					return {
						text: option.title,
						onclick: (event, item): void =>  {
							menu.close();
							this.#openApplication(option);
						}
					}
				}
			);

			const menu = new Menu({
				id: 'hcmLink-vacation-salary-menu',
				items,
				autoHide: true,
			});

			return menu;
		})
	}

	bindButton(button: HTMLElement): SalaryVacationMenu
	{
		// Only 'ui-btn' mode is supported
		if (this.#mode !== 'ui-btn')
		{
			return;
		}

		this.#button = button;

		if (!this.isHidden() && !this.isDisabled())
		{
			Event.bind(this.#button, 'click', () => {
				this.show(this.#button);
			})
		}

		if (this.isDisabled())
		{
			Dom.addClass(this.#button, 'ui-btn-disabled');
			this.#attachHintToButton(this.#button);
		}

		return this;
	}

	setSettings(settings: Settings): SalaryVacationMenu
	{
		this.#settings = settings;

		return this;
	}

	#openApplication(option: Option): void
	{
		BX.rest.AppLayout.openApplication(
			option.appId,
			option.options,
			{ PLACEMENT: option.code, PLACEMENT_ID: option.id },
		);
	}

	#attachHintToButton(button: HTMLElement): void
	{
		this.#button.setAttribute('data-hint', this.#getDisabledHintHtml());
		Dom.attr(this.#button, 'data-hint-no-icon', 'y');
		Dom.attr(this.#button, 'data-hint-html', 'y');
		Dom.attr(this.#button, 'data-hint-interactivity', 'y');

		if (BX.UI.Hint)
		{
			BX.UI.Hint.init(button.parentElement);
		}
	}

	#getDisabledHintHtml(): string
	{
		return Loc.getMessage('HUMANRESOURCES_HCMLINK_SALARY_VACATION_MENU_DISABLED_HINT', {
			'[LINK]': `
				<a target='_self'
					onclick='(() => {
						BX.Helper.show(\`redirect=detail&code=23343028\`);
					})()'
					style='cursor:pointer;'
				>
			`,
			'[/LINK]': '</a>',
		});
	}
}
