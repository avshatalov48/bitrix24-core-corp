/**
 * @module layout/ui/detail-card/tabs/timeline
 */
jn.define('layout/ui/detail-card/tabs/timeline', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { Icon } = require('assets/icons');
	const { Tab } = require('layout/ui/detail-card/tabs');
	const { TabType } = require('layout/ui/detail-card/tabs/factory/type');
	const { FloatingMenuItem } = require('layout/ui/detail-card/floating-button/menu/item');

	/** @var Timeline */
	let Timeline = null;

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
						backgroundColor: AppTheme.colors.bgPrimary,
					},
				},
				new Timeline({
					...this.state.result,
					ref: (ref) => {
						this.timelineRef = ref;
					},
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
			};

			return [
				new FloatingMenuItem({
					id: TabType.TIMELINE,
					title: Loc.getMessage('MCRM_DETAIL_TIMELINE_MENU_TITLE'),
					isSupported: true,
					isAvailable: (detailCard) => !detailCard.isNewEntity() && !detailCard.isReadonly(),
					menuPosition: 200,
					nestedItems: Timeline.getFloatingMenuItems(floatingItemsContext),
					icon: Icon.ADD_TIMELINE,
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
