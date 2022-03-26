(() => {
	class ProjectDateStartField extends LayoutComponent
	{
		render()
		{
			return View(
				{},
				FieldFactory.create(FieldFactory.Type.DATE, {
					readOnly: this.props.readOnly,
					showEditIcon: !this.props.readOnly,
					title: BX.message('MOBILE_LAYOUT_PROJECT_FIELDS_DATE_START_TITLE'),
					value: this.props.value,
					onChange: date => this.props.onChange(date),
				})
			);
		}
	}

	this.ProjectDateStartField = ProjectDateStartField;
})();