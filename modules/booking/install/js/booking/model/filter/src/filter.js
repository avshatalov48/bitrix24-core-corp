/* eslint-disable no-param-reassign */

import { Type } from 'main.core';
import { BuilderModel } from 'ui.vue3.vuex';
import type { ActionTree, GetterTree, MutationTree } from 'ui.vue3.vuex';

import { Model } from 'booking.const';
import type { ResourceModel } from 'booking.model.resources';

import type {
	FilterModelState,
	DatesCount,
	FilterFields,
	QuickFilter,
	DeletingResourceFilter,
} from './types';

export class Filter extends BuilderModel
{
	getName(): string
	{
		return Model.Filter;
	}

	getState(): FilterModelState
	{
		return {
			datesCount: {
				count: 0,
				minDate: '',
				maxDate: '',
			},
			fields: {},
			filterDates: [],
			fetchingNextDate: false,
			filteredBookingsIds: [],
			filteredMarks: [],
			isFilterMode: false,
			quickFilter: {
				hovered: {},
				active: {},
				ignoredBookingIds: {},
			},
			deletingResourceFilter: null,
		};
	}

	getGetters(): GetterTree<FilterModelState>
	{
		return {
			/** @function filter/datesCount */
			datesCount: (state): DatesCount => state.datesCount,
			/** @function filter/fields */
			fields: (state): FilterFields => state.fields,
			/** @function filter/displayFields */
			displayField: (state): FilterFields => state.fields,
			/** @function filter/resquestFields */
			requestFields: (state, getters): FilterFields => {
				if (
					getters.isDeletingResourceFilterMode
					&& Object.keys(state.deletingResourceFilter?.requestFields || {}).length > 0
				)
				{
					return state.deletingResourceFilter?.requestFields;
				}

				return state.fields;
			},
			/** @function filter/isMaxFilterDate */
			isMaxFilterDate: (state, getters, rootState, rootGetters): boolean => {
				return (
					state.datesCount?.maxDate?.length > 0
					&& rootGetters[`${Model.Interface}/selectedDateTs`] >= new Date(state.datesCount.maxDate).setHours(
						0,
						0,
						0,
						0,
					)
				);
			},
			/** @function filter/isMinFilterDate */
			isMinFilterDate: (state, getters, rootState, rootGetters): boolean => {
				return (
					state.datesCount?.minDate?.length > 0
					&& rootGetters[`${Model.Interface}/selectedDateTs`] <= new Date(state.datesCount.minDate).setHours(
						0,
						0,
						0,
						0,
					)
				);
			},
			/** @function filter/fetchingNextDate */
			fetchingNextDate: (state): boolean => state.fetchingNextDate,
			/** @function interface/filteredBookingsIds */
			filteredBookingsIds: (state): number[] => state.filteredBookingsIds,
			/** @function interface/filteredMarks */
			filteredMarks: (state): string[] => state.filteredMarks,
			/** @function interface/isFilterMode */
			isFilterMode: (state): boolean => state.isFilterMode,
			/** @typedef filter/isDeletingResourceFilterMode */
			isDeletingResourceFilterMode: (state): boolean => {
				return Type.isNumber(state.deletingResourceFilter?.resourceId);
			},
			deletingResource: (state, getters, rootState, rootGetters): ResourceModel | null => {
				if (getters.isDeletingResourceFilterMode)
				{
					return rootGetters[`${Model.Resources}/getById`](state.deletingResourceFilter.resourceId);
				}

				return null;
			},
			/** @function interface/quickFilter */
			quickFilter: (state): QuickFilter => state.quickFilter,
		};
	}

