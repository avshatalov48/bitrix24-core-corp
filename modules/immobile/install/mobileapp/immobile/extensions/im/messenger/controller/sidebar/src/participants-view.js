/**
 * @module im/messenger/controller/sidebar/participants-view
 */
jn.define('im/messenger/controller/sidebar/participants-view', (require, exports, module) => {
	const { Logger } = require('im/messenger/lib/logger');
	const { Item } = require('im/messenger/lib/ui/base/item');
	const { withPressed } = require('utils/color');
	const { LoaderItem } = require('im/messenger/lib/ui/base/loader');

	class SidebarParticipantsView extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.state = {
				participants: props.participants,
			};
			this.loader = new LoaderItem({
				enable: true,
				text: '',
			});
		}

		componentDidMount() {
			Logger.log('Participants.view.componentDidMount');
			if (this.state.participants.length === 0)
			{
				this.props.parentEmitter.emit('updateParticipants');
			}
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						flex: 1,
					},
				},
				this.renderListView(),
				this.renderFloatBtn(),
			);
		}

		renderListView()
		{
			const items = this.buildItems();
			const platform = Application.getPlatform();

			return ListView({
				ref: (ref) => this.listViewRef = ref,
				style: {
					marginTop: 12,
					flexDirection: 'column',
					flex: 1,
				},
				actionsForItem: (section, index) => {
					if (this.state.participants.length < index) // for empty row
					{
						return null;
					}

					return { right: this.getActionArrayRight() };
				},
				data: [{ items }],
				renderItem: (item) => {
					if (item.type === 'empty')
					{
						return this.getEmptyRow();
					}

					return new Item({ data: item, size: 'M', isCustomStyle: true });
				},
				// onRefresh: () => {
				// 	Logger.log('Participants.view.onRefresh');
				// 	this.setState({ isRefreshing: true });
				// 	this.props.parentEmitter.emit('updateParticipants');
				// }, // TODO this is disabled as the bootloader works crookedly on IOS and list refresh while is not need
				onActionClick: (section, index) => {
					this.onClickActionDelete(section, index);
				},
				onLoadMore: platform === 'ios' ? this.iosOnLoadMore.bind(this) : this.androidOnLoadMore.bind(this),
				renderLoadMore: platform === 'ios' ? this.iosRenderLoadMore.bind(this) : this.androidRenderLoadMore.bind(this),
			});
		}

		buildItems()
		{
			const participants = this.state.participants;

			const doneItems = participants.map((item, index) => (
				{
					type: 'item',
					key: index.toString(),
					title: item.title,
					isYouTitle: item.isYouTitle,
					subtitle: item.desc,
					avatarUri: item.imageUrl,
					avatarColor: item.imageColor,
					status: item.statusSvg,
					crownStatus: item.crownStatus,

					style: {
						parentView: {
							backgroundColor: Application.getPlatform() === 'ios'
								? '#FFFFFF'
								: withPressed('#FFFFFF'),
						},
						itemContainer: {
							flexDirection: 'row',
							alignItems: 'center',
							marginHorizontal: 14,
						},
						avatarContainer: {
							marginTop: 6,
							marginBottom: 6,
							paddingHorizontal: 2,
							paddingVertical: 3,
							position: 'relative',
							zIndex: 1,
							flexDirection: 'column',
							justifyContent: 'flex-end',
						},
						itemInfoContainer: {
							flexDirection: 'row',
							borderBottomWidth: 1,
							borderBottomColor: '#e9e9e9',
							flexGrow: 2,
							alignItems: 'center',
							marginBottom: 6,
							marginTop: 6,
							height: '100%',
							marginLeft: 16,
						},
						itemInfo: {
							mainContainer: {
								flexGrow: 2,
								maxWidth: '80%',
							},
							title: {
								marginBottom: 4,
								fontSize: 16,
								fontWeight: 500,
							},
							isYouTitle: {
								marginLeft: 4,
								marginBottom: 4,
								fontSize: 16,
								color: '#959CA4',
								fontWeight: 400,
							},
							subtitle: {
								color: '#959CA4',
								fontSize: 14,
								fontWeight: 400,
								textStyle: 'normal',
								align: 'baseline',
							},
						},
					},
				}
			));

			// push empty row
			doneItems.push({
				type: 'empty',
				key: (Number(doneItems.at(-1)?.key || 0) + 1).toString(),
			});

			return doneItems;
		}

		/**
		 * @desc Returns view without element for empty row
		 * @return {LayoutComponent}
		 * @private
		 */
		getEmptyRow()
		{
			const heightRow = this.getHeightEmptyRow();

			return View(
				{
					clickable: false,
				},
				View(
					{
						style: {
							height: heightRow,
						},
						clickable: false,
					},
				),
			);
		}

		/**
		 * @desc Returns height for empty row
		 * @return {number}
		 * @private
		 */
		getHeightEmptyRow()
		{
			if (Application.getPlatform() !== 'ios')
			{
				return 110;
			}
			const deviceHeight = device.screen.height || 810;
			const refHeightDevice = 844;
			const refHeightRow = 75;
			const refPercentAttitude = 1.57;
			const percentOffsetHeightDevice = (refHeightDevice - deviceHeight) / (refHeightDevice / 100);
			const percentOffsetHeightRow = percentOffsetHeightDevice * refPercentAttitude;

			return refHeightRow - (percentOffsetHeightRow * refHeightRow / 100);
		}

		renderFloatBtn()
		{
			if (!this.props.permissions.isCanAddParticipants)
			{
				return null;
			}

			return new UI.FloatingButtonComponent({
				position: { bottom: 25 },
				onClick: () => this.onClickBtnAdd(),
				ref: (ref) => this.floatingButtonRef = ref,
				onLongClick: () => this.onLongClickBtnAdd(),
			});
		}

		/**
		 * @desc Returns array actions for build right list context menu
		 * @return {Array<object|null>}
		 * @private
		 */
		getActionArrayRight()
		{
			const { permissions: { isCanRemoveParticipants }, loc } = this.props;

			return [
				isCanRemoveParticipants
					? {
						color: '#FF799C',
						title: loc.removeParticipants,
						icon: 'action_delete',
						key: 'key_action_delete',
					}
					: null,
			];
		}

		onClickBtnAdd()
		{
			Logger.log('onClick');
			this.props.parentEmitter.emit('clickBtnParticipantsAdd');
		}

		onClickActionDelete(section, index)
		{
			const correctIndex = Application.getPlatform() === 'ios' ? index : index - 1;

			const deletedUser = this.state.participants.find((el, i) => i === correctIndex);
			const onComplete = () => {
				this.props.parentEmitter.emit('clickBtnParticipantsDelete', [deletedUser]);
			};

			this.listViewRef.deleteRow(section, correctIndex, 'automatic', onComplete);
		}

		onLongClickBtnAdd()
		{
			Logger.log('onLongClick');
		}

		androidOnLoadMore()
		{
			if (this.state.participants.length > 0)
			{
				this.loader.disable();
			}
		}

		iosOnLoadMore() {}

		iosRenderLoadMore()
		{
			if (this.state.participants.length > 0)
			{
				return null;
			}

			return this.loader;
		}

		androidRenderLoadMore()
		{
			return this.loader;
		}
	}

	module.exports = { SidebarParticipantsView };
});
