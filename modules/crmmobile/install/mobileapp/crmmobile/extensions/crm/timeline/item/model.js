/**
 * @module crm/timeline/item/model
 */
jn.define('crm/timeline/item/model', (require, exports, module) => {
	const { SupportedTypes } = require('crm/timeline/item/factory');
	const { Type } = require('crm/type');
	const { Moment } = require('utils/date');
	const { get } = require('utils/object');
	const { isValidDateObject } = require('utils/type');

	const CounterTypes = {
		DANGER: 'danger',
		SUCCESS: 'success',
	};

	/**
	 * @class TimelineItemModel
	 */
	class TimelineItemModel
	{
		constructor(props = {})
		{
			/** @type TimelineItemProps */
			this.props = props;

			/** @type boolean */
			this.isPinned = BX.prop.getBoolean(props, 'isPinned', false);

			/** @type boolean */
			this.isScheduled = BX.prop.getBoolean(props, 'isScheduled', false);

			/** @type {boolean} */
			this.isEditable = BX.prop.getBoolean(props, 'isEditable', false);

			/** @type boolean */
			this.needShowMarketBanner = BX.prop.getBoolean(props, 'showMarketBanner', false);
		}

		/**
		 * @return {Moment|null}
		 */
		get deadline()
		{
			if (this.props.hasOwnProperty('timestamp'))
			{
				return this.props.timestamp ? Moment.createFromTimestamp(this.props.timestamp) : null;
			}

			if (this.props.hasOwnProperty('CREATED_SERVER'))
			{
				const isoString = this.props.CREATED_SERVER.replace(' ', 'T');
				const date = new Date(isoString);

				return isValidDateObject(date) ? new Moment(date) : null;
			}

			return null;
		}

		/**
		 * @return {number|string}
		 */
		get id()
		{
			if (this.isCompatible)
			{
				if (this.isScheduled)
				{
					const entityTypeId = BX.prop.getInteger(this.props, 'ASSOCIATED_ENTITY_TYPE_ID', 0);
					const entityId = BX.prop.getInteger(this.props, 'ASSOCIATED_ENTITY_ID', 0);

					return `${Type.resolveNameById(entityTypeId)}_${entityId}`;
				}

				return BX.prop.getInteger(this.props, 'ID', 0);
			}

			return this.props.id;
		}

		/**
		 * @return {string}
		 */
		get type()
		{
			return this.props.type;
		}

		/**
		 * @return {number[]}
		 */
		get sort()
		{
			return this.props.sort || [];
		}

		/**
		 * @return {TimelineLayoutSchema|{}}
		 */
		get layout()
		{
			return this.props.layout || {};
		}

		/**
		 * @return {boolean}
		 */
		get isCompatible()
		{
			return !SupportedTypes.includes(this.type);
		}

		/**
		 * @return {boolean}
		 */
		get hasLowPriority()
		{
			return BX.prop.getBoolean(this.layout, 'isLogMessage', false);
		}

		/**
		 * @return {boolean}
		 */
		get needsAttention()
		{
			const counterType = get(this.layout, 'icon.counterType', null);

			return counterType === CounterTypes.DANGER;
		}

		/**
		 * @return {boolean}
		 */
		get isIncomingChannel()
		{
			const counterType = get(this.layout, 'icon.counterType', null);

			return counterType === CounterTypes.SUCCESS;
		}

		/**
		 * @return {boolean}
		 */
		get isReadonly()
		{
			return !this.isEditable;
		}
	}

	module.exports = { TimelineItemModel };
});
