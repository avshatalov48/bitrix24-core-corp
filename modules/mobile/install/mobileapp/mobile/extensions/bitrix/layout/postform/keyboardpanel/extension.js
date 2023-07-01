(function(){

	this.KeyboardPanel = ({
		attachmentPanel,
		forAll,
		recipientsCount,
		recipientsString,
		attachments,
		postFormData,
		backgroundAvailable,
		onClickDestinationMenuItem,
		onClickMentionMenuItem,
		onClickAttachmentMenuItem,
		onClickBackgroundMenuItem,
		onKeyboardClick,
		onRecipientsLayout,
	}) =>
	{
		const iconWidth = 40;
		const iconHeightNormal = 40;
		const iconHeightSmall = 30;
		const backgroundColor = '#ffffff';
		const dividerColor = '#dbdde0';

		const config = {
			paddingLeftRight: 17,
			marginRightVisibilityIcon: 5,
			widthEllipsisButton: 38,
			widthArrow: 16,
			marginLeftArrow: 5,
			marginRightArrow: 5,
			marginLeftRightAttachmentIcon: 5,
			destinationColor: '#595959',
			destinationAllColor: '#000000',
			destinationPrefixColor: '#ababab',
		};

		const BBCodeVersion = 38;

		if (Application.getApiVersion() >= BBCodeVersion)
		{
			recipientsString = recipientsString
				.replace(/&nbsp;/g, ' ')
				.replace('#PREFIX_BEGIN#', `[COLOR=${config.destinationPrefixColor}]`).replace('#PREFIX_END#', '[/COLOR]')
				.replace('#ALL_BEGIN#', `[B][COLOR=${config.destinationAllColor}]`).replace('#ALL_END#', '[/COLOR][/B]');
		}
		else
		{
			recipientsString = recipientsString
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace('#PREFIX_BEGIN#', `<font color="${config.destinationPrefixColor}">`).replace('#PREFIX_END#', '</font>')
				.replace('#ALL_BEGIN#', `<b><font color="${config.destinationAllColor}">`).replace('#ALL_END#', '</font></b>');
		}

		return View(
			{
				testId: 'keyboardPanel',
				style: {
					position: 'absolute',
					left: 0,
					right: 0,
					bottom: 0,
					justifyContent: 'space-between',
					flexDirection: 'column',
				},
			},
			attachmentPanel,
			View(
				{
					style: {
						paddingLeft: 12,
						paddingRight: 12,
					}
				},
				View(
					{
						style: {
							height: 0.5,
							backgroundColor: dividerColor,
						}
					}
				)
			),
			View(
				{
					safeArea: {
						bottom: true
					},
					style: {
						backgroundColor: backgroundColor,
						justifyContent: 'space-between',
						flexDirection: 'row',
						paddingTop: 10,
						paddingBottom: 10,
						paddingLeft: config.paddingLeftRight,
						paddingRight: config.paddingLeftRight,
					}
				},
				View(
					{
						style: {
							flex: 0,
						},
					},
					ImageButton({
						iconName: 'post_visibility',
						style: {
							flex: 0,
							marginRight: config.marginRightVisibilityIcon,
							width: iconWidth,
							height: iconHeightNormal,
						},
						onClick: onClickDestinationMenuItem
					}),
				),
				View(
					{
						style: {
							flex: 1,
							flexDirection: 'row',
							alignItems: 'center',
						},
						onClick: onClickDestinationMenuItem,
					},
					View(
						{
							style: {
								flex: 0,
								marginRight: config.marginLeftArrow,
							},
							onLayout: ({ height }) => {
								onRecipientsLayout({
									type: 'KeyboardPanel',
									height,
								});
							},
						},
						(
							Application.getApiVersion() >= BBCodeVersion
								? BBCodeText({
									value: `[COLOR=${config.destinationColor}]${recipientsString}[/COLOR]`,
									style: {
										fontSize: 14,
										textAlignVertical: 'center',
										minHeight: iconHeightNormal,
										maxWidth: device.screen.width
											- config.widthArrow - config.marginLeftArrow - config.marginRightArrow // arrow
											- (2 * config.paddingLeftRight) // keyboard left and right padding
											- (iconWidth + config.marginRightVisibilityIcon) // visibility left icon
											- (iconWidth + 2 * config.marginLeftRightAttachmentIcon) // attachments button
											- config.widthEllipsisButton, //
									},
								})
								: Text({
									html: `<font color="${config.destinationColor}">${recipientsString}</font>`,
									style: {
										fontSize: 14,
										textAlignVertical: 'center',
										minHeight: iconHeightNormal,
										maxWidth: device.screen.width
											- config.widthArrow - config.marginLeftArrow - config.marginRightArrow // arrow
											- (2 * config.paddingLeftRight) // keyboard left and right padding
											- (iconWidth + config.marginRightVisibilityIcon) // visibility left icon
											- (iconWidth + 2 * config.marginLeftRightAttachmentIcon) // attachments button
											- config.widthEllipsisButton, //
									},
								})
						)
						,
					),
					View({
							style: {
								flex: 1,
								flexDirection: 'row',
								marginRight: config.marginRightArrow,
							},
							onClick: onClickDestinationMenuItem,
						},
						Image({
							named: 'icon_arrow_down',
							style: {
								width: config.widthArrow,
								height: 12,
								flex: 0,
							}
						})
					)
				),
				View(
					{
						style: {
							flex: 0,
							alignItems: 'center',
						},
					},
					ImageButton({
						iconName: "post_attachment",
						style: {
							width: iconWidth,
							height: (attachments.length ? iconHeightSmall : iconHeightNormal),
							marginLeft: config.marginLeftRightAttachmentIcon,
							marginRight: config.marginLeftRightAttachmentIcon,
						},
						onClick: onClickAttachmentMenuItem
					}),
					Text({
						style: {
							display: attachments.length ? 'flex' : 'none',
							fontSize: 9,
							fontColor: '#000000',
							textAlign: 'center'
						},
						text: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_KEYBOARDPANEL_ITEM_ATTACH_COUNTER')
							.toLocaleUpperCase(env.languageId)
							.replace('#NUM#', attachments.length)
					})
				),
				ImageButton({
					testId: 'openActionSheetButton',
					svg: {
						uri: currentDomain + postFormData.keyboardEllipsisIcon
					},
					style: {
						flex: 0,
						width: config.widthEllipsisButton,
						height: 40,
						backgroundColor: '#00000000'
					},
					onClick: onKeyboardClick
				})
			)
		);
	}

})();