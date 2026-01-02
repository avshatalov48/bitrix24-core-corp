/**
 * @module more-menu/list/utils
 */
jn.define('more-menu/list/utils', (require, exports, module) => {
	const { inAppUrl } = require('in-app-url');
	const { isEmpty } = require('utils/object');
	const { Type } = require('type');
	const { MoreMenuAnalytics } = require('more-menu/analytics');

	/**
	 * @param {array} sections
	 * @param {object} counters
	 * @return {array}
	 */
	const getUpdateSectionsWithCounters = (sections, counters) => {
		if (!Type.isArrayFilled(sections))
		{
			return [];
		}

		if (isEmpty(counters))
		{
			return sections;
		}

		return sections.map((section) => {
			if (section.hidden)
			{
				return section;
			}

			const updatedItems = section.items.map((item) => ({
				...item,
				counterValue: getCounterValue(counters, item),
			}));

			return {
				...section,
				items: updatedItems,
			};
		});
	};

	/**
	 * @param {array} sections
	 * @param {object} counters
	 * @return {number}
	 */
	const calculateTotalCounter = (sections, counters) => {
		if (!Type.isArrayFilled(sections) || isEmpty(counters))
		{
			return 0;
		}

		return (
			sections
				.filter((section) => !section.hidden)
				.flatMap((section) => section.items)
				.reduce((totalCounter, item) => totalCounter + getCounterValue(counters, item), 0)
		);
	};

	/**
	 * @param {object} counters
	 * @param {object} item
	 * @return {number}
	 */
	const getCounterValue = (counters, item) => {
		const counterName = item?.params?.counter;

		if (counterName && counters[counterName] !== undefined)
		{
			return counters[counterName];
		}

		return 0;
	};

	const handleItemClick = (item) => {
		if (!item)
		{
			return;
		}

		const { path, title, params = {} } = item;

		if (params?.analytics && MoreMenuAnalytics.isValidAnalyticsData(params.analytics))
		{
			new MoreMenuAnalytics(params.analytics).send();
		}

		if (path)
		{
			inAppUrl.open(path, { title, ...params });

			return;
		}

		if (params?.onclick)
		{
			// eslint-disable-next-line no-eval
			eval(params.onclick);

			return;
		}

		if (params?.url)
		{
			let backdrop = null;
			if (params?.backdrop && typeof params.backdrop === 'object')
			{
				if (Object.keys(params.backdrop).length > 0)
				{
					backdrop = params.backdrop;
				}
				else
				{
					backdrop = {};
				}
			}

			PageManager.openPage({
				url: params.url,
				useSearchBar: params?.useSearchBar,
				titleParams: { text: title, type: 'section' },
				cache: (params?.cache !== false),
				backdrop,
			});
		}
	};

	module.exports = {
		getUpdateSectionsWithCounters,
		calculateTotalCounter,
		handleItemClick,
	};
});
