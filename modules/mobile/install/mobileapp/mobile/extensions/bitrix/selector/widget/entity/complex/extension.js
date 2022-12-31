/**
 * @module selector/widget/entity/complex
 */
jn.define('selector/widget/entity/complex', (require, exports, module) => {

	/**
	 * @class ComplexSelector
	 */
	class ComplexSelector extends BaseSelectorEntity
	{
		static make(props)
		{
			let { entityIds } = props;

			if (!Array.isArray(entityIds))
			{
				entityIds = this.getEntityIds();
			}

			if (entityIds.length === 0)
			{
				throw new Error('Parameter {entityIds} is required.');
			}

			return super.make({ ...props, entityIds });
		}

		static getEntityIds()
		{
			return [];
		}

		static getEntityId()
		{
			return null;
		}

		static getEntitiesOptions(providerOptions = {}, entityIds)
		{
			return entityIds.map((entityId) => ({
				id: entityId,
				options: providerOptions[entityId] || {},
				searchable: true,
				dynamicLoad: true,
				dynamicSearch: true,
			}));
		}
	}

	module.exports = { ComplexSelector };
});
