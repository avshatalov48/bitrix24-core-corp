(() => {
	const require = (ext) => jn.require(ext);

	class PlaygroundComponent extends LayoutComponent
	{
		render()
		{}
	}

	layout.showComponent(new PlaygroundComponent());
})();
