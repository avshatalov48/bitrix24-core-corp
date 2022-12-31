(() => {
	const { StringField } = jn.require('layout/ui/fields/string');

	class ProjectNameField extends LayoutComponent
	{
		render()
		{
			return View(
				{},
				StringField({
					readOnly: false,
					required: true,
					focus: (this.props.focus || false),
					title: BX.message('MOBILE_LAYOUT_PROJECT_FIELDS_NAME_TITLE'),
					value: this.props.value,
					onChange: text => this.props.onChange(text),
				})
			);
		}
	}

	this.ProjectNameField = ProjectNameField;
})();