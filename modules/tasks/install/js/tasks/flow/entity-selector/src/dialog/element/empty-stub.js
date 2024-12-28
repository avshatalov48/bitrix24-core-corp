import { Tag, Loc } from 'main.core';
import { BaseStub, Tab } from 'ui.entity-selector';

import '../../css/empty-stub.css';

export class EmptyStub extends BaseStub
{
	#showArrow: boolean = true;

	constructor(tab: Tab, options: { [option: string]: any })
	{
		super(tab, options);

		this.#showArrow = options.showArrow;
	}

	render(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-flow__stub-container">
			    <div class="tasks-flow__stub-title">
			        ${Loc.getMessage('TASKS_FLOW_ENTITY_SELECTOR_STUB_TITLE')}
			    </div>
			    <div class="tasks-flow__stub-icon"></div>
			    <div class="tasks-flow__stub-subtitle-container">
			    	${this.#showArrow ? this.#renderArrow() : ''}
			    	<div class="tasks-flow__stub-subtitle-text">
				 		${Loc.getMessage(
							'TASKS_FLOW_ENTITY_SELECTOR_STUB_SUBTITLE',
							{
								'[helpdesklink]': '<a class="tasks-flow__stub-link" href="javascript:top.BX.Helper.show(\'redirect=detail&code=21307026\');">',
								'[/helpdesklink]': '</a>',
							},
						)}
					</div>
			    </div>
			</div>
		`;
	}

	#renderArrow(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-flow__stub-subtitle-arrow"></div>
		`;
	}
}
