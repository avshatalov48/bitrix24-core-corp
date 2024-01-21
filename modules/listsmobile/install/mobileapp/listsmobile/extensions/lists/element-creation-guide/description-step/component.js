/**
 * @module lists/element-creation-guide/description-step/component
 */
jn.define('lists/element-creation-guide/description-step/component', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { DescriptionStepView } = require('lists/element-creation-guide/description-step/view');
	const { EventEmitter } = require('event-emitter');
	const { NotifyManager } = require('notify-manager');

	class DescriptionStepComponent extends PureComponent
	{
		/**
		 * @param props
		 * @param {String} props.uid
		 * @param {String} props.iBlockId
		 * @param {String} props.name
		 * @param {String} props.formattedTime
		 * @param {Object} props.layout
		 */
		constructor(props)
		{
			super(props);

			this.state = { description: this.props.description === undefined ? null : this.props.description };
			this.sign = '';
			this.hasFieldsToRender = true;
			this.isConstantsTuned = false;
			this.isLoading = false;

			// eslint-disable-next-line no-undef
			this.uid = props.uid || Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);
		}

		get isLoaded()
		{
			return (this.state.description !== null);
		}

		loadDescription()
		{
			if (this.isLoaded || this.isLoading === true)
			{
				return;
			}

			this.isLoading = true;
			let description = '';
			let hasErrors = false;

			BX.ajax.runAction(
				'listsmobile.ElementCreationGuide.loadDescriptionStep',
				{
					data: {
						iBlockId: this.props.iBlockId || 0,
						elementId: 0,
					},
				},
			)
				.then((response) => {
					this.hasFieldsToRender = response.data.hasFieldsToRender;
					this.isConstantsTuned = response.data.isConstantsTuned;
					this.sign = response.data.signedIBlockIdAndElementId;
					description = response.data.description || '';
				})
				.catch((response) => {
					console.error(response.errors);
					if (Array.isArray(response.errors))
					{
						NotifyManager.showErrors(response.errors);
					}
					hasErrors = true;
				})
				.finally(() => {
					this.isLoading = false;

					this.setState({ description });

					this.customEventEmitter.emit(
						'DescriptionStepComponent:onAfterLoadDescription',
						[
							{
								description: this.state.description,
								hasFieldsToRender: this.hasFieldsToRender,
								sign: this.sign,
								isConstantsTuned: this.isConstantsTuned,
								hasErrors,
							},
						],
					);
				})
			;
		}

		render()
		{
			this.loadDescription();

			return new DescriptionStepView({
				uid: this.uid,
				iBlockId: this.props.iBlockId,
				name: this.props.name,
				formattedTime: this.props.formattedTime,
				description: this.state.description,
				isLoaded: this.isLoaded,
				layout: this.props.layout,
			});
		}
	}

	module.exports = { DescriptionStepComponent };
});
