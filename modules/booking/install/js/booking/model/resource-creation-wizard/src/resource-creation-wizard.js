/* eslint-disable no-param-reassign,max-lines-per-function */
import { Loc, Type } from 'main.core';
import { BuilderModel } from 'ui.vue3.vuex';
import type { ActionTree, GetterTree, MutationTree } from 'ui.vue3.vuex';

import { Model, NotificationFieldsMap, ResourceEntityType } from 'booking.const';
import { SlotRange } from 'booking.model.resources';
import type { IntegrationCalendarType, IntegrationCalendarDataType } from 'booking.model.resources';

import { getEmptyResource, getResource } from './lib';
import type {
	ResourceCreationWizardState,
	InitPayload,
	ResourceModel,
	AdvertisingResourceType,
} from './types';

export class ResourceCreationWizardModel extends BuilderModel
{
	getName(): string
	{
		return Model.ResourceCreationWizard;
	}

	getState(): ResourceCreationWizardState
	{
		return {
			resourceId: this.getVariable('resourceId', null),
			resourceName: '',
			resource: getEmptyResource(),
			advertisingResourceTypes: [],
			companyScheduleSlots: [],
			fetching: false,
			step: 1,
			isSaving: false,
			invalidResourceName: false,
			invalidResourceType: false,
			invalidIntegrationCalendarUser: false,
			isCompanyScheduleAccess: false,
			companyScheduleUrl: '',
			weekStart: 'Mon',
			globalSchedule: false,
			checkedForAll: {},
			isChannelChoiceAvailable: true,
			isIntegrationCalendarEnabled: false,
		};
	}

	getGetters(): GetterTree<ResourceCreationWizardState, any>
	{
		return {
			/** @function resource-creation-wizard/resourceId */
			resourceId: (state): ResourceModel => state.resourceId,
			/** @function resource-creation-wizard/getResource */
			getResource: (state): ResourceModel => state.resource,
			/** @function resource-creation-wizard/isSaving */
			isSaving: (state): boolean => state.isSaving,
			/** @function resource-creation-wizard/getCompanyScheduleSlots */
			getCompanyScheduleSlots: (state): SlotRange[] => state.companyScheduleSlots,
			/** @function resource-creation-wizard/isGlobalSchedule */
			isGlobalSchedule: (state): boolean => state.globalSchedule,
			startStep: (state): number => {
				return Type.isNull(state.resourceId) ? 1 : 2;
			},
			finishStep: (): number => 3,
			invalidIntegrationCalendarUser: (state): boolean => {
				return state.isIntegrationCalendarEnabled && state.invalidIntegrationCalendarUser;
			},
			invalidChooseTypeCard: (state): boolean => Type.isNull(state.resource.typeId),
			invalidSettingsCard: (state, getters): boolean => {
				return (
					state.invalidResourceName
					|| !state.resource.typeId
					|| getters.invalidIntegrationCalendarUser
				);
			},
			invalidCurrentCard: (state, getters): boolean => {
				if (state.step === 1)
				{
					return getters.invalidChooseTypeCard;
				}

				if (state.step === 2)
				{
					return getters.invalidSettingsCard;
				}

				return false;
			},
			/** @function resource-creation-wizard/isCompanyScheduleAccess */
			isCompanyScheduleAccess: (state): boolean => state.isCompanyScheduleAccess,
			/** @function resource-creation-wizard/companyScheduleUrl */
			companyScheduleUrl: (state): boolean => state.companyScheduleUrl,
			/** @function resource-creation-wizard/weekStart */
			weekStart: (state): boolean => state.weekStart,
			/** @function resource-creation-wizard/isChannelChoiceAvailable */
			isChannelChoiceAvailable: (state): boolean => state.isChannelChoiceAvailable,
			/** @function resource-creation-wizard/isCheckedForAll */
			isCheckedForAll: (state) => (type: string): boolean => state.checkedForAll[type] ?? true,
			advertisingResourceType: (state): AdvertisingResourceType | undefined => {
				const typeId = state.resource.typeId;

				return state.advertisingResourceTypes.find(({ relatedResourceTypeId }) => {
					return relatedResourceTypeId === typeId;
				}) || null;
			},
			entityCalendar: (state): IntegrationCalendarType | null => {
				return state.resource?.entities?.find((ent) => ent.entityType === ResourceEntityType.Calendar) || null;
			},
			isIntegrationCalendarEnabled: (state): boolean => {
				return state.isIntegrationCalendarEnabled;
			},
		};
	}

