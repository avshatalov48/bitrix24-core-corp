import { Event, Runtime, Text } from 'main.core';
import { EventEmitter, type BaseEvent } from 'main.core.events';
import type { Popup } from 'main.popup';
import type { Slider } from 'main.sidepanel';

import { BitrixVue, type VueCreateAppResult } from 'ui.vue3';
import { locMixin } from 'ui.vue3.mixins.loc-mixin';

import { Core } from 'tasks.v2.core';
import { EventName } from 'tasks.v2.const';
import type { Params } from 'tasks.v2.application.task-card';

import { App } from './component/app';

export class TaskFullCard
{
	#params: Params;
	#slider: Slider;
	#application: ?VueCreateAppResult;
	#handlers: { [eventName: string]: Function };
	#needToReloadGrid: boolean = false;

	constructor(params: Params = {})
	{
		this.#params = params;

		this.#params.taskId = this.#params.taskId || Text.getRandom();
	}

	async mountCard(slider: Slider): Promise<void>
	{
		if (!slider.isOpen())
		{
			return;
		}

		this.#slider = slider;

		this.#subscribe();

		this.#application = await this.#mountApplication(slider.getContentContainer());
	}

	unmountCard(): void
	{
		this.#unmountApplication();

		this.#unsubscribe();

		if (this.#needToReloadGrid && BX.Tasks.Util)
		{
			const id = this.#params.taskId;
			BX.Tasks.Util.fireGlobalTaskEvent('UPDATE', { ID: id }, { STAY_AT_PAGE: true }, { id });
		}
	}

	async #mountApplication(container: HTMLElement): Promise<VueCreateAppResult>
	{
		await Core.init();

		const application = BitrixVue.createApp(App, {
			...this.#params,
			id: this.#params.taskId,
		});

		application.mixin(locMixin);
		application.use(Core.getStore());
		application.mount(container);

		return application;
	}

	#unmountApplication(): void
	{
		this.#application?.unmount();
	}

	#subscribe(): void
	{
		this.#handlers = {
			[EventName.TaskUpdate]: this.#handleTaskUpdate,
			[EventName.CloseFullCard]: this.#onClose,
			[EventName.OpenFullCard]: this.#openFullCard,
			[EventName.OpenCompactCard]: this.#openCompactCard,
			[EventName.OpenGrid]: this.#openGrid,
			'BX.Main.Popup:onShow': this.#handlePopupShow,
		};

		Object.entries(this.#handlers).forEach(([event, handler]) => EventEmitter.subscribe(event, handler));
	}

	#unsubscribe(): void
	{
		Object.entries(this.#handlers).forEach(([event, handler]) => EventEmitter.unsubscribe(event, handler));
	}

	#handleTaskUpdate = (event: BaseEvent): void => {
		if (event.getData().id === this.#params.taskId)
		{
			const fieldsForReloadGrid = new Set(['deadlineTs', 'responsibleId']);
			const fields = Object.keys(event.getData());
			if (fields.some((field: string) => fieldsForReloadGrid.has(field)))
			{
				this.#needToReloadGrid = true;
			}
		}
	};

	#onClose = (event: BaseEvent): void => {
		if (event.getData().taskId === this.#params.taskId)
		{
			this.#slider.close();
		}
	};

	#openFullCard = async (baseEvent: BaseEvent): Promise<void> => {
		const { TaskCard } = await Runtime.loadExtension('tasks.v2.application.task-card');

		const params = baseEvent.getData();

		TaskCard.showFullCard(params);
	};

	#openCompactCard = async (baseEvent: BaseEvent): Promise<void> => {
		const { TaskCard } = await Runtime.loadExtension('tasks.v2.application.task-card');

		const params = baseEvent.getData();

		TaskCard.showCompactCard(params);
	};

	#openGrid = (baseEvent: BaseEvent): void => {
		const { taskId, type } = baseEvent.getData();
		const userId = Core.getParams().currentUserId;

		BX.SidePanel.Instance.open(`/company/personal/user/${userId}/tasks/?relationToId=${taskId}&relationType=${type}`);
	};

	#handlePopupShow = (event: BaseEvent): void => {
		const popup: Popup = event.getCompatData()[0];
		if (popup.getTargetContainer() !== document.body)
		{
			return;
		}

		const onScroll = () => popup.adjustPosition();
		const onClose = () => {
			popup.unsubscribe('onClose', onClose);
			popup.unsubscribe('onDestroy', onClose);
			Event.unbind(document, 'scroll', onScroll, true);
		};

		popup.subscribe('onClose', onClose);
		popup.subscribe('onDestroy', onClose);
		Event.bind(document, 'scroll', onScroll, true);
	};
}
