/**
 * @module tasks/statemanager/redux/slices/flows/observers/stateful-list-observer
 */
jn.define('tasks/statemanager/redux/slices/flows/observers/stateful-list-observer', (require, exports, module) => {
	const { isEqual } = require('utils/object');
	const { selectEntities } = require('tasks/statemanager/redux/slices/flows/src/selector');

	const observeListChange = (store, onChange) => {
		let prevFlows = selectEntities(store.getState());

		return store.subscribe(() => {
			const nextFlows = selectEntities(store.getState());

			const { moved, removed, added, created } = getDiffForTasksObserver(prevFlows, nextFlows);
			if (moved.length > 0 || removed.length > 0 || added.length > 0 || created.length > 0)
			{
				onChange({ moved, removed, added, created });
			}

			prevFlows = nextFlows;
		});
	};

	/**
	 * @private
	 * @param {Object.<string, TaskReduxModel>} prevFlows
	 * @param {Object.<string, TaskReduxModel>} nextFlows
	 * @return {{moved: TaskReduxModel[], removed: TaskReduxModel[], added: TaskReduxModel[]}}
	 */
	const getDiffForTasksObserver = (prevFlows, nextFlows) => {
		const moved = [];
		const removed = [];
		const added = [];
		const created = [];

		if (prevFlows === nextFlows)
		{
			return { moved, removed, added, created };
		}

		// Find added flows
		Object.values(nextFlows).forEach((nextFlow) => {
			if (!nextFlow.isRemoved)
			{
				const prevFlow = prevFlows[nextFlow.id];
				if (!prevFlow)
				{
					added.push(nextFlow);
				}
			}
		});

		// Find removed flows
		Object.values(prevFlows).forEach((prevFlow) => {
			const nextFlow = nextFlows[prevFlow.id];
			if (!nextFlow)
			{
				removed.push(prevFlow);
			}
		});

		// Find moved flows
		const processedFlowIds = new Set([...removed, ...added].map(({ id }) => id));
		Object.values(nextFlows).forEach((nextFlow) => {
			const prevFlow = prevFlows[nextFlow.id];
			if (!prevFlow || processedFlowIds.has(nextFlow.id))
			{
				return;
			}

			if (!isEqual(prevFlow, nextFlow))
			{
				moved.push(nextFlow);
			}
		});

		return { moved, removed, added, created };
	};

	module.exports = { observeListChange, getDiffForTasksObserver };
});
