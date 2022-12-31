/**
 * @module crm/entity-detail/toolbar/activity/model
 */
jn.define('crm/entity-detail/toolbar/activity/model', (require, exports, module) => {

	const { get } = require('utils/object');

	/**
	 * @class ActivityToolbarModel
	 */
	class ActivityToolbarModel
	{
		constructor(props)
		{
			this.props = props;
		}

		getId()
		{
			return this.props.id;
		}

		getType()
		{
			return this.props.type;
		}

		getLayout()
		{
			return this.props.layout;
		}

		getHeader()
		{
			return this.getLayout().header;
		}

		getAction()
		{
			return this.getHeader().titleAction;
		}

		getComplete()
		{
			return get(
				this.getHeader(),
				['changeStreamButton', 'action'],
				null,
			);
		}

		getTitle()
		{
			return this.getHeader().title;
		}

		getSubTitle()
		{
			return get(
				this.getLayout().body,
				['blocks', 'lineTitle', 'properties', 'contentBlock', 'properties', 'value'],
				'',
			);
		}

		getClientName()
		{
			return get(
				this.getLayout().body,
				['blocks', 'client', 'properties', 'contentBlock',  'properties', 'text'],
				'',
			);
		}
	}

	module.exports = { ActivityToolbarModel };
});