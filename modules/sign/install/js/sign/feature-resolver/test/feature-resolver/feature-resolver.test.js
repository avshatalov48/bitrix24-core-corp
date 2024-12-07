import { FeatureResolver } from '../../src/feature-resolver';

describe('Feature', () => {
	it('Should be a function', () => {
		assert(typeof FeatureResolver === 'function');
	});
	it('Should throw an error if instance is created directly', () => {
		assert.throws(() => {
			const feature = new FeatureResolver();
		});
	});
	it('Should return an instance', () => {
		const feature = FeatureResolver.instance();
		assert(typeof feature === 'object');
	});
});
