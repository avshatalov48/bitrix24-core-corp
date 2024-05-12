/**
 * @module tasks/layout/task/fields/checklist/theme/air/src/item
 */
jn.define('tasks/layout/task/fields/checklist/theme/air/src/item', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color, Indent } = require('tokens');
	const { IconView } = require('ui-system/blocks/icon');

	/**
	 * @param {number} completedCount
	 * @param {number} totalCount
	 * @param {string} title
	 */
	const Item = ({
		completedCount,
		totalCount,
		title,
		onClick,
	}) => View(
		{
			style: {
				flexDirection: 'row',
			},
			onClick,
		},
		IconView({
			icon: 'taskList2',
			size: {
				width: 32,
				height: 32,
			},
			iconColor: Color.accentMainPrimaryalt,
		}),
		View(
			{
				style: {
					flexDirection: 'row',
					marginLeft: Indent.M,
					marginTop: Indent.S,
					marginBottom: Indent.M,
				},
			},
			View(
				{
					style: {
						flexDirection: 'column',
						marginLeft: Indent.M,
						marginRight: Indent.XS,
					},
				},
				Text({
					text: title,
					style: {
						color: Color.base2,
						fontSize: 14,
					},
					numberOfLines: 1,
					ellipsize: 'end',
				}),
				Text({
					text: Loc.getMessage('TASKS_FIELDS_CHECKLIST_AIR_PROGRESS')
						.replace('#COMPLETED#', String(completedCount))
						.replace('#TOTAL#', String(totalCount)),
					style: {
						color: Color.base5,
						fontSize: 12,
					},
					numberOfLines: 1,
					ellipsize: 'end',
				}),
			),
			IconView({
				icon: 'chevronRight',
				size: {
					width: 20,
					height: 20,
				},
				iconColor: Color.base3,
			}),
		),
	);

	module.exports = {
		Item,
	};
});
