/**
 * @module more-menu/list
 */
jn.define('more-menu/list', (require, exports, module) => {
	const { Indent } = require('tokens');
	const { MenuList } = require('layout/ui/menu-list');
	const {
		getUpdateSectionsWithCounters,
		calculateTotalCounter,
		handleItemClick,
	} = require('more-menu/list/utils');
	const { isEqual } = require('utils/object');
	const { PropTypes } = require('utils/validation');

	/**
	 * @typedef {Object} MenuSection
	 * @property {string} id
	 * @property {string} code
	 * @property {string} title
	 * @property {number} sort
	 * @property {boolean} hidden
	 * @property {Array<MenuItem>} items
	 */

	/**
	 * @typedef {Object} MenuItem
	 * @property {string} id
	 * @property {string} imageName
	 * @property {string} path
	 * @property {string} title
	 * @property {number} sort
	 * @property {MenuItemParams} params
	 */

	/**
	 * @typedef {Object} MenuItemParams
	 * @property {string} counter
	 * @property {string} url
	 * @property {boolean} useSearchBar
	 * @property {boolean} cache
	 * @property {Object|boolean} backdrop
	 * @property {string} onclick
	 *
	 */
	/**
	 * @class List
	 */
	class List extends LayoutComponent
	{
		/**
		 * @param {Object} props
		 * @param {Array<MenuSection>} props.menuList
		 * @param {Object} props.counters
		 * @param {function} [props.onBeforeItemClick]
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

		get testId()
		{
			return 'more-menu-list';
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
			BX.removeEventListener('onPullEvent-main', this.subscribeToPullEvent);
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
			return View(
				{
					style: {
						paddingVertical: Indent.XS.toNumber(),
						paddingHorizontal: Indent.M.toNumber(),
					},
				},
				new MenuList({
					testId: this.testId,
					structure: this.state.sections,
					onItemClick: this.#onItemClick,
					highlightOnPress: true,
				}),
			);
		}

		#onItemClick = (item) => {
			if (this.props.onBeforeItemClick)
			{
				this.props.onBeforeItemClick();
			}

			handleItemClick(item);
		};
	}

	List.propTypes = {
		menuList: PropTypes.arrayOf(PropTypes.shape({
			id: PropTypes.string,
			code: PropTypes.string,
			title: PropTypes.string,
			sort: PropTypes.number,
			hidden: PropTypes.bool,
			items: PropTypes.arrayOf(PropTypes.shape({
				id: PropTypes.string,
				imageName: PropTypes.string,
				path: PropTypes.string,
				title: PropTypes.string,
				sort: PropTypes.number,
				params: PropTypes.oneOfType([
					PropTypes.shape({
						counter: PropTypes.string,
						url: PropTypes.string,
						useSearchBar: PropTypes.bool,
						cache: PropTypes.bool,
						backdrop: PropTypes.oneOfType([PropTypes.object, PropTypes.bool]),
						onclick: PropTypes.string,
					}),
					PropTypes.object,
				]),
			})),
		})),
		counters: PropTypes.object,
		onBeforeItemClick: PropTypes.func,
	};

	module.exports = { List };
});
