(() => {
	const DEFAULT_STAGE_BACKGROUND_COLOR = '#c3f0ff';

	/**
	 * @class Crm.Tunnel
	 */
	class Tunnel extends LayoutComponent
	{
		render()
		{
			const { dstStageColor, dstStageName, dstCategoryName } = this.props;

			return View(
				{
					style: styles.tunnelContent,
				},
				Text(
					{
						text: BX.message('CRM_TUNNEL_TITLE'),
						style: styles.tunnelTitle,
					},
				),
				Image({
					style: styles.tunnelArrow,
					resizeMode: 'center',
					svg: {
						content: svgImages.tunnelArrow,
					},
				}),
				Image({
					style: styles.tunnelStageIcon,
					resizeMode: 'center',
					svg: {
						content: svgImages.tunnelStageIcon
							.replace(
								'#COLOR#',
								(dstStageColor || DEFAULT_STAGE_BACKGROUND_COLOR).replace(/[^\d#A-Fa-f]/g, ''),
							),
					},
				}),
				View(
					{
						style: styles.tunnelTextContainer,
					},
					Text(
						{
							text: dstStageName,
							numberOfLines: 1,
							ellipsize: 'end',
							style: styles.tunnelText,
						},
					),
					Text(
						{
							style: styles.tunnelTextSeparator,
							text: '/',
						},
					),
					Text(
						{
							style: styles.tunnelText,
							text: dstCategoryName,
							numberOfLines: 1,
							ellipsize: 'end',
						},
					),
				),
			);
		}
	}

	const styles = {
		tunnelContent: {
			flexDirection: 'row',
			alignItems: 'center',
		},
		tunnelTitle: {
			color: '#bdc1c6',
			fontWeight: '600',
			fontSize: 12,
		},
		tunnelArrow: {
			width: 5,
			height: 8,
			marginHorizontal: 10,
		},
		tunnelStageIcon: {
			width: 13,
			height: 11,
			marginRight: 4,
		},
		tunnelTextContainer: {
			flexDirection: 'row',
			flex: 1,
		},
		tunnelText: {
			color: '#0b66c3',
			flexWrap: 'no-wrap',
			maxWidth: '47%',
		},
		tunnelTextSeparator: {
			color: '#0b66c3',
			flexWrap: 'no-wrap',
		},
	};

	const svgImages = {
		tunnelArrow: `<svg width="5" height="8" viewBox="0 0 5 8" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path opacity="0.5" fill-rule="evenodd" clip-rule="evenodd" d="M0 6.56513L2.10294 4.5123L2.64763 4.00018L2.10294 3.48775L0 1.43493L0.742066 0.710546L4.11182 4L0.742066 7.28945L0 6.56513Z" fill="#A8ADB4"/>
			</svg>`,
		tunnelStageIcon: '<svg width="13" height="11" viewBox="0 0 13 11" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0 2C0 0.895431 0.895431 0 2 0L8.52745 0C9.22536 0 9.87278 0.3638 10.2357 0.959904L13 5.5L10.2357 10.0401C9.87278 10.6362 9.22536 11 8.52745 11H2C0.895432 11 0 10.1046 0 9V2Z" fill="#COLOR#"/></svg>',
	};

	this.Crm = this.Crm || {};
	this.Crm.Tunnel = Tunnel;
})();
