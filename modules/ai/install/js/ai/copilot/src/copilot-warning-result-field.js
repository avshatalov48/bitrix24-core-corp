import { Tag, Loc, Dom, Event } from 'main.core';
import { Icon, Main as MainIconSet } from 'ui.icon-set.api.core';

import './css/copilot-warning-field.css';

export class CopilotWarningResultField
{
	#container: HTMLElement = null;

	render(expanded: boolean = false): HTMLElement
	{
		const warningIcon = new Icon({
			color: getComputedStyle(document.body).getPropertyValue('--ui-color-base-40'),
			size: 22,
			icon: MainIconSet.WARNING,
		});

		this.#container = Tag.render`
			<div class="ai__copilot_waning-field ${expanded ? '--expanded' : ''}">
				<span class="ai__copilot_waning-field-icon">
					${warningIcon.render()}
				</span>
				<span class="ai__copilot_waning-field-text">
					${Loc.getMessage('AI_COPILOT_RESULT_WARNING')}
				</span>
				${this.#renderReadMoreLink()}
			</div>
		`;

		return this.#container;
	}

	getInfoSliderContainer(): ?HTMLElement
	{
		return top.BX.Helper.getSlider()?.getContainer();
	}

	#renderReadMoreLink(): HTMLElement
	{
		const link = Tag.render`
			<span class="ai__copilot_waning-field-link">
				${Loc.getMessage('AI_COPILOT_RESULT_WARNING_MORE')}
			</span>
		`;

		Event.bind(link, 'click', () => {
			const articleCode = 20_412_666;

			if (top.BX && top.BX.Helper)
			{
				top.BX.Helper.show(`redirect=detail&code=${articleCode}`);
			}
		});

		return link;
	}

	expand(): void
	{
		Dom.addClass(this.#container, '--expanded');
	}

	collapse(): void
	{
		Dom.removeClass(this.#container, '--expanded');
	}
}
