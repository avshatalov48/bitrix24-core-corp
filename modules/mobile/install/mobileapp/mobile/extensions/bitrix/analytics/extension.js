/**
 * @module analytics
 */
jn.define('analytics', (require, exports, module) => {
	const { Type } = require('type');
	const { isValidAnalyticsData } = require('analytics/validator');
	const STATUSES = {
		SUCCESS: 'success',
		ERROR: 'error',
		ATTEMPT: 'attempt',
	};

	/**
	 * @class AnalyticsEvent
	 */
	class AnalyticsEvent
	{
		/**
		 * @param {AnalyticsDTO|AnalyticsEvent} [analyticsData]
		 */
		constructor(analyticsData)
		{
			this.data = {
				tool: null,
				category: null,
				event: null,
				type: null,
				c_section: null,
				c_sub_section: null,
				c_element: null,
				status: null,
				p1: null,
				p2: null,
				p3: null,
				p4: null,
				p5: null,
				...this.getDefaults(),
			};
			this.merge(analyticsData);
		}

		/**
		 * @protected
		 * @return {object}
		 */
		getDefaults()
		{
			return {};
		}

		/**
		 * @param {String} tool
		 */
		setTool(tool)
		{
			this.data.tool = tool;

			return this;
		}

		/**
		 * @return {String|null}
		 */
		getTool()
		{
			return this.data.tool;
		}

		/**
		 * @param {String} category
		 */
		setCategory(category)
		{
			this.data.category = category;

			return this;
		}

		/**
		 * @return {String|null}
		 */
		getCategory()
		{
			return this.data.category;
		}

		/**
		 * @param {String} event
		 */
		setEvent(event)
		{
			this.data.event = event;

			return this;
		}

		/**
		 * @return {String|null}
		 */
		getEvent()
		{
			return this.data.event;
		}

		/**
		 * @param {String} type
		 */
		setType(type)
		{
			this.data.type = type;

			return this;
		}

		/**
		 * @return {String|null}
		 */
		getType()
		{
			return this.data.type;
		}

		/**
		 * @param {String} section
		 */
		setSection(section)
		{
			this.data.c_section = section;

			return this;
		}

		/**
		 * @return {String|null}
		 */
		getSection()
		{
			return this.data.c_section;
		}

		/**
		 * @param {String} subSection
		 */
		setSubSection(subSection)
		{
			this.data.c_sub_section = subSection;

			return this;
		}

		/**
		 * @return {String|null}
		 */
		getSubSection()
		{
			return this.data.c_sub_section;
		}

		/**
		 * @param {String} element
		 */
		setElement(element)
		{
			this.data.c_element = element;

			return this;
		}

		/**
		 * @return {String|null}
		 */
		getElement()
		{
			return this.data.c_element;
		}

		/**
		 * @param {String} status
		 */
		setStatus(status)
		{
			this.data.status = status;

			return this;
		}

		/**
		 * @return {String|null}
		 */
		getStatus()
		{
			return this.data.status;
		}

		markAsSuccess()
		{
			this.data.status = STATUSES.SUCCESS;

			return this;
		}

		markAsError()
		{
			this.data.status = STATUSES.ERROR;

			return this;
		}

		markAsAttempt()
		{
			this.data.status = STATUSES.ATTEMPT;

			return this;
		}

		/**
		 * @param {String} p1
		 */
		setP1(p1)
		{
			this.data.p1 = p1;

			return this;
		}

		/**
		 * @return {String|null}
		 */
		getP1()
		{
			return this.data.p1;
		}

		/**
		 * @param {String} p2
		 */
		setP2(p2)
		{
			this.data.p2 = p2;

			return this;
		}

		/**
		 * @return {String|null}
		 */
		getP2()
		{
			return this.data.p2;
		}

		/**
		 * @param {String} p3
		 */
		setP3(p3)
		{
			this.data.p3 = p3;

			return this;
		}

		/**
		 * @return {String|null}
		 */
		getP3()
		{
			return this.data.p3;
		}

		/**
		 * @param {String} p4
		 */
		setP4(p4)
		{
			this.data.p4 = p4;

			return this;
		}

		/**
		 * @return {String|null}
		 */
		getP4()
		{
			return this.data.p4;
		}

		/**
		 * @param {String} p5
		 */
		setP5(p5)
		{
			this.data.p5 = p5;

			return this;
		}

		/**
		 * @return {String|null}
		 */
		getP5()
		{
			return this.data.p5;
		}

		/**
		 * @return {String}
		 */
		buildUrlByData()
		{
			const initialUrl = `${currentDomain}/_analytics/?`;
			const getParameters = [];

			Object.getOwnPropertyNames(this.data).forEach((prop) => {
				if (!Type.isNil(this.data[prop]))
				{
					getParameters.push(`st[${prop}]=${encodeURIComponent(this.data[prop])}`);
				}
			});

			return initialUrl + getParameters.join('&');
		}

		/**
		 * @return {Object}
		 */
		exportToObject()
		{
			const data = {};
			Object.getOwnPropertyNames(this.data).forEach((prop) => {
				if (!Type.isNil(this.data[prop]))
				{
					data[prop] = this.data[prop];
				}
			});

			return data;
		}

		send()
		{
			if (!isValidAnalyticsData(this.exportToObject()))
			{
				return;
			}

			if (Application.isBeta())
			{
				console.info('Sending analytics, v.2', this.exportToObject());
			}

			void BX.ajax({
				method: 'GET',
				url: this.buildUrlByData(),
			});
		}

		/**
		 * @param {AnalyticsDTO|AnalyticsEvent} analyticsData
		 */
		merge(analyticsData)
		{
			if (analyticsData && Type.isObject(analyticsData))
			{
				let data = null;
				if (analyticsData instanceof AnalyticsEvent)
				{
					data = analyticsData.exportToObject();
				}
				else if (analyticsData.data)
				{
					data = analyticsData.data;
				}
				else
				{
					data = analyticsData;
				}
				Object.getOwnPropertyNames(this.data).forEach((prop) => {
					if (!Type.isNil(data[prop]))
					{
						this.data[prop] = data[prop];
					}
				});
			}

			return this;
		}
	}

	module.exports = { AnalyticsEvent };
});
