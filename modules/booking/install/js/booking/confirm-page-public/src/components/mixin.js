export const Mixin = {
	computed: {
		isBookingCanceled(): boolean
		{
			return this.booking.isDeleted === true;
		},
	},
};
