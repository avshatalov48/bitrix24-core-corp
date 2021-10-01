(function(){

	this.ActionSheet = ({
		attachmentPanel,
		forAll,
		recipientsString,
		attachments,
		postFormData,
		titleShown,
		coloredMessage,
		onClickDestinationMenuItem,
		onClickMentionMenuItem,
		onClickAttachmentMenuItem,
		onOpenAttachmentList,
		onClickBackgroundMenuItem,
		onClickImportantMenuItem,
		onClickGratitudeMenuItem,
		onClickShowHideTitleItem,
		onClickVoteMenuItem,
		moduleVoteInstalled,
		useImportant,
		backgroundAvailable,
		onHide,
		animation
	}) => {

		const config = {
			destinationPrefixColor: '#ababab',
			fontColor: '#333333',
			fontSize: 16,
			borderColor: '#DBDDE0',
			borderWidth: 0.5,
			backgroundColor: '#FFFFFF',
			iconSize: 30,
			iconMarginLeft: 15,
			iconMarginRight: 10,
			iconMarginTop: 11,
			iconMarginBottom: 11,
		};

		return PanView(
			{
				testId: 'actionSheet',
				style: {
					position: 'absolute',
					left: 0,
					right: 0,
					bottom: 0,
				},
				onHide,
				animation,
			},
			attachmentPanel,
			ActionSheetHandler(),
			Shadow(
				{
					radius: 3,
					color: '#f2f7f9',
					offset: {
						x: 0,
						y: 0
					},
					inset: {
						left: 5,
						right: 5,
						bottom: 5,
					},
					style: {
						borderRadius: 5,
					}
				},
				View(
					{
						safeArea: {
							bottom: true
						},
						style: {
							borderTopColor: '#00000000',
							borderTopWidth: 0,
							borderRadius: 10,
							backgroundColor: config.backgroundColor,
						},
					},
					ActionSheetItemDestinationSelector({
						forAll,
						title: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_ACTIONSHEET_SELECTOR_TITLE2'),
						value: recipientsString,
						onClick: onClickDestinationMenuItem,
						config,
					}),
					ActionSheetItemAttach({
						iconName: 'post_attachment',
						title: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_ACTIONSHEET_ITEM_ATTACH'),
						attachmentsCount: (attachments ? attachments.length : 0),
						onClick: onClickAttachmentMenuItem,
						onOpenList: onOpenAttachmentList,
						config,
					}),
					ActionSheetItem({
						iconName: 'post_mention',
						title: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_ACTIONSHEET_ITEM_MENTION'),
						onClick: onClickMentionMenuItem,
						config
					}),
					ActionSheetItem({
						title: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_ACTIONSHEET_ITEM_BACKGROUND'),
						onClick: onClickBackgroundMenuItem,
						style: {
							opacity: (backgroundAvailable ? 1.0 : 0.3),
						},
						iconUrl: currentDomain + postFormData.backgroundIcon,
						config,
					}),
					(
						useImportant
							? ActionSheetItem({
								iconName: 'post_important',
								title: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_ACTIONSHEET_ITEM_IMPORTANT'),
								onClick: onClickImportantMenuItem,
								config,
							})
							: null
					),
					ActionSheetItem({
						iconName: 'post_thanks',
						title: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_ACTIONSHEET_ITEM_GRATITUDE'),
						onClick: onClickGratitudeMenuItem,
						config,
					}),
					ActionSheetItem({
						title: (titleShown ? BX.message('MOBILE_EXT_LAYOUT_POSTFORM_ACTIONSHEET_ITEM_TITLE_HIDE') : BX.message('MOBILE_EXT_LAYOUT_POSTFORM_ACTIONSHEET_ITEM_TITLE_SHOW')),
						onClick: () => {
							onClickShowHideTitleItem({
								show: !titleShown,
							});
						},
						style: {
							opacity: (!coloredMessage ? 1.0 : 0.3),
						},
						iconUrl: currentDomain + postFormData.titleIcon,
						config,
					}),
					(
						moduleVoteInstalled
							? ActionSheetItem({
								iconName: 'post_poll',
								title: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_ACTIONSHEET_ITEM_VOTE'),
								textStyle: {
									borderBottomWidth: 0,
								},
								onClick: onClickVoteMenuItem,
								config,
							})
							: null
					)
				)
			),
		);
	};

	this.ActionSheetItem = ({
		iconName,
		iconUrl,
		iconStyle,
		title,
		style = {},
		textStyle = {},
		onClick,
		config,
	}) => {

		let imageStyle = {
			marginLeft: config.iconMarginLeft,
			marginRight: config.iconMarginRight,
			marginTop: config.iconMarginTop,
			marginBottom: config.iconMarginBottom,
			width: config.iconSize,
			height: config.iconSize,
		};

		if (typeof iconStyle !== 'undefined')
		{
			imageStyle = Object.assign(imageStyle, iconStyle);
		}
		const iconProps = iconUrl ? {
			uri: iconUrl,
			resizeMode: 'cover',
			style: imageStyle,
		} : {
			named: iconName,
			style: imageStyle,
		};

		return View(
			{
				style: Object.assign({
					flexDirection: 'row',
					backgroundColor: config.backgroundColor,
				}, style),
			},
			Image(iconProps),
			View(
				{
					style: Object.assign({
						borderBottomWidth: config.borderWidth,
						borderBottomColor: config.borderColor,
						flexDirection: 'row',
						alignItems: 'center',
						flex: 1
					}, textStyle),
					onClick: onClick,
				},
				Text({
					text: title,
					style: {
						color: config.fontColor,
						fontSize: config.fontSize,
						flex: 1,
					},
				})
			)
		);
	};

	this.ActionSheetHandler = () => {
		return View(
			{
				style: {
					backgroundColor: '#00000000',
					alignItems: 'center',
				},
			},
			View(
				{
					style: {
						height: 5,
						width: 52,
						backgroundColor: '#525c6948',
						marginTop: 0,
						marginBottom: 7,
						borderRadius: 2.5,
					},
				}
			)
		);
	};

	this.ActionSheetItemDestinationSelector = ({
		forAll,
		title,
		value,
		onClick,
		config,
	}) => {

		const BBCodeVersion = 38;

		if (Application.getApiVersion() >= BBCodeVersion)
		{
			value = value
				.replace(/&nbsp;/g, ' ')
				.replace('#ALL_BEGIN#', '[B]').replace('#ALL_END#', '[/B]');
		}
		else
		{
			value = value
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace('#ALL_BEGIN#', `<b>`).replace('#ALL_END#', '</b>');
		}

		return (
			View(
				{
					style: {
						flexDirection: 'row',
						backgroundColor: '#e2f7fe',
					},
				},
				View(
					{
					},
					Image({
						named: 'post_visibility',
						style: {
							marginLeft: config.iconMarginLeft,
							marginRight: config.iconMarginRight,
							marginTop: config.iconMarginTop,
							marginBottom: config.iconMarginBottom,
							width: config.iconSize,
							height: config.iconSize,
						},
					})
				),
				View(
					{
						style: {
							borderBottomWidth: config.borderWidth,
							borderBottomColor: config.borderColor,
							alignItems: 'center',
							flex: 1,
							flexDirection: 'row',
						},
						onClick: onClick,
					},
					Text({
						text: title,
						style: {
							flex: 0,
							paddingRight: 5,
							overflow: 'hidden',
							color: config.destinationPrefixColor,
							fontSize: config.fontSize,
						},
					}),
					(
						Application.getApiVersion() >= BBCodeVersion
							? BBCodeText({
								value: value,
								style: {
									marginTop: 5,
									marginBottom: 5,
									maxWidth: '65%',
									color: config.fontColor,
									fontSize: config.fontSize,
								},
							})
							: Text({
								html: value,
								style: {
									marginTop: 5,
									marginBottom: 5,
									maxWidth: '65%',
									color: config.fontColor,
									fontSize: config.fontSize,
								},
							})

					)
					,
					View({
							style: {
								flex: 1,
								flexDirection: 'row'
							}
						},
						Image({
							named: 'icon_arrow_down',
							style: {
								width: 16,
								height: 12,
								flex: 0,
								marginRight: 10,
							}
						})
					)
				)
			)
		);
	};

	ActionSheetItemAttach = ({
		iconName,
		title,
		attachmentsCount = 0,
		style = {},
		textStyle = {},
		onClick,
		onOpenList,
		config,
	}) => {

		return (
			View(
				{
					style: Object.assign({
						flexDirection: 'row',
						backgroundColor: config.backgroundColor,
					}, style),
				},
				Image({
					named: iconName,
					style: {
						marginLeft: config.iconMarginLeft,
						marginRight: config.iconMarginRight,
						marginTop: config.iconMarginTop,
						marginBottom: config.iconMarginBottom,
						width: config.iconSize,
						height: config.iconSize,
					}
				}),
				View(
					{
						style: Object.assign({
							borderBottomWidth: config.borderWidth,
							borderBottomColor: config.borderColor,
							flexDirection: 'row',
							flex: 1,
							alignItems: 'center',
						}, textStyle),
						onClick: onClick,
					},
					Text({
						text: title,
						style: {
							flex: 1,
							color: config.fontColor,
							fontSize: config.fontSize,
						},
					}),
					View(
						{
							style: {
								display: attachmentsCount > 0 ? 'flex' : 'none',
								paddingRight: 10,
								paddingLeft: 10,
								paddingTop: 14,
								paddingBottom: 14,
							},
							onClick: onOpenList,
						},
						Text({
							style: {
								color: '#0B66C3'
							},
							text: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_ACTIONSHEET_ITEM_ATTACH_COUNTER').replace('#NUM#', attachmentsCount),
						})
					)
				)
			)
		);
	}

})();
