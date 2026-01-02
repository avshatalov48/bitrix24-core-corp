/**
 * @module layout/ui/viewer-list/src/list
 */
jn.define('layout/ui/viewer-list/src/list', (require, exports, module) => {
	const { Color } = require('tokens');
	const { StatefulList } = require('layout/ui/stateful-list');
	const { ListItemsFactory, ListItemType } = require('layout/ui/viewer-list/src/list-items-factory');
	const { UserProfile } = require('user-profile');
	const { fetchUsersIfNotLoaded } = require('statemanager/redux/slices/users/thunk');
	const { usersUpserted } = require('statemanager/redux/slices/users');
	const store = require('statemanager/redux/store');
	const { dispatch } = store;

	const PullCommand = {
		VIEW_ADD: 'add',
	};

	class ViewerList extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.layoutWidget = null;
			this.testId = (props.testId || '');
			this.statefulList = null;
			this.parentWidget = props.layout ?? layout;
		}

		render()
		{
			const { entityType, entityId, renderCustomDescription } = this.props;

			return View(
				{
					style: {
						flex: 1,
						backgroundColor: Color.bgContentPrimary.toHex(),
					},
					testId: `${this.testId}_USER_LIST`,
				},
				new StatefulList({
					testId: 'viewer-list',
					showAirStyle: true,
					shouldReloadDynamically: true,
					layout: this.parentWidget,
					actions: {
						loadItems: 'mobile.Viewer.getViewers',
					},
					actionCallbacks: {
						loadItems: this.onItemsLoaded,
					},
					actionParams: {
						loadItems: {
							params: {
								entityId,
								entityType,
							},
						},
					},
					itemType: ListItemType.VIEWERS,
					itemFactory: ListItemsFactory,
					itemParams: {
						renderCustomDescription,
					},
					itemsLoadLimit: 10,
					pull: {
						moduleId: 'contentview',
						callback: this.onPullCallback,
						shouldReloadDynamically: true,
					},
					isShowFloatingButton: false,
					useCache: false,
					onBeforeItemsRender: this.onBeforeItemsRender,
					itemDetailOpenHandler: this.itemDetailOpenHandler,
					ref: this.bindRef,
				}),
			);
		}

		updateViewerList = (userId, entityId, typeId) => {
			const viewTimestamp = Math.floor(Date.now() / 1000);

			const newViewer = {
				id: Number(userId),
				entityId: Number(entityId),
				entityType: typeId,
				viewTimestamp,
			};

			this.statefulList.updateItemsData([newViewer]);
		};

		onItemsLoaded = (responseData, context) => {
			const { users } = responseData || {};

			if (users && users.length > 0)
			{
				dispatch(usersUpserted(users));
			}
		};

		onPullCallback = async ({ params = {}, command }) => {
			const {
				ENTITY_ID: entityId,
				TYPE_ID: typeId,
				USER_ID: userId,
			} = params ?? {};

			if (!entityId || !typeId || !userId || !command)
			{
				throw new Error('Missing required parameters');
			}

			if (
				Number(entityId) === Number(this.props.entityId)
				&& typeId === this.props.entityType
				&& command === PullCommand.VIEW_ADD
			)
			{
				await dispatch(fetchUsersIfNotLoaded({ userIds: [Number(userId)] }));

				this.updateViewerList(userId, entityId, typeId);

				return { params: { eventName: command } };
			}

			return { params: { eventName: null, items: [] } };
		};

		onBeforeItemsRender = (items) => {
			return items.map((item, index) => ({
				...item,
				showBorder: index > 0,
				parentWidget: this.parentWidget,
			}));
		};

		bindRef = (ref) => {
			if (ref)
			{
				this.statefulList = ref;
			}
		};

		itemDetailOpenHandler(userId)
		{
			void UserProfile.open({
				ownerId: userId,
				parentWidget: this.parentWidget,
			});
		}
	}

	module.exports = { ViewerList };
});
