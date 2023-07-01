/**
 * @module layout/ui/detail-card/tabs/timeline
 */
jn.define('layout/ui/detail-card/tabs/timeline', (require, exports, module) => {

	const { Loc } = require('loc');
	const { Tab } = require('layout/ui/detail-card/tabs');
	const { TabType } = require('layout/ui/detail-card/tabs/factory/type');
	const { FloatingMenuItem } = require('layout/ui/detail-card/floating-button/menu/item');

	/** @var Timeline */
	let Timeline;

	try
	{
		Timeline = require('crm/timeline').Timeline;
	}
	catch (e)
	{
		console.warn(e);

		return;
	}

	/**
	 * @class TimelineTab
	 */
	class TimelineTab extends Tab
	{
		constructor(props)
		{
			super(props);

			/** @type {Timeline|null} */
			this.timelineRef = null;
		}

		getType()
		{
			return TabType.TIMELINE;
		}

		/**
		 * @returns {Promise.<Object>}
		 */
		getData()
		{
			return Promise.resolve({});
		}

		/**
		 * @returns {Promise.<boolean|Array>}
		 */
		validate()
		{
			return Promise.resolve(true);
		}

		/**
		 * @public
		 * @param {boolean} animate
		 */
		scrollTop(animate = true)
		{
			if (this.timelineRef)
			{
				this.timelineRef.scrollToTheTop(animate);
			}
		}

		/**
		 * @return {boolean}
		 */
		isReady()
		{
			return this.timelineRef instanceof Timeline;
		}

		/**
		 * @public
		 * @param {object} message
		 */
		processPushMessage(message)
		{
			if (this.timelineRef)
			{
				this.timelineRef.processPushMessage(message);
			}
		}

		renderResult()
		{
			const { onScroll, detailCard, externalFloatingButton } = this.props;

			return View(
				{
					style: {
						flexDirection: 'column',
						flexGrow: 1,
						backgroundColor: '#eef2f4',
					},
				},
				new Timeline({
					...this.state.result,
					ref: (ref) => this.timelineRef = ref,
					uid: this.uid,
					tabId: this.getId(),
					reloadFromProps: true,
					onScroll,
					detailCard,
					showFloatingButton: !externalFloatingButton,
				}),
			);
		}

		getFloatingMenuItems()
		{
			const floatingItemsContext = {
				detailCard: this.props.detailCard,
			}

			return [
				new FloatingMenuItem({
					id: TabType.TIMELINE,
					title: Loc.getMessage('MCRM_DETAIL_TIMELINE_MENU_TITLE'),
					isSupported: true,
					isAvailable: (detailCard) => !detailCard.isNewEntity() && !detailCard.isReadonly(),
					menuPosition: 200,
					nestedItems: Timeline.getFloatingMenuItems(floatingItemsContext),
					icon: '<svg width="31" height="32" viewBox="0 0 31 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5.31057 8.11735C5.31057 7.6297 5.70589 7.23438 6.19355 7.23438C6.6812 7.23438 7.07652 7.6297 7.07652 8.11735V9.16323C8.00473 9.51857 8.66382 10.4178 8.66382 11.471C8.66382 12.5242 8.00473 13.4234 7.07652 13.7787V16.8158C8.00473 17.1711 8.66382 18.0703 8.66382 19.1235C8.66382 20.1767 8.00473 21.0759 7.07652 21.4313V22.9934C7.07652 23.481 6.6812 23.8764 6.19355 23.8764C5.70589 23.8764 5.31057 23.481 5.31057 22.9934V21.4312C4.38242 21.0759 3.72339 20.1767 3.72339 19.1235C3.72339 18.0704 4.38242 17.1712 5.31057 16.8158V13.7787C4.38242 13.4233 3.72339 12.5241 3.72339 11.471C3.72339 10.4179 4.38242 9.51865 5.31057 9.16328V8.11735Z" fill="#767C87"/><path d="M11.9075 8.71889C11.2014 8.71889 10.629 9.29128 10.629 9.99737V12.9443C10.629 13.6503 11.2014 14.2227 11.9075 14.2227H24.6913C25.3973 14.2227 25.9697 13.6503 25.9697 12.9443V9.99737C25.9697 9.29128 25.3973 8.71889 24.6913 8.71889H11.9075Z" fill="#767C87"/><path d="M11.9075 16.3716C11.2014 16.3716 10.629 16.944 10.629 17.6501V20.597C10.629 21.303 11.2014 21.8754 11.9075 21.8754H17.6979C17.8485 19.5987 19.0407 17.6067 20.8024 16.3716H11.9075Z" fill="#767C87"/><path d="M23.9114 17.9879H26.082V21.2811H29.3754V23.4517H26.082V26.7452H23.9114V23.4517H20.6181V21.2811H23.9114V17.9879Z" fill="#767C87"/></svg>',
					tabId: TabType.TIMELINE,
				}),
			];
		}

		handleFloatingMenuAction({ actionId, tabId })
		{
			if (this.getId() !== tabId || !this.timelineRef)
			{
				return;
			}

			this.timelineRef.handleFloatingMenuAction(actionId);
		}
	}

	module.exports = { TimelineTab };
});
