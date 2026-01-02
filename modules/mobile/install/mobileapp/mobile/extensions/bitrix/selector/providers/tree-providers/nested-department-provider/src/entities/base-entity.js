/**
 * @module selector/providers/tree-providers/nested-department-provider/src/entities/base-entity
 */
jn.define('selector/providers/tree-providers/nested-department-provider/src/entities/base-entity', (require, exports, module) => {
	const { withCurrentDomain } = require('utils/url');

	class BaseEntity
	{
		static getId()
		{
			throw new Error('getId method should be implemented in derived class');
		}

		getEntityForDialog()
		{
			return {
				id: this.constructor.getId(),
				dynamicLoad: true,
				dynamicSearch: true,
				filters: [],
				searchable: true,
				substituteEntityId: null,
				options: this.getEntityOptions(),
			};
		}

		getEntityOptions()
		{
			return {};
		}

		prepareItemForDrawing(item, initialEntity)
		{
			return { ...item, initialEntity };
		}

		prepareCommonStyles(styleOptions)
		{
			const { item, initialEntity, imageStyles, imageUrl } = styleOptions;

			return {
				...item,
				imageUrl: withCurrentDomain(imageUrl),
				shortTitle: initialEntity.shortTitle,
				subtitle: initialEntity.subtitle,
				styles: {
					...item.styles,
					image: initialEntity?.styles?.image ?? imageStyles,
					selectedImage: initialEntity?.styles?.selectedImage ?? imageStyles,
				},
				typeIconFrame: item.typeIconFrame ?? 1,
				color: item.typeIconFrame === 2 ? null : imageStyles.backgroundColor,
			};
		}

		isEqualById(item, id)
		{
			return String(item.id) === String(id) && String(item.entityId) === this.constructor.getId();
		}

		findItem(id, items)
		{
			return items.find((item) => this.isEqualById(item, id));
		}
	}

	module.exports = {
		BaseEntity,
	};
});
