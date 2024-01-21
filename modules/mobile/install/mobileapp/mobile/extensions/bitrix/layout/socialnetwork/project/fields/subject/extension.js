(() => {
	const require = (ext) => jn.require(ext);

	const { StringField } = require('layout/ui/fields/string');
	const { MenuSelectField } = require('layout/ui/fields/menu-select');

	class ProjectSubjectField extends LayoutComponent
	{
		render()
		{
			if (this.props.readOnly)
			{
				return View(
					{},
					StringField({
						readOnly: true,
						title: BX.message('MOBILE_LAYOUT_PROJECT_FIELDS_SUBJECT_TITLE'),
						value: this.props.value,
					}),
				);
			}

			const selectedSubject = (
				this.props.value
					? this.props.subjects.find((subject) => Number(subject.ID) === Number(this.props.value))
					: this.props.subjects[0]
			);

			return View(
				{},
				MenuSelectField({
					readOnly: false,
					title: BX.message('MOBILE_LAYOUT_PROJECT_FIELDS_SUBJECT_TITLE'),
					value: (selectedSubject ? Number(selectedSubject.ID) : null),
					onChange: (id, title) => this.props.onChange(id, title),
					config: {
						menuTitle: BX.message('MOBILE_LAYOUT_PROJECT_FIELDS_SUBJECT_MENU_TITLE'),
						menuItems: this.props.subjects.map((subject) => ({
							id: Number(subject.ID),
							title: subject.NAME,
						})),
						parentWidget: this.props.parentWidget,
					},
				}),
			);
		}
	}

	this.ProjectSubjectField = ProjectSubjectField;
})();
