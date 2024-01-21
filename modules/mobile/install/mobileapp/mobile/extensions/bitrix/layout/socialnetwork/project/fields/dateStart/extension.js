(() => {
	const { DateTimeField } = jn.require('layout/ui/fields/datetime');

	class ProjectDateStartField extends LayoutComponent
	{
		render()
		{
			return View(
				{},
				DateTimeField({
					readOnly: this.props.readOnly,
					showEditIcon: !this.props.readOnly,
					title: BX.message('MOBILE_LAYOUT_PROJECT_FIELDS_DATE_START_TITLE'),
					value: this.props.value,
					config: {
						enableTime: false,
					},
					onChange: (date) => this.props.onChange(date),
				}),
			);
		}
	}

	this.ProjectDateStartField = ProjectDateStartField;
})();
