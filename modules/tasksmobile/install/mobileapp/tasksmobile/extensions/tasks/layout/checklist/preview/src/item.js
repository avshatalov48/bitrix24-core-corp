/**
 * @module tasks/layout/checklist/preview/src/item
 */
jn.define('tasks/layout/checklist/preview/src/item', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color, Indent } = require('tokens');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Text4, Text5 } = require('ui-system/typography/text');
	const { Line } = require('utils/skeleton');

	/**
	 * @param {string} testId
	 * @param {number} completedCount
	 * @param {number} totalCount
	 * @param {string} title
	 * @param {boolean} showBorder
	 * @param {boolean} isComplete
	 * @param {boolean} isLoading
	 * @param {function} onClick
	 */
	const Item = ({
		testId,
		completedCount,
		totalCount,
		title,
		showBorder,
		isComplete,
		isLoading,
		onClick,
	}) => View(
		{
			testId: `${testId}_ITEM`,
			style: {
				flexDirection: 'row',
				marginBottom: Indent.S.toNumber(),
				paddingTop: Indent.S.toNumber(),
				paddingHorizontal: Indent.XL.toNumber(),
			},
			onClick,
		},
		IconView({
			icon: Icon.TASK_LIST,
			size: {
				width: 24,
				height: 24,
			},
			iconColor: isComplete ? Color.base5 : Color.accentMainPrimaryalt,
		}),
		View(
			{
				style: {
					flexDirection: 'row',
					marginLeft: Indent.M.toNumber(),
					paddingBottom: Indent.L.toNumber(),
					flexGrow: 1,
					borderBottomWidth: showBorder ? 1 : 0,
					borderBottomColor: Color.bgSeparatorSecondary.toHex(),
				},
			},
			View(
				{
					style: {
						flexDirection: 'column',
						paddingTop: 1,
						flexGrow: 1,
						flexBasis: 20,
					},
				},
				(isLoading && title === '')
					? Line(200, 10, Indent.XS.toNumber(), Indent.L.toNumber())
					: Text4({
						testId: `${testId}_ITEM_TITLE`,
						text: title,
						color: isComplete ? Color.base5 : Color.base2,
						style: {
							marginBottom: Indent.XS.toNumber(),
						},
						numberOfLines: 3,
						ellipsize: 'end',
					}),
				Text5({
					testId: `${testId}_ITEM_COUNT`,
					text: Loc.getMessage('TASKS_FIELDS_CHECKLIST_AIR_PROGRESS_MSGVER_1')
						.replace('#COMPLETED#', String(completedCount))
						.replace('#TOTAL#', String(totalCount)),
					color: isComplete ? Color.base5 : Color.base3,
					style: {
						opacity: isLoading ? 0 : 1,
					},
					numberOfLines: 1,
					ellipsize: 'end',
				}),
				isLoading && View(
					{
						testId: `${testId}_ITEM_LOADING`,
						style: {
							width: 100,
							height: 8,
							position: 'absolute',
							bottom: 2,
						},
					},
					Line(100, 8),
				),
			),
			IconView({
				icon: Icon.CHEVRON_TO_THE_RIGHT,
				size: {
					width: 20,
					height: 20,
				},
				iconColor: isComplete ? Color.base5 : Color.base3,
			}),
		),
	);

	const ItemStub = ({ testId, title, showBorder = true } = {}) => Item({
		testId,
		title,
		showBorder,
		totalCount: 0,
		completedCount: 0,
		isComplete: true,
		isLoading: true,
	});

	module.exports = {
		Item,
		ItemStub,
	};
});
