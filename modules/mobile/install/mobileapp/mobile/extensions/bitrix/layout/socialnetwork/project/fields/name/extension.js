/**
 * @module layout/socialnetwork/project/fields/name
 */
jn.define('layout/socialnetwork/project/fields/name', (require, exports, module) => {
	const { StringField } = require('layout/ui/fields/string');

	class ProjectNameField extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.bindRef = this.bindRef.bind(this);
			this.onChange = this.onChange.bind(this);
		}

		render()
		{
			return View(
				{},
				StringField({
					readOnly: false,
					required: true,
					title: BX.message('MOBILE_LAYOUT_PROJECT_FIELDS_NAME_TITLE'),
					value: this.props.value,
					ref: this.bindRef,
					onChange: this.onChange,
				}),
			);
		}

		bindRef(ref)
		{
			this.props.bindRef?.(ref);
		}

		onChange(text)
		{
			this.props.onChange?.(text);
		}
	}

	module.exports = { ProjectNameField };
});
