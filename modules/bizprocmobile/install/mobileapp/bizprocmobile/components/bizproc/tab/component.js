(() => {
	const require = (ext) => jn.require(ext);

	const { PureComponent } = require('layout/pure-component');
	const { WorkflowList } = require('bizproc/workflow/list');
	class TabComponent extends PureComponent
	{
		render()
		{
			return new WorkflowList({ layout });
		}
	}

	layout.showComponent(new TabComponent());
})();
