/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,main_core,booking_const,booking_core) {
	'use strict';

	const CounterDictionary = Object.freeze({
	  BookingDelayed: 'booking_delayed',
	  BookingUnconfirmed: 'booking_unconfirmed'
	});
	class BookingFilter {
	  prepareFilter(fields, withinMonth = false) {
	    const dateTs = booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/${withinMonth ? 'viewDateTs' : 'selectedDateTs'}`];
	    const date = new Date(dateTs);
	    const filter = {
	      WITHIN: {
	        DATE_FROM: date.getTime() / 1000,
	        DATE_TO: withinMonth ? date.setMonth(date.getMonth() + 1) / 1000 : date.setDate(date.getDate() + 1) / 1000
	      }
	    };
	    if (main_core.Type.isArrayFilled(fields.CREATED_BY)) {
	      filter.CREATED_BY = fields.CREATED_BY.map(id => Number(id));
	    }
	    if (main_core.Type.isArrayFilled(fields.CONTACT)) {
	      filter.CRM_CONTACT_ID = fields.CONTACT.map(id => Number(id));
	    }
	    if (main_core.Type.isArrayFilled(fields.COMPANY)) {
	      filter.CRM_COMPANY_ID = fields.COMPANY.map(id => Number(id));
	    }
	    if (main_core.Type.isArrayFilled(fields.RESOURCE)) {
	      filter.RESOURCE_ID = fields.RESOURCE.map(id => Number(id));
	    }
	    if (fields.CONFIRMED) {
	      filter.IS_CONFIRMED = {
	        Y: 1,
	        N: 0
	      }[fields.CONFIRMED];
	    }
	    if (fields.REQUIRE_ATTENTION) {
	      filter.HAS_COUNTER_OF_TYPE = {
	        D: CounterDictionary.BookingDelayed,
	        AC: CounterDictionary.BookingUnconfirmed
	      }[fields.REQUIRE_ATTENTION];
	    }
	    return filter;
	  }
	}
	const bookingFilter = new BookingFilter();

	class BookingDateCountFilter extends BookingFilter {
	  prepareUndatedFilter(fields, withinMonth = false) {
	    const filter = super.prepareFilter(fields, withinMonth);
	    delete filter.WITHIN;
	    return filter;
	  }
	  prepareFutureOnlyFilter(fields, withinMonth = false) {
	    const filter = super.prepareFilter(fields, withinMonth);
	    filter.WITHIN = {
	      DATE_FROM: Math.trunc(Date.now() / 1000)
	    };
	    return filter;
	  }
	  prepareFutureFilter(fields, withinMonth = false) {
	    const filter = super.prepareFilter(fields, withinMonth);
	    const dateFrom = filter.WITHIN.DATE_FROM;
	    const today = Math.trunc(Date.now() / 1000);
	    if (dateFrom < today) {
	      filter.WITHIN.DATE_FROM = today;
	    }
	    return filter;
	  }
	}
	const bookingDateCountFilter = new BookingDateCountFilter();

	exports.bookingFilter = bookingFilter;
	exports.bookingDateCountFilter = bookingDateCountFilter;

}((this.BX.Booking.Lib = this.BX.Booking.Lib || {}),BX,BX.Booking.Const,BX.Booking));
//# sourceMappingURL=booking-filter.bundle.js.map
