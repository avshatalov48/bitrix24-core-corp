/**
 * @module tasks/flow-list/simple-list/items/flow-redux/src/flow-content
 */
jn.define('tasks/flow-list/simple-list/items/flow-redux/src/flow-content', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { PureComponent } = require('layout/pure-component');
	const { CounterView } = require('layout/ui/counter-view');
	const { openTaskCreateForm } = require('tasks/layout/task/create/opener');
	const { Color, Component, Indent } = require('tokens');
	const { Card, CardDesign } = require('ui-system/layout/card');
	const { ChipStatus, ChipStatusDesign, ChipStatusMode } = require('ui-system/blocks/chips/chip-status');
	const { H4 } = require('ui-system/typography/heading');
	const { Text6 } = require('ui-system/typography/text');
	const { Link4, LinkMode, Ellipsize } = require('ui-system/blocks/link');
	const { Button, ButtonSize, ButtonDesign } = require('ui-system/form/buttons');
	const { Entry } = require('tasks/entry');
	const { FeatureId } = require('tasks/enum');
	const { getFeatureRestriction } = require('tariff-plan-restriction');
	const { AvatarStack } = require('ui-system/blocks/avatar-stack');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { FlowAiAdvice } = require('tasks/flow-list/simple-list/items/flow-redux/src/flow-ai-advice');
	const { showToast } = require('toast');

	class FlowContent extends PureComponent
	{
		shouldComponentUpdate(nextProps, nextState)
		{
			if (this.props.id !== nextProps.id)
			{
				return true;
			}

			return super.shouldComponentUpdate(nextProps, nextState);
		}

		get flow()
		{
			return this.props.flow;
		}

		get pending()
		{
			return this.flow?.pending ?? [];
		}

		get atWork()
		{
			return this.flow?.atWork ?? [];
		}

		get completed()
		{
			return this.flow?.completed ?? [];
		}

		get isLast()
		{
			return this.flow?.isLast ?? false;
		}

		get id()
		{
			return this.flow?.id ?? 0;
		}

		get flowName()
		{
			return this.flow?.name ?? this.props.id ?? '';
		}

		get description()
		{
			return this.flow?.description ?? '';
		}

		get plannedCompletionTime()
		{
			return this.flow?.plannedCompletionTime;
		}

		get plannedCompletionTimeText()
		{
			return this.flow?.plannedCompletionTimeText ?? '';
		}

		get averagePendingTime()
		{
			return this.flow?.averagePendingTime ?? '';
		}

		get averageAtWorkTime()
		{
			return this.flow?.averageAtWorkTime ?? '';
		}

		get averageCompletedTime()
		{
			return this.flow?.averageCompletedTime ?? '';
		}

		get efficiencySuccess()
		{
			return this.flow?.efficiencySuccess ?? false;
		}

		get efficiency()
		{
			return this.flow?.efficiency ?? 0;
		}

		get tasksTotal()
		{
			return this.flow?.tasksTotal ?? 0;
		}

		get myTasksCounter()
		{
			return this.flow?.myTasksCounter ?? {};
		}

		get myTasksTotal()
		{
			return this.flow?.myTasksTotal ?? 0;
		}

		get active()
		{
			return this.flow?.active ?? false;
		}

		get enableFlowUrl()
		{
			return this.flow?.enableFlowUrl ?? '';
		}

		/**
		 * @return {FlowAiAdviceDTO}
		 */
		get aiAdvice()
		{
			return this.flow?.aiAdvice;
		}

		get shouldShowAiAdviceFooter()
		{
			return Boolean(this.aiAdvice);
		}

		get testId()
		{
			return `flow-content-${this.props.id}`;
		}

		getCardDesign()
		{
			return CardDesign.PRIMARY;
		}

		getUserAvatarOpacity()
		{
			return 1;
		}

		render()
		{
			if (Type.isNil(this.flow))
			{
				return null;
			}

			return View(
				{
					testId: this.testId,
				},
				this.renderCard(),
				this.shouldShowAiAdviceFooter && this.renderAiAdviceFooter(),
			);
		}

		renderAiAdviceFooter()
		{
			const isEnoughTasksForAdvice = this.tasksTotal >= this.aiAdvice.minTasksCountForAdvice;
			const isEfficiencyLow = this.efficiency <= this.aiAdvice.efficiencyThreshold;
			const isLowEfficiencyFooter = isEnoughTasksForAdvice && isEfficiencyLow;

			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'space-between',
						alignItems: 'center',
						marginHorizontal: Component.paddingLr.toNumber(),
						paddingHorizontal: Indent.XL2.toNumber(),
						marginTop: -Indent.L.toNumber(),
						marginBottom: this.isLast ? Indent.XL2.toNumber() : 0,
						paddingTop: Indent.XL3.toNumber(),
						paddingBottom: Indent.XL.toNumber(),
						backgroundColor: (
							isLowEfficiencyFooter
								? Color.copilotBgContent1.toHex()
								: Color.bgContentTertiary.toHex()
						),
						borderBottomLeftRadius: 12,
						borderBottomRightRadius: 12,
						zIndex: 0,
					},
					testId: `${this.testId}-ai-advice-footer`,
					onClick: () => {
						if (this.aiAdvice.advices.length > 0)
						{
							FlowAiAdvice.show(this.flow, this.props.layout);
						}
						else
						{
							showToast(
								{
									message: Loc.getMessage('TASKSMOBILE_FLOW_CONTENT_AI_ADVICE_FOOTER_TOAST'),
								},
								this.props.layout,
							);
						}
					},
				},
				BBCodeText({
					style: {
						flex: 1,
						color: (isLowEfficiencyFooter ? Color.base2.toHex() : Color.base4.toHex()),
						fontWeight: '400',
						fontSize: 13,
					},
					testId: `${this.testId}-ai-advice-footer-field`,
					numberOfLines: 2,
					ellipsize: 'end',
					value: this.getAiAdviceFooterText(),
				}),
				IconView({
					style: {
						marginLeft: Indent.L.toNumber(),
					},
					icon: Icon.CHEVRON_TO_THE_RIGHT,
					color: (isLowEfficiencyFooter ? Color.copilotAccentLess2 : Color.base4),
					size: 20,
				}),
			);
		}

		getAiAdviceFooterText()
		{
			if (this.tasksTotal < this.aiAdvice.minTasksCountForAdvice)
			{
				return Loc.getMessage('TASKSMOBILE_FLOW_CONTENT_AI_ADVICE_FOOTER_NO_DATA');
			}

			if (this.efficiency <= this.aiAdvice.efficiencyThreshold)
			{
				return Loc.getMessage(
					'TASKSMOBILE_FLOW_CONTENT_AI_ADVICE_FOOTER_LOW',
					{
						'#ANCHOR_START#': `[b][color=${Color.copilotAccentLess2}]`,
						'#ANCHOR_END#': '[/color][/b]',
					},
				);
			}

			return Loc.getMessage('TASKSMOBILE_FLOW_CONTENT_AI_ADVICE_FOOTER_HIGH');
		}

		renderCard()
		{
			return Card(
				{
					testId: `${this.testId}-card`,
					border: true,
					style: {
						marginHorizontal: Component.paddingLr.toNumber(),
						marginBottom: !this.shouldShowAiAdviceFooter && this.isLast ? Indent.XL2.toNumber() : 0,
						marginTop: Indent.XL2.toNumber(),
						zIndex: 1,
					},
					onClick: this.cardClickHandler,
					design: this.getCardDesign(),
				},
				this.renderHeader(),
				this.renderProgressInfo(),
				this.renderFooter(),
			);
		}

		cardClickHandler = () => {
			void requireLazy('tasks:layout/flow/detail').then(({ FlowDetail }) => {
				FlowDetail.open({
					flowId: this.id,
				});
			});
		};

		renderHeader()
		{
			const plannedCompletionText = this.preparePlannedCompletionTimeText();

			return View(
				{},
				H4({
					testId: `${this.testId}-name`,
					text: this.flowName,
					numberOfLines: 2,
					ellipsize: 'end',
				}),
				plannedCompletionText && Text6({
					testId: `${this.testId}-planned-completion-time`,
					text: plannedCompletionText,
					color: Color.base4,
					numberOfLines: 1,
					ellipsize: 'end',
					style: {
						marginTop: Indent.XS.toNumber(),
					},
				}),
			);
		}

		preparePlannedCompletionTimeText()
		{
			return Loc.getMessage('TASKSMOBILE_FLOW_CONTENT_PLANNED_COMLETION_TIME_TEXT', {
				'#TIME#': this.plannedCompletionTimeText,
			});
		}

		renderProgressInfo()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						marginTop: Indent.XL3.toNumber(),
						paddingVertical: Indent.S.toNumber(),
						alignItems: 'center',
						justifyContent: 'space-between',
					},
				},
				this.renderStrikethrough(),
				this.renderPendingStage(),
				this.renderAtWorkStage(),
				this.renderEfficiency(),
				this.renderCompletedStage(),
			);
		}

		renderPendingStage()
		{
			return this.renderProgressStat({
				title: Loc.getMessage('TASKSMOBILE_FLOW_CONTENT_PROGRESS_STATUS_WAITING'),
				value: this.getStageDurationText(this.pending.length, this.averagePendingTime),
				titleAlign: 'center',
				stage: 'pending',
			});
		}

		renderCompletedStage()
		{
			return this.renderProgressStat({
				title: Loc.getMessage('TASKSMOBILE_FLOW_CONTENT_PROGRESS_STATUS_COMPLETED'),
				value: this.getStageDurationText(this.completed.length, this.averageCompletedTime),
				titleAlign: 'center',
				paddingRight: 0,
				stage: 'completed',
			});
		}

		renderAtWorkStage()
		{
			return this.renderProgressStat({
				title: Loc.getMessage('TASKSMOBILE_FLOW_CONTENT_PROGRESS_STATUS_IN_PROGRESS'),
				value: this.getStageDurationText(this.atWork.length, this.averageAtWorkTime),
				stage: 'atwork',
			});
		}

		getAtWorkCountCircleBackgroundColor()
		{
			return Color.accentSoftBlue3;
		}

		getAtWorkCountCircleTextColor()
		{
			return Color.accentMainPrimaryalt;
		}

		renderUsersAtWorkCountCircle()
		{
			return this.renderUsersCountCircle({
				count: this.atWork.length,
				testId: `${this.testId}-at-work-tasks-count`,
				backgroundColor: this.getAtWorkCountCircleBackgroundColor(),
				color: this.getAtWorkCountCircleTextColor(),
			});
		}

		getCompletedCountCircleBackgroundColor()
		{
			return Color.accentSoftGreen3;
		}

		getCompletedCountCircleTextColor()
		{
			return Color.accentMainSuccess;
		}

		renderUsersCompletedCountCircle()
		{
			return this.renderUsersCountCircle({
				count: this.completed.length,
				testId: `${this.testId}-completed-tasks-count`,
				backgroundColor: this.getCompletedCountCircleBackgroundColor(),
				color: this.getCompletedCountCircleTextColor(),
			});
		}

		renderUsersCountCircle({
			count,
			testId,
			color,
			backgroundColor,
			marginLeft = 0,
			showPlusInCounter = false,
		})
		{
			const countText = count > 99
				? (
					showPlusInCounter
						? '+99'
						: '99+'
				)
				: (
					showPlusInCounter
						? `+${String(count)}`
						: String(count)
				);

			return View(
				{
					style: {
						backgroundColor: backgroundColor.toHex(),
						width: 32,
						height: 32,
						borderRadius: 16,
						alignItems: 'center',
						justifyContent: 'center',
						marginLeft,
						borderWidth: 2,
						borderColor: Color.base8.toHex(),
					},
				},
				Text6({
					testId,
					text: countText,
					color,
					numberOfLines: 1,
					ellipsize: 'end',
				}),
			);
		}

		getStageDurationText(stageUsersCount, durationText)
		{
			return stageUsersCount > 0 ? durationText : Loc.getMessage('TASKSMOBILE_FLOW_CONTENT_NO_TASKS_IN_STAGE');
		}

		renderStrikethrough()
		{
			return View(
				{
					style: {
						position: 'absolute',
						height: '100%',
						width: '100%',
						justifyContent: 'center',
						alignItems: 'center',
						paddingHorizontal: 35,
					},
				},
				View({
					style: {
						height: 1,
						width: '100%',
						backgroundColor: Color.bgSeparatorSecondary.toHex(),
					},
				}),
			);
		}

		renderProgressStat({
			title,
			value,
			titleAlign = 'center',
			paddingRight = Indent.XS2.toNumber(),
			stage = 'pending',
		})
		{
			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'column',
						alignItems: 'flex-start',
						flexGrow: 1,
						paddingRight,
						paddingTop: Indent.XS2.toNumber(),
					},
				},
				Text6({
					text: title,
					color: this.getStageHeaderColor(),
					numberOfLines: 1,
					ellipsize: 'end',
					style: {
						width: '100%',
						textAlign: titleAlign,
					},
				}),
				View(
					{
						style: {
							width: '100%',
							marginTop: Indent.M.toNumber(),
							alignItems: 'center',
						},
					},
					stage === 'pending' && this.renderPendingStack(),
					stage === 'atwork' && this.renderUsersAtWorkCountCircle(),
					stage === 'completed' && this.renderUsersCompletedCountCircle(),
					ChipStatus({
						testId: `${this.testId}-at-wait-status`,
						text: value,
						design: ChipStatusDesign.NEUTRAL,
						mode: ChipStatusMode.TINTED,
						compact: true,
						style: {
							marginTop: Indent.M.toNumber(),
						},
					}),
				),
			);
		}

		renderAvatarStackRestView(count)
		{
			return this.renderUsersCountCircle({
				count,
				testId: `${this.testId}-pending-avatar-stack-rest-count`,
				color: Color.base4,
				backgroundColor: Color.base7,
				showPlusInCounter: count !== 0,
			});
		}

		renderPendingStack()
		{
			const visibleEntityCount = this.pending.length === 2 ? 2 : 1;

			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'flex-start',
					},
				},
				this.pending.length === 0 && this.renderAvatarStackRestView(0),
				this.pending.length > 0 && AvatarStack({
					testId: `${this.testId}-pending-avatar-stack`,
					withRedux: true,
					size: 28,
					entities: this.pending,
					restView: this.renderAvatarStackRestView.bind(this),
					visibleEntityCount,
				}),
			);
		}

		getStageHeaderColor()
		{
			return Color.base4;
		}

		getEfficiencyChipStatusDesign()
		{
			return this.efficiencySuccess ? ChipStatusDesign.SUCCESS : ChipStatusDesign.ALERT;
		}

		getEfficiencySvgUri()
		{
			const pathToImages = `${currentDomain}/bitrix/mobileapp/tasksmobile/extensions/tasks/flow-list/simple-list/items/flow-redux/images/${AppTheme.id}`;

			return this.efficiencySuccess ? `${pathToImages}/success.png` : `${pathToImages}/alert.png`;
		}

		renderEfficiency()
		{
			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'column',
						alignItems: 'flex-start',
						flexGrow: 1,
						paddingRight: Indent.XS2.toNumber(),
						paddingTop: Indent.XS2.toNumber(),
					},
				},
				Text6({
					text: Loc.getMessage('TASKSMOBILE_FLOW_CONTENT_PROGRESS_STATUS_EFFICIENCY'),
					color: this.getStageHeaderColor(),
					numberOfLines: 1,
					ellipsize: 'end',
					style: {
						width: '100%',
						textAlign: 'center',
					},
				}),
				View(
					{
						style: {
							width: '100%',
							marginTop: Indent.M.toNumber(),
							alignItems: 'center',
						},
					},
					Image({
						style: {
							alignSelf: 'center',
							width: 56,
							height: 30,
						},
						uri: this.getEfficiencySvgUri(),
					}),
					ChipStatus({
						testId: `${this.testId}-efficiency-status`,
						text: `${this.efficiency}%`,
						design: this.getEfficiencyChipStatusDesign(),
						mode: ChipStatusMode.TINTED,
						compact: true,
						style: {
							marginTop: Indent.M.toNumber(),
						},
					}),
				),
			);
		}

		getCreateTaskButtonDisabledProperty()
		{
			return false;
		}

		renderFooter()
		{
			const { success = 0, danger = 0 } = this.myTasksCounter;
			const totalCounter = success + danger;
			const totalCounterText = totalCounter > 99 ? '99+' : String(totalCounter);

			return View(
				{
					style: {
						width: '100%',
						flexDirection: 'row',
						marginTop: Indent.XL3.toNumber(),
						alignItems: 'center',
						justifyContent: 'space-between',
					},
				},
				View(
					{
						style: {
							flex: 1,
							flexDirection: 'row',
							justifyContent: 'flex-start',
							alignItems: 'center',
						},
					},
					Button({
						testId: `${this.testId}-create-task`,
						text: Loc.getMessage('TASKSMOBILE_FLOW_CONTENT_CREATE_TASK_BUTTON_TEXT'),
						size: ButtonSize.M,
						design: ButtonDesign.FILLED,
						disabled: this.getCreateTaskButtonDisabledProperty(),
						onClick: this.createTaskButtonClickHandler,
						onDisabledClick: this.createTaskDisabledButtonClickHandler,
					}),
					this.renderShowTasksListButton(),
				),
				totalCounter > 0 && CounterView(
					totalCounterText,
					{
						isDouble: success > 0 && danger > 0,
						firstColor: danger > 0 ? Color.accentMainAlert.toHex() : Color.accentMainSuccess.toHex(),
						secondColor: Color.accentMainSuccess.toHex(),
					},
				),
			);
		}

		renderShowTasksListButton()
		{
			const countText = this.myTasksTotal > 99
				? '99+'
				: String(this.myTasksTotal);

			return View(
				{
					style: {
						flexDirection: 'column',
						flex: 1,
						paddingHorizontal: Indent.XL3.toNumber(),
						paddingVertical: Indent.M.toNumber(),
					},
				},
				Link4({
					testId: `${this.testId}-show-task-list-label`,
					text: Loc.getMessage('TASKSMOBILE_FLOW_CONTENT_MY_TASKS_BUTTON_TEXT', {
						'#TASKS_COUNT#': countText,
					}),
					ellipsize: Ellipsize.MIDDLE,
					mode: LinkMode.DASH,
					color: Color.base4,
					accent: true,
					numberOfLines: 1,
					textDecorationLine: 'underline',
					onClick: this.openFlowTasksListButtonClickHandler,
				}),
			);
		}

		openFlowTasksListButtonClickHandler = () => {
			this.openFlowTasksList();
		};

		createTaskButtonClickHandler = () => {
			const { isRestricted, showRestriction } = getFeatureRestriction(FeatureId.FLOW);
			if (isRestricted())
			{
				showRestriction({ parentWidget: this.props.layout });

				return;
			}

			openTaskCreateForm({
				initialTaskData: {
					flowId: this.id,
				},
				layoutWidget: this.props.layout,
				analyticsLabel: {
					c_section: 'flows',
					c_sub_section: 'flows_grid',
					c_element: 'flows_grid_button',
				},
			});
		};

		createTaskDisabledButtonClickHandler = () => {};

		openFlowTasksList()
		{
			Entry.openTaskList({
				flowId: this.id,
				flowName: this.flowName,
				flowEfficiency: this.efficiency,
				canCreateTask: this.active,
				analyticsLabel: this.props.analyticsLabel,
			});
		}
	}

	module.exports = {
		FlowContent,
	};
});
