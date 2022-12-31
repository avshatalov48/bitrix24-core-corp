(() => {
	const { StringField } = jn.require('layout/ui/fields/string');
	const { EntitySelectorField } = jn.require('layout/ui/fields/entity-selector');

	class ProjectTagsField extends LayoutComponent
	{
		render()
		{
			if (this.props.readOnly)
			{
				return View(
					{},
					StringField({
						readOnly: true,
						title: BX.message('MOBILE_LAYOUT_PROJECT_FIELDS_TAGS_TITLE'),
						value: this.props.value.join(', '),
					})
				);
			}

			return View(
				{},
				EntitySelectorField({
					readOnly: false,
					title: BX.message('MOBILE_LAYOUT_PROJECT_FIELDS_TAGS_TITLE'),
					value: this.props.value,
					multiple: true,
					config: {
						selectorType: EntitySelectorFactory.Type.PROJECT_TAG,
						enableCreation: true,
						entityList: this.props.value.map(tag => ({
							id: tag,
							title: tag,
						})),
						provider: {
							options: {
								groupId: this.props.projectId,
							},
							context: 'PROJECT_TAG',
						},
						castType: 'string',
						parentWidget: this.props.parentWidget,
					},
					onChange: value => this.props.onChange(value),
				})
			);
		}
	}

	this.ProjectTagsField = ProjectTagsField;
})();