(() => {
	class ProjectDescriptionField extends LayoutComponent
	{
		render()
		{
			return View(
				{},
				FieldFactory.create(FieldFactory.Type.TEXTAREA, {
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