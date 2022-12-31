/**
 * @module crm/timeline/scheduler/providers/base
 */
jn.define('crm/timeline/scheduler/providers/base', (require, exports, module) => {

	/**
	 * @abstract
	 * @class TimelineSchedulerBaseProvider
	 */
	class TimelineSchedulerBaseProvider extends LayoutComponent
	{
		/**
		 * @abstract
		 * @return {string}
		 */
		static getId() {}

		/**
		 * @abstract
		 * @return {string}
		 */
		static getTitle() {}

		/**
		 * @abstract
		 * @return {string}
		 */
		static getMenuTitle() {}

		/**
		 * @abstract
		 * @return {boolean}
		 */
		static isSupported()
		{
			return false;
		}

		/**
		 * @return {boolean}
		 */
		static isAvailableInMenu()
		{
			return true;
		}

		/**
		 * @abstract
		 * @return {string}
		 */
		static getMenuIcon() {}

		/**
		 * @return {number}
		 */
		static getMenuPosition()
		{
			return 0;
		}

		/**
		 * @return {object}
		 */
		static getBackdropParams()
		{
			return {};
		}

		constructor(props)
		{
			super(props);

			if (!props.layout)
			{
				throw new Error(`'layout' property must be specified`);
			}

			if (!props.entity)
			{
				throw new Error(`'entity' property must be specified`);
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