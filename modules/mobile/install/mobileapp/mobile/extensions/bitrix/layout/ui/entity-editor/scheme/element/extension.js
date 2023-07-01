/**
 * @module layout/ui/entity-editor/scheme/element
 */
jn.define('layout/ui/entity-editor/scheme/element', (require, exports, module) => {

	const { EntityEditorControlFactory } = require('layout/ui/entity-editor/control');
	const { EntityEditorVisibilityPolicy } = require('layout/ui/entity-editor/editor-enum/visibility-policy');

	/**
	 * @class EntitySchemeElement
	 */
	class EntitySchemeElement
	{
		static create(settings)
		{
			const self = new EntitySchemeElement();
			self.initialize(settings);

			return self;
		}

		constructor()
		{
			this.settings = {};
			this.name = '';
			this.type = '';
			this.title = '';
			this.originalTitle = '';

			this.visibilityPolicy = EntityEditorVisibilityPolicy.always;
			this.optionFlags = 0;
			this.options = {};

			this.editable = true;
			this.multiple = false;
			this.required = false;
			this.requiredConditionally = false;

			this.data = null;
			/** @type {EntitySchemeElement[]} */
			this.elements = null;
		}

		initialize(settings)
		{
			this.settings = settings ? settings : {};

			this.name = BX.prop.getString(this.settings, 'name', '');
			this.type = BX.prop.getString(this.settings, 'type', '');

			this.data = BX.prop.getObject(this.settings, 'data', {});

			this.editable = BX.prop.getBoolean(this.settings, 'editable', true);
			this.isShownAlways = BX.prop.getBoolean(this.settings, 'showAlways', false);
			this.multiple = BX.prop.getBoolean(this.settings, 'multiple', false);
			this.enableTitle = BX.prop.getBoolean(this.settings, 'enableTitle', true)
				&& this.getDataBooleanParam('enableTitle', true);
			this.required = BX.prop.getBoolean(this.settings, 'required', false);
			this.showRequired = BX.prop.getBoolean(this.settings, 'showRequired', true);
			this.requiredConditionally = BX.prop.getBoolean(this.settings, 'requiredConditionally', false);

			let title = BX.prop.getString(this.settings, 'title', '');
			let originalTitle = BX.prop.getString(this.settings, 'originalTitle', '');

			if (title !== '' && originalTitle === '')
			{
				originalTitle = title;
			}
			else if (originalTitle !== '' && title === '')
			{
				title = originalTitle;
			}

			this.title = title;
			this.originalTitle = originalTitle;

			this.visibilityPolicy = EntityEditorVisibilityPolicy.parse(
				BX.prop.getString(this.settings, 'visibilityPolicy', ''),
			);

			this.optionFlags = BX.prop.getInteger(this.settings, 'optionFlags', 0);
			this.options = BX.prop.getObject(this.settings, 'options', {});

			this.elements = [];

			const elementsData = BX.prop.getArray(this.settings, 'elements', []);
			elementsData.forEach((data) => {
				const element = EntitySchemeElement.create(data);
				if (EntityEditorControlFactory.has(element.getType()))
				{
					this.elements.push(element);
				}
			});
		}

		getData()
		{
			return { ...this.data };
		}

		getDataParam(name, defaultValue)
		{
			return BX.prop.get(this.data, name, defaultValue);
		}

		getDataBooleanParam(name, defaultValue)
		{
			return BX.prop.getBoolean(this.data, name, defaultValue);
		}

		getType()
		{
			return this.type;
		}

		getName()
		{
			return this.name;
		}

		/**
		 * @returns {EntitySchemeElement[]}
		 */
		getElements()
		{
			return [...this.elements];
		}

		isEditable()
		{
			return this.editable;
		}

		isMultiple()
		{
			return this.multiple;
		}

		isRequired()
		{
			return this.required;
		}

		isShowRequired()
		{
			return this.showRequired;
		}

		getTitle()
		{
			return this.title;
		}

		isTitleEnabled()
		{
			return this.enableTitle;
		}

		getCreationPlaceholder()
		{
			return BX.prop.getString(
				BX.prop.getObject(this.settings, 'placeholders', null),
				'creation',
				null,
			);
		}

		getOptionsFlags()
		{
			return this.optionFlags;
		}

		getVisibilityPolicy()
		{
			return this.visibilityPolicy;
		}
	}

	module.exports = { EntitySchemeElement };
});