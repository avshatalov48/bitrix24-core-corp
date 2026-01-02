import { BuilderModel } from 'ui.vue3.vuex';
import type { ActionTree, GetterTree, MutationTree } from 'ui.vue3.vuex';

import { Model } from 'booking.const';

import type { BookingInfoState, BookingInfoModel, ResourceInfo, ServiceInfo, ClientInfo } from './types';

export class BookingInfo extends BuilderModel
{
	getName(): string
	{
		return Model.BookingInfo;
	}

	getState(): BookingInfoState
	{
		return {
			bookingId: this.getVariable('bookingId', null),
			resources: [],
			services: [],
			client: {},
			note: '',
		};
	}

	getGetters(): GetterTree<BookingInfoState, any>
	{
		return {
			/** @function booking-info/resources */
			resources: (state): ResourceInfo[] => state.resources,
			/** @function booking-info/services */
			services: (state): ServiceInfo[] => state.services,
			/** @function booking-info/client */
			client: (state): ClientInfo => state.client,
			/** @function booking-info/note */
			note: (state): string => state.note,
		};
	}

	getActions(): ActionTree<BookingInfoState, any>
	{
		return {
			setBookingInfo({ commit }, bookingInfo: BookingInfoModel): void
			{
				commit('setBookingInfo', bookingInfo);
			},
		};
	}

	getMutations(): MutationTree<BookingInfoState>
	{
		return {
			setBookingInfo(state, bookingInfo: BookingInfoModel): void
			{
				state.id = bookingInfo.id;
				state.note = bookingInfo.note;
				state.resources = bookingInfo.resources;
				state.services = bookingInfo.services;
				state.client = bookingInfo.client;
			},
		};
	}
}
