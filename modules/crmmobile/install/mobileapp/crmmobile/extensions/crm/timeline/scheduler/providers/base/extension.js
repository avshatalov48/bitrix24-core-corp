/**
 * @module crm/timeline/scheduler/providers/base
 */
jn.define('crm/timeline/scheduler/providers/base', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { PureComponent } = require('layout/pure-component');
	const { Loc } = require('loc');
	const { Type } = require('type');

	/**
	 * @abstract
	 * @class TimelineSchedulerBaseProvider
	 */
	class TimelineSchedulerBaseProvider extends PureComponent
	{
		/**
		 * @abstract
		 * @return {string}
		 */
		static getId()
		{}

		/**
		 * @abstract
		 * @return {string}
		 */
		static getTitle()
		{}

		/**
		 * @abstract
		 * @return {string}
		 */
		static getMenuTitle()
		{}

		/**
		 * @return {string|null}
		 */
		static getMenuSubtitle()
		{
			return null;
		}

		/**
		 * @return {string|null}
		 */
		static getMenuSubtitleType()
		{
			return null;
		}

		/**
		 * @abstract
		 * @return {string}
		 */
		static getMenuShortTitle()
		{}

		/**
		 * @abstract
		 * @param {object} context
		 * @return {boolean}
		 */
		static isSupported(context = {})
		{
			return false;
		}

		/**
		 * @param {object} context
		 * @return {boolean}
		 */
		static isAvailableInMenu(context = {})
		{
			return true;
		}

		/**
		 * @abstract
		 * @return {Icon}
		 */
		static getMenuIcon()
		{}

		/**
		 * @return {number}
		 */

		static getMenuPosition(position)
		{
			if (Type.isNumber(position))
			{
				return position;
			}

			return this.getDefaultPosition();
		}

		static getDefaultPosition()
		{
			return Infinity;
		}

		/**
		 * @return {object}
		 */
		static getBackdropParams()
		{
			return {};
		}

		/**
		 * @return {object[]}
		 */
		static getMenuBadges()
		{
			if (this.shouldShowIsNewBadge())
			{
				return this.getIsNewBadge();
			}

			return [];
		}

		/**
		 * Returns true if the "New" badge should be shown.
		 * @return {boolean}
		 */
		static shouldShowIsNewBadge()
		{
			const isNewBadgeTimeEnd = this.getIsNewBadgeTimeEnd();
			if (isNewBadgeTimeEnd !== null)
			{
				return Date.now() < isNewBadgeTimeEnd.getTime();
			}

			return false;
		}

		/**
		 * Returns time in milliseconds when the "New" badge should be hidden.
		 * Override this method to enable "New" badge logic.
		 * @return {Date|null}
		 */
		static getIsNewBadgeTimeEnd()
		{
			return null;
		}

		/**
		 * Returns "New" badge data.
		 * @return {[{backgroundColor: *, color: *, title: ?string}]}
		 */
		static getIsNewBadge()
		{
			return [
				{
					title: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_BADGE_NEW_TITLE'),
					backgroundColor: AppTheme.colors.accentBrandBlue,
					color: AppTheme.colors.baseWhiteFixed,
				},
			];
		}

		/**
		 * You can override this method to implement any custom logic,
		 * for example open other component, browser, show confirm, etc.
		 *
		 * @public
		 * @param {TimelineScheduler} scheduler
		 * @param {object} context
		 */
		static open({ scheduler, context = {} })
		{
			const { parentWidget, entity, user, onActivityCreate, onClose, onCancel, onSkip } = scheduler;

			parentWidget.openWidget('layout', {
				modal: true,
				backgroundColor: AppTheme.colors.bgSecondary,
				backdrop: {
					onlyMediumPosition: false,
					showOnTop: true,
					forceDismissOnSwipeDown: true,
					mediumPositionPercent: 50,
					navigationBarColor: AppTheme.colors.bgSecondary,
					swipeAllowed: true,
					swipeContentAllowed: false,
					horizontalSwipeAllowed: false,
					...this.getBackdropParams(),
				},
			}).then((widget) => {
				widget.setTitle({ text: this.getTitle() });
				widget.enableNavigationBarBorder(false);
				widget.showComponent(new this({
					context,
					entity,
					user,
					onActivityCreate,
					onClose,
					onCancel,
					onSkip,
					layout: widget,
				}));
			}).catch(console.error);
		}

		constructor(props)
		{
			super(props);

			if (!props.layout)
			{
				throw new Error('\'layout\' property must be specified');
			}

			if (!props.entity)
			{
				throw new Error('\'entity\' property must be specified');
			}

			this.layout = props.layout;

			/** @type {TimelineEntityProps} */
			this.entity = props.entity;

			this.activityCreated = false;

			/** @type {object} */
			this.context = BX.prop.getObject(props, 'context', {});
		}

		componentDidMount()
		{
			let hiddenAlreadyFired = false;

			this.layout.setListener((eventName) => {
				if (!hiddenAlreadyFired && (eventName === 'onViewWillHidden' || eventName === 'onViewRemoved'))
				{
					hiddenAlreadyFired = true;

					if (!this.activityCreated && this.props.onCancel)
					{
						this.props.onCancel(this);
					}

					if (this.props.onClose)
					{
						this.props.onClose(this);
					}
				}
			});
		}

		close(callback = () => {})
		{
			this.layout.close(callback);
		}

		onActivityCreate(data)
		{
			this.activityCreated = true;

			if (this.props.onActivityCreate)
			{
				this.props.onActivityCreate(this, data);
			}
		}
	}

	module.exports = { TimelineSchedulerBaseProvider };
});
