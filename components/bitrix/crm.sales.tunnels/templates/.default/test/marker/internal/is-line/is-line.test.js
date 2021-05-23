import isLine from '../../../../src/marker/internal/is-line';
import validLines from './data/valid-lines';
import invalidLines from './data/invalid-lines';

describe('crm.sales.tunnels/.default', () => {
	describe('marker/internal/is-line', () => {
		it('Should be exported as function', () => {
			assert.ok(typeof isLine === 'function', 'isLine is not a function');
		});

		it('Should return true if passed valid line', () => {
			validLines.forEach((line) => {
				assert.ok(
					isLine(line) === true,
					`Return false for valid line ${JSON.stringify(line)}`,
				);
			});
		});

		it('Should return false if passed invalid line', () => {
			invalidLines.forEach((line) => {
				assert.ok(
					isLine(line) === false,
					`Return true for invalid line ${JSON.stringify(line)}`,
				);
			});
		});

		it('Should does not throws if passed invalid params', () => {
			assert.doesNotThrow(() => {
				isLine();
			});
		});
	});
});