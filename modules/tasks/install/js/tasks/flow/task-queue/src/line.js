import { Tag } from 'main.core';

import { Member, MemberData } from './member';

export type LineData = {
	serial: number,
	createdBy: number,
	creator: MemberData,
	responsibleId: number,
	responsible: MemberData,
	timeInStatus: {
		formatted: string,
	},
}

import './css/line.css';

export class Line
{
	#serial: number;
	#createdBy: number;
	#creator: Member;
	#responsibleId: number;
	#responsible: Member;
	#timeInStatus: string;

	constructor(lineData: LineData)
	{
		this.#serial = parseInt(lineData.serial, 10);
		this.#createdBy = parseInt(lineData.createdBy, 10);
		this.#creator = new Member(lineData.creator);
		this.#responsibleId = parseInt(lineData.responsibleId, 10);
		this.#responsible = new Member(lineData.responsible);
		this.#timeInStatus = lineData.timeInStatus.formatted;
	}

	render(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-flow__task-queue-line">
				<div class="tasks-flow__task-queue-line_number">${this.#serial}</div>
				<div class="tasks-flow__task-queue-line_avatar">${this.#creator.render()}</div>
				<div class="ui-icon-set --chevron-right" style="--ui-icon-set__icon-size: 18px;"></div>
				<div class="tasks-flow__task-queue-line_avatar">${this.#responsible.render()}</div>
				<div class="tasks-flow__task-queue-line_time" title="${this.#timeInStatus}">${this.#timeInStatus}</div>
			</div>
		`;
	}
}
