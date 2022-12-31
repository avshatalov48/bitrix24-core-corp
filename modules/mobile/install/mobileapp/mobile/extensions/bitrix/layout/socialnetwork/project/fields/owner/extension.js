(() => {
	const { UserField } = jn.require('layout/ui/fields/user');

	class ProjectOwnerField extends LayoutComponent
	{
		render()
		{
			return View(
				{},
				UserField({
					readOnly: this.props.readOnly,
					showEditIcon: !this.props.readOnly,
					title: BX.message('MOBILE_LAYOUT_PROJECT_FIELDS_OWNER_TITLE'),
					multiple: false,
					value: this.props.value,
					config: {
						provider: {
							context: 'GROUP_INVITE_OWNER',
						},
						entityList: [this.props.ownerData],
						parentWidget: this.props.parentWidget,
					},
					onChange: (ownerId, ownerData) => this.props.onChange(ownerId, ownerData[0]),
				})
			);
		}
	}

	this.ProjectOwnerField = ProjectOwnerField;
})();