	getActions(): ActionTree<FilterModelState>
	{
		return {
			/** @function filter/setFilterFields */
			setFilterFields: ({ commit }, fields: FilterFields): void => {
				commit('setFilterFields', fields);
			},
			/** @function filter/setDatesCount */
			setDatesCount: (store, datesCount: DatesCount): void => {
				store.commit('setDatesCount', datesCount);
			},
			/** @function filter/addFilterDates */
			addFilterDates: ({ commit }, filterDates: string[]): void => {
				commit('addFilterDates', filterDates);
			},
			/** @function filter/clearDatesCount */
			clearDatesCount: (store): void => {
				store.commit('setDatesCount', {
					count: 0,
					minDate: '',
					maxDate: '',
				});
			},
			/** @function filter/clearFilter */
			clearFilter: ({ getters, commit }): void => {
				commit('setFilterFields', {});
				commit('clearFilterDates');
				commit('setDatesCount', {
					count: 0,
					minDate: '',
					maxDate: '',
				});

				if (getters.isDeletingResourceFilterMode)
				{
					commit('setDeletingResourceFilter', null);
				}
			},
			setFetchingNextDate: ({ commit }, fetchingNextDate: boolean): void => {
				commit('setFetchingNextDate', fetchingNextDate);
			},
			/** @function interface/setFilteredBookingsIds */
			setFilteredBookingsIds: (store, filteredBookingsIds: number[]) => {
				store.commit('setFilteredBookingsIds', filteredBookingsIds);
			},
			/** @function interface/setFilteredMarks */
			setFilteredMarks: (store, dates: number[]) => {
				store.commit('setFilteredMarks', dates);
			},
			/** @function interface/setFilterMode */
			setFilterMode: (store, isFilterMode: boolean) => {
				store.commit('setFilterMode', isFilterMode);
			},
			/** @function interface/hoverQuickFilter */
			hoverQuickFilter: (store, hour: number) => {
				store.commit('hoverQuickFilter', hour);
			},
			/** @function interface/fleeQuickFilter */
			fleeQuickFilter: (store, hour: number) => {
				store.commit('fleeQuickFilter', hour);
			},
			/** @function interface/activateQuickFilter */
			activateQuickFilter: (store, hour: number) => {
				store.commit('activateQuickFilter', hour);
				store.commit('clearQuickFilterIgnoredBookingIds');
			},
			/** @function interface/deactivateQuickFilter */
			deactivateQuickFilter: (store, hour: number) => {
				store.commit('deactivateQuickFilter', hour);
				store.commit('clearQuickFilterIgnoredBookingIds');
			},
			/** @function filter/addQuickFilterIgnoredBookingId */
			addQuickFilterIgnoredBookingId: (store, bookingId: number) => {
				store.commit('addQuickFilterIgnoredBookingId', bookingId);
			},
			/** @function filter/setDeletingResourceFilter */
			setDeletingResourceFilter: ({ commit }, deletingResourceFilter: DeletingResourceFilter | null) => {
				commit('setDeletingResourceFilter', deletingResourceFilter);
			},
			/** @function filter/setDeletionResourceFIlterFields */
			setDeletionResourceFilterFields: ({ commit, state }, fields: FilterFields) => {
				if (state.deletingResourceFilter && Type.isNumber(state.deletingResourceFilter.resourceId))
				{
					commit('setDeletionResourceFilterFields', fields);
				}
			},
		};
	}

	getMutations(): MutationTree<FilterModelState>
	{
		return {
			setFilterFields: (state, fields: FilterFields) => {
				state.fields = fields;
			},
			setDatesCount: (state, datesCount: DatesCount) => {
				state.datesCount = datesCount;
			},
			addFilterDates: (state, filterDates: string[]) => {
				const filterDatesSet = new Set([
					...state.filterDates,
					...filterDates.map((date) => new Date(date).setHours(0, 0, 0, 0)),
				]);

				state.filterDates = [...filterDatesSet].sort();
			},
			clearFilterDates: (state) => {
				state.filterDates = [];
			},
			setFetchingNextDate: (state, fetchingNextDate: boolean) => {
				state.fetchingNextDate = fetchingNextDate;
			},
			setFilteredBookingsIds: (state, filteredBookingsIds: number[]) => {
				state.filteredBookingsIds = [...filteredBookingsIds];
			},
			setFilteredMarks: (state, dates: number[]) => {
				state.filteredMarks = dates;
			},
			setFilterMode: (state, isFilterMode: boolean) => {
				state.isFilterMode = isFilterMode;
			},
			hoverQuickFilter: (state, hour: number) => {
				state.quickFilter.hovered[hour] = hour;
			},
			fleeQuickFilter: (state, hour: number) => {
				delete state.quickFilter.hovered[hour];
			},
			activateQuickFilter: (state, hour: number) => {
				state.quickFilter.active[hour] = hour;
			},
			deactivateQuickFilter: (state, hour: number) => {
				delete state.quickFilter.active[hour];
			},
			addQuickFilterIgnoredBookingId: (state, bookingId: number) => {
				state.quickFilter.ignoredBookingIds[bookingId] = bookingId;
			},
			clearQuickFilterIgnoredBookingIds: (state) => {
				state.quickFilter.ignoredBookingIds = {};
			},
			setDeletingResourceFilter: (state, deletingResourceFilter: DeletingResourceFilter | null) => {
				state.deletingResourceFilter = deletingResourceFilter;
			},
			setDeletionResourceFilterFields: (state, fields: FilterFields) => {
				state.deletingResourceFilter.requestFields = fields;
			},
		};
	}
}
