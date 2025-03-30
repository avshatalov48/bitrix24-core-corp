import { Loc, Type } from 'main.core';
import { BuilderModel } from 'ui.vue3.vuex';
import type { ActionTree, GetterTree, MutationTree } from 'ui.vue3.vuex';

import { Model, NotificationFieldsMap } from 'booking.const';
import { SlotRange } from 'booking.model.resources';
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
			isCompanyScheduleAccess: false,
			weekStart: 'Mon',
			globalSchedule: false,
			checkedForAll: {},
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
			invalidChooseTypeCard: (state): boolean => Type.isNull(state.resource.typeId),
			invalidSettingsCard: (state): boolean => {
				return state.invalidResourceName || !state.resource.typeId;
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
			/** @function resource-creation-wizard/weekStart */
			weekStart: (state): boolean => state.weekStart,
			/** @function resource-creation-wizard/isCheckedForAll */
			isCheckedForAll: (state) => (type: string): boolean => state.checkedForAll[type] ?? true,
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
			setAdvertisingResourceTypes({ commit }, types: AdvertisingResourceType[]): void
			{
				const advertisingResourceType: AdvertisingResourceType[] = [
					...types,
					{
						code: 'none',
						name: Loc.getMessage('BRCW_CHOOSE_CATEGORY_YOUR_TYPE'),
						relatedResourceTypeId: 0,
					},
				];

				commit('setAdvertisingResourceTypes', advertisingResourceType);
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
					].reduce((acc: Partial<ResourceModel>, field: $Keys<ResourceModel>) => ({
						...acc,
						[field]: resourceType[field],
					}), {});

					Object.assign(patch, notifications);
				}

				commit('updateResource', patch);
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
			setAdvertisingResourceTypes(state: ResourceCreationWizardState, types: AdvertisingResourceType[]): void
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
		};
	}
}
