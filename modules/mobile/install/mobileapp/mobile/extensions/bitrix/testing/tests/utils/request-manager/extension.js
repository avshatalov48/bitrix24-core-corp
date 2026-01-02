(() => {
	const require = (ext) => jn.require(ext);
	const { RequestManager } = require('rest/run-action-executor');
	const { describe, test, beforeEach, expect } = require('testing');

	class TestStorage
	{
		constructor()
		{
			this.data = new Map();
		}

		get(key)
		{
			return this.data.get(key);
		}

		set(key, value)
		{
			this.data.set(key, value);
		}
	}

	function createManager(testLogger, testStorage)
	{
		return new RequestManager(
			'testAction',
			{},
			testLogger,
			{},
			testStorage,
		);
	}

	describe('RequestManager', () => {
		let testStorage = null;
		let testLogger = null;

		beforeEach(() => {
			testStorage = new TestStorage();
			testLogger = {
				info: () => {},
				warn: () => {},
			};
		});

		test('getSameOngoingRequest should return null if no request exists', () => {
			const manager = createManager(testLogger, testStorage);

			const result = manager.getSameOngoingRequest();

			expect(result).toBeNull();
		});

		test('getSameOngoingRequest should return the request if it is pending', () => {
			const manager = createManager(testLogger, testStorage);

			manager.setRequestStatus(RequestManager.PROMISE_STATUS.PENDING);

			const result = manager.getSameOngoingRequest();

			expect(result).not.toBeNull();
			expect(result?.status).toBe(RequestManager.PROMISE_STATUS.PENDING);
		});

		test('getSameOngoingRequest should return null if the request is fulfilled', () => {
			const manager = createManager(testLogger, testStorage);

			manager.setRequestStatus(RequestManager.PROMISE_STATUS.FULFILLED, { data: 'success' });

			const result = manager.getSameOngoingRequest();

			expect(result).toBeNull();
		});

		test('getSameOngoingRequest should return null if the request is rejected', () => {
			const manager = createManager(testLogger, testStorage);

			manager.setRequestStatus(RequestManager.PROMISE_STATUS.REJECTED, { error: 'failure' });

			const result = manager.getSameOngoingRequest();

			expect(result).toBeNull();
		});

		test('waitOngoingRequestResult should resolve when the request is fulfilled', async () => {
			const manager = createManager(testLogger, testStorage);

			const mockResponse = { data: 'success' };
			manager.setRequestStatus(RequestManager.PROMISE_STATUS.FULFILLED, mockResponse);

			const result = await manager.waitOngoingRequestResult(() => {}, () => {});

			expect(result).toEqual(mockResponse);
		});

		test('waitOngoingRequestResult should reject when the request is rejected', async () => {
			const manager = createManager(testLogger, testStorage);

			const mockError = { error: 'failure' };
			manager.setRequestStatus(RequestManager.PROMISE_STATUS.REJECTED, mockError);

			await expect(manager.waitOngoingRequestResult(() => {}, () => {})).rejects.toEqual(mockError);
		});

		test('waitOngoingRequestResult should retry when the main request is dead', async () => {
			const manager = createManager(testLogger, testStorage);

			const requestKey = manager.requestKey;
			const mockResponse = { data: 'retried success' };
			testStorage.set(requestKey, { status: RequestManager.PROMISE_STATUS.PENDING, timestamp: Date.now() - 2000 });

			let callCount = 0;
			const executeRequest = () => {
				callCount++;
				manager.setRequestStatus(RequestManager.PROMISE_STATUS.FULFILLED, mockResponse);

				return mockResponse;
			};

			const result = await manager.waitOngoingRequestResult(executeRequest, () => {});

			expect(callCount).toBe(1);
			expect(result).toEqual(mockResponse);
		});

		test('startRequest should start the pulse mechanism and set request status to pending', () => {
			const manager = createManager(testLogger, testStorage);

			manager.startRequest();

			expect(manager.pulseInterval).not.toBeNull();

			const requestState = testStorage.get(manager.requestKey);
			expect(requestState).toBeDefined();
			expect(requestState.status).toBe(RequestManager.PROMISE_STATUS.PENDING);
		});

		test('startRequest should clear pulseInterval when the request is completed', () => {
			const manager = createManager(testLogger, testStorage);

			manager.startRequest();
			manager.setRequestStatus(RequestManager.PROMISE_STATUS.FULFILLED, { data: 'success' });

			expect(manager.pulseInterval).toBeNull();
		});

		test('startRequest should correctly save data to the storage', () => {
			const manager = createManager(testLogger, testStorage);

			manager.startRequest();

			const requestState = testStorage.get(manager.requestKey);
			expect(requestState).toBeDefined();
			expect(requestState.status).toBe(RequestManager.PROMISE_STATUS.PENDING);
			expect(requestState.timestamp).toBeDefined();
		});

		test('startRequest should handle missing testStorage or testLogger gracefully', () => {
			const manager = new RequestManager('testAction', {}, null, {}, null);

			expect(() => manager.startRequest()).not.toThrow();
		});

		test('startRequest should update request status correctly on repeated calls', () => {
			const manager = createManager(testLogger, testStorage);

			manager.startRequest();
			manager.setRequestStatus(RequestManager.PROMISE_STATUS.FULFILLED);

			manager.startRequest();
			const requestState = testStorage.get(manager.requestKey);
			expect(requestState.status).toBe(RequestManager.PROMISE_STATUS.PENDING);
		});
	});
})();
