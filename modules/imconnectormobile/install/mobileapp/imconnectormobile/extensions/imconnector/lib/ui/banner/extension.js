/**
 * @module imconnector/lib/ui/banner
 */
jn.define('imconnector/lib/ui/banner', (require, exports, module) => {

	/**
	 * @param {BannerProps} props
	 * @return {*}
	 * @constructor
	 */
	function Banner(props)
	{
		const backgroundColor = BX.prop.getString(props.style, 'borderRadius', '#e5f9ff');

		return View(
			{
				style: {
					borderRadius: 12,
					backgroundColor,
					padding: 16,
					flexDirection: 'row',
					alignItems: 'center',
					marginBottom: 12,
				},
			},
			View(
				{
					style: {
						position: 'absolute',
						top: 49,
						left: -140,
						width: 260,
						height: 260,
						borderRadius: 130,
						borderWidth: 37,
						borderColor: '#ffffff',
						opacity: 0.5,
					},
				},
			),
			View(
				{
					style: {
						position: 'absolute',
						top: -110,
						right: -100,
						width: 198,
						height: 198,
						borderRadius: 99,
						borderWidth: 26,
						borderColor: '#ffffff',
						opacity: 0.4,
					},
				},
			),
			View(
				{
					style: {
						width: 82,
						height: 82,
						backgroundImage: props.iconUri,
						backgroundResizeMode: 'cover',
					},
				},
				props.isComplete
					? Image(
						{
							style: {
								alignSelf: 'flex-end',
								width: 28,
								height: 28,
							},
							svg: {
								content: completeIcon,
							},
						},
					)
					: null,
			),
			View(
				{
					style: {
						alignSelf: 'flex-start',
						flexDirection: 'column',
						marginLeft: 18,
						flex: 1,
					},
				},
				Text({
					style: {
						color: '#333333',
						fontSize: 16,
						fontWeight: '400',
						numberOfLines: 1,
						ellipsize: 'end',
						lineHeightMultiple: 1,
					},
					text: props.title,
				}),
				BBCodeText({
					style: {
						color: '#525C69',
						fontSize: 14,
						fontWeight: '400',
						numberOfLines: 0,
						ellipsize: 'end',
						lineHeightMultiple: 1.2,
					},
					value: props.description,
				}),
			),
		);
	}

	const completeIcon = `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<circle cx="12" cy="12" r="7" fill="white"/>
<path d="M10.5655 13.5087L11.0958 14.039L11.6261 13.5087L15.3824 9.75239L15.5858 9.9558L11.097 14.4446L11.0283 14.3759L11.0271 14.3772L9.10426 12.4543L9.30767 12.2509L10.5655 13.5087ZM3.26611 12C3.26611 16.8236 7.1764 20.7339 12 20.7339C16.8236 20.7339 20.7339 16.8236 20.7339 12C20.7339 7.1764 16.8236 3.26611 12 3.26611C7.1764 3.26611 3.26611 7.1764 3.26611 12Z" fill="#95C500" stroke="white" stroke-width="1.5"/>
</svg>
`;

	module.exports = { Banner };
});
