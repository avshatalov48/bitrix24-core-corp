(function() {
	class ProjectBackgroundAction
	{
		constructor()
		{
			BX.addCustomEvent(
				'projectbackground::project::action',
				(data) => ProjectBackgroundAction.executeAction(data),
			);
		}

		static async executeAction(data)
		{
			const { WorkgroupUtil } = await requireLazy('project/utils');

			void WorkgroupUtil.openProject(data.item || null, data);
		}
	}

	new ProjectBackgroundAction();
})();
