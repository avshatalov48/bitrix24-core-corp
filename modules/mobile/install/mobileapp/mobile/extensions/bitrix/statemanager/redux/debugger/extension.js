/**
 * @module statemanager/redux/debugger
 */
jn.define('statemanager/redux/debugger', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { connect } = require('statemanager/redux/connect');

	class ReduxDebugger extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				expanded: false,
			};
		}

		render()
		{
			return View(
				{
					style: {
						position: 'absolute',
						bottom: this.state.expanded ? 0 : 14,
						left: this.state.expanded ? 0 : 14,
						height: this.state.expanded ? '100%' : 70,
						width: this.state.expanded ? '100%' : 70,
						backgroundColor: AppTheme.colors.bgContentPrimary,
					},
				},
				View(
					{},
					this.state.expanded && View(
						{},
						ScrollView(
							{
								style: {
									height: '100%',
									width: '100%',
								},
							},
							View(
								{
									style: {
										padding: 10,
									},
								},
								Text({ text: JSON.stringify(this.getPrintableData(), null, 2) }),
							),
						),
						View(
							{
								style: {
									position: 'absolute',
									right: 14,
									top: 14,
								},
								onClick: () => this.toggle(),
							},
							Image({
								svg: {
									content: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 21C16.9706 21 21 16.9706 21 12C21 7.02944 16.9706 3 12 3C7.02944 3 3 7.02944 3 12C3 16.9706 7.02944 21 12 21ZM16.2986 14.8663L13.433 12.0006L16.2986 9.135L14.8658 7.70218L12.0002 10.5678L9.13451 7.70218L7.70169 9.135L10.5673 12.0006L7.70169 14.8663L9.13451 16.2991L12.0002 13.4335L14.8658 16.2991L16.2986 14.8663Z" fill="#764abc"/></svg>',
								},
								style: {
									width: 32,
									height: 32,
								},
							}),
						),
						View(
							{
								style: {
									position: 'absolute',
									right: 14,
									top: 64,
								},
								onClick: () => this.logToConsole(),
							},
							Image({
								svg: {
									content: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.34204 4.05078C6.23747 4.05078 5.34204 4.94621 5.34204 6.05078V17.8984C5.34204 19.003 6.23747 19.8984 7.34204 19.8984H16.6389C17.7435 19.8984 18.6389 19.003 18.6389 17.8984L18.6389 9.52909C18.6389 8.99155 18.4225 8.47663 18.0385 8.10044L14.4881 4.62213C14.1143 4.2559 13.6118 4.05078 13.0885 4.05078H7.34204ZM7.46487 5.92285C7.29918 5.92285 7.16487 6.05717 7.16487 6.22285V17.694C7.16487 17.8597 7.29918 17.994 7.46487 17.994H16.4655C16.6312 17.994 16.7655 17.8597 16.7655 17.694V9.6771C16.7655 9.64553 16.7605 9.61447 16.751 9.58496H14.2048C13.6525 9.58496 13.2048 9.13725 13.2048 8.58496V5.94509C13.1692 5.93055 13.1307 5.92285 13.0915 5.92285H7.46487ZM9.05724 12.0007H14.9418C15.1014 12.0007 15.231 12.1284 15.231 12.2859V13.3121C15.231 13.4686 15.1014 13.5973 14.9418 13.5973H9.05724C8.89868 13.5973 8.76904 13.4686 8.76904 13.3121V12.2859C8.76904 12.1284 8.89868 12.0007 9.05724 12.0007ZM10.0385 10.4042H9.11608C8.92461 10.4042 8.76904 10.2506 8.76904 10.0611V9.14966C8.76904 8.96019 8.92461 8.80762 9.11608 8.80762H10.0385C10.23 8.80762 10.3845 8.96019 10.3845 9.14966V10.0611C10.3845 10.2506 10.23 10.4042 10.0385 10.4042ZM9.05724 14.9948H14.9418C15.1014 14.9948 15.231 15.1214 15.231 15.279V16.3061C15.231 16.4627 15.1014 16.5913 14.9418 16.5913H9.05724C8.89868 16.5913 8.76904 16.4627 8.76904 16.3061V15.279C8.76904 15.1214 8.89868 14.9948 9.05724 14.9948Z" fill="#764abc"/></svg>',
								},
								style: {
									width: 32,
									height: 32,
								},
							}),
						),
					),
					!this.state.expanded && View(
						{
							style: {
								borderWidth: 3,
								borderColor: AppTheme.colors.accentExtraPurple,
								borderRadius: 5,
							},
							onClick: () => this.toggle(),
						},
						Image({
							style: {
								width: 70,
								height: 70,
							},
							svg: {
								uri: 'https://d33wubrfki0l68.cloudfront.net/0834d0215db51e91525a25acf97433051f280f2f/c30f5/img/redux.svg',
							},
						}),
					),
				),
			);
		}

		toggle()
		{
			this.setState({ expanded: !this.state.expanded });
		}

		logToConsole()
		{
			// eslint-disable-next-line no-console
			console.log('? Redux debug');
			// eslint-disable-next-line no-console
			console.log(this.props.state);

			dialogs.showSnackbar({
				title: 'Logged to console',
				id: 'ReduxDebugLogToConsole',
				backgroundColor: AppTheme.colors.accentExtraPurple,
				textColor: AppTheme.colors.bgContentPrimary,
				hideOnTap: true,
				autoHide: true,
			}, () => {});
		}

		getPrintableData()
		{
			let state = {};

			if (this.props.slices && Array.isArray(this.props.slices))
			{
				const slices = Array.isArray(this.props.slices) ? this.props.slices : [this.props.slices];
				slices.forEach((slice) => {
					if (this.props.state[slice])
					{
						state[slice] = this.props.state[slice];
					}
				});
			}
			else
			{
				state = { ...this.props.state };
			}

			return {
				state,
				ownProps: this.props.ownProps,
			};
		}
	}

	const mapStateToProps = (state, ownProps) => ({ state, ownProps });

	module.exports = {
		ReduxDebugger: connect(mapStateToProps)(ReduxDebugger),
	};
});

