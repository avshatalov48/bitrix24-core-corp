/**
 * @module crm/entity-detail/toolbar/activity
 */
jn.define('crm/entity-detail/toolbar/activity', (require, exports, module) => {

	const { ActivityFactory } = require('crm/entity-detail/toolbar/activity/factory');
	const { ActivityToolbarModel } = require('crm/entity-detail/toolbar/activity/model');
	const { NotifyManager } = require('notify-manager');
	const { EventEmitter } = require('event-emitter');

	class ActivityToolbar extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			const { detailCard } = props;

			this.state = {
				loaded: false,
			};

			this.topPaddingRef = detailCard.topPaddingRef;
			this.storage = Application.sharedStorage();
			this.detailCardUid = detailCard.uid;
			this.customEventEmitter = EventEmitter.createWithUid(this.detailCardUid);
		}

		componentDidMount()
		{
			this.customEventEmitter.on('Crm.Timeline::onTimelineRefresh', () => {
				this.hide();
			});
		}

		show(model, actionParams)
		{
			return new Promise((resolve) => {
				this.setState(
					{
						loaded: true,
						model: new ActivityToolbarModel(model),
						actionParams,
					},
					() => {
						this.topPaddingRef.show();
						this.activityRef.show();
						resolve();
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

		loadScheduled()
		{
			const { loaded } = this.state;
			const { entityTypeId, entityId } = this.props;

			if (loaded)
			{
				return Promise.resolve();
			}

			return new Promise((resolve, reject) => {
				BX.ajax.runAction('crmmobile.Timeline.loadScheduled', {
						json: {
							entityTypeId,
							entityId,
						},
					})
					.then(({ data }) => {
						if (!data)
						{
							reject();
							return;
						}

						this.setState(
							{
								loaded: true,
								model: new ActivityToolbarModel(data),
							},
							resolve,
						);
					})
					.catch(({ errors }) => {
						NotifyManager.showErrors(errors);
						reject();
					});
			});
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
				loaded &&  ActivityFactory.make(
					model.getType(),
					{
						actionParams,
						model,
						style,
						animation,
						onHide: this.hide.bind(this),
						ref: ref => this.activityRef = ref,
					},
				),
			);
		}
	}

	module.exports = { ActivityToolbar };
});