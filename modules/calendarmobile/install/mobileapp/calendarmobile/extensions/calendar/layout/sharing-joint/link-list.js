/**
 * @module calendar/layout/sharing-joint/link-list
 */
jn.define('calendar/layout/sharing-joint/link-list', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { chevronLeft } = require('assets/common');
	const { ContextMenu } = require('layout/ui/context-menu');
	const { Icons } = require('calendar/layout/icons');
	const { LinkItem } = require('calendar/layout/sharing-joint/link-item');
	const { Color } = require('tokens');

	class LinkList extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.loadUserLinks();
			this.onUserLinkDeleted = this.onUserLinkDeleted.bind(this);
			this.openSortingMenu = this.openSortingMenu.bind(this);
			this.onHeaderClickHandler = this.onHeaderClickHandler.bind(this);
			this.onSortByFrequentUseClickHandler = this.onSortByFrequentUseClickHandler.bind(this);
			this.onSortByDateClickHandler = this.onSortByDateClickHandler.bind(this);

			this.state = this.getState();

			this.layoutWidget = props.layoutWidget;
		}

		get model()
		{
			return this.props.model;
		}

		loadUserLinks()
		{
			this.model.loadUserLinks().then(() => this.redraw());
		}

		componentDidMount()
		{
			this.bindEvents();
		}

		componentWillUnmount()
		{
			this.unbindEvents();
		}

		bindEvents()
		{
			this.model.on('CalendarSharing:UserLinkDeleted', this.onUserLinkDeleted);
		}

		unbindEvents()
		{
			this.model.off('CalendarSharing:UserLinkDeleted', this.onUserLinkDeleted);
		}

		onUserLinkDeleted()
		{
			if (this.model.getUserLinks().length === 0)
			{
				this.redraw();
			}
		}

		redraw()
		{
			this.setState(this.getState());
		}

		getState()
		{
			return {
				userLinks: this.model.getUserLinks(),
				isSortByFrequentUse: this.model.isSortByFrequentUse(),
			};
		}

		render()
		{
			return View(
				{
					safeArea: {
						bottom: true,
					},
				},
				this.renderHeaderContainer(),
				this.renderList(),
			);
		}

		renderHeaderContainer()
		{
			return View(
				{
					style: styles.headerContainer,
				},
				this.renderHeader(),
				(this.state.userLinks.length > 0) && this.renderSortSettingsButton(),
			);
		}

		renderHeader()
		{
			return View(
				{
					style: styles.header,
					onClick: this.onHeaderClickHandler,
				},
				this.renderLeftArrow(),
				this.renderHeaderTitle(),
			);
		}

		onHeaderClickHandler()
		{
			this.layoutWidget.close();
		}

		renderLeftArrow()
		{
			return Image({
				tintColor: AppTheme.colors.base3,
				svg: {
					content: chevronLeft(),
				},
				style: styles.leftArrowIcon,
			});
		}

		renderHeaderTitle()
		{
			return Text({
				text: Loc.getMessage('CALENDARMOBILE_SHARING_JOINT_TITLE'),
				style: styles.headerTitle,
			});
		}

		renderSortSettingsButton()
		{
			return View(
				{
					style: styles.sortSettingsButton,
					onClick: this.openSortingMenu,
				},
				Image({
					tintColor: AppTheme.colors.base4,
					svg: {
						content: icons.sort,
					},
					style: styles.sortSettingsIcon,
				}),
			);
		}

		openSortingMenu()
		{
			const sortByFrequentUseId = 'calendar_sharing_menu_action_sort_by_frequent_use';
			const sortByDateId = 'calendar_sharing_menu_action_sort_by_date';

			const actions = [
				{
					id: sortByFrequentUseId,
					title: Loc.getMessage('CALENDARMOBILE_SHARING_SORT_BY_FREQUENT_USE'),
					onClickCallback: this.onSortByFrequentUseClickHandler,
				},
				{
					id: sortByDateId,
					title: Loc.getMessage('CALENDARMOBILE_SHARING_SORT_BY_DATE'),
					onClickCallback: this.onSortByDateClickHandler,
				},
			];

			const menu = new ContextMenu({ actions });
			menu.setSelectedActions([this.state.isSortByFrequentUse ? sortByFrequentUseId : sortByDateId]);
			menu.show(this.layoutWidget);
		}

		onSortByFrequentUseClickHandler()
		{
			this.updateSortByFrequentUse(true);
		}

		onSortByDateClickHandler()
		{
			this.updateSortByFrequentUse(false);
		}

		updateSortByFrequentUse(doSortByFrequentUse)
		{
			this.model.setSortByFrequentUse(doSortByFrequentUse);
			this.redraw();
		}

		renderList()
		{
			const links = this.getUserLinks();
			const hasLinks = links.length > 0;

			return View(
				{
					style: {
						flex: 1,
						backgroundColor: Color.bgContentPrimary.toHex(),
						...(hasLinks ? {} : {
							alignItems: 'center',
							justifyContent: 'center',
						}),
					},
					ref: (ref) => {
						this.listRef = ref;
					},
				},
				hasLinks && ScrollView(
					{
						style: {
							flex: 1,
						},
					},
					View(
						{},
						...links.map((link) => this.renderJointLink(link)),
					),
				),
				!hasLinks && this.renderEmptyState(),
			);
		}

		getUserLinks()
		{
			const links = [...this.state.userLinks].sort((a, b) => b.id - a.id);

			if (this.state.isSortByFrequentUse)
			{
				return [...links].sort((a, b) => b.frequentUse - a.frequentUse);
			}

			return links;
		}

		renderJointLink(link)
		{
			return View(
				{},
				new LinkItem({
					link,
					model: this.model,
					layoutWidget: this.layoutWidget,
				}),
			);
		}

		renderEmptyState()
		{
			return View(
				{
					style: styles.emptyStateContainer,
				},
				Image({
					svg: {
						content: Icons.calendarEmpty,
					},
					style: styles.emptyStateIcon,
				}),
				Text({
					text: Loc.getMessage('CALENDARMOBILE_SHARING_JOINT_LIST_EMPTY_TITLE'),
					style: styles.emptyStateText,
				}),
			);
		}
	}

	const icons = {
		sort: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M1.75949 8.86367L7.00793 3.61523L12.2564 8.86367H7.87452V19.8246H6.11577V8.86367H1.75949ZM17.916 3.94277H16.1572V14.9418H11.7752L17.0236 20.1902L22.272 14.9418H17.916V3.94277Z" fill="#525C69"/></svg>`,
	};

	const styles = {
		headerContainer: {
			flexDirection: 'row',
			alignItems: 'center',
		},
		header: {
			flex: 1,
			flexDirection: 'row',
			paddingVertical: 12,
			paddingHorizontal: 14,
		},
		leftArrowIcon: {
			width: 23,
			height: 23,
		},
		headerTitle: {
			marginLeft: 10,
			fontSize: 17,
			color: AppTheme.colors.base1,
			flex: 1,
		},
		sortSettingsButton: {
			paddingHorizontal: 14,
			justifyContent: 'center',
			height: '100%',
		},
		sortSettingsIcon: {
			width: 17,
			height: 17,
			marginRight: 5,
		},
		emptyStateContainer: {
			alignItems: 'center',
			justifyContent: 'center',
			padding: 10,
			marginBottom: 50,
		},
		emptyStateIcon: {
			width: 158,
			height: 129,
		},
		emptyStateText: {
			fontSize: 18,
			fontWeight: '400',
			marginTop: 20,
			color: AppTheme.colors.base2,
			textAlign: 'center',
		},
	};

	module.exports = { LinkList };
});
