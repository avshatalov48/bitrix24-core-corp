/**
 * @module layout/ui/simple-list/skeleton
 */
jn.define('layout/ui/simple-list/skeleton', (require, exports, module) => {
	const { LoadingScreenComponent } = require('layout/ui/loading-screen');
	const { Kanban } = require('layout/ui/simple-list/skeleton/type');

	const SkeletonTypes = {
		Kanban,
	};

	/**
	 * @class SkeletonFactory
	 */
	class SkeletonFactory
	{
		/**
		 * @public
		 * @param {string} type
		 * @param {object} props
		 * @return {object}
		 */
		static make(type, props)
		{
			if (SkeletonTypes[type])
			{
				return new SkeletonTypes[type](props);
			}

			return View(
				{
					style: {
						height: 80,
					},
				},
				new LoadingScreenComponent(props),
			);
		}

		/**
		 * @public
		 * @param {string} type
		 * @param {typeof LayoutComponent} componentClass
		 */
		static register(type, componentClass)
		{
			SkeletonTypes[type] = componentClass;
		}

		/**
		 * @public
		 * @param {string} origin
		 * @param {string} alias
		 */
		static alias(origin, alias)
		{
			if (!SkeletonTypes[origin])
			{
				throw new Error(`SkeletonFactory: cannot add alias ${alias} to non existing type ${origin}`);
			}

			SkeletonTypes[alias] = SkeletonTypes[origin];
		}
	}

	module.exports = { SkeletonFactory, SkeletonTypes };
});
