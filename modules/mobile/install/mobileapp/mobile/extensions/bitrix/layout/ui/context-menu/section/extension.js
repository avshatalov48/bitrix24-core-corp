(() =>
{
	const SECTION_DEFAULT = 'default';
	const SECTION_SERVICE = 'service';

	class ContextMenuSection extends LayoutComponent
	{
		static create(props)
		{
			return new this(props);
		}

		constructor(props)
		{
			super(props);

			this.id = props.id;
			this.actions = (props.actions || []);
		}

		render()
		{
			return View(
				{
					style: {
						...styles.sectionView,
						...this.props.style
					}
				},
				...this.renderActions()
			);
		}

		renderActions()
		{
			const hasIcons = Boolean(this.actions.find((action) => action.data.svgIcon));

			return this.actions.map((action, i) => {
				action.onClick = this.props.onClick;
				action.showIcon = hasIcons;
				action.firstInSection = !i;
				action.lastInSection = this.actions.length - 1 === i;
				action.props.enabled = this.props.enabled;
				return action;
			});
		}

		static getDefaultSectionName()
		{
			return SECTION_DEFAULT;
		}

		static getServiceSectionName()
		{
			return SECTION_SERVICE;
		}
	}

	const styles = {
		sectionView: {
			backgroundColor: '#FFFFFF',
			fontSize: 18,
			borderRadius: 15,
		},
	};

	this.ContextMenuSection = ContextMenuSection;
})();
