(() => {

	const Status = {
		INITIAL: 'INITIAL',
		FETCHING: 'FETCHING',
		DONE: 'DONE'
	};

	/**
	 * @class LazyLoadWrapper
	 */
	class LazyLoadWrapper extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				status: Status.INITIAL,
				refresh: false,
				result: null
			};
		}

		renderLoader()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						flexGrow: 1,
					}
				},
				new LoadingScreenComponent()
			);
		}

		fetch()
		{
			if (this.state.status !== Status.INITIAL)
			{
				return;
			}

			if (this.props.endpoint)
			{
				this.setState({status: Status.FETCHING});

				BX.ajax.runAction(this.props.endpoint, {
					json: this.props.payload
				})
					.then((response) => {
						this.showErrors(response.errors);
						this.setResult(response.data)
							.then(() => {
								if(this.props.onContentLoaded)
								{
									this.props.onContentLoaded();
								}
							});
					})
					.catch((response) => {
						this.showErrors(response.errors);
					})
				;
			}
			else
			{
				this.showError('Endpoint not found');
			}
		}

		showErrors(errors)
		{
			if (errors && errors.length)
			{
				errors.forEach((error) => {
					this.showError(error.message);
				});
			}
		}

		showError(errorText)
		{
			if (errorText.length)
			{
				navigator.notification.alert(errorText, null, '');
			}
		}

		renderInitial()
		{
			return View(
				{
					style: {
						backgroundColor: '#F0F2F5',
						justifyContent: 'center',
						alignItems: 'center'
					}
				},
			);
		}

		renderContent()
		{
			if (this.state.status === Status.INITIAL)
			{
				return this.renderInitial();
			}
			else if (this.state.status === Status.FETCHING)
			{
				return this.renderLoader();
			}
			else if (this.state.status === Status.DONE)
			{
				return this.props.renderContent({...this.state.result}, this.state.refresh);
			}

			return null;
		}

		refreshResult()
		{
			return new Promise((resolve) => {
				if (this.state.status !== Status.DONE)
				{
					resolve();
					return;
				}

				this.setState({refresh: true}, () => {
					this.setState({refresh: false}, () => {
						resolve();
					})
				});
			});
		}

		setResult(result)
		{
			return new Promise((resolve) => {
				const state = {
					status: Status.DONE,
					refresh: true,
					result
				};

				this.setState(state, () => {
					this.setState({refresh: false}, () => {
						resolve();
					})
				});
			});
		}

		render()
		{
			return View(
				{},
				this.renderContent()
			);
		}
	}

	this.LazyLoadWrapper = LazyLoadWrapper;
})();
