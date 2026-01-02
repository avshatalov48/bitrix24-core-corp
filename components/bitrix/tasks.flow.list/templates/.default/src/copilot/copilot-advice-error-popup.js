import { Event, Loc, Tag } from 'main.core';
import { Popup } from 'main.popup';
import { CopilotAdvice } from 'tasks.flow.copilot-advice';

export const CopilotAdviceErrorTypes = Object.freeze({
	NotEnoughTasks: 'notEnoughTasks',
	UnexpectedError: 'unexpectedError',
	AdviceFetching: 'adviceFetching',
});

type CopilotAdviceErrorType = $Values<typeof CopilotAdviceErrorTypes>;

type ErrorMessageCodes =
{
	titleCode: string,
	descriptionCode: string,
};

export class CopilotAdviceErrorPopup
{
	static show(bindElement: HTMLElement, errorType: ?CopilotAdviceErrorType = null): void
	{
		const messages = this.getMessagesByErrorType(errorType);
		const { root: popupContent, exampleLink } = Tag.render`
			<div class="tasks-flow__copilot-advice-error-popup">
				<div class="tasks-flow__copilot-advice-error-popup-title">
					<span class="tasks-flow__copilot-advice-error-popup-icon ui-icon-set --copilot-ai"/>
					<span class="tasks-flow__copilot-advice-error-popup-title-text">
						${Loc.getMessage(messages.titleCode)}
					</span>
				</div>
				<div class="tasks-flow__copilot-advice-error-popup-description">
					${Loc.getMessage(messages.descriptionCode)}
				</div>
				<div class="tasks-flow__copilot-advice-error-popup-example">
					<span class="tasks-flow__copilot-advice-error-popup-example-text" ref="exampleLink">
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

	static getMessagesByErrorType(errorType: ?CopilotAdviceErrorType = null): ErrorMessageCodes
	{
		let titleCode = '';
		let descriptionCode = '';

		switch (errorType)
		{
			case CopilotAdviceErrorTypes.UnexpectedError: {
				titleCode = 'TASKS_FLOW_LIST_COPILOT_UNEXPECTED_ERROR_POPUP_TITLE';
				descriptionCode = 'TASKS_FLOW_LIST_COPILOT_UNEXPECTED_ERROR_POPUP_DESCRIPTION';

				break;
			}

			case CopilotAdviceErrorTypes.AdviceFetching: {
				titleCode = 'TASKS_FLOW_LIST_COPILOT_ADVICE_FETCHING_POPUP_TITLE';
				descriptionCode = 'TASKS_FLOW_LIST_COPILOT_ADVICE_FETCHING_POPUP_DESCRIPTION';

				break;
			}

			case CopilotAdviceErrorTypes.NotEnoughTasks:
			default: {
				titleCode = 'TASKS_FLOW_LIST_COPILOT_NOT_ENOUGH_TASKS_POPUP_TITLE';
				descriptionCode = 'TASKS_FLOW_LIST_COPILOT_NOT_ENOUGH_TASKS_POPUP_DESCRIPTION';

				break;
			}
		}

		return {
			titleCode,
			descriptionCode,
		};
	}
}
