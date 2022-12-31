(() => {
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
			this.title = props.title;
		}

		render()
		{
			return View(
				{
					style: {
						...styles.sectionView(this.id === SECTION_SERVICE),
						...this.props.style,
					},
				},
				this.renderTitle(),
				...this.renderActions(),
			);
		}

		renderTitle()
		{
			if (!this.title)
			{
				return null;
			}

			return View(
				{
					style: {
						paddingTop: 10,
						paddingBottom: 10,
						paddingLeft: 20,
						paddingRight: 20,
						flexDirection: 'row',
					},
				},
				this.getTitleText(),
			);
		}

		getTitleText()
		{
			if (!this.title)
			{
				return null;
			}

			if (typeof this.title !== 'string')
			{
				return this.title;
			}

			return Text(
				{
					style: styles.title,
					numberOfLines: 1,
					ellipsize: 'end',
					text: this.title,
				},
			);

		}

		renderActions()
		{
			const renderAction = this.props.renderAction;
			if (!renderAction)
			{
				return [];
			}

			const hasIcons = this.actions.some((action) => {
				if (action.data)
				{
					return action.data.svgIcon || action.data.imgUri;
				}

				return false;
			});

			return this.actions.map((action, i) => renderAction(action, {
				onClick: this.props.onClick,
				showIcon: hasIcons,
				firstInSection: !i,
				lastInSection: this.actions.length - 1 === i,
				enabled: this.props.enabled,
			}));
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
		sectionView: (isService) => ({
			backgroundColor: isService ? '#fbfbfc' : '#ffffff',
			fontSize: 18,
			borderRadius: 15,
		}),
		title: {
			color: '#525c69',
			fontSize: 13,
		},
	};

	this.ContextMenuSection = ContextMenuSection;
})();
