/**
 * @module layout/ui/entity-editor/control/column
 */
jn.define('layout/ui/entity-editor/control/column', (require, exports, module) => {

	const { EntityEditorBaseControl } = require('layout/ui/entity-editor/control/base');

	/**
	 * @class EntityEditorColumn
	 */
	class EntityEditorColumn extends EntityEditorBaseControl
	{
		constructor(props)
		{
			super(props);

			/** @type {EntityEditorSection[]} */
			this.sections = [];
		}

		/**
		 * @returns {EntityEditorSection[]}
		 */
		getControls()
		{
			return this.sections;
		}

		renderSections()
		{
			return this.renderFromModel((ref, index) => {
				this.sections[index] = ref;
			});
		}

		render()
		{
			const renderedSections = this.renderSections();
			if (!renderedSections || renderedSections.length === 0)
			{
				return null;
			}

			return View(
				{
					style: {
						style: {
							flexDirection: 'column',
						},
					},
				},
				...renderedSections,
			);
		}
	}

	module.exports = { EntityEditorColumn };
});