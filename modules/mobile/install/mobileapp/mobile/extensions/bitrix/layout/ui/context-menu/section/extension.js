(() => {
	const SECTION_DEFAULT = 'default';
	const SECTION_SERVICE = 'service';
	const svgIcons = {
		['add']: {
			content: `<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.73364 3.75H8.25V8.25H3.75V9.71066H8.25V14.2423H9.73364V9.71066H14.2813V8.25H9.73364V3.75Z" fill="#2066B0"/></svg>`,
		},
	};

	const isAndroid = Application.getPlatform() === 'android';
	const navigationBarLeftMargin = isAndroid ? 20 : 16;

	/**
	 * @class ContextMenuSection
	 */
	class ContextMenuSection extends LayoutComponent
	{
		static create(props)
		{
			return new this(props);
		}

		get id()
		{
			return this.props.id;
		}

		get title()
		{
			return this.props.title;
		}

		get titleAction()
		{
			return this.props.titleAction;
		}

		get actions()
		{
			return BX.prop.getArray(this.props, 'actions', []);
		}

		get showTitleBorder()
		{
			return BX.prop.getBoolean(this.props, 'showTitleBorder', true);
		}

		get closeMenuHandler()
		{
			return this.props.closeHandler;
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
						paddingVertical: 10,
						paddingHorizontal: 20,
						paddingLeft: 0,
						marginLeft: navigationBarLeftMargin,
						flexDirection: 'row',
						borderBottomWidth: this.showTitleBorder ? 1 : 0,
						borderBottomColor: '#edeef0',
						justifyContent: 'space-between',
					},
				},
				this.getTitleText(),
				this.renderTitleAction(),
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

		renderTitleAction()
		{
			if (!this.titleAction)
			{
				return null;
			}

			let titleActionIcon = null;
			const icon = this.titleAction.iconType ? svgIcons[this.titleAction.iconType] : null;
			if (icon)
			{
				titleActionIcon = Image({
					style: {
						width: 18,
						height: 18,
						marginRight: 2,
					},
					resizeMode: 'center',
					svg: icon,
				});
			}

			return View(
				{
					style: {
						flexDirection: 'row',
						flexShrink: 2,
					},
					onClick: this.handleTitleActionClick.bind(this),
				},
				titleActionIcon,
				Text({
					style: {
						fontSize: 14,
						color: '#0065A3',
						flexShrink: 2,
					},
					numberOfLines: 1,
					text: this.titleAction.text,
					ellipsize: 'end',
				}),
			);
		}

		handleTitleActionClick()
		{
			if (!this.titleAction || !this.titleAction.action)
			{
				return;
			}

			let promise = this.titleAction.action();

			if (!(promise instanceof Promise))
			{
				promise = Promise.resolve();
			}

			promise.then(({ closeMenu = true, closeCallback} = {}) => {

				if (closeMenu)
				{
					this.closeMenuHandler(closeCallback);
				}
			});
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
			borderRadius: 12,
		}),
		title: {
			color: '#525c69',
			fontSize: 13,
		},
	};

	this.ContextMenuSection = ContextMenuSection;
})();
