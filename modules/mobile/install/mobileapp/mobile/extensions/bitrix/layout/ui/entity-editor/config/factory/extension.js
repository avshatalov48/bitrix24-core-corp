/**
 * @module layout/ui/entity-editor/config/factory
 */
jn.define('layout/ui/entity-editor/config/factory', (require, exports, module) => {

	const { EntityConfigColumn } = require('layout/ui/entity-editor/config/column');
	const { EntityConfigSection } = require('layout/ui/entity-editor/config/section');
	const { EntityConfigField } = require('layout/ui/entity-editor/config/field');

	const COLUMN = 'column';
	const SECTION = 'section';

	/**
	 * @class EntityConfigFactory
	 */
	class EntityConfigFactory
	{
		static createByType(type, settings)
		{
			let config;
			if (type === COLUMN)
			{
				config = EntityConfigColumn.create(settings);
			}
			else if (type === SECTION)
			{
				config = EntityConfigSection.create(settings);
			}
			else
			{
				config = EntityConfigField.create(settings);
			}

			return config;
		}
	}

	module.exports = { EntityConfigFactory };
});