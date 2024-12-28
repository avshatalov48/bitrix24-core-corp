import { Loc, Tag, Dom, Event, Runtime } from 'main.core';
import { BaseCache, MemoryCache } from 'main.core.cache';
import { Widget } from './index';

export class HcmLinkSalaryVacation
{
	static #hidden: boolean = true;
	static #disabled: boolean = false;

	static #cache: BaseCache<any> = new MemoryCache();

	static #salaryVacationMenu: Object | null;

	static async load(): Promise<void>
	{
		try
		{
			const { SalaryVacationMenu } = await Runtime.loadExtension('humanresources.hcmlink.salary-vacation-menu');
			this.#salaryVacationMenu = new SalaryVacationMenu();

			await this.#salaryVacationMenu.load();

			this.#hidden = this.#salaryVacationMenu.isHidden();
			this.#disabled = this.#salaryVacationMenu.isDisabled();

		}
		catch (e) {}
	}

	static getLayout(): ?HTMLElement
	{
		if (this.#hidden)
		{
			return null;
		}

		const disabled = this.#disabled;

		return this.#cache.remember('hcmLinkSalaryVacationLayout', () => {
			const layout = Tag.render`
				<div class="system-auth-form__scope system-auth-form__hcmlink ${disabled ? '--disabled' : ''}"
					${disabled ? `data-hint="${this.#getDisabledHintHtml()}"`: ''}
					data-hint-no-icon
					data-hint-html
					data-hint-interactivity
				>
					<div class="system-auth-form__item-container --flex" style="flex-direction:row;">
						<div class="system-auth-form__item-logo">
							<div class="system-auth-form__item-logo--image --hcmlink">
								<i></i>
							</div>
						</div>
						<div class="system-auth-form__item-title">
							<span>${Loc.getMessage('INTRANET_USER_PROFILE_SIGNDOCUMENT_HCMLINK_SALARY_VACATION')}</span>
						</div>
						${HcmLinkSalaryVacation.#getMenuButton()}
					</div>
				</div>
			`;

			if (!disabled)
			{
				Dom.addClass(layout, '--clickable');
				Event.bind(layout, 'click', () => {
					this.#salaryVacationMenu?.show(this.#getMenuButton());
				});
			}

			return layout;
		});
	}

	static #getMenuButton(): HTMLElement
	{
		return this.#cache.remember('hcmLinkSalaryVacationMenuButton', () => {
			return Tag.render`
				<div class="system-auth-form__btn--hcmlink ui-icon-set --chevron-right"></div>
			`;
		});
	}

	static #getDisabledHintHtml(): string
	{
		return Loc.getMessage('INTRANET_USER_PROFILE_SIGNDOCUMENT_HCMLINK_SALARY_VACATION_DISABLED', {
			'[LINK]': `
				<a target='_self'
					onclick='(() => {
						BX.Intranet.UserProfile.Widget.getInstance()?.hide();
						BX.Helper.show(\`redirect=detail&code=23343028\`);
					})()'
					style='cursor:pointer;'
				>
			`,
			'[/LINK]': '</a>',
		});
	}

	static closeWidget(): void
	{
		Widget.getInstance()?.hide();
	}
}