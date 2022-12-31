(() => {
	const { UserField } = jn.require('layout/ui/fields/user');

	class ProjectModeratorsField extends LayoutComponent
	{
		render()
		{
			return View(
				{},
				UserField({
					readOnly: this.props.readOnly,
					showEditIcon: !this.props.readOnly,
					title: BX.message('MOBILE_LAYOUT_PROJECT_FIELDS_MODERATORS_TITLE'),
					multiple: true,
					value: this.props.value,
					config: {
						provider: {
							context: 'GROUP_INVITE_MODERATORS',
						},
						entityList: this.props.moderatorsData,
						parentWidget: this.props.parentWidget,
					},
					onChange: (moderatorsIds, moderatorsData) => this.props.onChange(moderatorsIds, moderatorsData),
				})
			);
		}
	}

	this.ProjectModeratorsField = ProjectModeratorsField;
})();