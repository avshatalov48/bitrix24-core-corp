(() => {
	/**
	 * @function fileMenu
	 *
	 * @param callback
	 * @param externalStyles
	 * @param svgIcon
	 * @returns {*}
	 */
	const fileMenu = (callback, externalStyles, svgIcon) => {
		let styles = {
			wrapper: {
				justifyContent: 'center',
				alignItems: 'center',
				padding: 4,
				borderRadius: 6
			},
			icon: {
				width: 28,
				height: 28
			}
		}

		if (externalStyles)
		{
			styles = CommonUtils.objectMerge(styles, externalStyles);
		}

		return View(
			{
				style: styles.wrapper,
				onClick: () => callback()
			},
			Image(
				{
					style: styles.icon,
					svg: {
						content: svgIcon ? svgIcon : defaultSvgIcon
					},
					resizeMode: 'center'
				}
			)
		)
	}

	const defaultSvgIcon = `<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.99996 16.3333C8.28862 16.3333 9.33329 15.2887 9.33329 14C9.33329 12.7113 8.28862 11.6667 6.99996 11.6667C5.71129 11.6667 4.66663 12.7113 4.66663 14C4.66663 15.2887 5.71129 16.3333 6.99996 16.3333Z" fill="#A8ADB4"/><path d="M14 16.3333C15.2886 16.3333 16.3333 15.2887 16.3333 14C16.3333 12.7113 15.2886 11.6667 14 11.6667C12.7113 11.6667 11.6666 12.7113 11.6666 14C11.6666 15.2887 12.7113 16.3333 14 16.3333Z" fill="#A8ADB4"/><path d="M23.3333 14C23.3333 15.2887 22.2886 16.3333 21 16.3333C19.7113 16.3333 18.6666 15.2887 18.6666 14C18.6666 12.7113 19.7113 11.6667 21 11.6667C22.2886 11.6667 23.3333 12.7113 23.3333 14Z" fill="#A8ADB4"/></svg>`;

	jnexport(fileMenu);
})();