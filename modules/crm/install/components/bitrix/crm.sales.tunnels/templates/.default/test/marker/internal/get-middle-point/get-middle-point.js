import getMiddlePoint from '../../../../src/marker/internal/get-middle-point';
import validRects from './data/valid-rects';
import invalidRects from './data/invalid-rects';

describe('crm.sales.tunnels/.default', () => {
	describe('marker/internal/get-middle-point', () => {
		it('Should exported as function', () => {
			assert.ok(
				typeof getMiddlePoint === 'function',
				'isRect is not a function',
			);
		});

		it('Should valid point if passed valid rect', () => {
			validRects.forEach((item) => {
				assert.deepEqual(
					getMiddlePoint(item.rect),
					item.result,
					`Return bad result for valid rect 
					 rect:    ${JSON.stringify(item.rect)}
					 returns: ${JSON.stringify(getMiddlePoint(item.rect))}
					 result:  ${JSON.stringify(item.result)}`,
				);
			});
		});

		it('Should throws if passed invalid rect', () => {
			invalidRects.forEach((rect) => {
				assert.throws(
					() => getMiddlePoint(rect),
					'Does not throws for invalid rect'
				);
			});
		});
	});
});