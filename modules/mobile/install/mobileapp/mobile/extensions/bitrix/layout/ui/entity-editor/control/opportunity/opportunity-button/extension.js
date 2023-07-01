/**
 * @module layout/ui/entity-editor/control/opportunity/opportunity-button
 */
jn.define('layout/ui/entity-editor/control/opportunity/opportunity-button', (require, exports, module) => {

	const { EventEmitter } = require('event-emitter');

	class OpportunityButton extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.uid = props.uid || Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);
		}

		render()
		{
			return View(
				{
					style: {
						flexShrink: 0,
						justifyContent: 'center',
					},
					onClick: () => this.customEventEmitter.emit('OpportunityButton::Click'),
				},
				View(
					{
						style: {
							flexShrink: 0,
							height: 28,
							borderColor: '#00a2e8',
							borderRadius: 6,
							borderWidth: 1,
							paddingHorizontal: 24,
							justifyContent: 'center',
							backgroundColor: { default: '#ffffff', pressed: '#99c3f0ff' },
						},
						onClick: () => this.customEventEmitter.emit('OpportunityButton::Click'),
					},
					Text({
							style: {
								color: '#333333',
								fontSize: 14,
							},
							text: this.props.text ? this.props.text : BX.message('MOBILE_LAYOUT_UI_FIELDS_MONEY_OPPORTUNITY_BUTTON_DEFAULT_TEXT'),
						},
					),
				),
			);
		}
	}

	module.exports = {
		OpportunityButton,
	};

});
