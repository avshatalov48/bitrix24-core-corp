/**
 * @module tasks/flow-list/simple-list/items/flow-redux/src/flow-ai-advice
 */
jn.define('tasks/flow-list/simple-list/items/flow-redux/src/flow-ai-advice', (require, exports, module) => {
	const { Box } = require('ui-system/layout/box');
	const { Card, CardDesign } = require('ui-system/layout/card');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Link6, LinkDesign, LinkMode } = require('ui-system/blocks/link');
	const { Text4, Text5, Text7, H4 } = require('ui-system/typography');

	const { BottomSheet } = require('bottom-sheet');
	const { Color, Indent } = require('tokens');
	const { Loc } = require('loc');
	const { ProfileView } = require('user/profile');
	const { UIMenu } = require('layout/ui/menu');
	const { openTaskCreateForm } = require('tasks/layout/task/create/opener');

	class FlowAiAdvice extends LayoutComponent
	{
		/**
		 * @param flow
		 * @param {PageManager} parentWidget
		 */
		static show(flow, parentWidget = PageManager)
		{
			void new BottomSheet({
				titleParams: {
					text: Loc.getMessage('TASKSMOBILE_FLOW_AI_ADVICE_WIDGET_TITLE'),
					type: 'dialog',
				},
				component: (layout) => new FlowAiAdvice({ flow, parentWidget: layout }),
			})
				.setParentWidget(parentWidget)
				.setNavigationBarColor(Color.bgContentPrimary.toHex())
				.setBackgroundColor(Color.bgContentPrimary.toHex())
				.alwaysOnTop()
				.open()
			;
		}

		constructor(props)
		{
			super(props);

			this.menuButtonRefsMap = new Map();
		}

		get flow()
		{
			return this.props.flow;
		}

		get aiAdvice()
		{
			return this.flow.aiAdvice;
		}

		get testId()
		{
			return `flow-ai-advice-${this.flow.id}`;
		}

		render()
		{
			return Box(
				{
					backgroundColor: Color.bgContentPrimary,
					withScroll: true,
					withPaddingHorizontal: true,
					testId: `${this.testId}-container`,
				},
				this.renderHeader(),
				...this.aiAdvice.advices.map((advice, index) => this.renderAdvice(advice, index + 1)),
				this.renderFootnote(),
			);
		}

		renderHeader()
		{
			return Card(
				{
					style: {
						marginTop: Indent.M.toNumber(),
					},
					design: CardDesign.PRIMARY,
					border: true,
					testId: `${this.testId}-header`,
				},
				View(
					{
						style: {
							flexDirection: 'row',
						},
					},
					IconView({
						style: {
							backgroundColor: Color.copilotBgContent1.toHex(),
							borderRadius: 14,
						},
						icon: Icon.COPILOT,
						size: 28,
						color: Color.copilotAccentLess2,
					}),
					View(
						{
							style: {
								flex: 1,
								marginLeft: Indent.M.toNumber(),
							},
						},
						H4({
							text: Loc.getMessage('TASKSMOBILE_FLOW_AI_ADVICE_HEADER_TITLE'),
						}),
						BBCodeText({
							style: {
								marginTop: Indent.XS2.toNumber(),
								color: Color.base4.toHex(),
								fontSize: 13,
								fontWeight: '400',
							},
							testId: `${this.testId}-header-subtitle`,
							numberOfLines: 2,
							ellipsize: 'end',
							value: this.getHeaderSubtitleText(),
						}),
					),
				),
				View(
					{
						style: {
							marginTop: Indent.L.toNumber(),
							paddingHorizontal: Indent.M.toNumber(),
							paddingVertical: Indent.S.toNumber(),
							backgroundColor: Color.copilotBgContent1.toHex(),
							borderRadius: 6,
						},
						testId: `${this.testId}-header-note-container`,
					},
					Text5({
						color: Color.copilotAccentLess2,
						text: this.getHeaderNoteText(),
						testId: `${this.testId}-header-note-field`,
					}),
				),
			);
		}

		getHeaderSubtitleText()
		{
			const { tasksTotal, efficiency } = this.flow;
			const { minTasksCountForAdvice, efficiencyThreshold } = this.aiAdvice;

			if (tasksTotal < minTasksCountForAdvice)
			{
				return Loc.getMessagePlural(
					'TASKSMOBILE_FLOW_AI_ADVICE_HEADER_SUBTITLE_NO_DATA',
					minTasksCountForAdvice,
					{
						'#MIN_TASKS_COUNT_FOR_ADVICE#': minTasksCountForAdvice,
					},
				);
			}

			if (efficiency <= efficiencyThreshold)
			{
				return Loc.getMessage(
					'TASKSMOBILE_FLOW_AI_ADVICE_HEADER_SUBTITLE_LOW',
					{
						'#EFFICIENCY#': efficiency,
						'#ANCHOR_START#': `[color=${Color.accentMainAlert.toHex()}]`,
						'#ANCHOR_END#': '[/color]',
					},
				);
			}

			return Loc.getMessage(
				'TASKSMOBILE_FLOW_AI_ADVICE_HEADER_SUBTITLE_HIGH',
				{
					'#EFFICIENCY#': efficiency,
					'#ANCHOR_START#': `[color=${Color.accentExtraGrass.toHex()}]`,
					'#ANCHOR_END#': '[/color]',
				},
			);
		}

		getHeaderNoteText()
		{
			const { tasksTotal, efficiency } = this.flow;
			const { minTasksCountForAdvice, efficiencyThreshold } = this.aiAdvice;

			if (tasksTotal < minTasksCountForAdvice)
			{
				return Loc.getMessage('TASKSMOBILE_FLOW_AI_ADVICE_HEADER_NOTE_NO_DATA');
			}

			if (efficiency <= efficiencyThreshold)
			{
				return Loc.getMessage('TASKSMOBILE_FLOW_AI_ADVICE_HEADER_NOTE_LOW');
			}

			return Loc.getMessage('TASKSMOBILE_FLOW_AI_ADVICE_HEADER_NOTE_HIGH');
		}

		renderAdvice({ factor, advice = '' }, order)
		{
			return Card(
				{
					style: {
						flexDirection: 'row',
						marginTop: Indent.XL.toNumber(),
						paddingBottom: Indent.S.toNumber(),
					},
					excludePaddingSide: {
						left: true,
						right: true,
						bottom: true,
					},
					testId: `${this.testId}-advice-${order}-container`,
				},
				View(
					{
						style: {
							width: 24,
							height: 24,
							backgroundColor: Color.copilotBgContent1.toHex(),
							borderRadius: 12,
							alignItems: 'center',
							justifyContent: 'center',
						},
						testId: `${this.testId}-advice-${order}-order-container`,
					},
					Text4({
						color: Color.copilotAccentPrimary,
						text: String(order),
						testId: `${this.testId}-advice-${order}-order-field`,
					}),
				),
				View(
					{
						style: {
							flex: 1,
							marginLeft: Indent.L.toNumber(),
						},
						testId: `${this.testId}-advice-${order}-content-container`,
					},
					BBCodeText({
						style: {
							fontSize: 15,
							fontWeight: '400',
							color: Color.base1.toHex(),
						},
						value: factor,
						testId: `${this.testId}-advice-${order}-factor-field`,
						onUserClick: this.onUserClick.bind(this),
					}),
					advice && View(
						{
							style: {
								marginTop: Indent.L.toNumber(),
								paddingHorizontal: Indent.L.toNumber(),
								paddingTop: Indent.S.toNumber(),
								paddingBottom: Indent.L.toNumber(),
								backgroundColor: Color.copilotBgContent1.toHex(),
								borderRadius: 8,
							},
							testId: `${this.testId}-advice-${order}-advice-container`,
						},
						View(
							{
								style: {
									flexDirection: 'row',
									justifyContent: 'space-between',
								},
								testId: `${this.testId}-advice-${order}-advice-header-container`,
							},
							View(
								{
									style: {
										flexDirection: 'row',
									},
								},
								IconView({
									icon: Icon.COPILOT,
									color: Color.copilotAccentPrimary,
									size: 20,
								}),
								Text4({
									style: {
										marginLeft: Indent.S.toNumber(),
									},
									accent: true,
									text: Loc.getMessage('TASKSMOBILE_FLOW_AI_ADVICE_ADVICES_ADVICE'),
								}),
							),
							IconView({
								icon: Icon.MORE,
								color: Color.base4,
								size: 24,
								testId: `${this.testId}-advice-${order}-advice-header-menu-button`,
								forwardRef: (ref) => this.menuButtonRefsMap.set(order, ref),
								onClick: () => this.showAdviceMenu(this.menuButtonRefsMap.get(order), advice),
							}),
						),
						BBCodeText({
							style: {
								marginTop: Indent.S.toNumber(),
								fontSize: 15,
								fontWeight: '400',
								color: Color.base1.toHex(),
							},
							value: advice,
							testId: `${this.testId}-advice-${order}-advice-field`,
							onUserClick: this.onUserClick.bind(this),
						}),
					),
				),
			);
		}

		showAdviceMenu(target, adviceText)
		{
			new UIMenu([
				{
					id: 'task',
					testId: 'task',
					title: Loc.getMessage('TASKSMOBILE_FLOW_AI_ADVICE_MENU_ITEM_CREATE_TASK'),
					iconName: Icon.CIRCLE_CHECK,
					sectionCode: 'default',
					onItemSelected: () => {
						openTaskCreateForm({
							initialTaskData: {
								description: adviceText,
							},
							layoutWidget: this.props.parentWidget,
						});
					},
				},
				{
					id: 'calendar-event',
					testId: 'calendar-event',
					title: Loc.getMessage('TASKSMOBILE_FLOW_AI_ADVICE_MENU_ITEM_CREATE_CALENDAR_EVENT'),
					iconName: Icon.CALENDAR_WITH_SLOTS,
					sectionCode: 'default',
					onItemSelected: async () => {
						const { Entry } = await requireLazy('calendar:entry');
						if (Entry)
						{
							void Entry.openEventEditForm({
								ownerId: env.userId,
								description: adviceText,
								parentLayout: this.props.parentWidget,
							});
						}
					},
				},
			]).show({ target });
		}

		onUserClick({ userId })
		{
			ProfileView.openInBottomSheet(userId, this.props.parentWidget);
		}

		renderFootnote()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						flexWrap: 'wrap',
						alignItems: 'center',
						paddingVertical: Indent.XL.toNumber(),
					},
					testId: `${this.testId}-footnote-container`,
				},
				Text7({
					color: Color.base5,
					text: Loc.getMessage('TASKSMOBILE_FLOW_AI_ADVICE_FOOTNOTE'),
					testId: `${this.testId}-footnote-field`,
				}),
				Link6({
					style: {
						marginLeft: Indent.L.toNumber(),
					},
					design: LinkDesign.WHITE,
					mode: LinkMode.DASH,
					color: Color.base5,
					text: Loc.getMessage('TASKSMOBILE_FLOW_AI_ADVICE_FOOTNOTE_LINK'),
					testId: `${this.testId}-footnote-link`,
					onClick: () => helpdesk.openHelpArticle('20418172', 'helpdesk'),
				}),
			);
		}
	}

	module.exports = { FlowAiAdvice };
});
