import { ActivityFastSearchMutator } from '../../src/activity-fast-search-mutator';

const assert = require('assert');

describe('ActivityFastSearchMutator', () => {

	let mutator;

	beforeEach(() => {
		mutator = new ActivityFastSearchMutator();
	});

	describe('mutate', () => {
		it('should add ACTIVITY_FASTSEARCH_CREATED field if filter has activity fields and ACTIVITY_FASTSEARCH_CREATED field is missing', () => {
			const fields = {
				ACTIVITY_FASTSEARCH_NAME: 'John',
				ACTIVITY_FASTSEARCH_STATUS: 'COMPLETED',
			};
			let hasChanges;
			let mutatedFields;
			[mutatedFields, hasChanges] = mutator.mutate(fields, {});

			// assert what hasChanges is true


			assert.equal(hasChanges, true);
			assert.deepStrictEqual(mutatedFields, {
				...fields,
				ACTIVITY_FASTSEARCH_CREATED: '365',
			});
		});

		it('should not add ACTIVITY_FASTSEARCH_CREATED field if filter does not have activity fields', () => {
			const fields = {
				NAME: 'John',
				STATUS: 'COMPLETED',
			};
			let hasChanges;
			let mutatedFields;
			[mutatedFields, hasChanges] = mutator.mutate(fields, {});
			assert.equal(hasChanges, false);
			assert.deepStrictEqual(mutatedFields, fields);
		});

		it('should not add ACTIVITY_FASTSEARCH_CREATED field if ACTIVITY_FASTSEARCH_CREATED field already exists', () => {
			const fields = {
				ACTIVITY_FASTSEARCH_NAME: 'John',
				ACTIVITY_FASTSEARCH_STATUS: 'COMPLETED',
				ACTIVITY_FASTSEARCH_CREATED: '30',
			};
			let hasChanges;
			let mutatedFields;
			[mutatedFields, hasChanges] = mutator.mutate(fields, {});
			assert.equal(hasChanges, false);
			assert.deepStrictEqual(mutatedFields, fields);
		});

		it('should not add ACTIVITY_FASTSEARCH_CREATED field if ACTIVITY_FASTSEARCH_STATUS field is set to "NONE"', () => {
			const fields = {
				NAME: 'John',
				ACTIVITY_FASTSEARCH_STATUS: 'NONE',
			};
			let hasChanges;
			let mutatedFields;
			[mutatedFields, hasChanges] = mutator.mutate(fields, {});
			assert.equal(hasChanges, false);
			assert.deepStrictEqual(mutatedFields, {
				NAME: 'John',
				ACTIVITY_FASTSEARCH_STATUS: 'NONE',
			});
		});

		it('should not add ACTIVITY_FASTSEARCH_CREATED field if ACTIVITY_FASTSEARCH_STATUS field is an empty array', () => {
			const fields = {
				NAME: 'John',
				ACTIVITY_FASTSEARCH_STATUS: [],
			};
			let hasChanges;
			let mutatedFields;
			[mutatedFields, hasChanges] = mutator.mutate(fields, {});
			assert.equal(hasChanges, false);
			assert.deepStrictEqual(mutatedFields, {
				NAME: 'John',
				ACTIVITY_FASTSEARCH_STATUS: [],
			});
		});
	});
});