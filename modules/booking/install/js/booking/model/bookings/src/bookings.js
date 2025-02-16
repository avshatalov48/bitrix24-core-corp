import { BuilderModel, Store } from 'ui.vue3.vuex';
import type { GetterTree, ActionTree, MutationTree } from 'ui.vue3.vuex';

import { Model } from 'booking.const';
import { dateToTsRange } from './lib';
import type { BookingModel, BookingsState } from './types';

export class Bookings extends BuilderModel
{
	getName(): string
	{
		return Model.Bookings;
	}

	getState(): BookingsState
	{
		return {
			collection: {},
		};
	}

	getElementState(): BookingModel
	{
		return {
			id: 0,
			resourcesIds: [],
			clientId: 0,
			counter: 0,
			name: '',
			dateFromTs: 0,
			dateToTs: 0,
			timezoneFrom: Intl.DateTimeFormat().resolvedOptions().timeZone,
			timezoneTo: Intl.DateTimeFormat().resolvedOptions().timeZone,
			rrule: '',
			isConfirmed: false,
			visitStatus: 'unknown',
		};
	}

	getGetters(): GetterTree<BookingsState>
	{
		return {
			/** @function bookings/get */
			get: (state: BookingsState, getters, rootState, rootGetters): BookingModel[] => {
				const deletingBookings = rootGetters[`${Model.Interface}/deletingBookings`];

				return Object.values(state.collection).filter(({ id }) => !deletingBookings[id]);
			},
			/** @function bookings/getById */
			getById: (state: BookingsState) => (id: number | string): BookingModel => state.collection[id],
			/** @function bookings/getByIds */
			getByIds: (state: BookingsState, getters) => (ids: number[]): BookingModel[] => {
				return getters.get.filter((booking: BookingModel) => ids.includes(booking.id));
			},
			/** @function bookings/getByDateAndResources */
			getByDateAndResources: (state: BookingsState, getters) => {
				return (dateTs: number, resourcesIds: number[]): BookingModel[] => {
					return getters.getByDate(dateTs)
						.filter((booking: BookingModel) => {
							return resourcesIds
								.some((resourceId: number) => booking.resourcesIds.includes(resourceId))
							;
						})
					;
				};
			},
			/** @function bookings/getByDateAndIds */
			getByDateAndIds: (state: BookingsState, getters) => {
				return (dateTs: number, ids: number[]): BookingModel[] => {
					return getters.getByDate(dateTs)
						.filter((booking: BookingModel) => ids.includes(booking.id))
					;
				};
			},
			/** @function bookings/getByDate */
			getByDate: (state: BookingsState, getters) => (dateTs: number): BookingModel[] => {
				const [dateFrom, dateTo] = dateToTsRange(dateTs);

				return getters.get.filter(({ dateToTs, dateFromTs }) => dateToTs > dateFrom && dateTo > dateFromTs);
			},
		};
	}

	getActions(): ActionTree<BookingsState, any>
	{
		return {
			/** @function bookings/add */
			add: (store: Store, booking: BookingModel): void => {
				store.commit('add', booking);
			},
			/** @function bookings/insertMany */
			insertMany: (store: Store, bookings: BookingModel[]): void => {
				bookings.forEach((booking: BookingModel) => store.commit('insert', booking));
			},
			/** @function bookings/upsert */
			upsert: (store: Store, booking: BookingModel): void => {
				store.commit('upsert', booking);
			},
			/** @function bookings/upsertMany */
			upsertMany: (store: Store, bookings: BookingModel[]): void => {
				bookings.forEach((booking: BookingModel) => store.commit('upsert', booking));
			},
			/** @function bookings/update */
			update: (store: Store, payload: { id: number | string, booking: BookingModel }): void => {
				store.commit('update', payload);
			},
			/** @function bookings/delete */
			delete: (store: Store, bookingId: number | string): void => {
				store.commit('delete', bookingId);
			},
			deleteMany: (store: Store, bookingIds: number[]): void => {
				store.commit('deleteMany', bookingIds);
			},
		};
	}

	getMutations(): MutationTree<BookingsState>
	{
		return {
			add: (state: BookingsState, booking: BookingModel): void => {
				state.collection[booking.id] = booking;
			},
			insert: (state: BookingsState, booking: BookingModel): void => {
				state.collection[booking.id] ??= booking;
			},
			upsert: (state: BookingsState, booking: BookingModel): void => {
				state.collection[booking.id] ??= booking;
				Object.assign(state.collection[booking.id], booking);
			},
			update: (state: BookingsState, { id, booking }: { id: number | string, booking: BookingModel }): void => {
				const updatedBooking = { ...state.collection[id], ...booking };
				delete state.collection[id];
				state.collection[booking.id] = updatedBooking;
			},
			delete: (state: BookingsState, bookingId: number | string): void => {
				delete state.collection[bookingId];
			},
			deleteMany: (state, bookingIds: number[]): void => {
				for (const id of bookingIds)
				{
					delete state.collection[id];
				}
			},
		};
	}
}
