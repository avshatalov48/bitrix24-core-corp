/**
 * @module more-menu/block/tools-list
 */
jn.define('more-menu/block/tools-list', (require, exports, module) => {
	const { isEqual } = require('utils/object');
	const {
		handleItemClick,
		getUpdateSectionsWithCounters,
		calculateTotalCounter,
	} = require('more-menu/utils');
	const { List } = require('more-menu/ui/list');
	const { PropTypes } = require('utils/validation');

	/**
	 * @class ToolsList
	 */
	class ToolsList extends LayoutComponent
	{
		/**
		 * @param {Object} props
		 * @param {Array<MenuSection>} props.menuList
		 * @param {Object} props.counters
		 * @param {function} [props.onBeforeItemClick]
		 * @param {Object} [props.testId]
		 */
		constructor(props)
		{
			super(props);

			this.state = {
				sections: getUpdateSectionsWithCounters(props.menuList, props.counters),
			};

			this.currentCounters = {};

			this.subscribeToUserCounters = this.subscribeToUserCounters.bind(this);
			this.subscribeToPullEvent = this.subscribeToPullEvent.bind(this);
		}

		componentDidMount()
		{
			if (!this.props.counters)
			{
				this.initCounters();
			}

			this.subscribeToCounterChange();
		}

		componentWillUnmount()
		{
			BX.removeCustomEvent('onUpdateUserCounters', this.subscribeToUserCounters);
			BX.removeCustomEvent('onPullEvent-main', this.subscribeToPullEvent);
		}

		subscribeToCounterChange()
		{
			BX.addCustomEvent('onPullEvent-main', this.subscribeToPullEvent);
			BX.addCustomEvent('onUpdateUserCounters', this.subscribeToUserCounters);
		}

		subscribeToUserCounters(counters)
		{
			if (counters[env.siteId])
			{
				this.updateCounters(counters[env.siteId]);
			}
		}

		subscribeToPullEvent(command, params)
		{
			if (command === 'user_counter' && params[env.siteId])
			{
				this.updateCounters(params[env.siteId]);
			}
		}

		componentWillReceiveProps(nextProps)
		{
			super.componentWillReceiveProps(nextProps);
			const menuList = nextProps.menuList;
			if (
				Array.isArray(menuList)
				&& !isEqual(this.props.menuList, menuList)
			)
			{
				this.state.sections = getUpdateSectionsWithCounters(menuList, this.currentCounters);
			}
		}

		initCounters()
		{
			const cachedCounters = Application.sharedStorage().get('userCounters');
			try
			{
				const counters = cachedCounters ? JSON.parse(cachedCounters) : {};
				this.currentCounters = counters[env.siteId];

				if (this.currentCounters)
				{
					this.updateCounters(this.currentCounters);
				}
			}
			catch (e)
			{
				console.error(e);
			}
		}

		updateCounters(currentCounters)
		{
			this.currentCounters = {
				...this.currentCounters,
				...currentCounters,
			};

			const updatedSections = getUpdateSectionsWithCounters(this.state.sections, this.currentCounters);
			const totalCounter = calculateTotalCounter(updatedSections, this.currentCounters);

			this.setState({
				sections: updatedSections,
			}, () => {
				Application.setBadges({ more: totalCounter });
			});
		}

		render()
		{
			return new List({
				testId: this.props.testId,
				structure: this.state.sections,
				onItemClick: this.onItemClick,
			});
		}

		onItemClick = (item) => {
			if (this.props.onBeforeItemClick)
			{
				this.props.onBeforeItemClick();
			}

			handleItemClick(item);
		};
	}

	ToolsList.propTypes = {
		menuList: PropTypes.array.isRequired,
		counters: PropTypes.object,
		onBeforeItemClick: PropTypes.func,
		testId: PropTypes.string.isRequired,
	};

	module.exports = {
		ToolsList,
	};
});
