/**
 * @module layout/socialnetwork/project/fields/tags
 */
jn.define('layout/socialnetwork/project/fields/tags', (require, exports, module) => {
	const { StringField } = require('layout/ui/fields/string');
	const { EntitySelectorField } = require('layout/ui/fields/entity-selector');
	const { EntitySelectorFactoryType } = require('selector/widget/factory');

	class ProjectTagsField extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.onChange = this.onChange.bind(this);
		}

		render()
		{
			const { readOnly, value, projectId, parentWidget } = this.props;

			if (readOnly)
			{
				return View(
					{},
					StringField({
						readOnly: true,
						title: BX.message('MOBILE_LAYOUT_PROJECT_FIELDS_TAGS_TITLE'),
						value: value.join(', '),
					}),
				);
			}

			return View(
				{},
				EntitySelectorField({
					value,
					readOnly: false,
					title: BX.message('MOBILE_LAYOUT_PROJECT_FIELDS_TAGS_TITLE'),
					multiple: true,
					config: {
						parentWidget,
						selectorType: EntitySelectorFactoryType.PROJECT_TAG,
						enableCreation: true,
						closeAfterCreation: false,
						entityList: value.map((tag) => ({ id: tag, title: tag })),
						provider: {
							options: {
								groupId: projectId,
							},
							context: 'PROJECT_TAG',
						},
						castType: 'string',
					},
					onChange: this.onChange,
				}),
			);
		}

		onChange(value)
		{
			this.props.onChange?.(value);
		}
	}

	module.exports = { ProjectTagsField };
});
