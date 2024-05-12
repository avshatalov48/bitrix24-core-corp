/**
 * @module layout/ui/fields/entity-selector/theme/air/src/content
 */
jn.define('layout/ui/fields/entity-selector/theme/air/src/content', (require, exports, module) => {
	const { Indent } = require('tokens');
	const { AddButton } = require('layout/ui/fields/theme/air/elements/add-button');

	/**
	 * @param {function} Entity - functional component
	 * @return {function} - A new function that return an enhancing functional component with Entity component.
	 */
	const Content = (Entity) => {
		/**
		 * @param {EntitySelectorField} field
		 * @return {function} - functional component
		 */
		return function({
			field,
		}) {
			const addButtonText = field.getAddButtonText();
			let content = null;
			if (field.isEmpty())
			{
				content = [Entity({
					field,
					title: field.getEmptyText(),
				})];
			}
			else
			{
				content = field.getEntityList().map((entity) => Entity({
					field,
					id: entity.id,
					title: entity.title,
					imageUrl: entity.imageUrl,
					avatar: entity.avatar,
					indent: Indent.M,
				}));
			}

			return View(
				{
					style: {
						flexDirection: 'column',
					},
				},
				...content,
				addButtonText
				&& !field.isReadOnly()
				&& field.isMultiple()
				&& !field.isEmpty()
				&& AddButton({
					field,
					onClick: field.openSelector,
					text: addButtonText,
				}),
			);
		};
	};

	module.exports = {
		Content,
	};
});
