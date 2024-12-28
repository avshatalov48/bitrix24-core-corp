import { Event, Loc, Tag } from 'main.core';
import { Popup } from 'main.popup';
import { CopilotAdvice } from 'tasks.flow.copilot-advice';

export class NotEnoughTasksPopup
{
	static show(bindElement: HTMLElement): void
	{
		const { root: popupContent, exampleLink } = Tag.render`
			<div class="tasks-flow__not-enough-tasks-popup">
				<div class="tasks-flow__not-enough-tasks-popup-title">
					<span class="tasks-flow__not-enough-tasks-popup-icon ui-icon-set --copilot-ai"/>
					<span class="tasks-flow__not-enough-tasks-popup-title-text">
						${Loc.getMessage('TASKS_FLOW_LIST_COPILOT_NOT_ENOUGH_TASKS_POPUP_TITLE')}
					</span>
				</div>
				<div class="tasks-flow__not-enough-tasks-popup-description">
					${Loc.getMessage('TASKS_FLOW_LIST_COPILOT_NOT_ENOUGH_TASKS_POPUP_DESCRIPTION')}
				</div>
				<div class="tasks-flow__not-enough-tasks-popup-example">
					<span class="tasks-flow__not-enough-tasks-popup-example-text" ref="exampleLink">
						${Loc.getMessage('TASKS_FLOW_LIST_COPILOT_NOT_ENOUGH_TASKS_POPUP_SHOW_EXAMPLE')}
					</span>
				</div>
			</div>
		`;

		const popup = new Popup({
			bindElement,
			content: popupContent,
			cacheable: false,
			autoHide: true,
			minWidth: 270,
			width: 270,
			padding: 12,
			angle: {
				position: 'top',
				offset: 30,
			},
		});

		Event.bind(exampleLink, 'click', () => {
			CopilotAdvice.showExample();
			popup?.close();
		});

		popup.show();
	}
}
