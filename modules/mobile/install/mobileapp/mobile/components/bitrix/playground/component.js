(() => {

	// Empty component for experiments, showcases, sharing code, etc.
	// Please, keep it empty.

	const require = ext => jn.require(ext);

	class PlaygroundComponent extends LayoutComponent {
		constructor(props) {

			super(props);

			this.state = {};
		}

		render() {
			return View(
				{
					style: {},
				},
			);
		}
	}

	layout.showComponent(new PlaygroundComponent());
})();