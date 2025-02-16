import { Event, Loc, Text, Type } from 'main.core';
import { Notifier, NotificationOptions } from 'ui.notification-manager';
import { EventName, Model, Module } from 'booking.const';
import { resourceService } from 'booking.provider.service.resources-service';
import { resourceTypeService } from 'booking.provider.service.resources-type-service';
import type { SlotRange } from 'booking.model.resources';
import type { ResourceModel } from 'booking.model.resource-creation-wizard';
import { Step } from './step';
import { ConditionChecker, Types as SenderTypes } from 'crm.messagesender';

export class ResourceNotificationStep extends Step
{
	constructor()
	{
		super();

		this.step = 3;
	}

	get labelNext(): string
	{
		const resourceId = this.store.getters[`${Model.ResourceCreationWizard}/getResource`]?.id || null;

		return Type.isNull(resourceId)
			? Loc.getMessage('BRCW_BUTTON_CREATE_RESOURCE')
			: Loc.getMessage('BRCW_BUTTON_UPDATE_RESOURCE');
	}

	async #upsertResource(resource: ResourceModel): Promise<boolean>
	{
		const isUpdate = Boolean(resource.id);
		const result = await (isUpdate ? resourceService.update(resource) : resourceService.add(resource));

		let text = Loc.getMessage(isUpdate ? 'BRCW_UPDATE_SUCCESS_MESSAGE' : 'BRCW_CREATE_SUCCESS_MESSAGE');
		if (Type.isArrayFilled(result.errors))
		{
			text = result.errors[0].message;
		}

		Notifier.notify(this.#prepareNotificationOptions(text));

		return !Type.isArrayFilled(result.errors);
	}

	async next(): Promise<void>
	{
		this.store.commit(`${Model.ResourceCreationWizard}/setSaving`, true);

		const isApproved = await this.isBitrix24Approved();
		if (!isApproved)
		{
			this.store.commit(`${Model.ResourceCreationWizard}/setSaving`, false);

			return;
		}

		if (this.store.getters[`${Model.ResourceCreationWizard}/isGlobalSchedule`])
		{
			const slotSize = this.store.state[Model.ResourceCreationWizard].resource.slotRanges[0]?.slotSize ?? 60;
			const scheduleSlots = this.store.getters[`${Model.ResourceCreationWizard}/getCompanyScheduleSlots`];
			const timezone = this.store.getters[`${Model.Interface}/timezone`];
			const slotRanges = scheduleSlots.map((slotRange: SlotRange) => ({
				...slotRange,
				slotSize,
				timezone,
			}));

			await this.store.dispatch(`${Model.ResourceCreationWizard}/updateResource`, { slotRanges });
		}

		const resource = this.store.state[Model.ResourceCreationWizard].resource;
		const resourceType = this.store.getters[`${Model.ResourceTypes}/getById`](resource.typeId);

		const isSuccess = await this.#upsertResource(resource);
		if (!isSuccess)
		{
			this.store.commit(`${Model.ResourceCreationWizard}/setSaving`, false);

			return;
		}

		await resourceTypeService.update(resourceType);
		this.store.commit(`${Model.ResourceCreationWizard}/init`, { step: 1, resourceId: null, resource: null });
		Event.EventEmitter.emit(EventName.CloseWizard);
	}

	async isBitrix24Approved(): Promise<boolean>
	{
		if (!this.#isBitrix24SenderAvailable())
		{
			return Promise.resolve(true);
		}

		return ConditionChecker.checkIsApproved({
			senderType: SenderTypes.bitrix24,
		});
	}

	#isBitrix24SenderAvailable(): boolean
	{
		const bitrix24Sender = this.store.getters[`${Model.Notifications}/getSenders`]
			.find((sender) => sender.moduleId === Module.Crm && sender.code === SenderTypes.bitrix24)
		;

		if (!bitrix24Sender)
		{
			return false;
		}

		return bitrix24Sender.canUse;
	}

	back(): Promise<void>
	{
		return super.back();
	}

	#prepareNotificationOptions(text: string): NotificationOptions
	{
		return {
			id: Text.getRandom(),
			text,
		};
	}
}
