import { BaseMainTool } from './base-main-tool';
import { Event, Dom } from 'main.core';
import { SalaryVacationMenu } from 'humanresources.hcmlink.salary-vacation-menu';
import { Analytics } from '../analytics';

export class SalaryVacationTool extends BaseMainTool
{
	getIconClass(): string
	{
		return '--o-favorite';
	}

	getLayout(): HTMLElement
	{
		const container = super.getLayout();

		if (this.#getMenu().isHidden())
		{
			return null;
		}

		if (this.#getMenu().isDisabled())
		{
			Dom.attr(container, 'data-hint', '');
			Dom.attr(container, 'data-hint-interactivity', '');
			Event.bind(container, 'mouseenter', () => {
				this.#getHintInstance().show(container, this.options.disabledHint);
			});

			Event.bind(container, 'mouseleave', () => {
				this.#getHintInstance().hide(container);
			});
		}

		return super.getLayout();
	}

	onClick(): void
	{
		Analytics.send(Analytics.EVENT_CLICK_SALARY);
		if (!this.#getMenu().isHidden() && !this.#getMenu().isDisabled())
		{
			this.#getMenu().show(this.getLayout());
		}
	}

	#getMenu(): SalaryVacationMenu
	{
		return this.cache.remember('salaryMenu', () => {
			return new SalaryVacationMenu();
		});
	}

	#getHintInstance(): Manager
	{
		return this.cache.remember('hint', () => {
			return BX.UI.Hint.createInstance({
				popupParameters: {
					fixed: true,
				},
			});
		});
	}

	getId(): string
	{
		return 'salary-vacation';
	}
}
