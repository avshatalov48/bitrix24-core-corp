/**
 * @module crm/entity-tab/search/counter
 */
jn.define('crm/entity-tab/search/counter', (require, exports, module) => {
	const { BaseItem } = require('crm/entity-tab/search/base-item');
	const { Loc } = require('loc');

	const countersColors = {
		INCOMINGCHANNEL: {
			value: '#9dcf00',
			title: BX.message('M_CRM_ET_SEARCH_COUNTER_TYPE_FILTER_INCOMINGCHANNEL'),
			titleOtherUsers: BX.message('M_CRM_ET_SEARCH_COUNTER_TYPE_FILTER_INCOMINGCHANNEL_OTHER_USERS'),
		},
		CURRENT: {
			value: '#ff5752',
			title: BX.message('M_CRM_ET_SEARCH_COUNTER_TYPE_FILTER_CURRENT_2'),
			titleOtherUsers: BX.message('M_CRM_ET_SEARCH_COUNTER_TYPE_FILTER_CURRENT_2_OTHER_USERS'),
		},

		// @todo remove after creating view mode Activity in the mobile
		MY_PENDING: {
			value: '#2FC6F6',
			title: BX.message('M_CRM_ET_SEARCH_COUNTER_TYPE_FILTER_INCOMINGCHANNEL'),
			titleOtherUsers: BX.message('M_CRM_ET_SEARCH_COUNTER_TYPE_FILTER_INCOMINGCHANNEL_OTHER_USERS'),
		},
	};

	/**
	 * @class Counter
	 */
	class Counter extends BaseItem
	{
		constructor(props)
		{
			super(props);

			this.counter = {
				id: props.typeId,
				code: props.code,
				typeName: props.typeName,
				excludeUsers: props.excludeUsers || false,
			};
		}

		renderContent()
		{
			const { typeName } = this.props;

			const value = this.getCounterValue();

			// @todo remove after creating view mode Activity in the mobile
			const showValue = (this.counter.code !== 'my_pending');

			const content = [
				Text({
					style: this.styles.title,
					text: this.getTitle(),
					ellipsize: 'middle',
				}),
				showValue && Text({
					style: styles.value(typeName, parseInt(value, 10)),
					text: value,
				}),
			];

			if (this.props.active)
			{
				content.push(
					Image({
						style: this.styles.closeIcon,
						svg: {
							content: this.icon,
						},
					}),
				);
			}

			return content;
		}

		getButtonBackgroundColor()
		{
			return this.getCounterConfig().value;
		}

		getTitle()
		{
			// @todo remove after creating view mode Activity in the mobile
			if (this.counter.code === 'my_pending')
			{
				return Loc.getMessage('M_CRM_ET_SEARCH_COUNTER_TYPE_FILTER_MY_PENDING');
			}

			const config = this.getCounterConfig();
			return (this.counter.excludeUsers ? config.titleOtherUsers : config.title);
		}

		getCounterConfig()
		{
			return countersColors[this.counter.typeName];
		}

		getCounterValue()
		{
			if (this.props.value > 99)
			{
				return '99+';
			}

			return String(this.props.value);
		}

		getOnClickParams()
		{
			const params = super.getOnClickParams();
			params.counter = this.counter;

			return params;
		}
	}

	const styles = {
		value: (name, value) => {
			return {
				color: '#ffffff',
				borderRadius: 8,
				fontSize: 12,
				height: 17,
				backgroundColor: value ? countersColors[name].value : '#bdc1c6',
				marginLeft: 5,
				paddingHorizontal: Application.getPlatform() === 'android' ? 4 : 7,
				textAlign: 'center',
				fontWeight: '700',
			};
		},
	};

	module.exports = { Counter };
});
