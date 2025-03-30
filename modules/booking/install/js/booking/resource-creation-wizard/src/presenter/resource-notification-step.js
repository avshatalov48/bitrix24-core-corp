import { Event, Loc, Text, Type } from 'main.core';
import { Notifier, NotificationOptions } from 'ui.notification-manager';
import { ConditionChecker, Types as SenderTypes } from 'crm.messagesender';

import { EventName, Model, NotificationFieldsMap, Module } from 'booking.const';
import { resourceService } from 'booking.provider.service.resources-service';
import { resourceTypeService } from 'booking.provider.service.resources-type-service';
import type { SlotRange, ResourceModel } from 'booking.model.resources';
import type { ResourceTypeModel } from 'booking.model.resource-types';
import { Step } from './step';

export class ResourceNotificationStep extends Step
{
	constructor()
	{
		super();

		this.step = 3;
	}

	get #resource(): ResourceModel
	{
		return this.store.getters[`${Model.ResourceCreationWizard}/getResource`];
	}

	get labelNext(): string
	{
		return Type.isNull(this.#resource.id ?? null)
			? Loc.getMessage('BRCW_BUTTON_CREATE_RESOURCE')
			: Loc.getMessage('BRCW_BUTTON_UPDATE_RESOURCE');
	}

	async next(): Promise<void>
	{
		this.store.commit(`${Model.ResourceCreationWizard}/setSaving`, true);

		const isApproved = await this.#isBitrix24Approved();
		if (!isApproved)
		{
			this.store.commit(`${Model.ResourceCreationWizard}/setSaving`, false);

			return;
		}

		if (this.store.getters[`${Model.ResourceCreationWizard}/isGlobalSchedule`])
		{
			await this.store.dispatch(`${Model.ResourceCreationWizard}/updateResource`, {
				slotRanges: this.#prepareCompanySlotRanges(this.#resource),
			});
		}

		const isSuccess = await this.#upsertResource(this.#resource);
		if (!isSuccess)
		{
			this.store.commit(`${Model.ResourceCreationWizard}/setSaving`, false);

			return;
		}

		await resourceTypeService.update({
			id: this.#resource.typeId,
			...this.#prepareResourceTypeNotifications(this.#resource),
		});

		Event.EventEmitter.emit(EventName.CloseWizard);
	}

	#isBitrix24Approved(): Promise<boolean>
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

	#prepareCompanySlotRanges(resource: ResourceModel): SlotRange[]
	{
		const slotSize = resource.slotRanges[0]?.slotSize ?? 60;
		const scheduleSlots = this.store.getters[`${Model.ResourceCreationWizard}/getCompanyScheduleSlots`];
		const timezone = this.store.getters[`${Model.Interface}/timezone`];

		return scheduleSlots.map((slotRange: SlotRange) => ({
			...slotRange,
			slotSize,
			timezone,
		}));
	}

	#prepareResourceTypeNotifications(resource: ResourceModel): Partial<ResourceTypeModel>
	{
		const resourceType = this.store.getters[`${Model.ResourceTypes}/getById`](resource.typeId);

		return Object.values(this.store.getters[`${Model.Dictionary}/getNotifications`])
			.map(({ value }) => value)
			.reduce((acc: Object, type: string) => {
				const notificationOnField = NotificationFieldsMap.NotificationOn[type];
				const templateTypeField = NotificationFieldsMap.TemplateType[type];

				const isCheckedForAll = this.store.getters[`${Model.ResourceCreationWizard}/isCheckedForAll`](type);

				return {
					...acc,
					[notificationOnField]: isCheckedForAll ? resource[notificationOnField] : resourceType[notificationOnField],
					[templateTypeField]: isCheckedForAll ? resource[templateTypeField] : resourceType[templateTypeField],
				};
			}, {})
		;
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

	#prepareNotificationOptions(text: string): NotificationOptions
	{
		return {
			id: Text.getRandom(),
			text,
		};
	}
}
