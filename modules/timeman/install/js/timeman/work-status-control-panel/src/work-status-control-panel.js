import { Tag, Extension } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { BitrixVue } from 'ui.vue3';

import 'timeman';
import 'CJSTask';
import 'planner';
import 'tasks_planner_handler';
import 'calendar_planner_handler';
import 'ajax';
import 'timer';
import 'popup';
import 'ls';

import { App } from './component/app';

import './work-status-control-panel.css';

type Data = {
	workReport: Object,
	info: Object & {
		STATE: string,
		CAN_OPEN?: string,
		INFO: Object & {
			DATE_START: number,
			TIME_LEAKS: number,
		},
	},
	siteId: string,
};

export class WorkStatusControlPanel
{
	#data: Data = {};
	#timemanInstantContainerNode: HTMLElement;

	constructor()
	{
		const settings = Extension.getSettings('timeman.work-status-control-panel');

		this.#data.workReport = settings.get('workReport');
		this.#data.info = settings.get('info');
		this.#data.siteId = settings.get('siteId');

		this.#timemanInstantContainerNode = Tag.render`
			<div class="timeman-instant-container"></div>
		`;

		EventEmitter.subscribe('onTimemanInit', this.#init.bind(this));
		EventEmitter.subscribe('onTimeManDataRecieved', this.#updateState.bind(this));

		window.BX.timeman('bx_tm', this.#data.info, this.#data.siteId);
	}

	#mountApplication(container: HTMLElement): void
	{
		const application = BitrixVue.createApp(App, {});
		application.mount(container);
	}

	#init(): void
	{
		window.BXTIMEMAN.initFormWeekly(this.#data.workReport);
	}

	#updateState(baseEvent: BaseEvent): void
	{
		const [data] = baseEvent.getCompatData();

		this.#data.info = data;
	}

	renderWorkStatusControlPanel(): HTMLElement
	{
		event.stopPropagation();

		this.#mountApplication(this.#timemanInstantContainerNode);

		return Tag.render`
			${this.#timemanInstantContainerNode}
		`;
	}
}
