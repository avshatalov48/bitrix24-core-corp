/**
 * @module layout/ui/detail-card/tabs/timeline
 */
jn.define('layout/ui/detail-card/tabs/timeline', (require, exports, module) => {

	const { Tab } = require('layout/ui/detail-card/tabs');
	const { TabType } = require('layout/ui/detail-card/tabs/factory/type');

	let Timeline;

	try
	{
		Timeline = jn.require('crm/timeline').Timeline;
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
					uid: this.uid,
					tabId: this.getId(),
					reloadFromProps: true,
					onScroll: this.props.onScroll,
					ref: (ref) => this.timelineRef = ref,
					detailCardRef: this.props.detailCardRef,
				}),
			);
		}
	}

	module.exports = { TimelineTab };
});
