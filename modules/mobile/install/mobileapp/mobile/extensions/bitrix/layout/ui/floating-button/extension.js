(() => {

	const { Haptics } = jn.require('haptics');

	class FloatingButtonComponent extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			if (!props.onClick)
			{
				throw new Error('Need to set onClick callback.');
			}

			this.position = props.position || {};
		}

		get vibrationEnabled()
		{
			return BX.prop.getBoolean(this.props, 'vibrationEnabled', true);
		}

		render()
		{
			return View(
				{
					safeArea: {
						bottom: true,
						top: true,
						left: true,
						right: true,
					},
					testId: this.props.testId,
					style: {
						...styles.view(),
						...this.position,
					},
				},
				Shadow(
					{
						radius: 2,
						color: '#330984ab',
						offset: {
							x: 0,
							y: 2,
						},
						style: styles.shadow,
					},
					View(
						{
							style: styles.shadowView,
							onClick: () => this.onClick(),
							onLongClick: () => this.onLongClick(),
						},
						Image({
							style: styles.button,
							svg: {
								content: `<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7.875 0H6.125V6.125H0V7.875H6.125V14H7.875V7.875H14V6.125H7.875V0Z" fill="white"/></svg>`,
							},
						}),
					),
				),
			);
		}

		onClick()
		{
			if (this.props.onClick)
			{
				this.props.onClick();
			}
		}

		onLongClick()
		{
			if (this.props.onLongClick)
			{
				this.vibrate();
				this.props.onLongClick();
			}
		}

		vibrate()
		{
			if (this.vibrationEnabled)
			{
				Haptics.impactLight();
			}
		}
	}

	const styles = {
		view: () => {
			return {
				position: 'absolute',
				right: Application.getPlatform() === 'android' ? 14 : 13,
				bottom: Application.getPlatform() === 'android' ? 12 : 11,
			};
		},
		shadow: {
			borderRadius: 31,
		},
		shadowView: {
			height: Application.getPlatform() === 'android' ? 56 : 60,
			width: Application.getPlatform() === 'android' ? 56 : 60,
			borderRadius: 30,
			backgroundColor: { default: '#2fc6f6', pressed: '#cc2fc6f6' },
			justifyContent: 'center',
			alignItems: 'center',
		},
		button: {
			width: 14,
			height: 14,
		},
	};

	this.UI = this.UI || {};
	this.UI.FloatingButtonComponent = FloatingButtonComponent;
})();
