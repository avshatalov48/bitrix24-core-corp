import { processCheckListFileIds } from '../src/util';

describe('FileService util processCheckListFileIds',() => {
	it('should keep objects with id and fileId',() => {
		const input = [
			{ id: 17, fileId: "343" },
			{ id: 18, fileId: "342" }
		];
		const result = processCheckListFileIds(input);

		assert.deepStrictEqual(result, [
			{ id: 17, fileId: "343" },
			{ id: 18, fileId: "342" }
		]);
	});

	it('should ignore plain numbers',() => {
		const result = processCheckListFileIds([17, 18]);

		assert.deepStrictEqual(result, []);
	});

	it('should convert n-prefixed strings to objects',() => {
		const result = processCheckListFileIds(["n483", "n123"]);

		assert.deepStrictEqual(result, [
			{ id: "n483", fileId: "n483" },
			{ id: "n123", fileId: "n123" }
		]);
	});

	it('should handle mixed input correctly',() => {
		const input = [
			{ id: 17, fileId: "343" },
			18,
			"n483",
			{ id: 30, fileId: "522" }
		];
		const result = processCheckListFileIds(input);

		assert.deepStrictEqual(result, [
			{ id: 17, fileId: "343" },
			{ id: "n483", fileId: "n483" },
			{ id: 30, fileId: "522" }
		]);
	});

	it('should return empty array for non-array input',() => {
		assert.deepStrictEqual(processCheckListFileIds(null), []);
		assert.deepStrictEqual(processCheckListFileIds(undefined), []);
		assert.deepStrictEqual(processCheckListFileIds("test"), []);
	});
});
