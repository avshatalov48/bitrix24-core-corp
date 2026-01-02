/**
 * @module user-profile/common-tab/src/block/factory
 */
jn.define('user-profile/common-tab/src/block/factory', (require, exports, module) => {
	const { BlockCombiner } = require('user-profile/common-tab/src/block/combine');
	const { BlockRegistry, BlockOrderRegistry } = require('user-profile/common-tab/src/const/block-config');

	class ProfileBlockFactory
	{
		/**
		 * @param {Object} options
		 * @returns {Array}
		 */
		static getAll(options)
		{
			const instances = ProfileBlockFactory.#getInstances(options);
			const sortedBlocks = ProfileBlockFactory.#sortBlocks(instances);

			return BlockCombiner.combine(sortedBlocks, options.isEditMode);
		}

		static #sortBlocks(instances)
		{
			const blocks = [...instances.entries()].map(([type, block]) => ({ type, block }));

			return blocks
				.filter(({ type }) => BlockOrderRegistry[type] !== undefined)
				.sort((a, b) => {
					return BlockOrderRegistry[a.type].order - BlockOrderRegistry[b.type].order;
				});
		}

		static #createInstance(type, options)
		{
			const BlockClass = BlockRegistry[type];

			if (!BlockClass)
			{
				console.error(`Unknown block type: ${type}`);

				return null;
			}

			const block = new BlockClass(options);

			if (block.isAvailable())
			{
				return block;
			}

			return null;
		}

		static #getInstances(options)
		{
			const map = new Map();
			Object.keys(BlockRegistry).forEach((type) => {
				const instance = ProfileBlockFactory.#createInstance(type, {
					...options,
				});
				if (instance)
				{
					map.set(type, instance);
				}
			});

			return map;
		}
	}

	module.exports = {
		ProfileBlockFactory,
		BlockRegistry,
	};
});
