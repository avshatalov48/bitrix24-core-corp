import { Tag, Text } from 'main.core';
import { SidePanel } from 'main.sidepanel';
import { Member, MemberData } from './member';

export type LineData = {
	serial: number,
	title: string;
	url: string;
	createdBy: number,
	creator: MemberData,
	responsibleId: number,
	responsible: MemberData,
	timeInStatus: {
		formatted: string,
	},
	canRead: boolean,
}

import './css/line.css';

export class Line
{
	#serial: number;
	#title: string;
	#url: string;
	#createdBy: number;
	#creator: Member;
	#responsibleId: number;
	#responsible: Member;
	#timeInStatus: string;
	#canRead: boolean;

	constructor(lineData: LineData)
	{
		this.#serial = parseInt(lineData.serial, 10);
		this.#createdBy = parseInt(lineData.createdBy, 10);
		this.#creator = new Member(lineData.creator);
		this.#responsibleId = parseInt(lineData.responsibleId, 10);
		this.#responsible = new Member(lineData.responsible);
		this.#timeInStatus = lineData.timeInStatus.formatted;
		this.#title = lineData.title;
		this.#url = lineData.url;
		this.#canRead = Boolean(lineData.canRead);
	}

	render(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-flow__task-queue-line-container">
				<div class="tasks-flow__task-queue-line">
					${this.#getSerialElement()}
					${this.#getTitleElement()}
					<div class="tasks-flow__task-queue-line_time" title="${this.#timeInStatus}">${this.#timeInStatus}</div>
				</div>
				
				<div class="tasks-flow__task-queue-line-avatars">
					<div class="tasks-flow__task-queue-line_avatar">${this.#creator.render()}</div>
					<div class="ui-icon-set --chevron-right" style="--ui-icon-set__icon-size: 14px; color: #A8ADB4"></div>
					<div class="tasks-flow__task-queue-line_avatar">${this.#responsible.render()}</div>
				</div>
			</div>
		`;
	}

	#getTitleElement(): HTMLElement
	{
		return Tag.render`
			<div class="${this.#getTitleClass()}" onclick="${this.#openTask.bind(this)}">${Text.encode(this.#title)}</div>
		`;
	}

	#getSerialElement(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-flow__task-queue-line_number">${this.#getSerial()}</div>
		`;
	}

	#getTitleClass(): string
	{
		return this.#canRead
			? 'tasks-flow__task-queue-line_title-access'
			: 'tasks-flow__task-queue-line_title';
	}

	#openTask(): void
	{
		if (!this.#canRead)
		{
			return;
		}

		SidePanel.Instance.open(this.#url);
	}

	#getSerial(): string
	{
		if (this.#serial < 10)
		{
			return `0${this.#serial}`;
		}

		return this.#serial;
	}
}
