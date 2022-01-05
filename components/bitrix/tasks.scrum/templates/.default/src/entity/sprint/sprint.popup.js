import {Popup} from 'main.popup';
import {Event, Loc, Tag, Text} from 'main.core';
import {Button} from 'ui.buttons';

export class SprintPopup
{
	constructor(options)
	{
		this.sprints = options.sprints;
	}

	showCompletePopup(sprint)
	{
		return new Promise((resolve, reject) => {

			const popupId = 'tasks-scrum-complete-sprint' + Text.getRandom();

			const moveSelectId = 'tasks-scrum-sprint-complete-popup-move-select';

			const moveSprintsBlockId = 'tasks-scrum-sprint-complete-popup-move-sprints-block';
			const moveSprintSelectId = 'tasks-scrum-sprint-complete-popup-move-sprint-select';

			const getPopupContent = () => {
				const moveSprint = () => {
					let listSprintsOptions = '';
					this.sprints.forEach((sprint) => {
						if (sprint.isPlanned())
						{
							listSprintsOptions += `<option value="${sprint.getId()}">${sprint.getName()}</option>`;
						}
					});
					return Tag.render`
						<div id="${moveSprintsBlockId}" class="tasks-scrum-sprint-complete-popup-move-sprint">
							<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
								<div class="ui-ctl-after ui-ctl-icon-angle"></div>
								<select id="${moveSprintSelectId}" class="ui-ctl-element">
									<option value="0">
										${Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_POPUP_NEW_SPRINT')}
									</option>
									${listSprintsOptions}
								</select>
							</div>
						</div>
					`;
				};

				return Tag.render`
					<div class="tasks-scrum-sprint-complete-popup">
						<div class="tasks-scrum-sprint-complete-popup-result">
							<div class="tasks-scrum-sprint-complete-popup-result-header">
								${Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_POPUP_RESULT_HEADER')}
							</div>
							<div class="tasks-scrum-sprint-complete-popup-result-completed">
								${
									Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_POPUP_RESULT_COMPLETED')
										.replace('#tasks#', this.getTasksCountLabel(sprint.getCompletedTasks()))
										.replace('#storyPoints#', sprint.getCompletedStoryPoints())
								}
							</div>
							<div class="tasks-scrum-sprint-complete-popup-result-uncompleted">
								${
									Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_POPUP_RESULT_UNCOMPLETED')
										.replace('#tasks#', this.getTasksCountLabel(sprint.getUnCompletedTasks()))
										.replace('#storyPoints#', sprint.getUnCompletedStoryPoints())
								}
							</div>
						</div>
						<div class="tasks-scrum-sprint-complete-popup-actions">
							<div class="tasks-scrum-sprint-complete-popup-move-header">
								${Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_POPUP_MOVE_HEADER')}
							</div>
							<div class="tasks-scrum-sprint-complete-popup-move-select">
								<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
									<div class="ui-ctl-after ui-ctl-icon-angle"></div>
									<select id="${moveSelectId}" class="ui-ctl-element">
										<option value="backlog">
											${Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_POPUP_MOVE_SELECTOR_BACKLOG')}
										</option>
										<option value="sprint">
											${Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_POPUP_MOVE_SELECTOR_SPRINT')}
										</option>
									</select>
								</div>
							</div>
							${moveSprint()}
						</div>
					</div>
				`;
			};

			const popup = new Popup(popupId,
				null,
				{
					width: 360,
					autoHide: true,
					closeByEsc: true,
					offsetTop: 0,
					offsetLeft: 0,
					closeIcon: true,
					draggable: true,
					resizable: false,
					lightShadow: true,
					cacheable: false,
					titleBar: Loc.getMessage('TASKS_SCRUM_SPRINT_COMPLETE_TITLE_POPUP').
						replace('#name#', sprint.getName()),
					content: getPopupContent(),
					buttons: [
						new Button({
							text: Loc.getMessage('TASKS_SCRUM_SPRINT_TITLE_COMPLETE_BUTTON'),
							color: Button.Color.SUCCESS,
							events: {
								click: () => {
									const moveSelect = document.getElementById(moveSelectId);
									const moveSprintSelect = document.getElementById(moveSprintSelectId);
									resolve({
										sprintId: sprint.getId(),
										direction: moveSelect.value,
										targetSprint: moveSprintSelect.value
									});
								}
							}
						}),
						new Button({
							text: Loc.getMessage('TASKS_SCRUM_SPRINT_START_BUTTON_CANCEL_POPUP'),
							color: Button.Color.LINK,
							events: {
								click: () => popup.close()
							}
						}),
					]
				});

			popup.show();

			Event.bind(document.getElementById(moveSelectId), 'change', (event) => {
				if (event.target.value === 'sprint')
				{
					document.getElementById(moveSprintsBlockId).style.display = 'block';
				}
				else
				{
					document.getElementById(moveSprintsBlockId).style.display = 'none';
				}
			});
		});
	}

	getTasksCountLabel(count)
	{
		if (count > 5)
		{
			return count + ' ' + Loc.getMessage('TASKS_SCRUM_TASK_LABEL_3');
		}
		else if (count === 1)
		{
			return count + ' ' + Loc.getMessage('TASKS_SCRUM_TASK_LABEL_1');
		}
		else
		{
			return count + ' ' + Loc.getMessage('TASKS_SCRUM_TASK_LABEL_2');
		}
	}
}