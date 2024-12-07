(() => {
	const require = (ext) => jn.require(ext);
	const { SettingsActionExecutor } = require('tasks/dashboard/settings-action-executor');

	const loadComponents = async () => {
		if (typeof jnComponent?.preload !== 'function')
		{
			return;
		}

		const componentsToLoad = [
			'tasks:tasks.task.tabs',
			'tasks:tasks.dashboard',
			'tasks:tasks.task.view-new',
		];

		for (const component of componentsToLoad)
		{
			const { publicUrl } = availableComponents[component] ?? {};
			if (publicUrl)
			{
				// eslint-disable-next-line no-await-in-loop
				await jnComponent.preload(publicUrl);
			}
		}
	};

	setTimeout(async () => {
		await loadComponents();

		const ownerId = Number(env.userId);
		const executor = new SettingsActionExecutor({ ownerId });
		if (executor.getCache().isExpired())
		{
			await executor.call(false);
		}
	}, 3000);
})();
