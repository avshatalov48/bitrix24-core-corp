import type { BookingInfoModel } from 'booking.model.booking-info';
import type { BookingInfoResponse } from './types';

export function mapDtoToModel(bookingInfoDto: BookingInfoResponse): BookingInfoModel
{
	return {
		id: bookingInfoDto.id,
		resources: bookingInfoDto.resources || [],
		services: bookingInfoDto.services || [],
		client: bookingInfoDto.client,
		note: bookingInfoDto.note ?? '',
	};
}
