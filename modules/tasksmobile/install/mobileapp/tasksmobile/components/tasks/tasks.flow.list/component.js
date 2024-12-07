(() => {
	const require = (ext) => jn.require(ext);
	const { TasksFlowList, ListType } = require('tasks/layout/flow/list');
	const { tariffPlanRestrictionsReady } = require('tariff-plan-restriction');

	tariffPlanRestrictionsReady()
		.then(() => {
			BX.onViewLoaded(() => {
				layout.showComponent(
					new TasksFlowList({
						currentUserId: Number(env.userId),
						listType: ListType.FLOWS,
					}),
				);
			});
		})
		.catch(console.error)
	;
})();
