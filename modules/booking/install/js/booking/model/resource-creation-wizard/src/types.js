import type { ResourceModel } from 'booking.model.resources';

export type { ResourceModel };

export type ResourceId = number | null;

export type ResourceCreationWizardState = {
	resourceId: ResourceId;
	resourceName: string;
	resource: ResourceModel;
	advertisingResourceTypes: AdvertisingResourceType[],
	favorite: boolean;
	fetching: boolean,
	step: number;
	globalSchedule: boolean;
	isSaving: boolean;
	isCompanyScheduleAccess: boolean;
	invalidResourceName: boolean;
	invalidResourceType: boolean;
	weekStart: string;
	checkedForAll: { [type: string]: boolean };
}

export type InitPayload = {
	resourceId: ResourceId,
	resource: ResourceModel,
	step: number;
	favorite?: boolean;
}

export type ResourceCreationType = {
	code: string;
	name: string;
	icon: string;
	description?: string;
	relatedResourceTypeId: number,
	value: number;
}

export type AdvertisingResourceType = {
	code: string;
	name: string;
	description: string;
	relatedResourceTypeId: number;
}
