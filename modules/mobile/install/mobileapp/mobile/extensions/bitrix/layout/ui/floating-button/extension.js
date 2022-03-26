(() => {
	class FloatingButtonComponent extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			if (!props.onClickHandler)
			{
				throw new Error('Need set click callback');
			}
			this.onClickHandler = props.onClickHandler;
			this.position = props.position || {};
		}

		render()
		{
			return View(
				{
					style: { ...styles.view(), ...this.position }
				},
				Shadow(
					{
						radius: 2,
						color: '#CCCCCC',
						offset: {
							x: 0,
							y: 0
						},
						style: styles.shadow,
					},
					View(
						{
							style: styles.shadowView,
							onClick: () => this.onClickHandler()
						},
						Image({
							style: styles.button,
							svg: {
								content: `<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7.875 0H6.125V6.125H0V7.875H6.125V14H7.875V7.875H14V6.125H7.875V0Z" fill="white"/></svg>`
							}
						}),
					)
				)
			);
		}
	}

	const styles = {
		view: () => {
			return {
				position: 'absolute',
				right: Application.getPlatform() === 'android' ? 14 : 13,
				bottom: Application.getPlatform() === 'android' ? 14 : 13,
			}
		},
		shadow: {
			borderRadius: 31,
		},
		shadowView: {
			height: Application.getPlatform() === 'android' ? 56 : 60,
			width: Application.getPlatform() === 'android' ? 56 : 60,
			borderRadius: 30,
			backgroundColor: '#61C5F2',
			justifyContent: 'center',
			alignItems: 'center'
		},
		button: {
			width: 14,
			height: 14
		}
	};

	this.UI = this.UI || {};
	this.UI.FloatingButtonComponent = FloatingButtonComponent;
})();
