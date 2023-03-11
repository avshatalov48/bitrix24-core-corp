import {Event, Loc, Tag, Text} from 'main.core';
import {EventEmitter} from 'main.core.events';

import {Sprint} from '../sprint';
import {Culture} from '../../../utility/culture';

export class Date extends EventEmitter
{
	constructor(sprint: Sprint)
	{
		super(sprint);

		this.setEventNamespace('BX.Tasks.Scrum.Sprint.Header.Date');

		this.sprint = sprint;

		this.node = null;
	}

	render(): ?HTMLElement
	{
		if (this.sprint.isActive() || this.sprint.isCompleted())
		{
			return '';
		}

		this.node = Tag.render`
			<div class="tasks-scrum__sprint--date-container">
				<div class="tasks-scrum__sprint--date --start">${Date.getFormattedDateStart(this.sprint)}</div>
				<div class="tasks-scrum__sprint--date-separator"> - </div>
				<div class="tasks-scrum__sprint--date --end">${Date.getFormattedDateEnd(this.sprint)}</div>
				<input type="hidden" name="dateStart" value="${Text.encode(this.sprint.getDateStartFormatted())})">
				<input type="hidden" name="dateEnd" value="${Text.encode(this.sprint.getDateEndFormatted())}">
			</div>
		`;

		this.bindEvents();

		return this.node;
	}

	getNode(): ?HTMLElement
	{
		return this.node;
	}

	bindEvents()
	{
		if (this.sprint.isActive() || this.sprint.isCompleted())
		{
			return;
		}

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
			node.textContent = BX.date.format(
				Culture.getInstance().getDayMonthFormat(),
				Math.floor(BX.parseDate(value).getTime() / 1000)
			);
			/* eslint-enable */
		};
		const sendRequest = (data) => {
			this.emit('changeSprintDeadline', data);
		};

		const dateStartNode = this.node.querySelector('.--start');
		const dateEndNode = this.node.querySelector('.--end');
		const dateStartInput = this.node.querySelector('input[name="dateStart"]');
		const dateEndInput = this.node.querySelector('input[name="dateEnd"]');

		Event.bind(this.node, 'click', (event) => {
			const target = event.target;
			if (target.classList.contains('--start'))
			{
				showCalendar(target, dateStartInput);
			}
			else if (target.classList.contains('--end'))
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
		const weekCount = (parseInt(this.sprint.getDefaultSprintDuration(), 10) / 604800);

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

	static getFormattedTitleDatePeriod(sprint: Sprint): string
	{
		return Date.getFormattedDateStart(sprint) + ' - ' + Date.getFormattedDateEnd(sprint);
	}

	static getFormattedDateStart(sprint: Sprint): string
	{
		/* eslint-disable */
		return BX.date.format(Culture.getInstance().getDayMonthFormat(), sprint.getDateStart(), null, true);
		/* eslint-enable */
	}

	static getFormattedDateEnd(sprint: Sprint): string
	{
		/* eslint-disable */
		return BX.date.format(Culture.getInstance().getDayMonthFormat(), sprint.getDateEnd(), null, true);
		/* eslint-enable */
	}
}