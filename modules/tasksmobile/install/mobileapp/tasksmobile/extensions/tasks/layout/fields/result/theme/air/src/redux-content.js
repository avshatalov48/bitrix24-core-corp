/**
 * @module tasks/layout/fields/result/theme/air/redux-content
 */
jn.define('tasks/layout/fields/result/theme/air/redux-content', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { Card, CardDesign } = require('ui-system/layout/card');
	const { PureComponent } = require('layout/pure-component');
	const { AddButton } = require('layout/ui/fields/theme/air/elements/add-button');
	const { Loc } = require('loc');
	const { Text4 } = require('ui-system/typography/text');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Avatar } = require('ui-system/blocks/avatar');
	const { FileField } = require('layout/ui/fields/file');
	const { Circle, Line } = require('utils/skeleton');
	const { Menu } = require('tasks/layout/fields/result/menu');
	const { confirmDestructiveAction } = require('alert');
	const { CollapsibleText } = require('layout/ui/collapsible-text');
	const { Date } = require('tasks/layout/fields/result/date');
	const { dayMonth, longDate, shortTime } = require('utils/date/formats');
	const { UserName } = require('layout/ui/user/user-name');
	const { PlainTextFormatter } = require('bbcode/formatter/plain-text-formatter');

	const { connect } = require('statemanager/redux/connect');
	const { selectByTaskIdOrGuid } = require('tasks/statemanager/redux/slices/tasks');
	const { selectLastResult } = require('tasks/statemanager/redux/slices/tasks-results');

	class ReduxContent extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.menu = new Menu();
		}

		/**
		 * @returns {TaskResultField}
		 */
		get #field()
		{
			return this.props.field;
		}

		/**
		 * @returns {string}
		 */
		get #testId()
		{
			return this.#field.testId;
		}

		/**
		 * @returns {object}
		 */
		get #result()
		{
			return this.props.result;
		}

		#openResult(forceFocus = false)
		{
			this.#field.openResult(this.#result.id, forceFocus);
		}

		render()
		{
			if (this.props.resultsCount === 0)
			{
				return View();
			}

			return View(
				{
					ref: this.#field.bindContainerRef,
				},
				Card(
					{
						testId: this.#testId,
						style: {
							borderWidth: 1,
							borderColor: (this.#result ? Color.accentMainPrimary.toHex() : Color.base6.toHex()),
							zIndex: 2,
						},
						design: CardDesign.PRIMARY,
						badgeMode: null,
						hideCross: true,
						onClick: () => this.#openResult(),
					},
					this.#renderHeader(),
					this.#renderCreator(),
					this.#renderText(),
					this.#renderFiles(),
					this.#renderAddButton(),
				),
				this.#renderMoreButton(),
			);
		}

		#renderHeader()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'space-between',
						alignItems: 'center',
						paddingBottom: Indent.L.toNumber(),
						borderBottomWidth: 1,
						borderBottomColor: Color.bgSeparatorSecondary.toHex(),
					},
					testId: `${this.#testId}_HEADER`,
				},
				View(
					{
						style: {
							flexDirection: 'row',
						},
					},
					IconView({
						icon: Icon.WINDOW_FLAG,
						color: (this.#result ? Color.accentMainPrimary : Color.base6),
						size: 24,
					}),
					View(
						{
							style: {
								marginLeft: Indent.XS.toNumber(),
							},
						},
						Text4({
							text: Loc.getMessage('TASKS_FIELDS_RESULT_AIR_TITLE'),
							accent: true,
						}),
						this.#result && new Date({
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
						}),
						!this.#result && Line(90, 8, 7),
					),
				),
				this.#isCreator() && IconView({
					icon: Icon.MORE,
					color: Color.base5,
					forwardRef: (ref) => {
						this.moreButtonRef = ref;
					},
					onClick: () => {
						this.menu.show({
							target: this.moreButtonRef,
							onUpdate: () => this.#openResult(true),
							onRemove: () => {
								confirmDestructiveAction({
									title: Loc.getMessage('TASKS_FIELDS_RESULT_REMOVE_CONFIRM_TITLE_V2'),
									description: Loc.getMessage('TASKS_FIELDS_RESULT_REMOVE_CONFIRM_DESCRIPTION'),
									destructionText: Loc.getMessage('TASKS_FIELDS_RESULT_REMOVE_CONFIRM_YES'),
									onDestruct: () => this.#field.removeResult(this.#result.commentId),
								});
							},
						});
					},
				}),
			);
		}

		#renderCreator()
		{
			if (!this.#result)
			{
				return View(
					{
						style: {
							flexDirection: 'row',
							marginTop: Indent.XL.toNumber(),
							alignItems: 'center',
						},
					},
					Circle(32),
					View(
						{
							style: {
								marginLeft: Indent.M.toNumber(),
							},
						},
						Line(150, 8),
					),
				);
			}

			const userId = this.#result.createdBy;

			return View(
				{
					style: {
						flexDirection: 'row',
						marginTop: Indent.XL.toNumber(),
						alignItems: 'center',
					},
				},
				Avatar({
					id: userId,
					size: 32,
					testId: `${this.#testId}_CREATOR_AVATAR`,
					withRedux: true,
				}),
				UserName({
					style: {
						marginLeft: Indent.M.toNumber(),
					},
					id: userId,
					testId: `${this.#testId}_CREATOR_NAME`,
					withRedux: true,
				}),
			);
		}

		#renderText()
		{
			if (!this.#result)
			{
				return View(
					{
						style: {
							marginTop: Indent.M.toNumber(),
						},
					},
					...Array.from({ length: 3 }).fill(
						Line('100%', 8, 9),
					),
				);
			}

			const plainTextFormatter = new PlainTextFormatter();
			const plainAst = plainTextFormatter.format({
				source: this.#result.text,
				data: {
					files: this.#result.files ?? [],
				},
			});

			return new CollapsibleText({
				containerStyle: {
					marginTop: Indent.M.toNumber(),
				},
				style: {
					fontSize: 15,
					fontWeight: '400',
					color: Color.base2.toHex(),
				},
				value: plainAst.toString(),
				bbCodeMode: false,
				canExpand: false,
				testId: `${this.#testId}_TEXT`,
				onClick: () => this.#openResult(),
				onLongClick: () => this.#openResult(),
				onLinkClick: () => this.#openResult(),
			});
		}

		#renderFiles()
		{
			if (!this.#result)
			{
				return FileField({
					readOnly: true,
					showEditIcon: false,
					hasHiddenEmptyView: false,
					showTitle: false,
					showFilesName: true,
					multiple: true,
					value: [],
					config: {
						parentWidget: this.#field.parentWidget,
						isShimmed: true,
					},
				});
			}

			if (this.#result.files.length === 0)
			{
				return null;
			}

			return FileField({
				readOnly: true,
				showEditIcon: false,
				hasHiddenEmptyView: false,
				showTitle: false,
				showFilesName: true,
				multiple: true,
				value: this.#result.files,
				config: {
					parentWidget: this.#field.parentWidget,
				},
				testId: `${this.#testId}_FILES`,
			});
		}

		#renderAddButton()
		{
			return View(
				{
					style: {
						marginTop: Indent.L.toNumber(),
					},
				},
				AddButton({
					text: Loc.getMessage('TASKS_FIELDS_RESULT_AIR_ADD_RESULT'),
					testId: `${this.#testId}_ADD_BUTTON`,
					onClick: () => this.#field.createNewResult(),
				}),
			);
		}

		#renderMoreButton()
		{
			const resultsCount = this.props.resultsCount;
			if (resultsCount <= 1)
			{
				return null;
			}

			return Card(
				{
					style: {
						zIndex: 1,
						marginTop: -22,
					},
					design: CardDesign.PRIMARY,
					badgeMode: null,
					hideCross: true,
					testId: `${this.#testId}_MORE`,
				},
				View(
					{
						style: {
							flexDirection: 'row',
							justifyContent: 'space-between',
							alignItems: 'center',
							marginTop: Indent.XL3.toNumber(),
						},
						testId: `${this.#testId}_MORE_BUTTON`,
						onClick: (this.#result ? () => this.#field.openResultList() : () => {}),
					},
					this.#result && Text4({
						style: {
							color: Color.base4.toHex(),
						},
						text: Loc.getMessagePlural(
							'TASKS_FIELDS_RESULT_AIR_SHOW_MORE',
							resultsCount - 1,
							{ '#COUNT#': resultsCount - 1 },
						),
						testId: `${this.#testId}_MORE_BUTTON_TEXT`,
					}),
					!this.#result && Line(186, 8, 8),
					IconView({
						icon: Icon.CHEVRON_TO_THE_RIGHT,
						color: Color.base5,
						size: 24,
					}),
				),
			);
		}

		#isCreator()
		{
			return Number(this.#result?.createdBy) === Number(env.userId);
		}
	}

	const mapStateToProps = (state, ownProps) => {
		const taskId = ownProps.field.taskId;
		const result = selectLastResult(state, taskId);
		const { resultsCount } = selectByTaskIdOrGuid(state, taskId);

		if (!result.id)
		{
			return {
				result: undefined,
				resultsCount,
			};
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
			resultsCount,
		};
	};

	module.exports = {
		TaskResultAirReduxContent: connect(mapStateToProps)(ReduxContent),
	};
});
