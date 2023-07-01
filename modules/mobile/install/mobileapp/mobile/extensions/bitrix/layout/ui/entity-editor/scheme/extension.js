/**
 * @module layout/ui/entity-editor/scheme
 */
jn.define('layout/ui/entity-editor/scheme', (require, exports, module) => {

	const { Type } = require('type');
	const { EntityEditorControlFactory } = require('layout/ui/entity-editor/control');
	const { EntitySchemeElement } = require('layout/ui/entity-editor/scheme/element');

	/**
	 * @class EntityScheme
	 */
	class EntityScheme
	{
		static create(id, settings)
		{
			const self = new EntityScheme();
			self.initialize(id, settings);

			return self;
		}

		constructor()
		{
			this.id = '';
			this.settings = {};
			/** @var {EntitySchemeElement[]} */
			this.elements = [];
			/** @var {EntitySchemeElement[]} */
			this.availableElements = [];
		}

		initialize(id, settings)
		{
			this.id = Type.isStringFilled(id) ? id : Random.getString();
			this.settings = settings ? settings : {};

			this.elements = [];

			const currentData = BX.prop.getArray(this.settings, 'current', []);
			currentData.forEach((data) => {
				const element = EntitySchemeElement.create(data);
				if (EntityEditorControlFactory.has(element.getType()))
				{
					this.elements.push(element);
				}
			});

			this.availableElements = [];

			const availableData = BX.prop.getArray(this.settings, 'available', []);
			availableData.forEach((data) => {
				this.availableElements.push(EntitySchemeElement.create(data));
			});
		}

		/**
		 * @returns {EntitySchemeElement[]}
		 */
		getElements()
		{
			return [...this.elements];
		}

		/**
		 * @returns {EntitySchemeElement[]}
		 */
		getAvailableElements()
		{
			return [...this.availableElements];
		}
	}

	module.exports = { EntityScheme };
});