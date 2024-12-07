/**
 * @module tasks/layout/task/create-new/priority
 */
jn.define('tasks/layout/task/create-new/priority', (require, exports, module) => {
	const { Haptics } = require('haptics');
	const { Color } = require('tokens');
	const { TaskPriority } = require('tasks/enum');

	class Priority extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				priority: Number(props.priority),
			};

			this.onChange = this.onChange.bind(this);
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				priority: Number(props.priority),
			};
		}

		render()
		{
			return View(
				{
					testId: `${this.props.testId}_ClickableContainer`,
					style: {
						position: 'absolute',
						right: 0,
						width: 48,
						height: 42,
						alignItems: 'center',
						justifyContent: 'center',
						...this.props.style,
					},
					onClick: this.onChange,
				},
				Image({
					testId: `${this.props.testId}_Icon_${this.isHighPriorityTask() ? 'high' : 'normal'}`,
					style: {
						width: 20,
						height: 20,
					},
					svg: {
						content: `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="21" viewBox="0 0 20 21" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.33984 14.0605C7.45367 12.306 8.37782 10.7087 9.83263 9.73477C9.92073 9.67579 10.0402 9.71268 10.0871 9.80773C10.6664 10.9796 11.2466 11.5715 11.709 12.0432C12.2718 12.6172 12.6602 13.0134 12.6602 14.0605C12.6586 14.8418 12.452 15.5974 12.0747 16.2584C11.9174 16.534 11.7304 16.7931 11.5163 17.0309C11.4715 17.0805 11.4256 17.1293 11.3785 17.177C11.2265 17.3312 11.0623 17.4752 10.8867 17.6074H11.5163C11.5163 17.6074 12.0933 17.4227 12.7978 16.9942C13.9023 16.3223 15.3203 15.0511 15.3203 12.9521C15.3203 10.4432 14.0168 8.97298 12.8191 7.62224C11.8439 6.52231 10.9389 5.50159 10.8651 4.06364C10.8508 3.78453 10.5623 3.58336 10.323 3.72772C8.05074 5.09844 4.67969 8.58278 4.67969 12.2871C4.72193 14.2134 5.713 15.9601 7.27206 16.9945C7.64515 17.242 8.05078 17.4487 8.48371 17.6074H9.11328C8.94807 17.4667 8.79299 17.3163 8.64862 17.1574C8.59199 17.0951 8.53701 17.0315 8.48371 16.9666C8.30505 16.7492 8.1453 16.5179 8.00577 16.2754C7.62 15.6048 7.3888 14.848 7.33984 14.0605ZM6.40561 14.7008C6.37524 14.51 6.35386 14.3171 6.34177 14.1226L6.33783 14.0592L6.34194 13.9958C6.47593 11.9305 7.56376 10.0503 9.27633 8.90379C9.90768 8.48112 10.6914 8.77348 10.9836 9.36462C11.4911 10.3913 11.9767 10.8871 12.4248 11.3448C12.4752 11.3963 12.5252 11.4473 12.5746 11.4985C12.8259 11.7587 13.1212 12.0805 13.3352 12.5136C13.5578 12.964 13.6602 13.4638 13.6602 14.0605V14.0625C13.6595 14.3944 13.6286 14.7226 13.5693 15.0434C14.0122 14.4968 14.3203 13.8112 14.3203 12.9521C14.3203 11.0528 13.4549 9.86748 12.4009 8.65983C12.3018 8.54622 12.1984 8.42976 12.0928 8.31071L12.0925 8.31043L12.0924 8.31037C11.6564 7.81901 11.1814 7.28382 10.8027 6.72483C10.4822 6.25178 10.2042 5.72748 10.0349 5.13203C9.18151 5.77545 8.24092 6.6746 7.44983 7.73153C6.39679 9.13845 5.68305 10.7238 5.6797 12.2761C5.70095 13.1544 5.96051 13.9872 6.40561 14.7008Z" fill="${this.getIconColor()}"/></svg>`,
					},
				}),
			);
		}

		onChange()
		{
			const priority = (this.isHighPriorityTask() ? TaskPriority.NORMAL : TaskPriority.HIGH);

			if (this.props.onChange)
			{
				this.props.onChange(priority);
			}

			this.setState({ priority });
			Haptics.impactLight();
		}

		getIconColor()
		{
			return (this.isHighPriorityTask() ? Color.accentMainWarning.toHex() : Color.base3.toHex());
		}

		isHighPriorityTask()
		{
			return (this.state.priority === TaskPriority.HIGH);
		}
	}

	module.exports = { Priority };
});
