import { Dom, Loc, Tag, Text } from 'main.core';
import { Actions, Icon } from 'ui.icon-set.api.core';
import type { DisplayStrategy } from 'crm.copilot.call-assessment-selector';

import './../styles/selector-layout.css';

export class SelectorLayout implements DisplayStrategy
{
	#container: HTMLElement;
	#titleNode: HTMLElement;
	#isLoading: boolean = false;

	constructor()
	{
		this.#container = this.#createContainer();
	}

	getTargetNode(): HTMLElement
	{
		return this.#container;
	}

	updateTitle(title: ?string): void
	{
		const text = title ?? Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_SELECTOR_CALL_CARD_REPLACEMENT_EMPTY_STATE');

		this.#titleNode.innerText = text;
		this.#titleNode.title = text;
	}

	setLoading(isLoading: boolean): void
	{
		if (this.#isLoading === isLoading)
		{
			return;
		}

		this.#isLoading = isLoading;
		Dom.toggleClass(this.#container, '--loading');
	}

	#createContainer(): HTMLElement
	{
		const chevronDownIcon = new Icon({
			icon: Actions.CHEVRON_DOWN,
			color: '#A8ADB4',
			size: 16,
		});

		const overtitle = Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_SELECTOR_CALL_CARD_REPLACEMENT_OVERTITLE');
		this.#titleNode = Tag.render`<div class="crm-copilot__call-assessment-selector-title"></div>`;

		return Tag.render`
			<div class="crm-copilot__call-assessment-selector">
				<div class="crm-copilot__call-assessment-selector-wrapper">
					<div class="crm-copilot__call-assessment-selector-body">
						<div class="crm-copilot__call-assessment-selector-overtitle">${Text.encode(overtitle)}</div>
						${this.#titleNode}
					</div>
					<div class="crm-copilot__call-assessment-selector-arrow">
						${chevronDownIcon.render()}
					</div>
				</div>
			</div>
		`;
	}
}
