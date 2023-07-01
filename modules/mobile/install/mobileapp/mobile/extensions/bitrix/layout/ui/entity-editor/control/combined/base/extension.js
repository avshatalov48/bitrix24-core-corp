/**
 * @module layout/ui/entity-editor/control/combined/base
 */
jn.define('layout/ui/entity-editor/control/combined/base', (require, exports, module) => {

	const { MultipleCombinedType } = require('layout/ui/fields/multiple-combined');
	const { EntityEditorField } = require('layout/ui/entity-editor/control/field');
	const { stringify } = require('utils/string');

	/**
	 * @class EntityEditorCombinedBase
	 */
	class EntityEditorCombinedBase extends EntityEditorField
	{
		constructor(props)
		{
			super(props);

			this.state.isEditable = false;

			this.onEdit = this.onEdit.bind(this);
		}

		componentWillReceiveProps(props)
		{
			super.componentWillReceiveProps(props);

			this.state.isEditable = false;
		}

		initialize(id, uid, type, settings)
		{
			super.initialize(id, uid, type, settings);

			this.type = MultipleCombinedType;
		}

		getValueFromModel(defaultValue = '')
		{
			if (!this.model)
			{
				return defaultValue;
			}

			const values = this.model.getField(this.getName(), []);

			return (
				Array.isArray(values) && values.length
					? values.map((entityValue) => ({ ...entityValue, value: this.prepareValue(entityValue.value) }))
					: defaultValue
			);
		}

		prepareValue(value)
		{
			return value;
		}

		getLinkByType(type)
		{
			const { links } = this.schemeElement.getData();

			if (links && type && links.hasOwnProperty(type))
			{
				return stringify(links[type]);
			}

			return '';
		}

		prepareFieldProps()
		{
			return {
				...super.prepareFieldProps(),
				enableToEdit: this.parent.isInEditMode() || this.state.isEditable,
				onEdit: this.onEdit,
			};
		}

		onEdit()
		{
			return new Promise((resolve) => {
				this.setState({
					isEditable: true,
				}, resolve);
			});
		}
	}

	module.exports = { EntityEditorCombinedBase };
});
