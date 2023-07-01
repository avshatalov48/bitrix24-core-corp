/**
 * @module layout/ui/entity-editor/control/entity-selector
 */
jn.define('layout/ui/entity-editor/control/entity-selector', (require, exports, module) => {

	const { EntityEditorField } = require('layout/ui/entity-editor/control/field');

	/**
	 * @class EntitySelectorField
	 */
	class EntitySelectorField extends EntityEditorField
	{
		constructor(props)
		{
			super(props);

			this.reloadEntityListFromProps = this.editor && this.editor.settings.loadFromModel;
		}

		componentWillReceiveProps(props)
		{
			super.componentWillReceiveProps(props);

			if (!this.editor.isChanged)
			{
				this.reloadEntityListFromProps = this.editor && this.editor.settings.loadFromModel;
			}
		}

		prepareConfig()
		{
			return {
				...super.prepareConfig(),
				selectorTitle: this.getTitle(),
				reloadEntityListFromProps: this.reloadEntityListFromProps,
				entityList: this.prepareEntityList(),
			};
		}

		prepareEntityList()
		{
			const entityList = this.schemeElement.getDataParam('entityList', null);
			if (entityList)
			{
				return entityList;
			}

			const entityListField = this.schemeElement.getDataParam('entityListField', null);
			if (entityListField)
			{
				return this.model.getField(entityListField, []);
			}

			return [];
		}

		onFocusOut()
		{
			this.reloadEntityListFromProps = false;
			super.onFocusOut();
		}

		setValue(value)
		{
			this.reloadEntityListFromProps = false;

			return super.setValue(value);
		}
	}

	module.exports = { EntitySelectorField };
});
