/**
 * @module calendar/layout/dialog/sharing
 */
jn.define('calendar/layout/dialog/sharing', (require, exports, module) => {

	const { NotifyManager } = jn.require('notify-manager');
	const { CategorySvg } = jn.require('calendar/assets/category');
	const { ModelSharingStatus } = jn.require('calendar/model/sharing');
	const { SharingPanel } = jn.require('calendar/layout/sharing-panel');
	const { FadeLayout, FadeConfig } = jn.require('calendar/fade-layout');
	const { SharingSwitcher } = jn.require('calendar/layout/sharing-switcher');
	const { BooleanField, BooleanMode } = jn.require('layout/ui/fields/boolean');
	const { SharingEmptyState } = jn.require('calendar/layout/sharing-empty-state');

	const Status = {
		NONE: 'none',
		WAIT: 'wait',
	};

	/**
	 * @class Meetingslots
	 */
	class Sharing extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.sharing = this.props.sharing;

			this.state = {
				status: Status.WAIT,
				model: {
					...this.getSharing().getModel().getFieldsValues()
				},
			};
		}

		getSharing()
		{
			return this.props.sharing;
		}

		computedIsOn()
		{
			return {
				isOn: this.state.model.status === ModelSharingStatus.ENABLE,
			}
		}

		computedPublickUrl()
		{
			return {
				publicShortUrl: this.state.model.publicShortUrl,
			}
		}

		computedDidMount()
		{
			return {
				opacity: {
					stage1: this.getSharing().isOn()
						? FadeConfig.OPACITY_IN
						: FadeConfig.OPACITY_OUT,

					stage2: this.getSharing().isOn()
						? FadeConfig.OPACITY_OUT
						: FadeConfig.OPACITY_IN
				}
			}
		}

		setStateModel()
		{
			return new Promise((resolve) => {
				this.setState({
					model: {
						...this.getSharing().getModel().getFieldsValues()
					}
				}, () => resolve())
			})
		}

		componentDidMount()
		{
			this.setState({
				status: Status.NONE,
			})
		}

		handleAfterFade(status)
		{
			NotifyManager.showLoadingIndicator();
			(status === ModelSharingStatus.ENABLE ? this.getSharing().on() : this.getSharing().off())
				.then((response) => {
					if (response.errors && response.errors.length)
					{
						NotifyManager.showErrors(response.errors);
						NotifyManager.hideLoadingIndicator(true);
						return;
					}
					else
					{
						const fields = this.getSharing().resolveAjaxResponse(response);

						this.getSharing().getModel().setFields(fields);
						this.setStateModel().then(() => this.props.onSharing(fields))

						NotifyManager.hideLoadingIndicator(true);
					}
				})
		}

		fadeByStatus(status)
		{
			const stage1 = [];
			const stage2 = [];

			stage1.push({ref: this.block01_01});
			stage1.push({ref: this.block02_01});

			stage2.push({ref: this.block01_02});
			stage2.push({ref: this.block02_02});

			return FadeLayout.animate(status === ModelSharingStatus.DISABLE, stage1 , stage2)
		}

		render()
		{
			return View(
				{},
				this.state.status === Status.WAIT
					? new LoadingScreenComponent()
					: View(
						{
							style: {
								paddingTop: 22,
								backgroundColor: '#fff',
								borderRadius: 12,
								boxSizing: 'border-box',
							}
						},
						new SharingSwitcher({
							...this.computedIsOn(),
							setRefLayout1: (ref) => this.block01_01 = ref,
							setRefLayout2: (ref) => this.block01_02 = ref,
							layoutConfigDidMount: () => this.computedDidMount(),
							onChange: (status) => this.fadeByStatus(status)
								.then(() => this.handleAfterFade(status))

						}),
						View(
							{
								style: {
									minWidth: 123,
									height: 300,
									position: 'relative',
								}
							},
							View(
								{
									style: {
										flexShrink: 2,
										height: 1,
										backgroundColor: '#E3E4E4'
									}
								},
							),
							View(
								{
									style: {
										position: 'absolute',
										top: 0,
										left: 0,
										right: 0,
									}
								},
								new SharingEmptyState({
									...this.computedIsOn(),
									setRefLayout1: (ref) => this.block02_02 = ref,
									layoutConfigDidMount: () => this.computedDidMount(),
								}),
							),
							View(
								{
									style: {
										position: 'absolute',
										top: 0,
										left: 0,
										right: 0,
									}
								},
								new SharingPanel({
									...this.computedIsOn(),
									...this.computedPublickUrl(),
									setRefLayout1: (ref) => this.block02_01 = ref,
									layoutConfigDidMount: () => this.computedDidMount(),
								}),
							)
						),
					)
			)
		}
	}

	module.exports = { DialogSharing: Sharing, DialogStatus: Status };
});