import { Type } from 'main.core';
import type { BookingModel } from 'booking.model.bookings';
import type { BookingDto } from './types';

export function mapModelToDto(booking: BookingModel): BookingDto
{
	const mappings = {
		id: () => Number(booking.id) || 0,
		resources: () => booking.resourcesIds.map((id) => ({ id })),
		primaryClient: () => booking.clients?.[0],
		clients: () => booking.clients,
		name: () => booking.name,
		datePeriod: () => ({
			from: {
				timestamp: booking.dateFromTs / 1000,
				timezone: booking.timezoneFrom,
			},
			to: {
				timestamp: booking.dateToTs / 1000,
				timezone: booking.timezoneTo,
			},
		}),
		isConfirmed: () => booking.isConfirmed,
		rrule: () => booking.rrule,
		note: () => booking.note,
		visitStatus: () => booking.visitStatus,
		externalData: () => booking.externalData,
	};

	const dependentFields = new Map([
		['resources', ['resourcesIds']],
		['datePeriod', ['dateFromTs', 'dateToTs']],
	]);

	return Object.keys(mappings).reduce((result, field) => {
		const dependencies = dependentFields.get(field);
		const hasDependencies = dependencies ? dependencies.every((dep) => dep in booking) : true;

		if (hasDependencies && (field in booking || dependencies))
		{
			const value = mappings[field]();
			if (value !== undefined)
			{
				// eslint-disable-next-line no-param-reassign
				result[field] = value;
			}
		}

		return result;
	}, {});
}

export function mapDtoToModel(bookingDto: BookingDto): BookingModel
{
	const clients = bookingDto.clients.filter((client) => Type.isArrayFilled(Object.values(client.data)));

	return {
		id: bookingDto.id,
		updatedAt: bookingDto.updatedAt,
		resourcesIds: bookingDto.resources.map(({ id }) => id),
		primaryClient: clients?.[0],
		clients,
		counter: bookingDto.counter,
		counters: bookingDto.counters,
		name: bookingDto.name,
		dateFromTs: bookingDto.datePeriod.from.timestamp * 1000,
		timezoneFrom: bookingDto.datePeriod.from.timezone,
		dateToTs: bookingDto.datePeriod.to.timestamp * 1000,
		timezoneTo: bookingDto.datePeriod.to.timezone,
		isConfirmed: bookingDto.isConfirmed,
		rrule: bookingDto.rrule,
		note: bookingDto.note,
		visitStatus: bookingDto.visitStatus,
		externalData: bookingDto.externalData,
	};
}
