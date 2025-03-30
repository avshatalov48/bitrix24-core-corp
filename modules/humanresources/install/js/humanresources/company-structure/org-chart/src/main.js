import { EventEmitter, type BaseEvent } from 'main.core.events';
import { events } from './events';
import { BitrixVue } from 'ui.vue3';
import { createPinia } from 'ui.vue3.pinia';
import { Dom } from 'main.core';
import { Chart } from './app';
import { PermissionChecker } from 'humanresources.company-structure.permission-checker';

export class App
{
	static #subscribeOnEvents(app: Object): void
	{
		const onCloseByEsc = (event: BaseEvent) => {
			const [sidePanelEvent] = event.data;
			sidePanelEvent.denyAction();
		};

		const onClose = () => {
			EventEmitter.unsubscribe(events.HR_ORG_CHART_CLOSE_BY_ESC, onCloseByEsc);
			EventEmitter.unsubscribe(events.HR_ORG_CHART_CLOSE, onClose);
			app.unmount();
		};
		EventEmitter.subscribe(events.HR_ORG_CHART_CLOSE_BY_ESC, onCloseByEsc);
		EventEmitter.subscribe(events.HR_ORG_CHART_CLOSE, onClose);
	}

	static async mount(containerId: string): Promise<void>
	{
		const container = document.getElementById(containerId);
		const app = BitrixVue.createApp(Chart);
		const store = createPinia();
		app.use(store);
		App.#subscribeOnEvents(app);

		const slider = BX.SidePanel.Instance.getTopSlider();
		if (slider)
		{
			slider.showLoader();
		}
		Dom.addClass(container, 'humanresources-chart__back');

		await PermissionChecker.init();
		if (slider)
		{
			slider.closeLoader();
		}
		Dom.removeClass(container, 'humanresources-chart__back');
		app.mount(container);
	}
}
