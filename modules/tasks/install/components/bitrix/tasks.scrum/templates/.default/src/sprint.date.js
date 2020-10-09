import {Event, Loc, Tag, Text} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {Sprint} from './sprint';

export class SprintDate extends EventEmitter
{
	constructor(sprint: Sprint)
	{
		super(sprint);

		this.setEventNamespace('BX.Tasks.Scrum.SprintDate');

		this.sprint = sprint;

		this.nodeId = 'tasks-scrum-sprint-header-date-' + Text.getRandom();
		this.defaultSprintDuration = sprint.getDefaultSprintDuration();
	}

	createDate(startTimestamp, endTimestamp)
	{
		if (this.sprint.isActive() || this.sprint.isCompleted())
		{
			return '';
		}

		/* eslint-disable */
		const dateStart = BX.date.format('j F', startTimestamp);
		const dateEnd = BX.date.format('j F', endTimestamp);
		/* eslint-enable */

		return Tag.render`
			<div id="${this.nodeId}" class="tasks-scrum-sprint-date">
				<div class="tasks-scrum-sprint-date-start">${dateStart}</div>
				<div class="tasks-scrum-sprint-date-separator">-</div>
				<div class="tasks-scrum-sprint-date-end">${dateEnd}</div>
				<input type="hidden" name="dateStart">
				<input type="hidden" name="dateEnd">
			</div>
		`;
	}

	updateDateStartNode(timestamp)
	{
		const dateStartNode = this.node.querySelector('.tasks-scrum-sprint-date-start');
		dateStartNode.textContent = BX.date.format('j F', timestamp);
	}

	updateDateEndNode(timestamp)
	{
		const dateEndNode = this.node.querySelector('.tasks-scrum-sprint-date-end');
		dateEndNode.textContent = BX.date.format('j F', timestamp);
	}

	onAfterAppend()
	{
		if (this.sprint.isActive() || this.sprint.isCompleted())
		{
			return;
		}

		this.node = document.getElementById(this.nodeId);
		const parentPopup = this.node.closest('.popup-window');

		const customBlur = () => {
			BX.calendar.get().popup.close();
		};

		const showCalendar = (node, field) => {
			/* eslint-disable */
			BX.calendar({
				node: node,
				field: field,
				bTime: false,
				bSetFocus: false,
				bHideTime: false
			});
			/* eslint-enable */
			if (parentPopup)
			{
				Event.bindOnce(parentPopup, 'click', customBlur);
			}
		};

		const updateDateNode = (node, value) => {
			/* eslint-disable */
			node.textContent = BX.date.format('j F', Math.floor(BX.parseDate(value).getTime() / 1000));
			/* eslint-enable */
		};
		const sendRequest = (data) => {
			this.emit('changeSprintDeadline', data);
		};

		const dateStartNode = this.node.querySelector('.tasks-scrum-sprint-date-start');
		const dateEndNode = this.node.querySelector('.tasks-scrum-sprint-date-end');
		const dateStartInput = this.node.querySelector('input[name="dateStart"]');
		const dateEndInput = this.node.querySelector('input[name="dateEnd"]');

		Event.bind(this.node, 'click', (event) => {
			const target = event.target;
			if (target.classList.contains('tasks-scrum-sprint-date-start'))
			{
				showCalendar(target, dateStartInput);
			}
			else if (target.classList.contains('tasks-scrum-sprint-date-end'))
			{
				showCalendar(target, dateEndInput);
			}
			event.stopPropagation();
		});
		Event.bind(dateStartInput, 'change', (event) => {
			const value = event.target.value;
			updateDateNode(dateStartNode, value);
			sendRequest({
				sprintId: this.sprint.getId(),
				dateStart: Math.floor(BX.parseDate(value).getTime() / 1000)
			});
			if (parentPopup)
			{
				Event.unbind(parentPopup, 'click', customBlur);
			}
		});
		Event.bind(dateEndInput, 'change', (event) => {
			const value = event.target.value;
			updateDateNode(dateEndNode, value);
			sendRequest({
				sprintId: this.sprint.getId(),
				dateEnd: Math.floor(BX.parseDate(value).getTime() / 1000)
			});
			if (parentPopup)
			{
				Event.unbind(parentPopup, 'click', customBlur);
			}
		});
	}

	getWeeks()
	{
		const weekCount = (parseInt(this.defaultSprintDuration, 10) / 604800);
		if (weekCount > 5)
		{
			return weekCount + ' ' + Loc.getMessage('TASKS_SCRUM_DATE_WEEK_NAME_3');
		}
		else if (weekCount === 1)
		{
			return weekCount + ' ' + Loc.getMessage('TASKS_SCRUM_DATE_WEEK_NAME_1');
		}
		else
		{
			return weekCount + ' ' + Loc.getMessage('TASKS_SCRUM_DATE_WEEK_NAME_2');
		}
	}
}