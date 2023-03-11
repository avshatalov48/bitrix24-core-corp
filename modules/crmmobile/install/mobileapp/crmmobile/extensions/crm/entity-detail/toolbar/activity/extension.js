/**
 * @module crm/entity-detail/toolbar/activity
 */
jn.define('crm/entity-detail/toolbar/activity', (require, exports, module) => {

	const { ActivityFactory } = require('crm/entity-detail/toolbar/activity/factory');
	const { ActivityToolbarModel } = require('crm/entity-detail/toolbar/activity/model');
	const { EventEmitter } = require('event-emitter');

	class ActivityToolbar extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				loaded: false,
			};

			const { detailCard } = props;

			this.detailCardUid = detailCard.uid;
			this.customEventEmitter = EventEmitter.createWithUid(this.detailCardUid);

			this.handleHide = this.hide.bind(this);
		}

		get topPaddingRef()
		{
			return BX.prop.get(this.props.detailCard, 'topPaddingRef', null);
		}

		componentDidMount()
		{
			this.customEventEmitter.on('Crm.Timeline::onTimelineRefresh', this.handleHide);
		}

		show(model, actionParams)
		{
			return new Promise((resolve, reject) => {
				this.setState(
					{
						loaded: true,
						model: new ActivityToolbarModel(model),
						actionParams,
					},
					() => {
						if (this.topPaddingRef && this.activityRef)
						{
							this.topPaddingRef.show();
							this.activityRef.show();
							resolve();
						}
						else
						{
							reject();
						}
					},
				);
			});
		}

		hide()
		{
			if (this.topPaddingRef && this.activityRef)
			{
				this.topPaddingRef.hide();
				this.activityRef.hide();
			}
		}

		render()
		{
			const { loaded, model, actionParams } = this.state;
			const { style, animation } = this.props;

			return View(
				{
					style: {
						flex: 1,
					},
					clickable: false,
				},
				loaded && ActivityFactory.make(
					model.getType(),
					{
						actionParams,
						model,
						style,
						animation,
						onHide: this.handleHide,
						ref: ref => this.activityRef = ref,
					},
				),
			);
		}
	}

	module.exports = { ActivityToolbar };
});