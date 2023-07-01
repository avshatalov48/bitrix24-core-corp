(() => {

	const { get } = jn.require('utils/object');

	const styles = {
		container: {
			position: 'absolute',
			left: 0,
			right: 0,
			bottom: 0,
		},
		shadow: (componentStyle) => ({
			color: get(componentStyle, 'shadowColor', '#e6e7e9'),
			radius: 3,
			offset: {
				y: -3,
			},
			inset: {
				left: 3,
				right: 3,
			},
			style: {
				borderTopLeftRadius: get(componentStyle, 'borderRadius', 12),
				borderTopRightRadius: get(componentStyle, 'borderRadius', 12),
			},
		}),
		innerContent: (componentStyle) => {
			return {
				borderTopRightRadius: get(componentStyle, 'borderRadius', 12),
				borderTopLeftRadius: get(componentStyle, 'borderRadius', 12),
				flexDirection: 'row',
				backgroundColor: get(componentStyle, 'backgroundColor', '#ffffff'),
				alignItems: 'center',
				paddingLeft: get(componentStyle, 'paddingLeft', 8),
				paddingRight: get(componentStyle, 'paddingRight', 8),
				paddingTop: get(componentStyle, 'paddingTop', 0),
				paddingBottom: get(componentStyle, 'paddingBottom', 0),
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
			const { toolbarRef } = this.props;
			const safeArea = BX.prop.getBoolean(this.props, 'safeArea', true);

			return View(
				{
					ref: (ref) => {
						if (toolbarRef)
						{
							toolbarRef(ref);
						}
					},
					style: styles.container,
				},
				Shadow(
					styles.shadow(this.componentStyle),
					View(
						{
							safeArea: (
								safeArea
									? {
										bottom: true,
										top: true,
										left: true,
										right: true,
									}
									: {}
							),
							style: styles.innerContent(this.componentStyle),
						},
						...this.renderInnerContent(),
					),
				),
			);
		}

		renderInnerContent()
		{
			if (this.props.renderContent)
			{
				return [this.props.renderContent()];
			}

			return this.props.items || [];
		}

		get componentStyle()
		{
			return this.props.style || {};
		}
	}

	this.UI = this.UI || {};
	this.UI.BottomToolbar = BottomToolbar;

})();
