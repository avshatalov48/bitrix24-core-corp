(() =>
{
	const ACTION_DELETE = 'delete';
	const ACTION_CANCEL = 'cancel';

	/**
	 * @class ContextMenu
	 */
	class ContextMenu extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.id = props.id;
			this.parentId = props.parentId;
			this.parent = (props.parent || {});
			this.layoutWidget = props.layoutWidget;

			this.closeHandler = this.close.bind(this);
			this.changeAvailabilityHandler = this.changeAvailability.bind(this);

			this.state.enabled = true;

			this.actions = this.prepareActions(props.actions);

			this.params = (props.params || {});

			this.showCancelButton = this.getParam('showCancelButton', true);

			this.actionsBySections = this.getActionsBySections();
		}

		getParam(name, defaultValue)
		{
			defaultValue = (defaultValue || '');
			return (this.params[name] !== undefined ? this.params[name] : defaultValue);
		}

		prepareActions(actions)
		{
			if (!Array.isArray(actions))
			{
				return [];
			}

			return actions.map((action) => {
				if (action.type)
				{
					action = {
						...this.getActionConfigByType(action.type),
						...action,
						type: ContextMenuItem.getTypeButtonName()
					};
				}

				action.sectionCode = (action.sectionCode || ContextMenuSection.getDefaultSectionName());
				action.parentId = this.parentId;
				action.parent = this.parent;
				action.updateItemHandler = (this.props.updateItemHandler || null);
				action.closeHandler = this.closeHandler;
				action.changeAvailabilityHandler = this.changeAvailabilityHandler;
				return action;
			});
		}

		getActionConfigByType(type)
		{
			if (type === ACTION_DELETE)
			{
				return {
					data: {
						svgIcon: '<svg width="16" height="20" viewBox="0 0 16 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.365 0.358398H6.40958V1.84482H1.87921C1.1169 1.84482 0.498932 2.46279 0.498932 3.2251V4.81772H15.276V3.2251C15.276 2.46279 14.658 1.84482 13.8957 1.84482H9.365V0.358398Z" fill="#525C69"/><path d="M1.97671 6.30421H13.7984L12.6903 18.8431C12.6484 19.318 12.2505 19.6823 11.7737 19.6823H4.00133C3.52452 19.6823 3.12669 19.318 3.08472 18.8431L1.97671 6.30421Z" fill="#525C69"/></svg>'
					}
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
				actionsBySections[sectionCode].push(ContextMenuItem.create(action));
			});

			if (this.showCancelButton)
			{
				const serviceSectionName = ContextMenuSection.getServiceSectionName();
				actionsBySections[serviceSectionName] = (actionsBySections[serviceSectionName] || []);
				actionsBySections[serviceSectionName].push(ContextMenuItem.create(this.getCancelButtonConfig()));
			}

			return actionsBySections;
		}

		render()
		{
			return View(
				{
					style: styles.view,
				},
				...this.renderSections(),
			);
		}

		renderSections()
		{
			const results = new Map();
			let i = 0;

			for (let sectionCode in this.actionsBySections)
			{
				const section = ContextMenuSection.create({
					id: sectionCode,
					actions: this.actionsBySections[sectionCode],
					enabled: this.state.enabled,
					style: {
						marginTop: (i > 0 ? 10 : (this.getParam('title', '') === '' ? 10 : 0))
					}
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
				data: {
					svgIcon: '<svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M10.7562 8.54581L16.4267 14.2163L14.2165 16.4266L8.54596 10.7561L2.87545 16.4266L0.665192 14.2163L6.3357 8.54581L0.665192 2.87529L2.87545 0.665039L8.54596 6.33555L14.2165 0.665039L16.4267 2.87529L10.7562 8.54581Z" fill="#818993"/></svg>'
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

			// @todo the height of the title on android more than on ios
			let height = this.getParam('title', '') !== '' ? 70 : 20;

			for (let sectionCode in this.actionsBySections)
			{
				height += this.actionsBySections[sectionCode].reduce((sum, action) => {
					if (action.isActiveItem())
					{
						return sum + itemHeight;
					}

					return sum;
				}, indentBetweenSections);
			}

			return height;
		}

		show(parentWidget = null)
		{
			return new Promise((resolve, reject) => {
				const widgetName = 'layout';
				const widgetParams = {
					backdrop: {
						shouldResizeContent: true,
						swipeAllowed: true,
						onlyMediumPosition: true,
						horizontalSwipeAllowed: false,
						mediumPositionHeight: this.calcMediumHeight(),
						hideNavigationBar: (this.getParam('title', '') === ''),
						navigationBarColor: BACKGROUND_COLOR,
					},
					useSearch: false,
					useClassicSearchField: false,
					onReady: (layoutWidget) => {
						this.layoutWidget = layoutWidget;

						if (this.getParam('title', '') !== '')
						{
							layoutWidget.setTitle({text: this.getParam('title')});
							layoutWidget.enableNavigationBarBorder(false);
						}
						layoutWidget.showComponent(this);

						resolve();
					},
					onError: error => reject(error),
				};

				if (parentWidget)
				{
					parentWidget.openWidget(widgetName, widgetParams);
				}
				else
				{
					PageManager.openWidget(widgetName, widgetParams);
				}
			});
		}

		close(callback = () => {})
		{
			if (this.layoutWidget)
			{
				this.layoutWidget.close(callback);
			}
		}

		changeAvailability(enabled = true)
		{
			this.setState({enabled});
		}
	}

	const BACKGROUND_COLOR = '#EEF2F4';

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
