import isRect from '../../../../src/marker/internal/is-rect';
import validRects from './data/valid-rects';
import invalidRects from './data/invalid-rects';

describe('crm.sales.tunnels/.default', () => {
	describe('marker/internal/is-rect', () => {
		it('Should exported as function', () => {
			assert.ok(
				typeof isRect === 'function',
				'isRect is not a function',
			);
		});

		it('Should return true if passed valid rect', () => {
			validRects.forEach((rect) => {
				assert.ok(
					isRect(rect) === true,
					`Return false for valid rect ${JSON.stringify(rect)}`,
				);
			});
		});

		it('Should return false if passed invalid rect', () => {
			invalidRects.forEach((rect) => {
				assert.ok(
					isRect(rect) === false,
					`Return true for invalid rect ${JSON.stringify(rect)}`,
				);
			});
		});
	});
});