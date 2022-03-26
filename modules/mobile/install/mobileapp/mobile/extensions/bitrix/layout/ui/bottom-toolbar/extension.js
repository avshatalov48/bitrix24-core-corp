(() => {
	const styles = {
		innerContainer: (isWithSafeArea) => {
			return {
				position: 'absolute',
				left: 0,
				right: 0,
				bottom: 0,
				height: (isWithSafeArea ? 108 : 98),
			};
		},
		shadow: {
			color: '#e6e6e6',
			radius: 3,
			offset: {
				y: -3,
			},
			inset: {
				left: 3,
				right: 3,
			},
			style: {}
		},
		container: (isWithSafeArea) => {
			return {
				borderTopRightRadius: 12,
				borderTopLeftRadius: 12,
				flexDirection: 'row',
				backgroundColor: '#ffffff',
				height: (isWithSafeArea ? 105 : 95),
				alignItems: 'center',
				paddingLeft: 8,
				paddingRight: 8,
			};
		},
	};

	/**
	 * @class BottomToolbar
	 */
	class BottomToolbar extends LayoutComponent
	{
		render()
		{
			return View(
				{
					style: styles.innerContainer(this.props.isWithSafeArea),
				},
				Shadow(
					styles.shadow,
					View(
						{
							style: styles.container(this.props.isWithSafeArea),
							safeArea: (this.props.isWithSafeArea ? {bottom: true} : {}),
						},
						...this.props.items
					)
				),
			);
		}
	}

	this.UI = this.UI || {};
	this.UI.BottomToolbar = BottomToolbar;
	this.UI.BottomToolbar.styles = styles;
})();
