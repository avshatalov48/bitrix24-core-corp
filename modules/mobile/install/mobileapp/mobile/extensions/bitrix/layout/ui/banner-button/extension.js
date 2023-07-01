(() => {

	/**
	 * @function BannerButton
	 * @param {Object} props
	 * @param {String} props.title
	 * @param {String} props.description
	 * @param {Function} props.onClick
	 * @param {Object} props.options
	 * @returns {View}
	 */
	function BannerButton({title, description, onClick, ...options})
	{
		options = options || {};
		options.backgroundColor = BX.prop.getString(options, 'backgroundColor', '#c3f0ff');
		options.showArrow = BX.prop.getBoolean(options, 'showArrow', true);

		const emptyCallback = () => {};

		return View(
			{
				style: {
					...Styles.container,
					backgroundColor: options.backgroundColor,
				},
				onClick: onClick || emptyCallback,
			},
			View(
				{
					style: Styles.content,
				},
				Text({
					text: String(title),
					style: {
						...Styles.title,
						marginBottom: description ? 4 : 0,
					},
				}),
				description && Text({
					text: String(description),
					style: Styles.description,
				})
			),
			options.showArrow && View(
				{
					style: Styles.arrow,
				},
				Image({
					style: Styles.icon,
					svg: SvgImages.arrowRight,
				})
			)
		);
	}

	const Styles = {
		container: {
			padding: 16,
			borderRadius: 12,
			flexDirection: 'row',
			justifyContent: 'space-between',
		},
		content: {
			paddingRight: 16,
		},
		arrow: {
			flexDirection: 'column',
			justifyContent: 'center',
		},
		icon: {
			width: 10,
			height: 16,
		},
		title: {
			fontSize: 18,
			fontWeight: 'bold',
		},
		description: {
			fontSize: 14,
			color: '#525C69',
		},
	};

	const SvgImages = {
		arrowRight: {
			content: `<svg width="10" height="16" viewBox="0 0 10 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0.160156 2.34343L4.68721 6.87048L5.85979 7.99985L4.68721 9.12989L0.160156 13.6569L1.75762 15.2544L9.01178 8.00025L1.75762 0.746094L0.160156 2.34343Z" fill="#6a737f"/></svg>`
		}
	};

	this.UI = this.UI || {};
	this.UI.BannerButton = BannerButton;

})();
