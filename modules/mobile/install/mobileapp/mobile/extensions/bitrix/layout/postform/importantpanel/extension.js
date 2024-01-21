(function() {
	const require = (ext) => jn.require(ext);
	const AppTheme = require('apptheme');

	this.ImportantPanel = ({
		importantUntil,
		onSetImportant,
		onSetImportantUntil,
		onLayout,
		menuCancelTextColor,
	}) => {
		return View(
			{
				style: {
					paddingBottom: 16,
				},
				onLayout: ({ height }) => {
					onLayout({ height });
				},
			},
			Separator({
				clickCallback: () => {
					onImportantSeparatorClick({
						onSetImportant,
						menuCancelTextColor,
					});
				},
			}),
			View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						paddingHorizontal: 14,
					},
				},
				Image({
					named: 'icon_important',
					style: {
						marginRight: 7,
						width: 18,
						height: 18,
					},
				}),
				Text({
					text: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_IMPORTANT_TITLE'),
					style: {
						fontSize: 13,
					},
				}),
			),
			View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						paddingLeft: 38,
					},
					onClick: () => {
						const dates = Utils.getImportantDatePeriods();

						dialogs.showDatePicker(
							{
								title: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_IMPORTANT_PERIOD_DIALOG_TITLE'),
								type: 'date',
								value: (importantUntil || dates.oneWeek),
								items: [
									{
										value: dates.always,
										name: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_IMPORTANT_PERIOD_ALWAYS'),
									},
									{
										value: dates.oneDay,
										name: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_IMPORTANT_PERIOD_ONE_DAY'),
									},
									{
										value: dates.twoDays,
										name: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_IMPORTANT_PERIOD_TWO_DAYS'),
									},
									{
										value: dates.oneWeek,
										name: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_IMPORTANT_PERIOD_ONE_WEEK'),
									},
									{
										value: dates.oneMonth,
										name: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_IMPORTANT_PERIOD_ONE_MONTH'),
									},
								],
							},
							(eventName, newTs) => {
								if (eventName === 'onPick')
								{
									onSetImportantUntil(newTs);
								}
							},
						);
					},
				},
				renderPeriod({ importantUntil }),
				Image({
					named: 'icon_arrow_down',
					style: {
						flex: 0,
						width: 16,
						height: 12,
					},
				}),
			),
		);
	};

	const renderPeriod = ({ importantUntil }) => {
		return Text({
			text: this.getValueTitle({ importantUntil }),
			style: {
				flex: 0,
				fontSize: 16,
				color: AppTheme.colors.base1,
				marginRight: 6,
			},
		});
	};

	const onImportantSeparatorClick = ({
		onSetImportant,
		menuCancelTextColor,
	}) => {
		const
			menu = dialogs.createPopupMenu();

		menu.setData(
			[
				{
					id: 'close',
					title: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_IMPORTANT_MENU_DELETE'),
					iconName: 'delete',
					sectionCode: '0',
				},
				{
					id: 'cancel',
					title: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_IMPORTANT_MENU_CANCEL'),
					textColor: menuCancelTextColor,
					sectionCode: '0',
				},
			],
			[
				{ id: '0' },
			],
			(eventName, item) => {
				if (
					eventName === 'onItemSelected'
					&& item.id === 'close'
				)
				{
					onSetImportant(false);
				}
			},
		);

		menu.setPosition('center');
		menu.show();
	};

	getValueTitle = ({ importantUntil }) => {
		if (!importantUntil)
		{
			return BX.message('MOBILE_EXT_LAYOUT_POSTFORM_IMPORTANT_PERIOD_ALWAYS');
		}

		const delta = importantUntil - Utils.getNowDate();
		const aDay = 60 * 60 * 24 * 1000;

		if (delta < aDay * 1.5)
		{
			return BX.message('MOBILE_EXT_LAYOUT_POSTFORM_IMPORTANT_PERIOD_ONE_DAY');
		}

		if (delta < aDay * 2.5)
		{
			return BX.message('MOBILE_EXT_LAYOUT_POSTFORM_IMPORTANT_PERIOD_TWO_DAYS');
		}

		if (
			delta > aDay * 6.5
				&& delta < aDay * 7.5
		)
		{
			return BX.message('MOBILE_EXT_LAYOUT_POSTFORM_IMPORTANT_PERIOD_ONE_WEEK');
		}

		if (
			delta > aDay * 27.5
				&& delta < aDay * 31.5
		)
		{
			return BX.message('MOBILE_EXT_LAYOUT_POSTFORM_IMPORTANT_PERIOD_ONE_MONTH');
		}

		if (
			delta > aDay * 360 * 9.9
		)
		{
			return BX.message('MOBILE_EXT_LAYOUT_POSTFORM_IMPORTANT_PERIOD_ALWAYS');
		}

		return BX.message('MOBILE_EXT_LAYOUT_POSTFORM_IMPORTANT_PERIOD_TO_DATE').replace('#DATE#', new Date(importantUntil).toLocaleDateString());
	};
})();
