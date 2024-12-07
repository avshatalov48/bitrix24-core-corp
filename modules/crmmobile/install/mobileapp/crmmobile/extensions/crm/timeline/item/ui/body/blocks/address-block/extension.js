/**
 * @module crm/timeline/item/ui/body/blocks/address-block
 */
jn.define('crm/timeline/item/ui/body/blocks/address-block', (require, exports, module) => {
	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');
	const { TimelineFontSize, TimelineFontColor, TimelineFontWeight } = require('crm/timeline/item/ui/styles');

	/**
	 * @class TimelineItemBodyAddressBlock
	 */
	class TimelineItemBodyAddressBlock extends TimelineItemBodyBlock
	{
		render()
		{
			return Text({
				text: this.getValue(),
				style: {
					fontSize: TimelineFontSize.get(this.props.size),
					color: TimelineFontColor.get(this.props.color),
					fontWeight: TimelineFontWeight.get(this.props.weight),
				},
			});
		}

		getValue()
		{
			const { addressFormatted } = this.props;

			return String(addressFormatted);
		}
	}

	module.exports = { TimelineItemBodyAddressBlock };
});
