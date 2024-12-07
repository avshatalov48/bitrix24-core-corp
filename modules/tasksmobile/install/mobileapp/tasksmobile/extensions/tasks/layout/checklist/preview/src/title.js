/**
 * @module tasks/layout/checklist/preview/src/title
 */
jn.define('tasks/layout/checklist/preview/src/title', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color, Indent } = require('tokens');
	const { Text5 } = require('ui-system/typography/text');
	const text = Loc.getMessage('TASKS_FIELDS_CHECKLIST_AIR_TITLE');

	/**
	 * @param {boolean} loading
	 * @param {number} count
	 * @param {string} testId
	 */
	const Title = ({ count = 0, testId, loading = false }) => {
		const shouldShowCounter = count > 0 && !loading;

		return View(
			{
				style: {
					flexDirection: 'row',
					marginBottom: Indent.XL.toNumber(),
					paddingHorizontal: Indent.XL2.toNumber(),
				},
			},
			Text5({
				testId: `${testId}_TITLE`,
				text: shouldShowCounter ? `${text}: ` : String(text),
				color: Color.base4,
				style: {
					flexShrink: 2,
				},
				numberOfLines: 1,
				ellipsize: 'end',
			}),
			shouldShowCounter && Text5({
				testId: `${testId}_TITLE_COUNT`,
				text: String(count),
				color: Color.base5,
				numberOfLines: 1,
				ellipsize: 'end',
			}),
		);
	};

	module.exports = {
		Title,
	};
});
