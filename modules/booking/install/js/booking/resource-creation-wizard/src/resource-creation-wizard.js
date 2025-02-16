import { Event } from 'main.core';
import { BitrixVue } from 'ui.vue3';
import type { VueCreateAppResult } from 'ui.vue3';

import { Core } from 'booking.core';
import { EventName, Model } from 'booking.const';
import { locMixin } from 'booking.component.mixin.loc-mixin';
import { Notifications } from 'booking.model.notifications';
import { ResourceCreationWizardModel } from 'booking.model.resource-creation-wizard';
import { SidePanelInstance } from 'booking.lib.side-panel-instance';
import { App } from './components/app';

type ResourceId = number | null;

export class ResourceCreationWizard
{
	static #width: number = 600;

	#application: VueCreateAppResult | null = null;

	static #makeName(resourceId: string = 'new'): string
	{
		return `booking:resource-creation-wizard:${resourceId || 'new'}`;
	}

	#mountContent(container: HTMLElement): void
	{
		const application = BitrixVue.createApp(App, Core.getParams());

		application.mixin(locMixin);
		application.use(Core.getStore());
		application.mount(container);

		this.#application = application;
	}

	async #initCore(resourceId: ResourceId): Promise<void>
	{
		try
		{
			await Core.init();
			await Core.addDynamicModule(
				ResourceCreationWizardModel
					.create()
					.setVariables({ resourceId }),
			);
			await Core.addDynamicModule(Notifications.create());
		}
		catch (error)
		{
			console.error('Init Resource creation wizard error', error);
		}
	}

	#makeContainer(): HTMLElement
	{
		const container = document.createElement('div');
		container.id = 'booking-resource-creation-wizard-app';

		return container;
	}

	open(resourceId: ResourceId = null): void
	{
		SidePanelInstance.open(ResourceCreationWizard.#makeName(resourceId), {
			width: ResourceCreationWizard.#width,
			cacheable: false,
			events: {
				onClose: this.closeSidePanel.bind(this),
			},
			contentCallback: async () => {
				await this.#initCore(resourceId);
				this.subscribe();

				const container = this.#makeContainer();
				this.#mountContent(container);

				return container;
			},
		});
	}

	async closeSidePanel(): Promise<void>
	{
		this.unsubscribe();
		this.#application.unmount();
		this.#application = null;

		await Core.removeDynamicModule(Model.ResourceCreationWizard);
		await Core.removeDynamicModule(Model.Notifications);
	}

	subscribe(): void
	{
		Event.EventEmitter.subscribe(
			EventName.CloseWizard,
			this.close,
		);
	}

	unsubscribe(): void
	{
		Event.EventEmitter.unsubscribe(
			EventName.CloseWizard,
			this.close,
		);
	}

	close(): void
	{
		SidePanelInstance.close();
	}
}
