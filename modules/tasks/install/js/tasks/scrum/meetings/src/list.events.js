import {Loc, Tag, Event, Dom, Text} from 'main.core';
import {EventEmitter} from 'main.core.events';

import {CreatedEvent, CreatedEventType} from './created.event';
import {Culture} from './culture';

export class ListEvents extends EventEmitter
{
	constructor()
	{
		super();

		this.setEventNamespace('BX.Tasks.Scrum.ListEvents');

		this.listIsShown = false;

		this.todayEvent = null;

		this.node = null;
		this.listNode = null;
		this.buttonNode = null;
	}

	setTodayEvent(todayEvent: ?CreatedEventType)
	{
		this.todayEvent = todayEvent;
	}

	existsTodayEvent(): boolean
	{
		return this.todayEvent !== null;
	}

	render(listEvents: Array<CreatedEventType>): HTMLElement
	{
		const visibility = listEvents.length === 0 ? '' : '--visible';

		this.listIsShown = listEvents.length > 0 && !this.existsTodayEvent();

		this.node = Tag.render`
			<div class="tasks-scrum__widget-meetings--plan-content  ${visibility}">
				${this.renderList(listEvents)}
				${this.renderButton()}
			</div>
		`;

		return this.node;
	}

	renderList(listEvents: Array<CreatedEventType>)
	{
		const list = new Map();
		const groupedList = new Map();
		const sort = new Map();

		listEvents.forEach((event: CreatedEventType) => {
			const key = this.getFormattedDate(event.from);
			const group = groupedList.has(key) ? groupedList.get(key) : new Set();
			group.add(event);
			sort.set(key, event.from);
			groupedList.set(key, group);
		});
		const sortedMap = new Map([...sort.entries()].sort((first, second) => first[1] - second[1]));
		[...sortedMap.keys()]
			.forEach((key: string) => {
				list.set(key, groupedList.get(key));
			})
		;

		const visibility = this.existsTodayEvent() ? '' : '--visible';

		this.listNode = Tag.render`
			<div class="tasks-scrum__widget-meetings--plan ${visibility}">
				${this.renderSeparator()}
				${this.renderEvents(list)}
			</div>
			
		`;

		Event.bind(this.listNode, 'transitionend', this.onTransitionEnd.bind(this));

		return this.listNode;
	}

	renderSeparator(): ?HTMLElement
	{
		if (!this.existsTodayEvent())
		{
			return '';
		}

		return Tag.render`
			<div class="tasks-scrum__widget-meetings--header-separator">
				<span>${Loc.getMessage('TSM_PLANNING_EVENTS_TITLE')}</span>
			</div>
		`;
	}

	renderEvents(list: Map<string, Set<CreatedEventType>>): ?HTMLElement
	{
		if (list.size === 0)
		{
			return '';
		}

		return Tag.render`
			<div class="tasks-scrum__widget-meetings--timetable-wrapper">
				${
					[...list.values()].map((group: Set<CreatedEventType>, index: number) => {
						return Tag.render`
							<div class="tasks-scrum__widget-meetings--timetable-day">
								<div class="tasks-scrum__widget-meetings--timetable-title">
									${Text.encode([...list.keys()][index])}
								</div>
								${
									[...group.values()]
										.map((event: CreatedEventType) => {
											const createdEvent = new CreatedEvent();
											createdEvent.subscribe('showView', () => this.emit('showView'))
											return createdEvent.render(event)
										})
								}
							</div>
						`;
					})
				}
			</div>
		`;
	}

	renderButton(): ?HTMLElement
	{
		this.buttonNode = Tag.render`
			<div class="tasks-scrum__widget-meetings-btn-box-center ">
				<button
					class="tasks-scrum__widget-meetings--plan-btn ui-qr-popupcomponentmaker__btn --border --visible"
					data-role="toggle-list-events"
				>
					${this.getButtonText()}
				</button>
			</div>
		`;

		Event.bind(this.buttonNode, 'click', () => {
			this.listIsShown = !this.listIsShown;
			if (this.listIsShown)
			{
				this.showList();
			}
			else
			{
				this.hideList();
			}
			this.buttonNode.querySelector('button').textContent = this.getButtonText();
		});

		return this.buttonNode;
	}

	showList()
	{
		this.listNode.style.height = `${ this.listNode.scrollHeight }px`

		Dom.addClass(this.listNode, '--visible');
	}

	hideList()
	{
		this.listNode.style.height = `${ this.listNode.scrollHeight }px`;
		this.listNode.clientHeight;
		this.listNode.style.height = '0';

		Dom.removeClass(this.listNode, '--visible');
	}

	onTransitionEnd()
	{
		if (this.listNode.style.height !== '0px')
		{
			this.listNode.style.height = 'auto'
		}
	}

	getButtonText(): string
	{
		if (this.listIsShown)
		{
			return Loc.getMessage('TSM_MEETINGS_SCHEDULED_BUTTON_HIDE');
		}
		else
		{
			return Loc.getMessage('TSM_MEETINGS_SCHEDULED_BUTTON');
		}
	}

	getFormattedDate(ts: number): string
	{
		/* eslint-disable */
		return BX.date.format(Culture.getInstance().getDayMonthFormat(), ts, null, true);
		/* eslint-enable */
	}
}