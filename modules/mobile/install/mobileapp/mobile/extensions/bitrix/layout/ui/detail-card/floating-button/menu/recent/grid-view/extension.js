/**
 * @module layout/ui/detail-card/floating-button/menu/recent/grid-view
 */
jn.define('layout/ui/detail-card/floating-button/menu/recent/grid-view', (require, exports, module) => {
	const { Color, Indent, Corner } = require('tokens');
	const { EventEmitter } = require('event-emitter');
	const { withPressed } = require('utils/color');
	const { ScrollView } = require('layout/ui/scroll-view');
	const { Text5 } = require('ui-system/typography');
	const { IconView } = require('ui-system/blocks/icon');

	/**
	 * @typedef {Object} RecentGridViewProps
	 * @property {DetailCardComponent} detailCard
	 * @property {FloatingMenuItem[]} items
	 *
	 * @class RecentGridView
	 * @param {RecentGridViewProps} props
	 * @return {View|null}
	 */
	class RecentGridView extends LayoutComponent
	{
		/**
		 * @returns {number}
		 */
		static getHeight()
		{
			return 96;
		}

		render()
		{
			const items = this.#getItems();

			return View(
				{},
				View(
					{
						style: {
							paddingTop: Indent.M.toNumber(),
							paddingBottom: Indent.L.toNumber(),
							paddingHorizontal: Indent.XL.toNumber(),
						},
					},
					ScrollView(
						{
							horizontal: true,
							showsHorizontalScrollIndicator: false,
							style: {
								height: RecentGridView.getHeight() - Indent.M.toNumber() - Indent.L.toNumber(),
								backgroundColor: Color.bgSecondary.toHex(),
							},
						},
						...items.map((item) => this.#renderItem(item)),
					),
				),
				View({
					style: {
						height: 4,
						backgroundColor: Color.bgSeparatorSecondary.toHex(),
						width: '100%',
					},
				}),
			);
		}

		#getItems()
		{
			const { items, detailCard } = this.props;

			return items.map((item, index) => ({
				index,
				key: `${item.getId()}/${item.getTabId()}`,
				actionId: item.getId(),
				tabId: item.getTabId(),
				title: item.getShortTitle() || item.getTitle(),
				icon: item.getIcon(),
				uid: detailCard?.uid,
				type: 'default',
				section: 0,
			}));
		}

		/**
		 * @param {number} index
		 * @param {string} title
		 * @param {string} uid
		 * @param {string} key
		 * @param {Icon} icon
		 * @param {string} actionId
		 * @param {?string} tabId
		 * @return {View}
		 */
		#renderItem = ({ index, title, uid, icon, actionId, tabId }) => {
			const { items } = this.props;
			const isShowDivider = index !== items.length - 1;

			return View(
				{
					style: {
						flexDirection: 'row',
					},
					onClick: this.#handleOnClick({ uid, actionId, tabId }),
				},
				View(
					{
						testId: `recent-item-${tabId || 'root'}-${actionId}`,
						style: {
							width: 80,
							borderRadius: Corner.M.getValue(),
							alignItems: 'center',
							padding: Indent.XS.toNumber(),
							backgroundColor: withPressed(Color.bgSecondary.toHex()),
						},
					},
					icon && IconView({
						icon,
						size: 30,
						color: Color.base0,
					}),
					Text5({
						style: {
							marginTop: Indent.XS.toNumber(),
							textAlign: 'center',
						},
						color: Color.base1,
						numberOfLines: 2,
						ellipsize: 'end',
						text: title,
					}),
				),
				isShowDivider && this.#renderDivider(),
			);
		};

		#handleOnClick = ({ uid, actionId, tabId }) => () => {
			const emitter = EventEmitter.createWithUid(uid);
			const eventArgs = [{ actionId, tabId }];

			emitter.emit('DetailCard.FloatingMenu.Item::onRecentAction', eventArgs);
		};

		#renderDivider()
		{
			return View(
				{
					style: {
						alignSelf: 'center',
						paddingHorizontal: Indent.L.toNumber(),
					},
				},
				View({
					style: {
						width: 1,
						height: 60,
						backgroundColor: Color.bgSeparatorSecondary.toHex(),
					},
				}),
			);
		}
	}

	module.exports = {
		RecentGridView,
	};
});
