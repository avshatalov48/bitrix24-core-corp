import isOverlap from '../../../../src/marker/internal/is-overlap';
import {sorted as overlappedSorted, unsorted as overlappedUnsorted} from './data/overlapped-lines';
import {sorted as notOverlappedSorted, unsorted as notOverlappedUnsorted} from './data/not-overlapped-lines';
import incorrectLines from './data/incorrect-lines';

describe('crm.sales.tunnels/.default', () => {
	describe('marker/internal/is-overlap', () => {
		it('Should be exported as function', () => {
			assert(typeof isOverlap === 'function');
		});

		it('Should return true if passed overlapped lines', () => {
			overlappedSorted.forEach((item, index) => {
				assert.ok(
					isOverlap(item.line1, item.line2) === true,
					`#sorted, lines from item #${index} not overlapped`,
				);
			});

			overlappedUnsorted.forEach((item, index) => {
				assert.ok(
					isOverlap(item.line1, item.line2) === true,
					`#unsorted, lines from item #${index} not overlapped`,
				);
			});
		});

		it('Should return false if passed not overlapped lines', () => {
			notOverlappedSorted.forEach((item, index) => {
				assert.ok(
					isOverlap(item.line1, item.line2) === false,
					`#sorted, lines from item #${index} not overlapped`,
				);
			});

			notOverlappedUnsorted.forEach((item, index) => {
				assert.ok(
					isOverlap(item.line1, item.line2) === false,
					`#unsorted, lines from item #${index} not overlapped`,
				);
			});
		});

		it('Should throws if passed incorrect params', () => {
			incorrectLines.forEach((item, index) => {
				assert.throws(
					() => {
						isOverlap(item.line1, item.line2);
					},
					`Not throws for item ${index}`
				);
			});
		});
	});
});