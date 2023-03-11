(() => {
	const { ContextMenuBanner } = jn.require('layout/ui/context-menu/banner');

	const ACTION_DELETE = 'delete';
	const ACTION_CANCEL = 'cancel';
	const DEFAULT_BANNER_HEIGHT = device.screen.width > 375 ? 258 : 300;
	const DEFAULT_BANNER_ITEM_HEIGHT = 30;

	/**
	 * @class ContextMenu
	 */
	class ContextMenu extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.id = props.id;
			this.params = (props.params || {});
			this.testId = `${props.testId}_CONTEXT_MENU`;
			this.parentId = props.parentId;
			this.parent = (props.parent || {});
			this.layoutWidget = props.layoutWidget;

			this.closeByApi = false;
			this.closeHandler = this.close.bind(this);

			this.state.enabled = true;

			this.actions = this.prepareActions(props.actions);

			this.showCancelButton = this.getParam('showCancelButton', true);

			this.actionsBySections = this.getActionsBySections();

			this.titlesBySectionCode = props.titlesBySectionCode || {};
		}

		componentWillReceiveProps(props)
		{
			this.testId = `${props.testId}_CONTEXT_MENU`;
		}

		getParam(name, defaultValue)
		{
			defaultValue = (defaultValue || '');
			return (this.params[name] !== undefined ? this.params[name] : defaultValue);
		}

		showActionLoader()
		{
			return this.getParam('showActionLoader', true);
		}

		prepareActions(actions)
		{
			if (!Array.isArray(actions))
			{
				return [];
			}

			const overallShowActionLoaderParam = this.showActionLoader();

			return actions.map((action) => {
				if (action.type)
				{
					action = {
						...this.getActionConfigByType(action.type),
						...action,
					};
				}

				action.sectionCode = (action.sectionCode || ContextMenuSection.getDefaultSectionName());
				action.parentId = this.parentId;
				action.parent = this.parent;
				action.updateItemHandler = (this.props.updateItemHandler || null);
				action.closeHandler = this.closeHandler;

				if (!action.hasOwnProperty('showActionLoader'))
				{
					action.showActionLoader = overallShowActionLoaderParam;
				}

				return action;
			});
		}

		getActionConfigByType(type)
		{
			if (type === ACTION_DELETE)
			{
				return {
					data: {
						svgIcon: '<svg width="16" height="20" viewBox="0 0 16 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.365 0.358398H6.40958V1.84482H1.87921C1.1169 1.84482 0.498932 2.46279 0.498932 3.2251V4.81772H15.276V3.2251C15.276 2.46279 14.658 1.84482 13.8957 1.84482H9.365V0.358398Z" fill="#767C87"/><path d="M1.97671 6.30421H13.7984L12.6903 18.8431C12.6484 19.318 12.2505 19.6823 11.7737 19.6823H4.00133C3.52452 19.6823 3.12669 19.318 3.08472 18.8431L1.97671 6.30421Z" fill="#767C87"/></svg>',
					},
				};
			}

			return {};
		}

		getActionsBySections()
		{
			const actionsBySections = {};

			this.actions.forEach((action) => {
				const sectionCode = action.sectionCode;
				action.getParentWidget = () => this.layoutWidget;
				actionsBySections[sectionCode] = (actionsBySections[sectionCode] || []);
				actionsBySections[sectionCode].push({
					...action,
					isActive: (
						!action.onActiveCallback
						|| (
							action.onActiveCallback
							&& action.onActiveCallback(action.id, action.parentId, action.parent)
						)
					),
				});
			});

			if (this.showCancelButton)
			{
				const serviceSectionName = ContextMenuSection.getServiceSectionName();
				actionsBySections[serviceSectionName] = (actionsBySections[serviceSectionName] || []);
				const actionCancel = this.getCancelButtonConfig();
				actionsBySections[serviceSectionName].push({
					...actionCancel,
					isActive: (
						!actionCancel.onActiveCallback
						|| (
							actionCancel.onActiveCallback
							&& actionCancel.onActiveCallback(actionCancel.id, actionCancel.parentId, actionCancel.parent)
						)
					),
				});
			}

			return actionsBySections;
		}

		render()
		{
			return ScrollView(
				{
					style: styles.view,
					testId: this.testId,
				},
				View(
					{},
					this.renderBanner(),
					...this.renderSections(),
				),
			);
		}

		renderBanner()
		{
			if (this.banner)
			{
				return new ContextMenuBanner({
					banner: this.banner,
					menu: this,
				});
			}

			return null;
		}

		get banner()
		{
			const { banner } = this.props;

			return typeof banner === 'object' ? banner : null;
		}

		getBannerHeight()
		{
			if (this.banner)
			{
				const itemsHeight = Array.isArray(this.banner.featureItems) ? (this.banner.featureItems.length * DEFAULT_BANNER_ITEM_HEIGHT) : 0;

				return Math.max(DEFAULT_BANNER_HEIGHT, itemsHeight);
			}

			return 0;
		}

		getCustomSectionHeight()
		{
			if (this.props.customSection)
			{
				return this.props.customSection.height;
			}

			return 0;
		}

		renderSections()
		{
			const results = new Map();
			let i = 0;

			if (this.props.customSection)
			{
				results.set('custom', this.props.customSection.layout);
				i++;
			}

			for (const sectionCode in this.actionsBySections)
			{
				const section = ContextMenuSection.create({
					id: sectionCode,
					title: this.titlesBySectionCode[sectionCode],
					actions: this.actionsBySections[sectionCode],
					renderAction: (action, {
						onClick,
						showIcon,
						firstInSection,
						lastInSection,
						enabled,
					}) => ContextMenuItem.create({
						...action,
						onClick,
						showIcon,
						firstInSection,
						lastInSection,
						enabled,
						testId: this.testId,
					}),
					enabled: this.state.enabled,
					style: {
						marginTop: (i > 0 ? 10 : (this.getParam('title', '') === '' ? 10 : 0)),
					},
				});
				results.set(sectionCode, section);
				i++;
			}
			return results.values();
		}

		getCancelButtonConfig()
		{
			return {
				id: ACTION_CANCEL,
				parentId: this.parentId,
				title: BX.message('CONTEXT_MENU_CANCEL'),
				sectionCode: ContextMenuSection.getServiceSectionName(),
				largeIcon: false,
				type: ContextMenuItem.getTypeCancelName(),
				showActionLoader: false,
				data: {
					svgIcon: '<svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M10.7562 8.54581L16.4267 14.2163L14.2165 16.4266L8.54596 10.7561L2.87545 16.4266L0.665192 14.2163L6.3357 8.54581L0.665192 2.87529L2.87545 0.665039L8.54596 6.33555L14.2165 0.665039L16.4267 2.87529L10.7562 8.54581Z" fill="#A8ADB4"/></svg>',
				},
				onClickCallback: () => {
					return Promise.resolve(true);
				},
				closeHandler: this.closeHandler,
			};
		}

		/**
		 * @todo check and correct this method, now on the iOs and on the Android the menu rendering is different
		 * @returns {number}
		 */
		calcMediumHeight()
		{
			const indentBetweenSections = 10;
			const itemHeight = 60;
			const sectionTitleHeight = 38;

			// @todo the height of the title on android more than on ios
			let height = this.getParam('title', '') !== '' ? 70 : 20;

			for (const sectionCode in this.actionsBySections)
			{
				if (this.titlesBySectionCode[sectionCode])
				{
					height += sectionTitleHeight;
				}

				height += this.actionsBySections[sectionCode].reduce((sum, action) => {
					if (action.isActive)
					{
						return sum + itemHeight;
					}

					return sum;
				}, indentBetweenSections);
			}

			return height + this.getBannerHeight() + this.getCustomSectionHeight();
		}

		show(parentWidget = PageManager)
		{
			const shouldResizeContent = this.getParam('shouldResizeContent', false);
			const showPartiallyHidden = this.getParam('showPartiallyHidden', false);
			const onlyMediumPosition = !showPartiallyHidden;

			let mediumPositionHeight, mediumPositionPercent;

			if (showPartiallyHidden)
			{
				mediumPositionPercent = 50;
			}
			else
			{
				mediumPositionHeight = this.getParam('mediumPositionHeight');
				mediumPositionHeight = (mediumPositionHeight === '' ? this.calcMediumHeight() : mediumPositionHeight);
			}

			return new Promise((resolve, reject) => {
				const widgetParams = {
					backdrop: {
						shouldResizeContent,
						swipeAllowed: true,
						forceDismissOnSwipeDown: true,
						onlyMediumPosition,
						mediumPositionHeight,
						mediumPositionPercent,
						horizontalSwipeAllowed: false,
						hideNavigationBar: (this.getParam('title', '') === ''),
						navigationBarColor: BACKGROUND_COLOR,
					},
				};

				Object.keys(widgetParams.backdrop).forEach(key => {
					if (widgetParams.backdrop[key] === undefined)
					{
						delete widgetParams.backdrop[key];
					}
				});

				parentWidget
					.openWidget('layout', widgetParams)
					.then((layoutWidget) => {
						this.layoutWidget = layoutWidget;

						if (this.getParam('title', '') !== '')
						{
							layoutWidget.setTitle({ text: this.getParam('title') });
							layoutWidget.enableNavigationBarBorder(false);
						}

						layoutWidget.setListener((eventName) => {
							if (eventName === 'onViewHidden' || eventName === 'onViewRemoved')
							{
								if (this.props.onClose)
								{
									this.props.onClose();
								}

								if (!this.closeByApi && this.props.onCancel)
								{
									this.props.onCancel();
								}
							}
						});

						layoutWidget.showComponent(this);

						resolve();
					})
					.catch(reject)
				;
			});
		}

		close(callback = () => {})
		{
			this.closeByApi = true;

			if (this.layoutWidget)
			{
				this.layoutWidget.close(callback);
			}
		}
	}

	const BACKGROUND_COLOR = '#eef2f4';

	const styles = {
		view: {
			backgroundColor: BACKGROUND_COLOR,
			safeArea: {
				bottom: true,
			},
		},
	};

	this.ContextMenu = ContextMenu;
})();
