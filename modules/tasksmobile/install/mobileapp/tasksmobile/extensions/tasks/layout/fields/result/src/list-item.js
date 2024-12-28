/**
 * @module tasks/layout/fields/result/list-item
 */
jn.define('tasks/layout/fields/result/list-item', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { PureComponent } = require('layout/pure-component');
	const { Avatar } = require('ui-system/blocks/avatar');
	const { connect } = require('statemanager/redux/connect');
	const { selectById } = require('tasks/statemanager/redux/slices/tasks-results');
	const { Text4, Text5 } = require('ui-system/typography/text');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Date } = require('tasks/layout/fields/result/date');
	const { dayMonth, longDate, shortTime } = require('utils/date/formats');
	const { Loc } = require('loc');
	const { BBCodeParser } = require('bbcode/parser');

	class TaskResultListItemContent extends PureComponent
	{
		get #result()
		{
			return this.props.result;
		}

		get #testId()
		{
			return `TASK_RESULT_LIST_ITEM_${this.#result.id}`;
		}

		render()
		{
			if (!this.#result)
			{
				return View({ style: { display: 'none' } });
			}

			return View(
				{
					style: {
						flexDirection: 'row',
						paddingLeft: Indent.XL3.toNumber(),
						paddingTop: Indent.XL2.toNumber(),
						backgroundColor: Color.bgContentPrimary.withPressed(),
					},
					testId: this.#testId,
					onClick: () => this.props.onResultClick(this.#result.id),
				},
				this.#renderCreator(),
				View(
					{
						style: {
							flex: 1,
							marginLeft: Indent.XL.toNumber(),
							paddingRight: Indent.XL3.toNumber(),
							paddingBottom: Indent.XL2.toNumber(),
							borderBottomWidth: (this.props.showBottomBorder ? 1 : 0),
							borderBottomColor: Color.bgSeparatorSecondary.toHex(),
						},
					},
					this.#renderText(),
					View(
						{
							style: {
								flexDirection: 'row',
								height: 20,
								alignItems: 'center',
								marginTop: Indent.XS2.toNumber(),
							},
						},
						this.#renderDate(),
						this.#renderFiles(),
					),
				),
			);
		}

		#renderCreator()
		{
			return Avatar({
				id: this.#result.createdBy,
				size: 32,
				testId: `${this.#testId}_CREATOR`,
				withRedux: true,
			});
		}

		#renderText()
		{
			return Text4({
				color: Color.base1,
				text: new BBCodeParser().parse(this.#result.text).toPlainText(),
				numberOfLines: 2,
				ellipsize: 'end',
				testId: `${this.#testId}_TEXT`,
			});
		}

		#renderDate()
		{
			return new Date({
				style: {
					color: Color.base3.toHex(),
				},
				defaultFormat: (moment) => Loc.getMessage(
					'TASKS_FIELDS_RESULT_DATE_FORMAT',
					{
						'#DATE#': moment.format(moment.inThisYear ? dayMonth() : longDate()),
						'#TIME#': moment.format(shortTime),
					},
				),
				timeSeparator: '',
				showTime: true,
				useTimeAgo: true,
				timestamp: this.#result.createdAt,
				testId: `${this.#testId}_DATE`,
			});
		}

		#renderFiles()
		{
			if (!this.#result.files || this.#result.files.length === 0)
			{
				return null;
			}

			return View(
				{
					style: {
						flexDirection: 'row',
						marginLeft: Indent.M.toNumber(),
					},
				},
				IconView({
					color: Color.base3,
					iconSize: {
						width: 20,
						height: 20,
					},
					icon: Icon.ATTACH,
				}),
				Text5({
					color: Color.base3,
					text: this.#result.files.length.toString(),
					testId: `${this.#testId}_FILES`,
				}),
			);
		}
	}

	const mapStateToProps = (state, ownProps) => {
		const resultId = ownProps.id;
		const result = selectById(state, resultId);

		if (!result)
		{
			return { result };
		}

		const {
			id,
			commentId,
			createdBy,
			createdAt,
			status,
			text,
			files,
		} = result;

		return {
			result: {
				id,
				commentId,
				createdBy,
				createdAt,
				status,
				text,
				files,
			},
		};
	};

	module.exports = {
		TaskResultListItem: connect(mapStateToProps)(TaskResultListItemContent),
	};
});
