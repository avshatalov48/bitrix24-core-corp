/**
 * @module crm/timeline/scheduler/providers/base
 */
jn.define('crm/timeline/scheduler/providers/base', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');

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
		 * @return {string}
		 */
		static getMenuIcon()
		{}

		/**
		 * @return {number}
		 */
		static getMenuPosition()
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
			return [];
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
				backgroundColor: '#eef2f4',
				backdrop: {
					onlyMediumPosition: false,
					showOnTop: true,
					forceDismissOnSwipeDown: true,
					mediumPositionPercent: 50,
					navigationBarColor: '#EEF2F4',
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
			});
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

		close()
		{
			this.layout.close();
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
