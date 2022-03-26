(() => {
	class ProjectSubjectField extends LayoutComponent
	{
		render()
		{
			if (this.props.readOnly)
			{
				return View(
					{},
					FieldFactory.create(FieldFactory.Type.STRING, {
						readOnly: true,
						title: BX.message('MOBILE_LAYOUT_PROJECT_FIELDS_SUBJECT_TITLE'),
						value: this.props.value,
					})
				);
			}

			const selectedSubject = (
				this.props.value
					? this.props.subjects.find(subject => Number(subject.ID) === Number(this.props.value))
					: this.props.subjects[0]
			);

			return View(
				{},
				FieldFactory.create(FieldFactory.Type.MENU_SELECT, {
					readOnly: false,
					title: BX.message('MOBILE_LAYOUT_PROJECT_FIELDS_SUBJECT_TITLE'),
					value: (selectedSubject ? selectedSubject.NAME : null),
					parentWidget: this.props.parentWidget,
					menuTitle: BX.message('MOBILE_LAYOUT_PROJECT_FIELDS_SUBJECT_MENU_TITLE'),
					menuItems: this.props.subjects.map((subject) => ({id: Number(subject.ID), title: subject.NAME})),
					onChange: (id, title) => this.props.onChange(id, title),
				})
			);
		}
	}

	this.ProjectSubjectField = ProjectSubjectField;
})();