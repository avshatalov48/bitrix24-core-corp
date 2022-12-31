(() => {
	const { TextAreaField } = jn.require('layout/ui/fields/textarea');

	class ProjectDescriptionField extends LayoutComponent
	{
		render()
		{
			return View(
				{},
				TextAreaField({
					readOnly: false,
					title: BX.message('MOBILE_LAYOUT_PROJECT_FIELDS_DESCRIPTION_TITLE'),
					value: this.props.value,
					onChange: text => this.props.onChange(text),
				})
			);
		}
	}

	this.ProjectDescriptionField = ProjectDescriptionField;
})();