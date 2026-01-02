import { Event, Loc, Type } from 'main.core';
import { EventName, Model } from 'booking.const';
import { RcwAnalytics } from 'booking.lib.analytics';
import { Step } from './step';

export class ResourceSettingsStep extends Step
{
	constructor()
	{
		super();
		this.step = 2;
	}

	get #isFirstStep(): boolean
	{
		return this.store.getters[`${Model.ResourceCreationWizard}/startStep`] === this.step;
	}

	get labelBack(): string
	{
		return this.#isFirstStep ? super.labelBack : Loc.getMessage('BRCW_BUTTON_BACK');
	}

	async next(): Promise<void>
	{
		const store = this.store;

		if (!store.state[Model.ResourceCreationWizard].resource.name)
		{
			if (!Type.isNull(store.state[Model.ResourceCreationWizard].resourceId))
			{
				await store.dispatch(`${Model.ResourceCreationWizard}/setInvalidResourceName`, true);

				return;
			}

			await store.dispatch(`${Model.ResourceCreationWizard}/updateResource`, {
				name: Loc.getMessage('BRCW_DEFAULT_RESOURCE_NAME'),
			});
		}

		if (!store.state[Model.ResourceCreationWizard].resource.typeId)
		{
			await store.dispatch(`${Model.ResourceCreationWizard}/setInvalidResourceType`, true);

			return;
		}

		if (!this.#validateIntegrationCalendarUser())
		{
			return;
		}

		await super.next();
		RcwAnalytics.sendAddResourceStep2();
	}

	async back()
	{
		if (this.#isFirstStep)
		{
			Event.EventEmitter.emit(EventName.CloseWizard);
		}
		else
		{
			await super.back();
		}
	}

	#validateIntegrationCalendarUser(): boolean
	{
		const isIntegrationCalendarEnabled = this.store.getters[`${Model.ResourceCreationWizard}/isIntegrationCalendarEnabled`];
		const calendarEntity = this.store.getters[`${Model.ResourceCreationWizard}/entityCalendar`];

		if (!isIntegrationCalendarEnabled || !calendarEntity || calendarEntity.data.userIds.length > 0)
		{
			return true;
		}

		if (!calendarEntity.data.locationId && calendarEntity.data.reminders.length === 0)
		{
			return true;
		}

		void this.store.dispatch(`${Model.ResourceCreationWizard}/setInvalidIntegrationCalendarUser`, true);

		return false;
	}
}
