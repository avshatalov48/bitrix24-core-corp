import { Type } from 'main.core';
import { inInterval, IncludeBoundaries } from '../../src/in-interval';

describe('inInterval', () => {
	it('Should be a function', () => {
		assert.equal(Type.isFunction(inInterval), true);
	});

	const interval = [1, 10];

	describe('when set option "all"', () => {
		const options = {
			include: IncludeBoundaries.all,
		};

		it('should return true if value is within the interval ', () => {
			assert.equal(inInterval(1, interval, options), true);
			assert.equal(inInterval(2, interval, options), true);
			assert.equal(inInterval(10, interval, options), true);
			assert.equal(inInterval(0, interval, options), false);
			assert.equal(inInterval(11, interval, options), false);
		});
	});

	describe('when set option "left"', () => {
		const options = {
			include: IncludeBoundaries.left,
		};

		it('should return true if value is within the interval ', () => {
			assert.equal(inInterval(0, interval, options), false);
			assert.equal(inInterval(1, interval, options), true);
			assert.equal(inInterval(3, interval, options), true);
			assert.equal(inInterval(9, interval, options), true);
			assert.equal(inInterval(10, interval, options), false);
			assert.equal(inInterval(11, interval, options), false);
		});
	});

	describe('when set option "right"', () => {
		const options = {
			include: IncludeBoundaries.right,
		};

		it('should return true if value is within the interval ', () => {
			assert.equal(inInterval(0, interval, options), false);
			assert.equal(inInterval(1, interval, options), false);
			assert.equal(inInterval(2, interval, options), true);
			assert.equal(inInterval(9, interval, options), true);
			assert.equal(inInterval(10, interval, options), true);
			assert.equal(inInterval(11, interval, options), false);
		});
	});

	describe('when set option "none"', () => {
		const options = {
			include: IncludeBoundaries.none,
		};

		it('should return true if value is within the interval', () => {
			assert.equal(inInterval(0, interval, options), false);
			assert.equal(inInterval(1, interval, options), false);
			assert.equal(inInterval(2, interval, options), true);
			assert.equal(inInterval(5, interval, options), true);
			assert.equal(inInterval(9, interval, options), true);
			assert.equal(inInterval(10, interval, options), false);
			assert.equal(inInterval(11, interval, options), false);
		});
	});
	it('should set "none" by default', () => {
		assert.equal(inInterval(1, interval), false);
		assert.equal(inInterval(1, interval, {}), false);
		assert.equal(inInterval(10, interval), false);
		assert.equal(inInterval(10, interval, {}), false);
	});
});
