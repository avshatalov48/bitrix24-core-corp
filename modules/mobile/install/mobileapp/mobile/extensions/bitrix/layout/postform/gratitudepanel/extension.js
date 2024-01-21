(function() {
	const require = (ext) => jn.require(ext);
	const AppTheme = require('apptheme');

	this.GratitudePanel = ({
		onSetGratitudeEmployee,
		onSetGratitudeMedal,
		onSetGratitudeMedalWidget,
		onLayout,
		employees,
		medal,
		menuCancelTextColor,
		postFormData,
		medalsList,
	}) => {
		const config = {
			medalSize: 62,
			largeAvatarSize: 44,
			largeAvatarPadding: 17,
			smallAvatarSize: 30,
			smallAvatarShift: 10,
			smallAvatarBorder: 4,
			smallListLimit: 6,
		};

		let medalImage = null;
		let medalBackgroundColor = AppTheme.colors.bgContentPrimary;

		if (medalsList[medal] !== undefined)
		{
			medalImage = currentDomain + medalsList[medal].medalUrl;
			medalBackgroundColor = medalsList[medal].backgroundColor;
		}

		const largeLimit = ((employees.length === 3) ? 3 : 2);
		const employeesLarge = employees.slice(0, largeLimit);
		const employeesSmall = employees.slice(largeLimit);
		const smallListCount = employeesSmall.length;

		return View(
			{
				style: {},
				onLayout: ({ height }) => {
					onLayout({ height });
				},
			},
			Separator({
				clickCallback: () => {
					onGratitudeSeparatorClick({
						onSetGratitudeMedal,
						menuCancelTextColor,
						postFormData,
						medal,
						onSetGratitudeMedalWidget,
						medalsList,
					});
				},
			}),
			View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				View(
					{
						testId: `gratitudePanelMedal_${medal}`,
						style: {
							marginLeft: 16,
							width: config.medalSize,
							height: config.medalSize,
							borderRadius: parseInt(config.medalSize / 2, 10),
							backgroundImageSvgUrl: medalImage,
							backgroundResizeMode: 'cover',
							alignItems: 'center',
							justifyContent: 'center',
							marginRight: 5,
						},
						onClick: () => {
							onOpenMedalSelector({
								medal,
								onSetGratitudeMedal,
								onSetGratitudeMedalWidget,
								medalsList,
							});
						},
					},
				),
				View(
					{
						style: {
							flex: 1,
							marginLeft: 5,
							marginRight: 10,
							marginTop: 9,
						},
					},
					View(
						{},
						...employeesLarge.map((employee) => {
							return renderGratitudeEmployeeItemLarge({
								employee,
								config,
								defaultUserAvatar: currentDomain + postFormData.userAvatar,
							});
						}),
					),
					renderGratitudeEmployeeListSmall({
						employeesSmall,
						smallListCount,
						config,
						medalBackgroundColor,
						defaultUserAvatar: currentDomain + postFormData.userAvatar,
					}),
				),
			),
			View(
				{
					style: {
						flexDirection: 'row',
						marginTop: 12,
						marginBottom: 20,
					},
				},
				View(
					{
						testId: 'gratitudePanelLinkSelectEmployee',
						style: {
							flex: 0,
							flexDirection: 'row',
							paddingHorizontal: 16,
							alignItems: 'center',
						},
						onClick: () => {
							(new FormEntitySelector('GRATITUDE', ['users']))
								.setEntitiesOptions({
									user: {
										options: {
											intranetUsersOnly: true,
											emailUsers: false,
										},
										searchable: true,
										dynamicLoad: true,
										dynamicSearch: true,
									},
								})
								.open({ selected: Utils.formatSelectedRecipients({ users: employees }) })
								.then((recipients) => onGratitudeEmployeeSelected({
									onSetGratitudeEmployee,
									recipients,
								}))
								.catch(console.error);
						},
					},
					Text({
						style: {
							marginRight: 5,
						},
						ellipsize: 'middle',
						text: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_GRATITUDEPANEL_SELECT_EMPLOYEE_TITLE'),
					}),
					Image({
						named: 'icon_arrow_down',
						style: {
							width: 16,
							height: 12,
						},
					}),
				),
				View(
					{
						style: {
							flex: 1,
						},
					},
				),
				View(
					{
						testId: 'gratitudePanelLinkSelectMedal',
						style: {
							flex: 0,
							flexDirection: 'row',
							paddingHorizontal: 16,
							alignItems: 'center',
						},
						onClick: () => {
							onOpenMedalSelector({
								medal,
								onSetGratitudeMedal,
								onSetGratitudeMedalWidget,
								medalsList,
							});
						},
					},
					Text({
						style: {
							marginRight: 5,
						},
						ellipsize: 'middle',
						text: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_GRATITUDEPANEL_MENU_MEDAL'),
					}),
					Image({
						named: 'icon_arrow_down',
						style: {
							width: 16,
							height: 12,
						},
					}),
				),
			),
		);
	};

	const renderGratitudeEmployeeItemLarge = ({
		employee,
		config,
		defaultUserAvatar,
	}) => {
		return View(
			{
				testId: `gratitudePanelEmployeeLarge_${employee.id}`,
				style: {
					flexDirection: 'row',
					alignItems: 'center',
					marginBottom: config.largeAvatarPadding,
				},
			},
			Image({
				style: {
					backgroundColor: AppTheme.colors.base5,
					width: config.largeAvatarSize,
					height: config.largeAvatarSize,
					borderRadius: parseInt(config.largeAvatarSize / 2, 10),
					marginRight: 9,
				},
				uri: (employee.imageUrl ? employee.imageUrl : defaultUserAvatar),
				resizeMode: 'cover',
			}),
			View(
				{
					style: {
						flex: 1,
					},
				},
				Text({
					style: {
						fontSize: 16,
						color: AppTheme.colors.base1,
					},
					text: employee.title,
				}),
				Text({
					style: {
						fontSize: 12,
						color: AppTheme.colors.accentMainAlert,
					},
					text: employee.subtitle,
				}),
			),
		);
	};

	const renderGratitudeEmployeeListSmall = ({
		employeesSmall,
		smallListCount,
		config,
		medalBackgroundColor,
		defaultUserAvatar,
	}) => {
		if (smallListCount <= 0)
		{
			return null;
		}

		const iconsListCount = Math.min(parseInt(smallListCount, 10), parseInt(config.smallListLimit, 10));

		return View(
			{
				style: {
					flexDirection: 'row',
				},
			},
			View(
				{
					style: {
						width: (iconsListCount * config.smallAvatarSize - ((iconsListCount - 1) * config.smallAvatarShift)),
						height: config.smallAvatarSize,
					},
				},
				...employeesSmall.reverse().splice(0, config.smallListLimit).map((employee, index) => {
					return renderGratitudeEmployeeItemSmall({
						employee,
						index,
						medalBackgroundColor,
						config,
						defaultUserAvatar,
					});
				}),
			),
			View(
				{
					style: {
						display: smallListCount > config.smallListLimit ? 'flex' : 'none',
						marginLeft: 5,
						marginTop: 4,
						borderRadius: 10,
						backgroundColor: AppTheme.colors.bgContentPrimary,
						height: 21,
						justifyContent: 'center',
						paddingLeft: 5,
						paddingRight: 5,
					},
				},
				Text(
					{
						style: {
							color: AppTheme.colors.base2,
							fontSize: 12,
						},
						text: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_GRATITUDEPANEL_MEDALS_EMPLOYEES_SMALL_MORE').replace(
							'#NUM#',
							(smallListCount - config.smallListLimit),
						),
					},
				),
			),
		);
	};

	const renderGratitudeEmployeeItemSmall = ({
		employee,
		index,
		medalBackgroundColor,
		config,
		defaultUserAvatar,
	}) => {
		return View(
			{
				testId: `gratitudePanelEmployeeSmall_${employee.id}`,
				style: {
					position: 'absolute',
					top: 0,
					right: index * (config.smallAvatarSize - config.smallAvatarShift),
				},
			},
			Image({
				style: {
					backgroundColor: AppTheme.colors.base5,
					width: config.smallAvatarSize,
					height: config.smallAvatarSize,
					borderWidth: config.smallAvatarBorder,
					borderColor: medalBackgroundColor,
					borderRadius: parseInt(config.smallAvatarSize / 2, 10),
				},
				uri: employee.imageUrl || defaultUserAvatar,
				resizeMode: 'cover',
			}),
		);
	};

	const onGratitudeEmployeeSelected = ({
		onSetGratitudeEmployee,
		recipients,
	}) => {
		recipients = Utils.formatSelectedRecipients(recipients);
		onSetGratitudeEmployee(recipients.users);
	};

	const onGratitudeSeparatorClick = ({
		onSetGratitudeMedal,
		menuCancelTextColor,
		postFormData,
		medal,
		onSetGratitudeMedalWidget,
		medalsList,
	}) => {
		const menu = dialogs.createPopupMenu();

		menu.setData(
			[
				{
					id: 'medal',
					title: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_GRATITUDEPANEL_MENU_MEDAL'),
					iconUrl: currentDomain + postFormData.menuMedalIcon,
					sectionCode: '0',
				},
				{
					id: 'close',
					title: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_GRATITUDEPANEL_MENU_DELETE'),
					iconUrl: currentDomain + postFormData.menuDeleteIcon,
					sectionCode: '0',
				},
				{
					id: 'cancel',
					title: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_GRATITUDEPANEL_MENU_CANCEL'),
					textColor: menuCancelTextColor,
					sectionCode: '0',
				},
			],
			[
				{ id: '0' },
			],
			(eventName, item) => {
				if (eventName === 'onItemSelected')
				{
					if (item.id === 'close')
					{
						onSetGratitudeMedal(null);
					}
					else if (item.id === 'medal')
					{
						onOpenMedalSelector({
							medal,
							onSetGratitudeMedal,
							onSetGratitudeMedalWidget,
							medalsList,
						});
					}
				}
			},
		);

		menu.setPosition('center');
		menu.show();
	};

	const onOpenMedalSelector = ({
		medal,
		onSetGratitudeMedal,
		onSetGratitudeMedalWidget,
		medalsList,
	}) => {
		PageManager.openWidget(
			'layout',
			{
				title: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_GRATITUDEPANEL_MEDALS_DIALOG_TITLE'),
				useLargeTitleMode: true,
				modal: false,
				backdrop: {
					mediumPositionHeight: 570,
				},
				onReady: (layoutWidget) => {
					onSetGratitudeMedalWidget(layoutWidget);

					const medalSelector = new MedalSelectorComponent({
						medal,
						medalsList,
						onSelectMedal: (medal) => {
							onSetGratitudeMedal(medal);
						},
					});
					layoutWidget.showComponent(medalSelector);
				},
				onError: console.error,
			},
		);
	};
})();
