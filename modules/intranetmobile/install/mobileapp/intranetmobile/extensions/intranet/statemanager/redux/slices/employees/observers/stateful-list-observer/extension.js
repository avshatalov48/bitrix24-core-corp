/**
 * @module intranet/statemanager/redux/slices/employees/observers/stateful-list-observer
 */
jn.define('intranet/statemanager/redux/slices/employees/observers/stateful-list-observer', (require, exports, module) => {
	const { selectEntities } = require('intranet/statemanager/redux/slices/employees/selector');

	const observeListChange = (store, getFilters, onChange) => {
		let prevEmployees = selectEntities(store.getState());

		return store.subscribe(() => {
			const nextEmployees = selectEntities(store.getState());

			const {
				moved,
				removed,
				added,
				created,
			} = getDiffForTasksObserver(prevEmployees, nextEmployees, getFilters);
			if (moved.length > 0 || removed.length > 0 || added.length > 0 || created.length > 0)
			{
				onChange({ moved, removed, added, created });
			}

			prevEmployees = nextEmployees;
		});
	};

	/**
	 * Exported for tests
	 *
	 * @private
	 * @param {Object.<number, IntranetUserReduxModel>} prevEmployees
	 * @param {Object.<number, IntranetUserReduxModel>} nextEmployees
	 * @param {() => object} getFilters
	 * @return {{
	 * moved: IntranetUserReduxModel[],
	 * removed: IntranetUserReduxModel[],
	 * added: IntranetUserReduxModel[],
	 * created: IntranetUserReduxModel[]
	 * }}
	 */
	const getDiffForTasksObserver = (prevEmployees, nextEmployees, getFilters) => {
		const moved = [];
		const removed = [];
		const added = [];
		const created = [];

		const { employeeStatus, isExtranetUser } = getFilters();

		if (prevEmployees === nextEmployees || !employeeStatus)
		{
			return { moved, removed, added, created };
		}

		Object.values(nextEmployees).forEach((nextEmployee) => {
			const { id, employeeStatus: nextEmployeeStatus, isExtranetUser: nextIsExtranetUser } = nextEmployee;
			const prevEmployee = prevEmployees[id];

			if (!prevEmployee)
			{
				added.push(nextEmployee);

				return;
			}

			const { employeeStatus: prevEmployeeStatus, isExtranetUser: prevIsExtranetUser } = prevEmployee;

			const statusFilterSuitabilityChanged = !employeeStatus.includes(prevEmployeeStatus)
				&& employeeStatus.includes(nextEmployeeStatus);
			const extranetStatusChanged = (nextIsExtranetUser !== prevIsExtranetUser)
				&& (nextIsExtranetUser === isExtranetUser || isExtranetUser === undefined);

			if (statusFilterSuitabilityChanged || extranetStatusChanged)
			{
				added.push(nextEmployee);
			}
		});

		// Find removed users
		Object.values(prevEmployees).forEach((prevEmployee) => {
			const { id, employeeStatus: prevEmployeeStatus, isExtranetUser: prevIsExtranetUser } = prevEmployee;
			const nextEmployee = nextEmployees[id];

			if (!nextEmployee)
			{
				removed.push(prevEmployee);

				return;
			}

			const { employeeStatus: nextEmployeeStatus, isExtranetUser: nextIsExtranetUser } = nextEmployee;

			const statusChanged = employeeStatus.includes(prevEmployeeStatus) && !employeeStatus.includes(nextEmployeeStatus);
			const extranetStatusChanged = (nextIsExtranetUser !== prevIsExtranetUser)
				&& (nextIsExtranetUser !== isExtranetUser && isExtranetUser !== undefined);

			if (statusChanged || extranetStatusChanged)
			{
				removed.push(prevEmployee);
			}
		});

		// Find moved users
		Object.values(nextEmployees).forEach((nextEmployee) => {
			const { id, employeeStatus: nextEmployeeStatus } = nextEmployee;
			const prevEmployee = prevEmployees[id];

			if (!prevEmployee)
			{
				return;
			}

			const { employeeStatus: prevEmployeeStatus } = prevEmployee;

			const statusChanged = prevEmployeeStatus !== nextEmployeeStatus;
			const statusIsValid = employeeStatus.includes(prevEmployeeStatus) && employeeStatus.includes(nextEmployeeStatus);

			if (statusChanged && statusIsValid)
			{
				moved.push(nextEmployee);
			}
		});

		// added.forEach((addedTask, index) => {});

		return { moved, removed, added, created };
	};

	module.exports = { observeListChange };
});
