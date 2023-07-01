(() => {
	const require = ext => jn.require(ext);
	const { describe, test, expect } = require('testing');
	const { MenuRecentStorage, EVENTS_LIMIT } = require('layout/ui/detail-card/floating-button/menu/recent/storage');

	// Mock Application object
	class Application
	{
		constructor()
		{
			this.keyValueStore = new Map();
		}

		storageById(storageId)
		{
			if (!this.keyValueStore.has(storageId))
			{
				this.keyValueStore.set(storageId, new Map());
			}

			return {
				getObject: (key, defaultValue) => this.keyValueStore.get(storageId).get(key) || defaultValue,
				setObject: (key, value) => this.keyValueStore.get(storageId).set(key, value),
			};
		}
	}

	const getRecentStorage = (arguments, application = null) => {
		const storage = new MenuRecentStorage(arguments);

		storage.setApplication(application || new Application());

		return storage;
	};

	describe('MenuRecentStorage', () => {
		test('Constructor throws error for invalid entityTypeId', () => {
			expect(() => getRecentStorage({ entityTypeId: -1 })).toThrow();
		});

		test('Constructor sets correct entityTypeId and categoryId', () => {
			const storage = getRecentStorage({ entityTypeId: 1, categoryId: 1 });

			expect(storage.entityTypeId).toBe(1);
			expect(storage.categoryId).toBe(1);
		});

		test('getRankedItems returns empty array when no events', () => {
			const storage = getRecentStorage({ entityTypeId: 1 });

			expect(storage.getRankedItems()).toEqual([]);
		});

		test('Add event with invalid actionId', () => {
			const storage = getRecentStorage({ entityTypeId: 1 });

			expect(() => storage.addEvent(null, 'tab1')).toThrow();
		});

		test('Add event without tabId', () => {
			const storage = getRecentStorage({ entityTypeId: 1 });

			storage.addEvent('action1', 'tab1');
			storage.addEvent('action1');

			const rankedItems = storage.getRankedItems();

			expect(rankedItems.length).toBe(2);
			expect(rankedItems[0]).toMatchObject({ actionId: 'action1', tabId: 'tab1', quantity: 1 });
			expect(rankedItems[1]).toMatchObject({ actionId: 'action1', tabId: null, quantity: 1 });
		});

		test('getRankedItems returns ranked items correctly', () => {
			const storage = getRecentStorage({ entityTypeId: 1 });

			// Add sample events
			storage.addEvent('action1', 'tab1');
			storage.addEvent('action1', 'tab1');
			storage.addEvent('action1', 'tab2');
			storage.addEvent('action2', 'tab1');
			storage.addEvent('action3', null);

			const rankedItems = storage.getRankedItems();
			expect(rankedItems.length).toBe(4);

			// Check sorting by quantity and timestamp
			expect(rankedItems[0]).toMatchObject({ actionId: 'action1', tabId: 'tab1', quantity: 2 });
			expect(rankedItems[1]).toMatchObject({ actionId: 'action1', tabId: 'tab2', quantity: 1 });
			expect(rankedItems[2]).toMatchObject({ actionId: 'action2', tabId: 'tab1', quantity: 1 });
			expect(rankedItems[3]).toMatchObject({ actionId: 'action3', tabId: null, quantity: 1 });
		});

		test('getRankedItems with categoryId returns ranked items correctly', () => {
			const storage = getRecentStorage({ entityTypeId: 1, categoryId: 1 });

			// Add sample events
			storage.addEvent('action1', 'tab1');
			storage.addEvent('action1', 'tab1');
			storage.addEvent('action1', 'tab2');
			storage.addEvent('action2', 'tab1');
			storage.addEvent('action3', null);

			const rankedItems = storage.getRankedItems();
			expect(rankedItems.length).toBe(4);

			// Check sorting by quantity and timestamp
			expect(rankedItems[0]).toMatchObject({ actionId: 'action1', tabId: 'tab1', quantity: 4 });
			expect(rankedItems[1]).toMatchObject({ actionId: 'action1', tabId: 'tab2', quantity: 2 });
			expect(rankedItems[2]).toMatchObject({ actionId: 'action2', tabId: 'tab1', quantity: 2 });
			expect(rankedItems[3]).toMatchObject({ actionId: 'action3', tabId: null, quantity: 2 });
		});

		test('EVENTS_LIMIT is respected', () => {
			const storage = getRecentStorage({ entityTypeId: 1, categoryId: 1 });

			for (let i = 0; i < (EVENTS_LIMIT + 50); i++)
			{
				storage.addEvent(`action${i}`, `tab${i}`);
			}

			const rankedItems = storage.getRankedItems();
			expect(rankedItems.length).toBe(EVENTS_LIMIT);
		});

		test('Different entity type events are separated', () => {
			const application = new Application();
			const storage1 = getRecentStorage({ entityTypeId: 1 }, application);
			const storage2 = getRecentStorage({ entityTypeId: 2 }, application);

			storage1.addEvent('action1', 'tab1');
			storage2.addEvent('action1', 'tab1');

			const rankedItems1 = storage1.getRankedItems();
			const rankedItems2 = storage2.getRankedItems();

			expect(rankedItems1.length).toBe(1);
			expect(rankedItems2.length).toBe(1);

			expect(rankedItems1[0]).toMatchObject({ actionId: 'action1', tabId: 'tab1', quantity: 1 });
			expect(rankedItems2[0]).toMatchObject({ actionId: 'action1', tabId: 'tab1', quantity: 1 });
		});

		test('Same category events are separated from entity events', () => {
			const application = new Application();
			const storage1 = getRecentStorage({ entityTypeId: 1, categoryId: 1 }, application);
			const storage2 = getRecentStorage({ entityTypeId: 1, categoryId: 2 }, application);

			storage1.addEvent('action1', 'tab1');
			storage2.addEvent('action1', 'tab1');

			const rankedItems1 = storage1.getRankedItems();
			const rankedItems2 = storage2.getRankedItems();

			expect(rankedItems1.length).toBe(1);
			expect(rankedItems2.length).toBe(1);

			expect(rankedItems1[0]).toMatchObject({ actionId: 'action1', tabId: 'tab1', quantity: 3 });
			expect(rankedItems2[0]).toMatchObject({ actionId: 'action1', tabId: 'tab1', quantity: 3 });
		});

		test('Different category events are separated from entity events', () => {
			const application = new Application();
			const storage1 = getRecentStorage({ entityTypeId: 1, categoryId: 1 }, application);
			const storage2 = getRecentStorage({ entityTypeId: 1, categoryId: 2 }, application);

			storage1.addEvent('action1', 'tab1');
			storage2.addEvent('action2', 'tab2');

			const rankedItems1 = storage1.getRankedItems();
			const rankedItems2 = storage2.getRankedItems();

			expect(rankedItems1.length).toBe(2);
			expect(rankedItems2.length).toBe(2);

			expect(rankedItems1[0]).toMatchObject({ actionId: 'action1', tabId: 'tab1', quantity: 2 });
			expect(rankedItems1[1]).toMatchObject({ actionId: 'action2', tabId: 'tab2', quantity: 1 });
			expect(rankedItems2[0]).toMatchObject({ actionId: 'action2', tabId: 'tab2', quantity: 2 });
			expect(rankedItems2[1]).toMatchObject({ actionId: 'action1', tabId: 'tab1', quantity: 1 });
		});
	});
})();
