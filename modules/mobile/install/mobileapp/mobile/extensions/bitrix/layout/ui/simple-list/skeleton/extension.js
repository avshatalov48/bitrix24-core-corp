/**
 * @module layout/ui/simple-list/skeleton
 */
jn.define('layout/ui/simple-list/skeleton', (require, exports, module) => {

	const {
		Kanban,
	} = require('layout/ui/simple-list/skeleton/type');

	const SkeletonTypes = {
		Kanban,
	};

	/**
	 * @class SkeletonFactory
	 */
	class SkeletonFactory
	{
		static make(type, props)
		{
			if (SkeletonTypes[type])
			{
				return new SkeletonTypes[type](props);
			}

			console.warn('Skeleton type not found. Use LoadingScreenComponent');
			return new LoadingScreenComponent();
		}
	}

	module.exports = { SkeletonFactory, SkeletonTypes };

});
