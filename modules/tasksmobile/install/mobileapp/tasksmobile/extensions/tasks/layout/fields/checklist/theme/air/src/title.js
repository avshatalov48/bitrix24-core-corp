/**
 * @module tasks/layout/task/fields/checklist/theme/air/src/title
 */
jn.define('tasks/layout/task/fields/checklist/theme/air/src/title', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color, Indent } = require('tokens');

	/**
	 * @param {number} count
	 */
	const Title = ({ count = 0 }) => View(
		{
			style: {
				flexDirection: 'row',
				paddingVertical: Indent.M,
				marginBottom: Indent.XS,
			},
		},
		Text({
			text: Loc.getMessage('TASKS_FIELDS_CHECKLIST_AIR_TITLE'),
			style: {
				color: Color.base4,
				fontSize: 12,
				flexShrink: 2,
			},
			numberOfLines: 1,
			ellipsize: 'end',
		}),
		count && Text({
			text: String(count),
			style: {
				color: Color.base5,
				fontSize: 12,
				marginLeft: Indent.XS2,
			},
			numberOfLines: 1,
			ellipsize: 'end',
		}),
	);

	module.exports = {
		Title,
	};
});
