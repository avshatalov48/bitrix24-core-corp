import {Loc, Tag, Event, Text} from 'main.core';
import {EventEmitter} from 'main.core.events';

import {Culture} from './culture';

export type CreatedEventType = {
	id: number,
	name: string,
	color: string,
	from: number,
	to: number,
	repeatable: boolean
}

export class CreatedEvent extends EventEmitter
{
	constructor()
	{
		super();

		this.setEventNamespace('BX.Tasks.Scrum.CreatedEvent');

		this.node = null;
	}

	render(event: ?CreatedEventType): ?HTMLElement
	{
		if (event === null)
		{
			return '';
		}

		event.color = (event.color === '' ? '#86b100' : event.color);

		const colorBorder = this.convertHexToRGBA(event.color, 0.5);
		const colorBackground = this.convertHexToRGBA(event.color, 0.15);

		this.node = Tag.render`
			<div
				class="tasks-scrum__widget-meetings--timetable-content"
				style="background: ${colorBackground}; --meetings-border-color: ${colorBorder};"
			>
				<div class="tasks-scrum__widget-meetings--timetable-navigation">
					<div class="tasks-scrum__widget-meetings--timetable-time">
						${this.getFormattedTime(event.from, event.to)}
					</div>
					${this.renderVideoCallButton()}
				</div>
				<div class="tasks-scrum__widget-meetings--timetable-name">${Text.encode(event.name)}</div>
				${event.repeatable ? this.renderRepetition() : ''}
			</div>
		`;

		Event.bind(this.node, 'click', this.openViewCalendarSidePanel.bind(this, event));

		return this.node;
	}

	openViewCalendarSidePanel(event: CreatedEventType)
	{
		new window.top.BX.Calendar.SliderLoader(
			event.id,
			{
				entryDateFrom: new Date(event.from * 1000)
			}
		).show();

		this.emit('showView');
	}

	renderVideoCallButton(): ?HTMLElement
	{
		return '';

		const videoCallUiClasses = 'ui-btn-split ui-btn-light-border ui-btn-xs ui-btn-light ui-btn-no-caps';

		return Tag.render`
			<div class="tasks-scrum__widget-meetings--timetable-video-call ${videoCallUiClasses}">
				<button class="ui-btn-main">
					${Loc.getMessage('TSM_VIDEO_CALL_BUTTON')}
				</button>
				<div class="ui-btn-menu"></div>
			</div>
		`;
	}

	renderRepetition(): ?HTMLElement
	{
		return Tag.render`
			<div class="tasks-scrum__widget-meetings--timetable-repetition">
				<i class="tasks-scrum__widget-meetings--timetable-repetition-icon"></i>
				<span>${Loc.getMessage('TSM_REPETITION_TITLE')}</span>
			</div>
		`;
	}

	convertHexToRGBA(hexCode, opacity)
	{
		let hex = hexCode.replace('#', '');

		if (hex.length === 3)
		{
			hex = `${hex[0]}${hex[0]}${hex[1]}${hex[1]}${hex[2]}${hex[2]}`;
		}

		const r = parseInt(hex.substring(0, 2), 16);
		const g = parseInt(hex.substring(2, 4), 16);
		const b = parseInt(hex.substring(4, 6), 16);

		return `rgba(${r},${g},${b},${opacity})`;
	}

	getFormattedTime(from: number, to: number): string
	{
		/* eslint-disable */
		return `${BX.date.format(Culture.getInstance().getShortTimeFormat(), from, null, true)
			} - ${BX.date.format(Culture.getInstance().getShortTimeFormat(), to, null, true)}`
		;
		/* eslint-enable */
	}
}