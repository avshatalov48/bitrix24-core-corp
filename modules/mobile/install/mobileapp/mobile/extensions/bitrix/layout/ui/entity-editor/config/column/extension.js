/**
 * @module layout/ui/entity-editor/config/column
 */
jn.define('layout/ui/entity-editor/config/column', (require, exports, module) => {

	const { EntityConfigBaseItem } = require('layout/ui/entity-editor/config/base');
	const { EntityConfigSection } = require('layout/ui/entity-editor/config/section');

	/**
	 * @class EntityConfigColumn
	 */
	class EntityConfigColumn extends EntityConfigBaseItem
	{
		static create(settings)
		{
			const self = new EntityConfigColumn();
			self.initialize(settings);

			return self;
		}

		constructor()
		{
			super();
			this.sections = [];
		}

		doInitialize()
		{
			const elements = BX.prop.getArray(this.data, 'elements', []);
			elements.forEach((element) => {
				if (element.type === 'section')
				{
					const config = EntityConfigSection.create({ data: element });
					this.addSection(config);
				}
			});
		}

		addSection(section)
		{
			this.sections.push(section);
		}
	}

	module.exports = { EntityConfigColumn };
});