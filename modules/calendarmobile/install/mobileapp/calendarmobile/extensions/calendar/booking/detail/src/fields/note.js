/**
 * @module calendar/booking/detail/fields/note
 */
jn.define('calendar/booking/detail/fields/note', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { Indent, Color } = require('tokens');

	const { Text4 } = require('ui-system/typography/text');

	class NoteField extends PureComponent
	{
		/** @return {BookingModel} */
		get bookingInfo()
		{
			return this.props.value;
		}

		getId()
		{
			return this.props.id;
		}

		isReadOnly()
		{
			return this.props.readOnly;
		}

		isRequired()
		{
			return false;
		}

		isEmpty()
		{
			return this.bookingInfo.getNote().length === 0;
		}

		render()
		{
			return View(
				{
					style: {
						backgroundColor: Color.accentSoftOrange3.toHex(),
						borderColor: Color.accentSoftOrange2.toHex(),
						borderWidth: 1,
						borderRadius: 12,
						paddingHorizontal: Indent.XL.toNumber(),
						paddingVertical: Indent.M.toNumber(),
					},
				},
				Text4({
					text: this.bookingInfo.getNote(),
					color: Color.base2,
				}),
			);
		}
	}

	module.exports = {
		NoteField: (props) => new NoteField(props),
	};
});
