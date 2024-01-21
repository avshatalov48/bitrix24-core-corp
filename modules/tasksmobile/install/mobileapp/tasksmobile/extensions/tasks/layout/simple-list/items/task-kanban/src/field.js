/**
 * @module tasks/layout/simple-list/items/task-kanban/src/field
 */
jn.define('tasks/layout/simple-list/items/task-kanban/src/field', (require, exports, module) => {
	const AppTheme = require('apptheme');

	/**
	 * @param {string} title
	 * @param {Function} body
	 * @return {object}
	 */
	const Field = ({ title, body }) => View(
		{
			style: {
				marginBottom: 4,
				paddingVertical: 6,
			},
		},
		View(
			{
				style: {
					marginBottom: 4,
				},
			},
			Text({
				text: String(title).toLocaleUpperCase(env.languageId),
				style: {
					fontSize: 10,
					color: AppTheme.colors.base3,
				},
			}),
		),
		body(),
	);

	/**
	 * @param {string} title
	 * @param {string} value
	 * @param {string} testId
	 * @return {object}
	 */
	const TextField = ({ title, value, testId }) => Field({
		title,
		body: () => Text({
			testId,
			text: String(value),
			style: {
				fontSize: 14,
				color: AppTheme.colors.base2,
			},
		}),
	});

	/**
	 * @param {string} title
	 * @param {string} value
	 * @param {string} icon - SVG content of icon
	 * @param {string} testId
	 * @return {object}
	 */
	const IconField = ({ title, value, icon, testId }) => Field({
		title,
		body: () => View(
			{
				testId,
				style: {
					flexDirection: 'row',
					alignItems: 'center',
				},
			},
			Image({
				tintColor: AppTheme.colors.base3,
				svg: {
					content: icon,
				},
				style: {
					width: 24,
					height: 24,
					marginRight: 8,
				},
			}),
			Text({
				text: String(value),
				style: {
					fontSize: 14,
					color: AppTheme.colors.base2,
				},
			}),
		),
	});

	module.exports = {
		Field,
		TextField,
		IconField,
	};
});
