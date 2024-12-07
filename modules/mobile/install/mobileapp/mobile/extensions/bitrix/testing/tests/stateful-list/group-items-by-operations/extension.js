(() => {
	const require = (ext) => jn.require(ext);
	const { StatefulList } = require('layout/ui/stateful-list');
	const { describe, test, expect } = require('testing');

	const prepareItem = (item) => {
		return {
			id: item.id,
			name: item.name,
		};
	};

	const prepareResult = (result) => {
		return {
			add: result.add.map((item) => prepareItem(item)),
			update: result.update.map((item) => prepareItem(item)),
			delete: result.delete,
		};
	};

	describe('layout/ui/stateful-list groupItemsByOperations', () => {
		test('should return empty operations with empty itemsToProcess and itemsFromServer', () => {
			const itemsToProcess = [];
			const itemsFromServer = [];
			const stateItems = [
				{ id: 1, name: 'Item 1' },
				{ id: 2, name: 'Item 2' },
				{ id: 3, name: 'Item 3' },
			];

			const statefulList = new StatefulList({});
			statefulList.state.items = stateItems;
			const result = statefulList.groupItemsByOperations(itemsToProcess, itemsFromServer);
			expect(result).toEqual({ add: [], update: [], delete: [] });
		});

		test('should add new items if they exists in itemsToProcess, itemsFromServer, but not in stateItems', () => {
			const itemsToProcess = [1, 2, 3];
			const itemsFromServer = [
				{ id: 1, name: 'Item 1' },
				{ id: 2, name: 'Item 2' },
				{ id: 3, name: 'Item 3' },
			];
			const stateItems = [];

			const statefulList = new StatefulList({});
			statefulList.state.items = stateItems;
			const result = prepareResult(statefulList.groupItemsByOperations(itemsToProcess, itemsFromServer));
			expect(result).toEqual({
				add: [
					{ id: 1, name: 'Item 1' },
					{ id: 2, name: 'Item 2' },
					{ id: 3, name: 'Item 3' },
				],
				update: [],
				delete: [],
			});
		});

		test('should update if id exists in all variables (itemsToProcess, itemsFromServer, stateItems)', () => {
			const itemsToProcess = [1, 2];
			const itemsFromServer = [
				{ id: 1, name: 'Item 1 from server' },
				{ id: 2, name: 'Item 2 from server' },
			];
			const stateItems = [
				{ id: 1, name: 'Item 1' },
				{ id: 2, name: 'Item 2' },
				{ id: 3, name: 'Item 3' },
				{ id: 4, name: 'Item 4' },
			];

			const statefulList = new StatefulList({});
			statefulList.state.items = stateItems;
			const result = prepareResult(statefulList.groupItemsByOperations(itemsToProcess, itemsFromServer));
			expect(result).toEqual({
				add: [],
				update: [
					{ id: 1, name: 'Item 1 from server' },
					{ id: 2, name: 'Item 2 from server' },
				],
				delete: [],
			});
		});

		test('should delete if id exists in itemsToProcess and stateItems, but not in itemsFromServer', () => {
			const itemsToProcess = [1];
			const itemsFromServer = [];
			const stateItems = [
				{ id: 1, name: 'Item 1' },
			];

			const statefulList = new StatefulList({});
			statefulList.state.items = stateItems;
			const result = prepareResult(statefulList.groupItemsByOperations(itemsToProcess, itemsFromServer));
			expect(result).toEqual({
				add: [],
				update: [],
				delete: [1],
			});
		});

		test('should add, delete, update in one iteration', () => {
			const itemsToProcess = [1, 2, 3, 4, 5];
			const itemsFromServer = [
				{ id: 1, name: 'Item 1 from server' },
				{ id: 2, name: 'Item 2 from server' },
				{ id: 4, name: 'Item 4 from server' },
				{ id: 5, name: 'Item 5 from server' },
			];
			const stateItems = [
				{ id: 1, name: 'Item 1' },
				{ id: 2, name: 'Item 2' },
				{ id: 3, name: 'Item 3' },
				{ id: 4, name: 'Item 4' },
				{ id: 6, name: 'Item 6' },
			];

			const statefulList = new StatefulList({});
			statefulList.state.items = stateItems;
			const result = prepareResult(statefulList.groupItemsByOperations(itemsToProcess, itemsFromServer));
			expect(result).toEqual({
				add: [{ id: 5, name: 'Item 5 from server' }],
				update: [
					{ id: 1, name: 'Item 1 from server' },
					{ id: 2, name: 'Item 2 from server' },
					{ id: 4, name: 'Item 4 from server' },
				],
				delete: [3],
			});
		});
	});
})();