	getActions(): ActionTree<ResourceCreationWizardState, any>
	{
		return {
			async initState({ state, dispatch }): Promise<void>
			{
				if (Type.isNull(state.resourceId))
				{
					await dispatch('initCreateMaster');
				}
				else
				{
					await dispatch('initEditMaster');
				}
			},
			initCreateMaster({ state, commit }): void
			{
				commit('init', {
					resourceId: state.resourceId,
					step: 1,
				});
				commit('setCurrentResourceName', Loc.getMessage('BRCW_TITLE'));
			},
			initEditMaster({ state, commit }): void
			{
				const resourceId = state.resourceId;
				const resource = getResource(resourceId);

				commit('init', {
					resourceId,
					resource,
					step: 2,
				});
				commit('setCurrentResourceName', resource.name);

				commit(
					'setIsIntegrationCalendarEnabled',
					resource.entities?.find((ent) => ent.entityType === ResourceEntityType.Calendar)?.data?.userIds?.length > 0,
				);
			},
			nextStep({ state, getters, commit }): void
			{
				if (getters.invalidCurrentCard)
				{
					return;
				}

				if (state.step < getters.finishStep)
				{
					commit('updateStep', state.step + 1);
				}
			},
			prevStep({ state, getters, commit }): void
			{
				if (state.step > getters.startStep)
				{
					commit('updateStep', state.step - 1);
				}
			},
			setAdvertisingTypes({ commit }, types: AdvertisingResourceType[]): void
			{
				const advertisingResourceType: AdvertisingResourceType[] = [
					...types,
					{
						code: 'none',
						name: Loc.getMessage('BRCW_CHOOSE_CATEGORY_YOUR_TYPE'),
						relatedResourceTypeId: 0,
					},
				];

				commit('setAdvertisingTypes', advertisingResourceType);
			},
			/** @function resource-creation-wizard/updateResource */
			updateResource({ commit, rootGetters }, patch: Partial<ResourceModel>): void
			{
				if (patch.typeId)
				{
					const resourceType = rootGetters[`${Model.ResourceTypes}/getById`](patch.typeId);
					const notifications = [
						...Object.values(NotificationFieldsMap.NotificationOn),
						...Object.values(NotificationFieldsMap.TemplateType),
						...Object.values(NotificationFieldsMap.Settings).flat(),
					].reduce((acc: Partial<ResourceModel>, field: $Keys<ResourceModel>) => ({
						...acc,
						[field]: resourceType[field],
					}), {});

					Object.assign(patch, notifications);
				}

				commit('updateResource', patch);
			},
			/** @function resource-creation-wizard/createResourceEntityCalendar */
			createResourceEntityCalendar({ commit }): void
			{
				commit('createResourceEntityCalendar');
			},
			/** @function resource-creation-wizard/updateResourceEntityCalendar */
			updateResourceEntityCalendar({ commit }, calendarPath: Partial<IntegrationCalendarDataType>): void
			{
				commit('updateResourceEntityCalendar', calendarPath);
			},
			/** @function resource-creation-wizard/setCompanyScheduleSlots */
			setCompanyScheduleSlots({ commit }, slots: SlotRange[]): void
			{
				const defaultTimeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;

				commit('setCompanyScheduleSlots', slots.map((slotRange) => ({
					...slotRange,
					weekDays: Object.values(slotRange.weekDays),
					timezone: slotRange.timezone || defaultTimeZone,
				})));
			},
			/** @function resource-creation-wizard/setGlobalSchedule */
			setGlobalSchedule({ commit }, checked: boolean): void
			{
				commit('setGlobalSchedule', checked);
			},
			/** @function resource-creation-wizard/setCompanyScheduleAccess */
			setCompanyScheduleAccess({ commit }, isCompanyScheduleAccess: boolean): void
			{
				commit('setCompanyScheduleAccess', isCompanyScheduleAccess);
			},
			/** @function resource-creation-wizard/setCompanyScheduleUrl */
			setCompanyScheduleUrl({ commit }, companyScheduleUrl: string): void
			{
				commit('setCompanyScheduleUrl', companyScheduleUrl);
			},
			/** @function resource-creation-wizard/setInvalidResourceName */
			setInvalidResourceName({ commit, state }, invalid: boolean): void
			{
				if (state.invalidResourceName !== invalid)
				{
					commit('setInvalidResourceName', invalid);
				}
			},
			/** @function resource-creation-wizard/setInvalidResourceType */
			setInvalidResourceType({ commit, state }, invalid: boolean): void
			{
				if (state.invalidResourceType !== invalid)
				{
					commit('setInvalidResourceType', invalid);
				}
			},
			/** @function resource-creation-wizard/setWeekStart */
			setWeekStart({ commit }, weekStart: string): void
			{
				commit('setWeekStart', weekStart);
			},
			/** @function resource-creation-wizard/setCheckedForAll */
			setCheckedForAll({ commit }, { type, isChecked }: { type: string, isChecked: boolean }): void
			{
				commit('setCheckedForAll', { type, isChecked });
			},
			setSlotLengthId({ commit }, { slotLengthId }: { slotLengthId: number }): void
			{
				commit('setSlotLengthId', { slotLengthId });
			},
			/** @function resource-creation-wizard/setIsChannelChoiceAvailable */
			setIsChannelChoiceAvailable({ commit }, isChannelChoiceAvailable: boolean): void
			{
				commit('setIsChannelChoiceAvailable', isChannelChoiceAvailable);
			},
			/** @function resource-creation-wizard/setIsIntegrationCalendarEnabled */
			setIsIntegrationCalendarEnabled({ commit }, isIntegrationCalendarEnabled: boolean): void
			{
				commit('setIsIntegrationCalendarEnabled', isIntegrationCalendarEnabled);
			},
			/** @function resource-creation-wizard/setInvalidIntegrationCalendarUser */
			setInvalidIntegrationCalendarUser({ commit }, invalidIntegrationCalendarUser: boolean): void
			{
				commit('setInvalidIntegrationCalendarUser', invalidIntegrationCalendarUser);
			},
		};
	}

