/**
 * @module layout/ui/fields/entity-selector/theme/air/src/content
 */
jn.define('layout/ui/fields/entity-selector/theme/air/src/content', (require, exports, module) => {
	const { Indent } = require('tokens');
	const { AddButton } = require('layout/ui/fields/theme/air/elements/add-button');
	const { MoreButton } = require('layout/ui/fields/theme/air/elements/more-button');

	const MAX_ELEMENTS = 5;

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
			const showAll = field.getShowAllFromState();
			const entityList = field.getEntityList();
			const isCollapsed = entityList.length > MAX_ELEMENTS;
			const preparedEntityList = showAll ? entityList : entityList.slice(0, MAX_ELEMENTS);
			const showAllButton = isCollapsed && !showAll;
			const showAddButton = (
				field.shouldShowAddButton()
				&& field.getAddButtonText()
				&& !field.isReadOnly()
				&& !field.isRestricted()
				&& !field.isEmpty()
				&& field.isMultiple()
			);

			let content = null;
			if (field.isEmpty())
			{
				content = [
					Entity({
						field,
						title: field.getEmptyText(),
					}),
				];
			}
			else
			{
				content = preparedEntityList.map((entity, index) => Entity({
					field,
					...entity,
					indent: Indent.M,
					title: field.getEntityTitle(entity),
					isFirst: index === 0,
					isLast: index === entityList.length - 1,
				}));
			}

			return View(
				{
					testId: `${field.testId}_CONTENT`,
					style: {
						flexDirection: 'column',
					},
				},
				...content,
				showAddButton && AddButton({
					onClick: () => field.openSelector(false, field.addButtonRef),
					text: field.getAddButtonText(),
					testId: field.testId,
					bindAddButtonRef: field.bindAddButtonRef,
				}),
				showAllButton && MoreButton({
					onClick: field.onShowAllClick,
					testId: field.testId,
					text: `${BX.message('FIELDS_BASE_SHOW_ALL')} ${entityList.length - MAX_ELEMENTS}`,
				}),
			);
		};
	};

	module.exports = {
		Content,
	};
});
