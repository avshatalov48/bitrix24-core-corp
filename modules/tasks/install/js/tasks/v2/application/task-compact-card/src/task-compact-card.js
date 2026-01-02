import { Dom, Runtime, Tag, Text } from 'main.core';
import { EventEmitter, type BaseEvent } from 'main.core.events';
import type { Popup } from 'main.popup';

import { BitrixVue, type VueCreateAppResult } from 'ui.vue3';
import { locMixin } from 'ui.vue3.mixins.loc-mixin';

import { TaskMappers } from 'tasks.v2.provider.service.task-service';
import { Core } from 'tasks.v2.core';
import { EventName } from 'tasks.v2.const';
import type { Params } from 'tasks.v2.application.task-card';

import { App } from './component/app';

export class TaskCompactCard
{
	#params: Params;
	#popup: Popup;
	#application: ?VueCreateAppResult;
	#handlers: { [eventName: string]: Function };

	constructor(params: Params = {})
	{
		this.#params = params;

		this.#params.taskId = this.#params.taskId || Text.getRandom();
	}

	async mountCard(popup: Popup): Promise<void>
	{
		this.#popup = popup;

		await this.#mountApplication(popup.getContentContainer());

		this.#adjustPosition();
		this.#subscribe();

		const dragHandle = Tag.render`<div class="tasks-compact-card-popup-drag-handle"></div>`;
		Dom.append(dragHandle, popup.getContentContainer());
		this.#popup.setDraggable({
			element: dragHandle,
			restrict: true,
		});
	}

	unmountCard(): void
	{
		this.#unmountApplication();

		this.#unsubscribe();
	}

	async #mountApplication(container: HTMLElement): Promise<void>
	{
		await Core.init();

		const application = BitrixVue.createApp(App, this.#params);

		application.mixin(locMixin);
		application.use(Core.getStore());
		application.mount(container);

		this.#application = application;
	}

	#unmountApplication(): void
	{
		this.#application.unmount();
	}

	#subscribe(): void
	{
		this.#handlers = {
			[`${EventName.CloseCard}:${this.#params.taskId}`]: this.#closeCard,
			[`${EventName.OpenFullCard}:${this.#params.taskId}`]: this.#openFullCard,
			[`${EventName.OpenSliderCard}:${this.#params.taskId}`]: this.#openSliderCard,
			[`${EventName.ShowOverlay}:${this.#params.taskId}`]: this.#showOverlay,
			[`${EventName.HideOverlay}:${this.#params.taskId}`]: this.#hideOverlay,
			[`${EventName.AdjustPosition}:${this.#params.taskId}`]: this.#handleAdjustPosition,
			'BX.Main.Popup:onShow': this.#handlePopupShow,
		};

		Object.entries(this.#handlers).forEach(([event, handler]) => EventEmitter.subscribe(event, handler));
	}

	#unsubscribe(): void
	{
		Object.entries(this.#handlers).forEach(([event, handler]) => EventEmitter.unsubscribe(event, handler));
	}

	#openFullCard = async (baseEvent: BaseEvent): Promise<void> => {
		this.#closeCard();

		const { TaskCard } = await Runtime.loadExtension('tasks.v2.application.task-card');

		this.#params.taskId = baseEvent.getData();

		TaskCard.showFullCard(this.#params);
	};

	#openSliderCard = (baseEvent: BaseEvent): void => {
		const task = baseEvent.getData().task;
		const checkLists = baseEvent.getData().checkLists;

		const data = TaskMappers.mapModelToSliderData(task, checkLists);

		const path = Core.getParams().paths.editPath;
		BX.SidePanel.Instance.open(path, {
			requestMethod: 'post',
			requestParams: data,
			cacheable: false,
		});

		this.#closeCard();
	};

	#closeCard = (): void => {
		this.#popup.close();
	};

	#showOverlay = (): void => {
		Dom.addClass(this.#popup.getPopupContainer(), '--overlay');
	};

	#hideOverlay = (): void => {
		Dom.removeClass(this.#popup.getPopupContainer(), '--overlay');
	};

	#handleAdjustPosition = (baseEvent?: BaseEvent): void => {
		const { innerPopup, titleFieldHeight, animate } = baseEvent?.getData() ?? {};
		if (!innerPopup)
		{
			this.#popup.setOffset({
				offsetTop: 0,
			});
			this.#adjustPosition();

			return;
		}

		const innerPopupContainer = innerPopup.getPopupContainer();
		const popupContainer = this.#popup.getPopupContainer();

		const heightDifference = innerPopupContainer.offsetHeight - popupContainer.offsetHeight;
		const popupPaddingTop = 20;
		const offset = titleFieldHeight + heightDifference / 2 + popupPaddingTop * 2;

		Dom.style(popupContainer, '--overlay-offset-top', `-${offset}px`);
		if (!animate)
		{
			this.#adjustPosition();
			Dom.style(popupContainer, 'transition', 'none');
			setTimeout(() => Dom.style(popupContainer, 'transition', null));
		}
	};

	#adjustPosition(): void
	{
		this.#popup.adjustPosition({
			forceBindPosition: true,
		});
	}

	#handlePopupShow = (event): void => {
		this.#handlePopupShow.openedPopupsCount ??= 0;

		const popup: Popup = event.getCompatData()[0];
		const popupContainer = this.#popup.getPopupContainer();

		const onClose = (): void => {
			popup.unsubscribe('onClose', onClose);
			popup.unsubscribe('onDestroy', onClose);

			this.#handlePopupShow.openedPopupsCount--;
			if (this.#handlePopupShow.openedPopupsCount === 0)
			{
				Dom.removeClass(popupContainer, '--disable-drag');
			}
		};

		popup.subscribe('onClose', onClose);
		popup.subscribe('onDestroy', onClose);

		this.#handlePopupShow.openedPopupsCount++;
		Dom.addClass(popupContainer, '--disable-drag');
	};
}