	getMutations(): MutationTree<ResourceCreationWizardState>
	{
		return {
			init(state: ResourceCreationWizardState, { step, resourceId, resource = null }: InitPayload): void
			{
				state.step = step;
				state.resourceId = resourceId;
				state.resource = Type.isNull(resourceId) ? getEmptyResource() : resource;
			},
			setCurrentResourceName(state: ResourceCreationWizardState, name: string): void
			{
				state.resourceName = name;
			},
			setAdvertisingTypes(state: ResourceCreationWizardState, types: AdvertisingResourceType[]): void
			{
				state.advertisingResourceTypes = types;
			},
			setCompanyScheduleSlots(state: ResourceCreationWizardState, slots: SlotRange[]): void
			{
				state.companyScheduleSlots = slots;
			},
			updateStep(state: ResourceCreationWizardState, step): void
			{
				state.step = step;
			},
			updateResource(state: ResourceCreationWizardState, patch: Partial<ResourceModel>): void
			{
				state.resource = {
					...state.resource,
					...patch,
				};
			},
			createResourceEntityCalendar(state): void
			{
				const hasCalendarEntity = state.resource?.entities?.some(
					(entity) => entity.entityType === ResourceEntityType.Calendar,
				);

				if (!hasCalendarEntity)
				{
					state.resource?.entities?.push({
						entityType: ResourceEntityType.Calendar,
						entityId: 0,
						data: {
							userIds: [],
							locationId: null,
							checkAvailability: false,
							reminders: [],
						},
					});
				}
			},
			updateResourceEntityCalendar(state, calendarPath: Partial<IntegrationCalendarDataType>): void
			{
				const index = state.resource?.entities.findIndex((ent) => ent.entityType === ResourceEntityType.Calendar);

				if (index >= 0)
				{
					const entityCalendar = state.resource.entities[index];
					entityCalendar.data = {
						...entityCalendar.data,
						...calendarPath,
					};
				}
			},
			setGlobalSchedule(state: ResourceCreationWizardState, checked: boolean): void
			{
				state.globalSchedule = Boolean(checked);
			},
			updateFetching(state: ResourceCreationWizardState, fetching: boolean): void
			{
				state.fetching = fetching;
			},
			setSaving(state: ResourceCreationWizardState, isSaving: boolean): void
			{
				state.isSaving = isSaving;
			},
			setCompanyScheduleAccess(state: ResourceCreationWizardState, isCompanyScheduleAccess: boolean): void
			{
				state.isCompanyScheduleAccess = isCompanyScheduleAccess;
			},
			setCompanyScheduleUrl(state: ResourceCreationWizardState, companyScheduleUrl: string): void
			{
				state.companyScheduleUrl = companyScheduleUrl;
			},
			setInvalidResourceName(state, invalid: boolean): void
			{
				state.invalidResourceName = invalid;
			},
			setInvalidResourceType(state, invalid: boolean): void
			{
				state.invalidResourceType = invalid;
			},
			setWeekStart(state, weekStart: string): void
			{
				state.weekStart = weekStart;
			},
			setCheckedForAll(state, { type, isChecked }: { type: string, isChecked: boolean }): void
			{
				state.checkedForAll[type] = isChecked;
			},
			setSlotLengthId(state, { slotLengthId }: { slotLengthId: number }): void
			{
				state.slotLengthId = slotLengthId;
			},
			setIsChannelChoiceAvailable(state, isChannelChoiceAvailable: boolean): void
			{
				state.isChannelChoiceAvailable = isChannelChoiceAvailable;
			},
			setIsIntegrationCalendarEnabled(state, isIntegrationCalendarEnabled: boolean): void
			{
				state.isIntegrationCalendarEnabled = isIntegrationCalendarEnabled;
			},
			setInvalidIntegrationCalendarUser(state, invalidIntegrationCalendarUser = false): void
			{
				state.invalidIntegrationCalendarUser = invalidIntegrationCalendarUser;
			},
		};
	}
}
