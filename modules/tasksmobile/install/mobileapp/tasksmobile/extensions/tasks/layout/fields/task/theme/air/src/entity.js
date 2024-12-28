/**
 * @module tasks/layout/fields/task/theme/air/src/entity
 */
jn.define('tasks/layout/fields/task/theme/air/src/entity', (require, exports, module) => {
	const { Indent, Color } = require('tokens');
	const { Text4 } = require('ui-system/typography/text');
	const { IconView } = require('ui-system/blocks/icon');
	const { DeadlinePill } = require('tasks/layout/deadline-pill');
	const { Avatar } = require('ui-system/blocks/avatar');
	const { Line, Circle } = require('utils/skeleton');
	const { TaskStatus } = require('tasks/enum');
	const { Entry } = require('tasks/entry');

	/**
	 * @param {object} field
	 * @param {string} id
	 * @param {string} title
	 * @param {boolean} isFirst
	 * @param {boolean} isLast
	 * @param {object} customData
	 */
	const Entity = ({
		field,
		id,
		title,
		isFirst,
		isLast,
		customData,
	}) => {
		const testId = `${field.testId}_SUBTASK_${id}`;
		const responsible = customData?.responsible;
		const deadline = customData?.deadline;
		const isCompleted = customData?.isCompleted;
		const status = customData?.status;
		const isLoading = customData?.isLoading;

		return View(
			{
				testId,
				style: {
					flexDirection: 'row',
					justifyContent: 'flex-start',
					marginTop: !isFirst && Indent.M.toNumber(),
					marginBottom: isLast ? Indent.XL.toNumber() : 0,
					paddingTop: Indent.S.toNumber(),
				},
				onClick: () => Entry.openTask({ id }, { parentWidget: field.getParentWidget() }),
			},
			IconView({
				icon: field.getDefaultLeftIcon(),
				size: 24,
				color: Color.accentMainPrimaryalt,
				style: {
					marginRight: Indent.M.toNumber(),
				},
			}),
			View(
				{
					style: {
						flex: 2,
						flexDirection: 'row',
						justifyContent: 'space-between',
						borderBottomWidth: 1,
						borderBottomColor: !isLast && Color.bgSeparatorSecondary.toHex(),
						paddingBottom: Indent.L.toNumber(),
					},
				},
				View(
					{
						style: {
							flexShrink: 2,
							flexDirection: 'column',
						},
					},
					isLoading && View(
						{
							style: {
								height: 18,
							},
						},
						Line('90%', 8, 0, Indent.S.toNumber()),
					),
					!isLoading && Text4({
						testId: `${testId}_TITLE`,
						text: title || id,
						style: {
							color: isCompleted && status !== TaskStatus.SUPPOSEDLY_COMPLETED
								? Color.base4.toHex()
								: Color.base2.toHex(),
							flexShrink: 2,
							marginBottom: Indent.S.toNumber(),
							textDecorationLine: isCompleted && status !== TaskStatus.SUPPOSEDLY_COMPLETED ? 'line-through' : 'none',
						},
						numberOfLines: 2,
						ellipsize: 'end',
					}),
					isLoading && View(
						{
							style: {
								flexDirection: 'row',
							},
						},
						View(
							{
								style: {
									marginRight: 10,
								},
							},
							Circle(24),
						),
						Line(100, 24, 0, Indent.S.toNumber()),
					),
					!isLoading && View(
						{
							style: {
								flexDirection: 'row',
								alignItems: 'center',
							},
						},
						Avatar({
							size: 24,
							id: responsible,
							testId: `${testId}_RESPONSIBLE`,
							withRedux: true,
						}),
						DeadlinePill({
							id,
							testId: `${testId}_DEADLINE`,
							backgroundColor: Color.bgContentPrimary.toHex(),
							deadline,
							readOnly: true,
						}),
					),
				),
				(field.isReadOnly() || field.isRestricted()) && IconView({
					icon: 'chevronRight',
					size: 20,
					color: Color.base5,
				}),
				(!isLoading && !field.isReadOnly() && !field.isRestricted()) && IconView({
					icon: 'cross',
					size: 20,
					color: Color.base5,
					onClick: () => field.removeEntity(id),
				}),
			),
		);
	};

	module.exports = { Entity };
});
