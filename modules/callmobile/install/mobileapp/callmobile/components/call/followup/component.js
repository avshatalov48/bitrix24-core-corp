(() => {
	const require = (ext) => jn.require(ext);
	const {
		Button,
		ButtonSize,
		ButtonDesign,
		Icon,
		Ellipsize,
	} = require('ui-system/form/buttons/button');
	const { Avatar, AvatarShape, AvatarEntityType } = require('ui-system/blocks/avatar');
	const { AvatarStack } = require('ui-system/blocks/avatar-stack');
	const { Entry } = require('tasks/entry');
	const { EfficiencyRow } = require('call:followup/efficiency-row');
	const { EfficiencyScore } = require('call:followup/efficiency-score');
	const { Audioplayer } = require('call:followup/audioplayer');
	const { ParticipantsList } = require('call:followup/participants-list');
	const { getScoreColor, getTimeInSeconds, formatTimeRange, getFullDuration, formatDateForFollowup } = require('call:followup/util');
	const { Color, Indent } = require('tokens');
	const { Alert } = require('alert');
	const { Loc } = require('loc');
	const { BottomSheet } = require('bottom-sheet');
		const DialogOpener = () => {
				try
				{
						const { DialogOpener } = require('im/messenger/api/dialog-opener');

						return DialogOpener;
				}
				catch (e)
				{
						console.log(e, 'DialogOpener not found');

						return null;
				}
		};

	const icons = {
		crossIcon: '<svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0.614216 8.60998C0.399428 8.82477 0.399428 9.17301 0.614216 9.3878C0.829004 9.60259 1.17725 9.60259 1.39203 9.3878L5.00297 5.77686L8.61432 9.38822C8.82911 9.603 9.17735 9.603 9.39214 9.38822C9.60693 9.17343 9.60693 8.82519 9.39214 8.6104L5.78079 4.99904L9.3917 1.38813C9.60649 1.17334 9.60649 0.825098 9.3917 0.61031C9.17691 0.395522 8.82867 0.395522 8.61388 0.61031L5.00297 4.22123L1.39254 0.610793C1.17775 0.396004 0.829508 0.396004 0.614719 0.610793C0.399931 0.825581 0.399931 1.17382 0.614719 1.38861L4.22515 4.99905L0.614216 8.60998Z" fill="#FF5752"/></svg>',
		checkIcon: '<svg width="15" height="12" viewBox="0 0 15 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.2295 1.11113C14.4438 1.32637 14.4431 1.67461 14.2279 1.88895L5.1898 10.8889C4.9752 11.1026 4.62822 11.1026 4.41362 10.8889L0.310346 6.80286C0.0951072 6.58852 0.0943763 6.24028 0.308713 6.02504C0.52305 5.8098 0.87129 5.80907 1.08653 6.02341L4.80172 9.72303L13.4517 1.10949C13.6669 0.895156 14.0152 0.89589 14.2295 1.11113Z" fill="#1BCE7B"/> </svg>',
		calendarIcon: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"> <path fill-rule="evenodd" clip-rule="evenodd" d="M7.79297 3.76823C7.79297 3.40004 7.49449 3.10156 7.1263 3.10156C6.75811 3.10156 6.45964 3.40004 6.45964 3.76823V4.18066H5.57812C4.33548 4.18066 3.32812 5.18802 3.32812 6.43066V13.5841C3.32812 14.8268 4.33548 15.8341 5.57812 15.8341H14.4251C15.6678 15.8341 16.6751 14.8268 16.6751 13.5841V6.43066C16.6751 5.18802 15.6678 4.18066 14.4251 4.18066H13.5266V3.76823C13.5266 3.40004 13.2281 3.10156 12.8599 3.10156C12.4918 3.10156 12.1933 3.40004 12.1933 3.76823V4.18066H7.79297V3.76823ZM12.1933 5.79297V5.18066H7.79297V5.79297C7.79297 6.16116 7.49449 6.45964 7.1263 6.45964C6.75811 6.45964 6.45964 6.16116 6.45964 5.79297V5.18066H5.57812C4.88777 5.18066 4.32813 5.74031 4.32813 6.43066V13.5841C4.32813 14.2745 4.88777 14.8341 5.57812 14.8341H14.4251C15.1155 14.8341 15.6751 14.2745 15.6751 13.5841V6.43066C15.6751 5.74031 15.1155 5.18066 14.4251 5.18066H13.5266V5.79297C13.5266 6.16116 13.2281 6.45964 12.8599 6.45964C12.4918 6.45964 12.1933 6.16116 12.1933 5.79297ZM5.97827 8.4362C5.97827 8.06801 6.27675 7.76953 6.64494 7.76953H7.31836C7.68655 7.76953 7.98503 8.06801 7.98503 8.4362V9.14129C7.98503 9.50948 7.68655 9.80796 7.31836 9.80796H6.64494C6.27675 9.80796 5.97827 9.50948 5.97827 9.14129V8.4362ZM9.04508 8.4362C9.04508 8.06801 9.34356 7.76953 9.71175 7.76953H10.3852C10.7534 7.76953 11.0518 8.06801 11.0518 8.4362V9.14129C11.0518 9.50948 10.7534 9.80796 10.3852 9.80796H9.71175C9.34356 9.80796 9.04508 9.50948 9.04508 9.14129V8.4362ZM12.6423 7.76953C12.2741 7.76953 11.9756 8.06801 11.9756 8.4362V9.14129C11.9756 9.50948 12.2741 9.80796 12.6423 9.80796H13.3157C13.6839 9.80796 13.9823 9.50948 13.9823 9.14129V8.4362C13.9823 8.06801 13.6839 7.76953 13.3157 7.76953H12.6423ZM5.97827 11.623C5.97827 11.2549 6.27675 10.9564 6.64494 10.9564H7.31836C7.68655 10.9564 7.98503 11.2549 7.98503 11.623V12.3281C7.98503 12.6963 7.68655 12.9948 7.31836 12.9948H6.64494C6.27675 12.9948 5.97827 12.6963 5.97827 12.3281V11.623Z" fill="#9760E8"/> </svg>',
		watchIcon: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"> <path fill-rule="evenodd" clip-rule="evenodd" d="M15.6693 9.9987C15.6693 13.1283 13.1322 15.6654 10.0026 15.6654C6.87299 15.6654 4.33594 13.1283 4.33594 9.9987C4.33594 6.86909 6.87299 4.33203 10.0026 4.33203C13.1322 4.33203 15.6693 6.86909 15.6693 9.9987ZM10.0026 16.6654C13.6845 16.6654 16.6693 13.6806 16.6693 9.9987C16.6693 6.3168 13.6845 3.33203 10.0026 3.33203C6.32071 3.33203 3.33594 6.3168 3.33594 9.9987C3.33594 13.6806 6.32071 16.6654 10.0026 16.6654ZM10.4317 6.67079C10.4317 6.39465 10.2078 6.17079 9.9317 6.17079C9.65556 6.17079 9.4317 6.39465 9.4317 6.67079V10.0903C9.4317 10.2229 9.48438 10.3501 9.57815 10.4439C9.67192 10.5376 9.7991 10.5903 9.9317 10.5903L12.8092 10.5903C13.0853 10.5903 13.3092 10.3664 13.3092 10.0903C13.3092 9.81415 13.0853 9.59029 12.8092 9.59029L10.4317 9.59031V6.67079Z" fill="#9760E8"/> </svg>',
		hourglassIcon: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"> <path fill-rule="evenodd" clip-rule="evenodd" d="M5.98438 3.67578C5.70823 3.67578 5.48438 3.89964 5.48438 4.17578C5.48438 4.45192 5.70823 4.67578 5.98438 4.67578H6.12109V6.91317C6.12109 7.16003 6.23875 7.37994 6.3395 7.53306C6.45048 7.70172 6.59854 7.87362 6.75972 8.0394C7.08352 8.37243 7.50731 8.72846 7.92697 9.05072C8.34878 9.37464 8.77947 9.6744 9.12452 9.89497C9.15516 9.91456 9.18531 9.93364 9.21487 9.95215C9.18531 9.97066 9.15516 9.98974 9.12452 10.0093C8.77947 10.2299 8.34878 10.5297 7.92697 10.8536C7.50731 11.1758 7.08352 11.5319 6.75972 11.8649C6.59854 12.0307 6.45048 12.2026 6.3395 12.3712C6.23875 12.5244 6.12109 12.7443 6.12109 12.9911V15.2285H5.98438C5.70823 15.2285 5.48438 15.4524 5.48438 15.7285C5.48438 16.0047 5.70823 16.2285 5.98438 16.2285H14.1663C14.4425 16.2285 14.6663 16.0047 14.6663 15.7285C14.6663 15.4524 14.4425 15.2285 14.1663 15.2285H13.955V12.9911C13.955 12.8534 13.9137 12.7341 13.875 12.6488C13.8344 12.5592 13.7816 12.4741 13.7268 12.3968C13.6174 12.2425 13.4717 12.0799 13.3127 11.9198C12.9926 11.5976 12.5725 11.2397 12.1544 10.9098C11.7345 10.5785 11.3053 10.2664 10.9612 10.0352C10.9184 10.0064 10.8766 9.9787 10.836 9.95215C10.8766 9.9256 10.9184 9.89787 10.9612 9.86914C11.3053 9.63793 11.7345 9.3258 12.1544 8.99449C12.5725 8.66455 12.9926 8.30673 13.3127 7.9845C13.4717 7.8244 13.6174 7.66179 13.7268 7.5075C13.7816 7.43021 13.8344 7.34514 13.875 7.25553C13.9137 7.17017 13.955 7.05085 13.955 6.91317V4.67578H14.1663C14.4425 4.67578 14.6663 4.45192 14.6663 4.17578C14.6663 3.89964 14.4425 3.67578 14.1663 3.67578H5.98438ZM7.12109 6.8871V4.67578H12.955V6.86063C12.9465 6.87612 12.9325 6.89879 12.9111 6.92904C12.8469 7.01953 12.7441 7.13795 12.6032 7.27979C12.3234 7.56144 11.9383 7.89113 11.5349 8.20946C11.1332 8.52643 10.7246 8.82335 10.4035 9.03912C10.2531 9.14013 10.1269 9.22002 10.0323 9.27502C9.93806 9.22318 9.81245 9.14787 9.66313 9.05242C9.34298 8.84776 8.93577 8.56456 8.53603 8.25759C8.13414 7.94898 7.75272 7.6262 7.47671 7.34231C7.33798 7.19962 7.23748 7.07853 7.17487 6.98338C7.13956 6.92971 7.12585 6.89862 7.12109 6.8871ZM12.955 15.2285H7.12109V13.0172C7.12585 13.0057 7.13956 12.9746 7.17487 12.9209C7.23748 12.8258 7.33798 12.7047 7.47671 12.562C7.75272 12.2781 8.13414 11.9553 8.53603 11.6467C8.93577 11.3397 9.34298 11.0565 9.66313 10.8519C9.81245 10.7564 9.93806 10.6811 10.0323 10.6293C10.1269 10.6843 10.2531 10.7642 10.4035 10.8652C10.7246 11.081 11.1332 11.3779 11.5349 11.6948C11.9383 12.0132 12.3234 12.3429 12.6032 12.6245C12.7441 12.7663 12.8469 12.8848 12.9111 12.9753C12.9325 13.0055 12.9465 13.0282 12.955 13.0437V15.2285Z" fill="#9760E8"/> </svg>',
	};

	const styles = {
		container: {
			paddingHorizontal: 14,
			paddingTop: 14,
			paddingBottom: 18,
			backgroundColor: Color.bgContentPrimary.toHex(),
			borderRadius: 12,
			width: '200',
			marginLeft: 18,
			marginRight: 18,
			marginTop: 18,
		},
		separator: {
			width: '100%',
			height: 1,
			backgroundColor: Color.bgSeparatorSecondary.toHex(),
		},
		userRowInner: {
			paddingRight: 18,
			flexDirection: 'row',
			flex: 1,
			alignItems: 'center',
		},
		usersContainer: {
			marginTop: 11,
			paddingLeft: 18,
			width: '100%',
		},
		userName: {
			fontSize: 17,
			fontWeight: 400,
			lineHeight: 23,
			color: Color.base1.toHex(),
		},
		userPosition: {
			fontSize: 13,
			fontWeight: 400,
			lineHeight: 16,
			color: Color.base3.toHex(),
		},
	};

	const AI_OUTCOME_VALUES = {
		agendaClearlyStated: 'CALL_COMPONENT_EFFICIENCY_AGENDA_CLEARLY',
		agendaItemsCovered: 'CALL_COMPONENT_EFFICIENCY_AGENDA_COVERED',
		conclusionsAndActionsOutlined: 'CALL_COMPONENT_EFFICIENCY_AGENDA_CONCLUSIONS',
	};

	class Followup extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.tabs = new Map([
				[BX.message('CALL_COMPONENT_ABOUT'), { ref: null, isActive: true, name: 'about' }],
				[BX.message('CALL_COMPONENT_EFFICIENCY_RECOMMENDATIONS'), { ref: null, isActive: false, name: 'efficiency' }],
				[BX.message('CALL_COMPONENT_AGREEMENTS'), { ref: null, isActive: false, name: 'agreements' }],
				[BX.message('CALL_COMPONENT_INSIGHTS'), { ref: null, isActive: false, name: 'insights' }],
				[BX.message('CALL_COMPONENT_SUMMARY'), { ref: null, isActive: false, name: 'summary' }],
				[BX.message('CALL_COMPONENT_TRANSCRIPTIONS'), { ref: null, isActive: false, name: 'transcribation' }],
			]);
			this.positions = {};
			this.buttonRefs = {};
			this.isPaused = true;

			this.state = {
				result: {},
				users: {},
				selectedTab: null,
				progress: 90,
				trackWidth: null,
				isPlaying: false,
				currentSeconds: 70,
				duration: null,
				isDragEnabled: true,
				currentSpeed: 1,
			};
			this.summaryPlayerRef = null;
			this.transcribationPlayerRef = null;

			this.getFollowupOutcome();
			this.getUsersData();
		}

		renderTitle(title)
		{
			return Text({
				style: { fontSize: 19, color: Color.base0.toHex(), fontWeight: '500', marginBottom: 20 },
				text: title,
			})
		}

		renderSubtitle(text)
		{
			return Text({
				style: { fontSize: 16, color: Color.base0.toHex(), fontWeight: '500' },
				text,
			});
		}

		renderCheckRow(isChecked, text)
		{
			return View(
				{
					style: { display: 'flex', flexDirection: 'row', alignItems: 'center', marginTop: 15 },
				},
				Image(
					{
						style: { marginRight: 12, width: 15, height: 15 },
						svg: { content: isChecked ? icons.checkIcon : icons.crossIcon },
					},
				),
				Text({
					style: { fontSize: 15, color: isChecked ? Color.base2.toHex() : '#909090', fontWeight: '400' },
					text,
				}),
			)
		}

		renderHeader()
		{
			const score = this.state.result.aiOutcome.overview?.efficiencyValue;
			const date = new Date(this.state.result.call.startDate);

			const entities = Object.values(this.state.users).map((user) => (
				{
					id: user.id,
					name: user.name,
					testId: `AvatarStackItem-${user.id}`,
					uri: user.avatar,
				})
			);

			return View(
				{
					style: {
						marginLeft: 18,
						marginRight: 18,
						marginTop: 18,
					},
				},
				BBCodeText({
					onUserClick: ({ userId }) => DialogOpener().open({ dialogId: userId }),
					style: {
						fontSize: 19,
						fontWeight: '600',
						color: Color.base1.toHex(),
					},
					value: this.state.result.aiOutcome.overview?.topic,
				}),
				View(
					{
						style: {
							display: 'flex',
							flexDirection: 'row',
							justifyContent: 'space-between',
							marginTop: 24,
							alignItems: 'flex-start',
						},
					},
					View(
						{},
						score && View(
							{
								style: {
									display: 'flex',
									flexDirection: 'row',
									flexWrap: 'wrap',
								},
							},
							Text({
								style: { fontSize: 15, color: Color.base2.toHex(), fontWeight: '400', marginRight: 5 },
								text: BX.message('CALL_COMPONENT_EFFICIENCY'),
							}),
							Text({
								style: { fontSize: 15, color: getScoreColor(75), fontWeight: '400', marginRight: 5 },
								text: `${score}%`,
							}),
							Text({
								style: { fontSize: 15, color: Color.base1.toHex(), fontWeight: '600', marginRight: 5 },
								text: `${BX.message(`CALL_COMPONENT_EFFICIENCY_${score < 50 ? 0 : score}`)}`,
							}),
						),
						View(
							{
								style: { display: 'flex', flexDirection: 'row', marginTop: 15 },
							},
							Image(
								{
									style: { marginRight: 5, width: 20, height: 20 },
									svg: { content: icons.calendarIcon },
								},
							),
							Text({
								style: { fontSize: 15, color: Color.base2.toHex(), fontWeight: '400' },
								text: formatDateForFollowup(date),
							}),
						),
						View(
							{
								style: { display: 'flex', flexDirection: 'row', marginTop: 15 },
							},
							View(
								{
									style: {
										display: 'flex',
										flexDirection: 'row',
										borderRightColor: Color.base5.toHex(),
										borderRightWidth: 1,
										paddingRight: 5,
									},
								},
								Image(
									{
										style: { marginRight: 5, width: 20, height: 20 },
										svg: { content: icons.watchIcon },
									},
								),
								Text({
									style: { fontSize: 15, color: Color.base2.toHex(), fontWeight: '400' },
									text: formatTimeRange(this.state.result.call.startDate, this.state.result.call.endDate),
								}),
							),
							View(
								{
									style: { display: 'flex', flexDirection: 'row' },
								},
								Image(
									{
										style: { marginRight: 5, width: 20, height: 20 },
										svg: { content: icons.hourglassIcon },
									},
								),
								Text({
									style: { fontSize: 15, color: Color.base2.toHex(), fontWeight: '400' },
									text: getFullDuration(this.state.result.call.startDate, this.state.result.call.endDate),
								}),
							),
						),
						Object.values(this.state.users).length > 0 && View(
							{
								style: {
									position: 'relative',
									width: '100%',
									marginTop: 15,
									height: 40,
								}
							},

							View(
								{},
								AvatarStack({
									entities,
									offset: Indent.S,
									outline: Indent.XS,
									size: 20,
									shape: AvatarShape.NONE,
									style: {
										margin: 0,
										left: 9,
										top: 6,
										marginBottom: 6,
									},
								}),
							),
							View(
								{ style: { position: 'absolute', top: 0, left: 0 } },
								Button({
									testId: 'button-bottomsheet-user-list',
									text: ' '.repeat(entities.length >= 6 ? 25 : entities.length * 4),
									size: ButtonSize.M,
									onClick: () => this.openUserList(),
									rightIcon: Icon.CHEVRON_TO_THE_RIGHT_SIZE_S,
									design: ButtonDesign.OUTLINE,
									ellipsize: Ellipsize.END,
								}),
							)
						)
					),
				),
			);
		}

		openUserList()
		{
			const component = new ParticipantsList({ users: this.state.users });
			const bottomSheet = new BottomSheet({ component })
				.setBackgroundColor(Color.bgContentPrimary.toHex())
				.setTopPosition(100)

			bottomSheet.open();
		}

		renderCopilotScore()
		{
			const efficiency = this.state.result.aiOutcome.overview?.efficiency;
			const efficiencyViews = Object.entries(efficiency ?? {})
				.filter(([key, value]) => key !== 'type')
				.map(([key, value]) => (
					this.renderCheckRow(value, BX.message(AI_OUTCOME_VALUES[key]))
				));

			const score = this.state.result.aiOutcome.overview?.efficiencyValue;
			const meetingType = this.state.result.aiOutcome.overview?.meetingDetails?.type;

			return View(
				{
					style: {
						...styles.container,
					},
					ref: (ref) => {
						this.copilotScoreBlockRef = ref;
						this.tabs.set(BX.message('CALL_COMPONENT_EFFICIENCY_RECOMMENDATIONS'), { ...this.tabs.get(BX.message('CALL_COMPONENT_EFFICIENCY_RECOMMENDATIONS')), ref: this.copilotScoreBlockRef });
					},
					onLayout: (params) => {
						this.positions['efficiency'] = { y: params.y, height: params.height };
					}
				},
				this.renderTitle(BX.message('CALL_COMPONENT_EFFICIENCY_RECOMMENDATIONS')),
				score && View(
					{
						style: { display: 'flex', flexDirection: 'row', justifyContent: 'space-between' },
					},
					new EfficiencyScore({ score: score }),
					View(
						{
							style: {
								width: '70%',
							},
						},
						Text({
							style: { fontSize: 15, color: Color.base2.toHex(), fontWeight: '400' },
							text: `${BX.message('CALL_COMPONENT_MEETING_TYPE')} — ${meetingType}. ${BX.message(`CALL_COMPONENT_EFFICIENCY_RECOMMENDATIONS_${score < 75 ? 0 : score}`)}`,
						}),
					)
				),
				...efficiencyViews,
			);
		}

		async createMeeting()
		{
			const { Entry: CalendarEntry } = await requireLazy('calendar:entry');
			if (!CalendarEntry)
			{
				return;
			}
			const calType = 'group';
			void CalendarEntry.openEventEditForm({
				ownerId: env.userId,
				calType,
			});
		}

		renderSummary(agreements, tasks, meetings)
		{
			const agreementsViews = agreements?.map((item, index) => (
				BBCodeText({
					onUserClick: ({ userId }) => DialogOpener().open({ dialogId: userId }),
					style: { fontSize: 15, color: Color.base2.toHex(), fontWeight: '400', marginTop: 12 },
					value: `${index + 1}. ${item.agreement}`,
				})
			));
			const tasksViews = tasks?.map((item, index) => (
				View(
					{},
					BBCodeText({
						onUserClick: ({ userId }) => DialogOpener().open({ dialogId: userId }),
						style: { fontSize: 15, color: Color.base2.toHex(), fontWeight: '400', marginTop: 12 },
						value: `${index + 1}. ${item.task}`,
					}),
					View(
						{
							style: { marginTop: 14 }
						},
						Button({
							testId: 'Button',
							onClick: () => {
								Entry.openTaskCreation({ title: item.task });
							},
							disabled: false,
							text: BX.message('CALL_COMPONENT_TASK_CREATE'),
							leftIcon: Icon.PLUS,
							size: ButtonSize.M,
							design: ButtonDesign.OUTLINE_ACCENT_2,
							ellipsize: Ellipsize.END,
							onDisabledClick: this.onDisabledClick,
						}),
					),
				)
			));

			const meetingsViews = meetings?.map((item, index) => (
				View(
					{},
					BBCodeText({
						onUserClick: ({ userId }) => DialogOpener().open({ dialogId: userId }),
						style: { fontSize: 15, color: Color.base2.toHex(), fontWeight: '400', marginTop: 12 },
						value: `${index + 1}. ${item.meeting}`,
					}),
					View(
						{
							style: { marginTop: 14 }
						},
						Button({
							testId: 'Button',
							onClick: () => this.createMeeting(),
							disabled: false,
							text: BX.message('CALL_COMPONENT_MEETING_CREATE'),
							leftIcon: Icon.PLUS,
							size: ButtonSize.M,
							design: ButtonDesign.OUTLINE_ACCENT_2,
							ellipsize: Ellipsize.END,
							onDisabledClick: this.onDisabledClick,
						}),
					),
				)
			));

			return View(
				{
					style: {
						...styles.container,
					},
					ref: (ref) =>{
						this.summaryBlockRef = ref;
						this.tabs.set(BX.message('CALL_COMPONENT_AGREEMENTS'), { ...this.tabs.get(BX.message('CALL_COMPONENT_AGREEMENTS')), ref: this.summaryBlockRef });
					},
					onLayout: (params) => {
						this.positions['agreements'] = { y: params.y, height: params.height };
					},
				},
				// TODO: make renderView
				this.renderTitle(BX.message('CALL_COMPONENT_AGREEMENTS')),
				View(
					{
						style: {
							display: 'flex',
							flexDirection: 'row',
							marginTop: 18,
						}
					},
					View(
						{
							style: {
								width: 2,
								height: '100%',
								backgroundColor: '#1F86FF',
								marginRight: 14,
							}
						}
					),
					agreements && View(
						{},
						this.renderSubtitle(BX.message('CALL_COMPONENT_AGREEMENTS_COMMON')),
						...agreementsViews,
					)
				),
				View(
					{
						style: {
							display: 'flex',
							flexDirection: 'row',
							marginTop: 18,
						}
					},
					View(
						{
							style: {
								width: 2,
								height: '100%',
								backgroundColor: '#1BCE7B',
								marginRight: 14,
							}
						}
					),
					tasks && View(
						{},
						this.renderSubtitle(BX.message('CALL_COMPONENT_AGREEMENTS_TASKS')),
						...tasksViews,
					),
				),
				View(
					{
						style: {
							display: 'flex',
							flexDirection: 'row',
							marginTop: 18,
						},
					},
					View(
						{
							style: {
								width: 2,
								height: '100%',
								backgroundColor: '#1BCE7B',
								marginRight: 14,
							}
						}
					),
					meetings && View(
						{},
						this.renderSubtitle(BX.message('CALL_COMPONENT_AGREEMENTS_MEETINGS')),
						...meetingsViews,
					)
				)
			)
		}

		renderAgreement()
		{
			return View(
				{
					style: {
						...styles.container,
					},
					ref: (ref) => {
						this.agreementBlockRef = ref;
						this.tabs.set(BX.message('CALL_COMPONENT_ABOUT'), { ...this.tabs.get(BX.message('CALL_COMPONENT_ABOUT')), ref: this.agreementBlockRef });
					},
					onLayout: (params) => {
						this.positions['about'] = { y: params.y, height: params.height };
					}
				},
				this.renderTitle(BX.message('CALL_COMPONENT_ABOUT')),
				BBCodeText({
					onUserClick: ({ userId }) => DialogOpener().open({ dialogId: userId }),
					style: { fontSize: 15, color: Color.base2.toHex(), fontWeight: '400' },
					value: this.state.result?.aiOutcome?.overview?.agenda?.explanation,
				}),
			);
		}

		renderAnalysis(insights)
		{
			const insightsView = insights.map((item, index) => (
				View(
					{},
					BBCodeText({
						onUserClick: ({ userId }) => DialogOpener().open({ dialogId: userId }),
						style: { fontSize: 15, color: Color.base2.toHex(), fontWeight: '400', marginTop: index !== 0 && 15 },
						value: item.detailedInsight,
					}),
				)
			))

			return View(
				{
					style: {
						...styles.container,
					},
					ref: (ref) => {
						this.analysisBlockRef = ref;
						this.tabs.set(BX.message('CALL_COMPONENT_INSIGHTS'), { ...this.tabs.get(BX.message('CALL_COMPONENT_INSIGHTS')), ref: this.analysisBlockRef });
					},
					onLayout: (params) => {
						this.positions['insights'] = params;
					},
				},
				this.renderTitle(BX.message('CALL_COMPONENT_INSIGHTS')),
				...insightsView,
			);
		}

		renderResume(trackUrl)
		{
			const summary = this.state.result.aiOutcome.summary;

			const summaryViews = (Array.isArray(summary) && summary.length > 0) && summary.map((item) => (
				View(
					{
						style: {
							flexDirection: 'row',
							height: 'auto',
						},
					},
					View(
						{
							style: {
								flexDirection: 'column',
								alignItems: 'center',
								marginRight: 10,
							},
						},
						View(
							{
								style: {
									borderRadius: 50,
									width: 6,
									height: 6,
									backgroundColor: '#1F86FF',
									marginTop: 7,
								},
							},
						),
						View(
							{
								style: {
									marginTop: 5,
									width: 1,
									flex: 1,
									backgroundColor: Color.base7.toHex(),
								},
							},
						),
					),
					View(
						{
							style: {
								flex: 1,
							},
							onClick: () => {
								this.summaryPlayerRef.setTime(getTimeInSeconds(item.start));
							},
						},
						Text({
							style: { fontSize: 15, color: '#007bff' },
							text: `${item.start} - ${item.end}`,
						}),
						Text({
							style: { fontSize: 15, color: Color.base2.toHex(), fontWeight: '500' },
							text: item.title,
						}),
						BBCodeText({
							onUserClick: ({ userId }) => DialogOpener().open({ dialogId: userId }),
							style: { fontSize: 15, color: Color.base2.toHex(), fontWeight: '400' },
							value: item.summary,
						}),
					),
				)
			));

			return View(
				{
					style: {
						...styles.container,
						height: 'auto',
					},
					ref: (ref) => {
						this.resumeBlockRef = ref;
						this.tabs.set(BX.message('CALL_COMPONENT_SUMMARY'), { ...this.tabs.get(BX.message('CALL_COMPONENT_SUMMARY')), ref: this.resumeBlockRef });
					},
					onLayout: (params) => {
						this.positions['summary'] = { y: params.y, height: params.height };
					},
				},
				this.renderTitle(BX.message('CALL_COMPONENT_SUMMARY')),
				trackUrl && new Audioplayer({
					ref: (ref) => this.summaryPlayerRef = ref,
					trackUrl,
				}),
				BBCodeText({
					onUserClick: ({ userId }) => DialogOpener().open({ dialogId: userId }),
					style: { fontSize: 15, padding: 15, paddingBottom: 0, color: Color.base2.toHex(), fontWeight: '500' },
					value: this.state.result.aiOutcome?.overview?.detailedTakeaways,
				}),
				summaryViews && View(
					{
						style: {
							padding: 15,
							paddingBottom: 0,
						},
					},
					...summaryViews,
				),
			);
		}

		renderTranscribation(trackUrl)
		{
			const summary = this.state.result.aiOutcome.transcribe ?? [];
			const summaryViews = summary.map((item) => (
				View(
					{
						style: {
							flexDirection: 'row',
							height: 'auto',
						},
					},
					View(
						{
							style: {
								flex: 1,
							},
							onClick: () => {
								this.transcribationPlayerRef.setTime(getTimeInSeconds(item.start));
							},
						},
						View(
							{
								style: { display: 'flex', flexDirection: 'row', marginTop: 15 },
							},
							Text({
								style: { fontSize: 15, color: '#007bff', marginRight: 5 },
								text: `${item.start} - ${item.end}`,
							}),
							BBCodeText({
								onUserClick: ({ userId }) => DialogOpener().open({ dialogId: userId }),
								style: { fontSize: 15, color: Color.base2.toHex(), fontWeight: '500' },
								value: `[user=${item.userId}]${item.user}[/user]`,
							}),
						),
						Text({
							style: { fontSize: 15, color: Color.base2.toHex(), fontWeight: '400', marginTop: 5 },
							text: `"${item.text}"`,
						}),
					),
				)
			));


			const timeLine = View(
				{
					style: {
						padding: 15,
					},
				},
				...summaryViews
			)

			return View(
				{
					style: {
						...styles.container,
						height: 'auto'
					},
					ref: (ref) => {
						this.transcribationBlockRef = ref;
						this.tabs.set(BX.message('CALL_COMPONENT_TRANSCRIPTIONS'), { ...this.tabs.get(BX.message('CALL_COMPONENT_TRANSCRIPTIONS')), ref: this.transcribationBlockRef });
					},
					onLayout: (params) => {
						this.positions['transcribation'] = { y: params.y, height: params.height };
					}
				},
				this.renderTitle(BX.message('CALL_COMPONENT_TRANSCRIPTIONS')),
					trackUrl && new Audioplayer({
					ref: (ref) => this.transcribationPlayerRef = ref,
					trackUrl,
				}),
				timeLine,
			)
		}

		scrollFeed(ref)
		{
			const position = this.feedRef.getPosition(ref);
			this.feedRef.scrollTo({ x: position.x, y: position.y - 10, animated: true });
		}

		getFollowupOutcome()
		{
			const callId = BX.componentParameters.get('callId');
			BX.ajax.runAction('call.FollowUp.outcome', { data: { callId } })
				.then((response) => {
					this.setState({
						result: { ...response.data.result },
					});
				})
				.catch((error) => {
					Alert.alert(error);
				});
		}

		getUsersData()
		{
			const callId = BX.componentParameters.get('callId');
			BX.ajax.runAction('im.call.getUsers', { data: { callId } })
				.then((response) => {
					this.setState({
						users: { ...response.data },
					});
				})
				.catch((error) => {
					console.error(error);
					Alert.alert(error);
				});
		}

		setActiveTab(activeName)
		{
			if (this.activeTab === activeName)
			{
				return;
			}

			this.activeTab = activeName;

			this.tabs.forEach((value, key) => {
				const shouldBeActive = value.name === activeName;
				if (value.isActive !== shouldBeActive)
				{
					value.isActive = shouldBeActive;
				}
			});

			const btnPos = this.tabsRef.getPosition(this.buttonRefs[this.activeTab]);
			this.tabsRef.scrollTo({ x: btnPos.x - 10, y: btnPos.y, animated: true });
			this.setState({});
		}

		render()
		{
			const agreements = this.state.result.aiOutcome.overview?.agreements;
			const tasks = this.state.result.aiOutcome.overview?.tasks;
			const meetings = this.state.result.aiOutcome.overview?.meetings;
			const insights = this.state.result.aiOutcome.insights?.insights;
			const tracks = this.state.result.tracks?.[0];
			const trackUrl = tracks?.url;
			const hasSummary = agreements || tasks || meetings;

			const tabs = ScrollView(
				{
					style: {
						height: 50,
					},
					ref: (ref) => {
						this.tabsRef = ref;
					},
					horizontal: true,
				},
				View(
					{
						style: {
							height: device.screen.width,
							flexDirection: 'row',
							paddingHorizontal: 18,
						},
					},
					...Array.from(this.tabs, ([title, { ref, isActive, name }]) => {
						if (name === 'insights' && !insights)
						{
							return;
						}
						return View(
							{
								style: { marginRight: 10 },
								ref: (ref) => {
									this.buttonRefs[name] = ref;
								},
							},
							Button({
								testId: `ButtonTab${1}`,
								onClick: () => this.scrollFeed(ref),
								text: title,
								size: ButtonSize.M,
								design: isActive ? ButtonDesign.OUTLINE : ButtonDesign.OUTLINE_NO_ACCENT,
								ellipsize: Ellipsize.END,
							}),
						)
					})
				)
			);

			const feed = ScrollView(
				{
					style: {
						height: '100%',
						backgroundColor: Color.bgContentSecondary.toHex(),
					},
					ref: (ref) => {
						this.feedRef = ref;
					},
					onScroll: (params) => {
						const screenCenterY = params.contentOffset.y + device.screen.height / 2;

						let activeTabName = null;
						let minDistance = Infinity;

						for (const [title, { name }] of this.tabs.entries())
						{
							const pos = this.positions[name];
							if (!pos) continue;

							const blockCenter = pos.y + pos.height / 2;
							const distance = Math.abs(blockCenter - screenCenterY);

							if (distance < minDistance)
							{
								minDistance = distance;
								activeTabName = name;
							}
						}
						if (activeTabName)
						{
							this.setActiveTab(activeTabName);
						}
					}
				},
				View(
					{
						style: {
							minHeight: device.screen.height,
						}
					},
					this.renderHeader(),
					this.renderAgreement(),
					this.renderCopilotScore(),
					hasSummary && this.renderSummary(agreements, tasks, meetings),
					insights && this.renderAnalysis(insights),
					this.renderResume(trackUrl),
					this.renderTranscribation(trackUrl),
				)
			);

			const FollowUp = View(
				{
					style: {
						paddingBottom: 100,
					}
				},
				tabs,
				feed,
			);

			return View(
				{},
				FollowUp,
			);
		}
	}

	layoutWidget.showComponent(new Followup());
})();
