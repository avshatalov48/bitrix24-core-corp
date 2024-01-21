(() => {
	const require = (ext) => jn.require(ext);

	const { describe, test, expect } = require('testing');
	const { PushProcessor, queueItemsStatus } = require('layout/ui/stateful-list/pull/src/push-processor');
	const { command } = require('layout/ui/stateful-list/pull/src/command');

	const getPushProcessor = (queue) => {
		const pushProcessor = new PushProcessor({
			eventCallbacks: [],
		});
		pushProcessor.queue = queue;

		return pushProcessor;
	};

	describe('stateful-list/pull/src/push-processor.js optimizeQueue', () => {
		test('should set an empty queue when in queue are empty', () => {
			const pushProcessor = getPushProcessor([]);
			pushProcessor.optimizeQueue();

			expect(pushProcessor.queue).toEqual([]);
		});

		test('should return the same element if it is the only one in the queue', () => {
			const queue = [
				{
					eventName: command.UPDATED,
					items: [{ id: 1 }],
					status: queueItemsStatus.WAITING,
				},
			];
			const pushProcessor = getPushProcessor(queue);
			pushProcessor.optimizeQueue();

			expect(pushProcessor.queue).toEqual(queue);
		});

		test('should remove item from updated if it exists in deleted', () => {
			const pushProcessor = getPushProcessor([
				{
					eventName: command.DELETED,
					items: [{ id: 2 }, { id: 3 }],
					status: queueItemsStatus.WAITING,
				},
				{
					eventName: command.UPDATED,
					items: [{ id: 1 }, { id: 2 }],
					status: queueItemsStatus.WAITING,
				},
			]);
			pushProcessor.optimizeQueue();

			expect(pushProcessor.queue).toEqual([
				{
					eventName: command.DELETED,
					items: [{ id: 2 }, { id: 3 }],
					status: queueItemsStatus.WAITING,
				},
				{
					eventName: command.UPDATED,
					items: [{ id: 1 }],
					status: queueItemsStatus.WAITING,
				},
			]);
		});

		test('should remove item from added if it exists in deleted', () => {
			const pushProcessor = getPushProcessor([
				{
					eventName: command.DELETED,
					items: [{ id: 2 }, { id: 3 }],
					status: queueItemsStatus.WAITING,
				},
				{
					eventName: command.ADDED,
					items: [{ id: 3 }, { id: 4 }],
					status: queueItemsStatus.WAITING,
				},
			]);
			pushProcessor.optimizeQueue();

			expect(pushProcessor.queue).toEqual([
				{
					eventName: command.DELETED,
					items: [{ id: 2 }, { id: 3 }],
					status: queueItemsStatus.WAITING,
				},
				{
					eventName: command.ADDED,
					items: [{ id: 4 }],
					status: queueItemsStatus.WAITING,
				},
			]);
		});

		test('should remove item from updated if it exists in added', () => {
			const pushProcessor = getPushProcessor([
				{
					eventName: command.ADDED,
					items: [{ id: 2 }, { id: 3 }],
					status: queueItemsStatus.WAITING,
				},
				{
					eventName: command.UPDATED,
					items: [{ id: 1 }, { id: 2 }],
					status: queueItemsStatus.WAITING,
				},
			]);
			pushProcessor.optimizeQueue();

			expect(pushProcessor.queue).toEqual([
				{
					eventName: command.ADDED,
					items: [{ id: 2 }, { id: 3 }],
					status: queueItemsStatus.WAITING,
				},
				{
					eventName: command.UPDATED,
					items: [{ id: 1 }],
					status: queueItemsStatus.WAITING,
				},
			]);
		});

		test('should remove item from added and updated if it exists in deleted, should reorder added and updated in queue', () => {
			const pushProcessor = getPushProcessor([
				{
					eventName: command.DELETED,
					items: [{ id: 2 }, { id: 3 }, { id: 4 }],
					status: queueItemsStatus.WAITING,
				},
				{
					eventName: command.UPDATED,
					items: [{ id: 1 }, { id: 2 }],
					status: queueItemsStatus.WAITING,
				},
				{
					eventName: command.ADDED,
					items: [{ id: 4 }, { id: 5 }],
					status: queueItemsStatus.WAITING,
				},
			]);
			pushProcessor.optimizeQueue();

			expect(pushProcessor.queue).toEqual([
				{
					eventName: command.DELETED,
					items: [{ id: 2 }, { id: 3 }, { id: 4 }],
					status: queueItemsStatus.WAITING,
				},
				{
					eventName: command.ADDED,
					items: [{ id: 5 }],
					status: queueItemsStatus.WAITING,
				},
				{
					eventName: command.UPDATED,
					items: [{ id: 1 }],
					status: queueItemsStatus.WAITING,
				},
			]);
		});

		test('should remove item from added and updated if it exists in deleted,'
			+ ' should remove item from updated if it exists in added, should reorder added and updated in queue', () => {
			const pushProcessor = getPushProcessor([
				{
					eventName: command.DELETED,
					items: [{ id: 2 }, { id: 3 }, { id: 4 }],
					status: queueItemsStatus.WAITING,
				},
				{
					eventName: command.UPDATED,
					items: [{ id: 1 }, { id: 2 }, { id: 6 }],
					status: queueItemsStatus.WAITING,
				},
				{
					eventName: command.ADDED,
					items: [{ id: 4 }, { id: 5 }, { id: 6 }],
					status: queueItemsStatus.WAITING,
				},
			]);
			pushProcessor.optimizeQueue();

			expect(pushProcessor.queue).toEqual([
				{
					eventName: command.DELETED,
					items: [{ id: 2 }, { id: 3 }, { id: 4 }],
					status: queueItemsStatus.WAITING,
				},
				{
					eventName: command.ADDED,
					items: [{ id: 5 }, { id: 6 }],
					status: queueItemsStatus.WAITING,
				},
				{
					eventName: command.UPDATED,
					items: [{ id: 1 }],
					status: queueItemsStatus.WAITING,
				},
			]);
		});

		test('should reorder queue: first deleted then updated', () => {
			const pushProcessor = getPushProcessor([
				{
					eventName: command.UPDATED,
					items: [{ id: 1 }],
					status: queueItemsStatus.WAITING,
				},
				{
					eventName: command.DELETED,
					items: [{ id: 2 }],
					status: queueItemsStatus.WAITING,
				},
			]);
			pushProcessor.optimizeQueue();

			expect(pushProcessor.queue).toEqual([
				{
					eventName: command.DELETED,
					items: [{ id: 2 }],
					status: queueItemsStatus.WAITING,
				},
				{
					eventName: command.UPDATED,
					items: [{ id: 1 }],
					status: queueItemsStatus.WAITING,
				},
			]);
		});

		test('should remove duplicate items from queue, reorder and group', () => {
			const queue = [
				{
					eventName: command.UPDATED,
					items: [{ id: 1 }],
					status: queueItemsStatus.WAITING,
				},
				{
					eventName: command.UPDATED,
					items: [{ id: 2 }],
					status: queueItemsStatus.WAITING,
				},
				{
					eventName: command.UPDATED,
					items: [{ id: 1 }],
					status: queueItemsStatus.WAITING,
				},
				{
					eventName: command.UPDATED,
					items: [{ id: 3 }],
					status: queueItemsStatus.WAITING,
				},
				{
					eventName: command.VIEW,
					items: [{ id: 6 }],
					status: queueItemsStatus.WAITING,
				},
				{
					eventName: command.DELETED,
					items: [{ id: 4 }],
					status: queueItemsStatus.WAITING,
				},
				{
					eventName: command.DELETED,
					items: [{ id: 5 }],
					status: queueItemsStatus.WAITING,
				},
				{
					eventName: command.DELETED,
					items: [{ id: 4 }],
					status: queueItemsStatus.WAITING,
				},
			];
			const pushProcessor = getPushProcessor(queue);
			pushProcessor.optimizeQueue();

			expect(pushProcessor.queue).toEqual([
				{
					eventName: command.DELETED,
					items: [{ id: 4 }, { id: 5 }],
					status: queueItemsStatus.WAITING,
				},
				{
					eventName: command.UPDATED,
					items: [{ id: 1 }, { id: 2 }, { id: 3 }, { id: 6 }],
					status: queueItemsStatus.WAITING,
				},
			]);
		});

		test('should return only reload if it exists in queue', () => {
			const pushProcessor = getPushProcessor([
				{
					eventName: command.UPDATED,
					items: [{ id: 1 }],
					status: queueItemsStatus.WAITING,
				},
				{
					eventName: command.DELETED,
					items: [{ id: 2 }],
					status: queueItemsStatus.WAITING,
				},
				{
					eventName: command.RELOAD,
					status: queueItemsStatus.WAITING,
				},
				{
					eventName: command.ADDED,
					items: [{ id: 2 }],
					status: queueItemsStatus.WAITING,
				},
			]);
			pushProcessor.optimizeQueue();

			expect(pushProcessor.queue).toEqual([
				{
					eventName: command.RELOAD,
					status: queueItemsStatus.WAITING,
				},
			]);
		});

		test('should remove updated from queue if all items in updated exists in deleted, group items in deleted', () => {
			const pushProcessor = getPushProcessor([
				{
					eventName: command.UPDATED,
					items: [{ id: 1 }, { id: 2 }],
					status: queueItemsStatus.WAITING,
				},
				{
					eventName: command.UPDATED,
					items: [{ id: 3 }],
					status: queueItemsStatus.WAITING,
				},
				{
					eventName: command.UPDATED,
					items: [{ id: 4 }],
					status: queueItemsStatus.WAITING,
				},
				{
					eventName: command.DELETED,
					items: [{ id: 1 }, { id: 2 }, { id: 3 }],
					status: queueItemsStatus.WAITING,
				},
				{
					eventName: command.DELETED,
					items: [{ id: 4 }, { id: 5 }, { id: 6 }],
					status: queueItemsStatus.WAITING,
				},
			]);
			pushProcessor.optimizeQueue();

			expect(pushProcessor.queue).toEqual([
				{
					eventName: command.DELETED,
					items: [{ id: 1 }, { id: 2 }, { id: 3 }, { id: 4 }, { id: 5 }, { id: 6 }],
					status: queueItemsStatus.WAITING,
				},
			]);
		});
	});
})();
