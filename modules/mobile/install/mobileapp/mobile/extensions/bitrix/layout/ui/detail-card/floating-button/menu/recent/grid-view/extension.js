/**
 * @module layout/ui/detail-card/floating-button/menu/recent/grid-view
 */
jn.define('layout/ui/detail-card/floating-button/menu/recent/grid-view', (require, exports, module) => {
	const { EventEmitter } = require('event-emitter');
	const { withPressed } = require('utils/color');
	const { changeFillColor } = require('utils/svg');
	const { AppTheme } = require('apptheme/extended');

	const TINT_COLOR = AppTheme.colors.accentMainPrimaryalt;

	/**
	 * @constructor
	 * @param {DetailCardComponent} detailCard
	 * @param {FloatingMenuItem[]} items
	 * @return {View|null}
	 */
	function RecentGridView(detailCard, items)
	{
		items = items.map((item) => ({
			key: `${item.getId()}/${item.getTabId()}`,
			actionId: item.getId(),
			tabId: item.getTabId(),
			title: item.getShortTitle() || item.getTitle(),
			svgIcon: item.getIcon(),
			uid: detailCard.uid,
			type: 'default',
			section: 0,
		}));

		return GridView({
			style: {
				flex: 1,
				marginTop: 22,
				paddingLeft: 2,
				paddingRight: 2,
				backgroundColor: AppTheme.colors.bgSecondary,
			},
			data: [{ items }],
			params: {
				orientation: 'horizontal',
				rows: 1,
			},
			renderItem: RecentGridViewItem,
		});
	}

	/**
	 * @constructor
	 * @param {string} title
	 * @param {string} uid
	 * @param {string} key
	 * @param {string} svgIcon
	 * @param {string} actionId
	 * @param {?string} tabId
	 * @return {View}
	 */
	function RecentGridViewItem({ title, uid, key, svgIcon, actionId, tabId })
	{
		const emitter = EventEmitter.createWithUid(uid);
		const eventArgs = [{ actionId, tabId }];

		if (Application.getPlatform() === 'android')
		{
			svgIcon = changeFillColor(svgIcon, TINT_COLOR);
		}

		const onClick = () => emitter.emit('DetailCard.FloatingMenu.Item::onRecentAction', eventArgs);

		return View(
			{
				testId: `recent-item-${tabId || 'root'}-${actionId}`,
				onClick,
			},
			View(
				{
					style: {
						width: 90,
						paddingHorizontal: 4,
					},
				},
				Shadow(
					{
						style: {
							borderRadius: 12,
							width: 60,
							height: 60,
							justifyContent: 'center',
							alignSelf: 'center',
						},
						color: AppTheme.colors.shadowPrimary,
						radius: 1,
						offset: {
							y: 1,
						},
						inset: {
							left: 1,
							right: 1,
						},
					},
					View(
						{
							style: {
								backgroundColor: withPressed(AppTheme.colors.bgContentPrimary),
								borderRadius: 12,
								width: 60,
								height: 60,
								justifyContent: 'center',
								alignSelf: 'center',
							},
							onClick,
						},
						Image({
							style: {
								alignSelf: 'center',
								width: 38,
								height: 38,
							},
							tintColor: TINT_COLOR,
							resizeMode: 'contain',
							svg: {
								content: svgIcon,
							},
						}),
					),
				),
				Text({
					style: {
						width: '100%',
						color: AppTheme.colors.base1,
						fontSize: 11,
						textAlign: 'center',
						marginTop: 5,
						marginLeft: 1,
					},
					numberOfLines: 1,
					ellipsize: 'end',
					text: title,
				}),
			),
		);
	}

	module.exports = { RecentGridView };
});
