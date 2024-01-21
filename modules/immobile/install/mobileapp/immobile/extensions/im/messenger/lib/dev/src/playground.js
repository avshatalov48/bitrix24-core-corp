/**
 * @module im/messenger/lib/dev/playground
 */
jn.define('im/messenger/lib/dev/playground', (require, exports, module) => {
	class Playground extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
		}

		render()
		{
			return View(
				{},
			);
		}
	}

	module.exports = { Playground };
});
