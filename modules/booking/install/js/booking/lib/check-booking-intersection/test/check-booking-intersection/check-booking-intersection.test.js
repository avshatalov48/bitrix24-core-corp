import { Type } from 'main.core';
import { checkBookingIntersection } from '../../src/check-booking-intersection';

describe('checkBookingIntersection', () => {
	it('Should be a function', () => {
		assert.equal(Type.isFunction(checkBookingIntersection), true);
	});
});
