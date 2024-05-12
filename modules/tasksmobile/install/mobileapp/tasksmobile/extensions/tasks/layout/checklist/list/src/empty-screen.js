/**
 * @module tasks/layout/checklist/list/src/empty-screen
 */
jn.define('tasks/layout/checklist/list/src/empty-screen', (require, exports, module) => {
	const { EmptyScreen } = require('layout/ui/empty-screen');

	/**
	 * @object emptyScreenItemType
	 */
	const emptyScreenItemType = {
		key: 'emptyScreenItem',
		type: 'checklist-emptyScreen',
	};
	const ChecklistEmptyScreen = ({ title, imageName, onClick }) => {
		return View(
			{
				style: {
					position: 'relative',
					height: 316,
					width: '100%',
				},
			},
			new EmptyScreen({
				title,
				image: {
					uri: EmptyScreen.makeLibraryImagePath(imageName, 'tasks'),
					style: {
						width: 138,
						height: 114,
					},
				},
				description: () => Button({
					text: 'dsdsa',
					onClick,
				}),
			}),
		);
	};

	module.exports = { ChecklistEmptyScreen, emptyScreenItemType };
});
