/**
 * @module layout/ui/warning-block
 */
jn.define('layout/ui/warning-block', (require, exports, module) => {
	const BlockType = {
		warning: 'warning',
		info: 'info',
	};

	/**
	 * @class WarningBlock
	 */
	class WarningBlock extends LayoutComponent
	{
		get type()
		{
			return this.props.type || BlockType.warning;
		}

		getTypeBackground()
		{
			if (this.type === BlockType.info)
			{
				return '#c3f0ff';
			}

			return '#fef3b8';
		}

		getIconContent()
		{
			if (this.type === BlockType.info)
			{
				return Icons.infoIcon;
			}

			return Icons.warningIcon;
		}

		render()
		{
			return View(
				{
					style: styles.container(this.getTypeBackground()),
				},
				this.renderIcon(),
				this.renderText(),
				this.renderToWebIcon(),
			);
		}

		renderIcon()
		{
			return View(
				{
					style: styles.warningIconContainer,
				},
				Image({
					style: styles.warningIcon,
					svg: {
						content: this.getIconContent(),
					},
				}),
			);
		}

		renderText()
		{
			const { title, description } = this.props;

			return View(
				{
					style: styles.rightContainer,
				},
				title && Text({
					style: styles.title,
					text: title,
				}),
				description && Text({
					style: styles.description,
					text: description,
				}),
			);
		}

		renderToWebIcon()
		{
			const { redirectUrl, redirectTitle } = this.props;
			if (!redirectUrl)
			{
				return null;
			}

			return View(
				{
					style: styles.toWebIconContainer,
				},
				Image({
					style: styles.toWebIcon,
					svg: {
						content: Icons.webIcon,
					},
					onClick: () => {
						qrauth.open({
							title: redirectTitle,
							redirectUrl,
							layout: this.props.layout || layout,
						});
					},
				}),
			);
		}
	}

	const Icons = {
		warningIcon: `<svg width="19" height="19" viewBox="0 0 19 19" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M18.6666 9.66695C18.6666 14.6375 14.6372 18.667 9.66666 18.667C4.69606 18.667 0.666626 14.6375 0.666626 9.66695C0.666626 4.69643 4.69606 0.666992 9.66666 0.666992C14.6372 0.666992 18.6666 4.69643 18.6666 9.66695ZM8.53478 5.66947C8.53478 5.09857 8.99147 4.64184 9.56242 4.64184H9.70195C10.2729 4.64184 10.7296 5.09857 10.7296 5.66947V9.51363C10.7296 10.0845 10.2729 10.5413 9.70195 10.5413H9.56242C8.99147 10.5413 8.53478 10.0845 8.53478 9.51363V5.66947ZM9.6385 14.4227C10.349 14.4227 10.9326 13.8391 10.9326 13.1286C10.9326 12.4181 10.349 11.8346 9.6385 11.8346C8.92802 11.8346 8.34443 12.4181 8.34443 13.1286C8.34443 13.8391 8.92802 14.4227 9.6385 14.4227Z" fill="#C48300"/></svg>`,
		infoIcon: '<svg width="19" height="19" viewBox="0 0 19 19" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8.28852 4.70169H11.0261L10.7102 10.6155H8.60439L8.28852 4.70169Z" fill="#525C69"/><path d="M9.65703 14.7523C10.5862 14.7523 11.3394 13.9991 11.3394 13.07C11.3394 12.1409 10.5862 11.3877 9.65703 11.3877C8.7279 11.3877 7.9747 12.1409 7.9747 13.07C7.9747 13.9991 8.7279 14.7523 9.65703 14.7523Z" fill="#525C69"/><path fill-rule="evenodd" clip-rule="evenodd" d="M9.64813 18.6312C14.6092 18.6312 18.631 14.6095 18.631 9.64837C18.631 4.68728 14.6092 0.665527 9.64813 0.665527C4.68704 0.665527 0.665283 4.68728 0.665283 9.64837C0.665283 14.6095 4.68704 18.6312 9.64813 18.6312ZM9.64813 16.4972C13.4306 16.4972 16.497 13.4309 16.497 9.64838C16.497 5.86588 13.4306 2.79955 9.64813 2.79955C5.86563 2.79955 2.7993 5.86588 2.7993 9.64838C2.7993 13.4309 5.86563 16.4972 9.64813 16.4972Z" fill="#525C69"/></svg>',
		webIcon: `<svg width="15" height="14" viewBox="0 0 15 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M5.44083 2.46983V0H2.83333C1.54467 0 0.5 1.04467 0.5 2.33333V11.6667C0.5 12.9553 1.54467 14 2.83333 14H12.1667C13.4553 14 14.5 12.9553 14.5 11.6667V9.058H12.029L12.0294 10.3627L12.0216 10.4988C11.9542 11.079 11.4611 11.5294 10.8627 11.5294H4.13725L4.0012 11.5216C3.42097 11.4542 2.97059 10.9611 2.97059 10.3627V3.63725L2.97844 3.5012C3.04583 2.92097 3.53895 2.47059 4.13725 2.47059L5.44083 2.46983Z" fill="#828B95"/><path d="M14.5 0.4V6.68753C14.5 6.95479 14.1769 7.08865 13.9879 6.89967L11.6964 4.60833L8.46504 7.84044C8.34789 7.95761 8.15792 7.95763 8.04076 7.84046L6.71804 6.51775C6.6009 6.4006 6.60088 6.21067 6.71801 6.09351L9.94994 2.86067L7.60061 0.512169C7.41157 0.323195 7.54541 0 7.81271 0H14.1C14.3209 0 14.5 0.179086 14.5 0.4Z" fill="#828B95"/></svg>`,
	};

	const styles = {
		container: (backgroundColor) => ({
			borderRadius: 12,
			backgroundColor,
			paddingVertical: 15,
			paddingHorizontal: 18,
			flexDirection: 'row',
			alignItems: 'center',
		}),
		warningIconContainer: {
			marginRight: 12,
			padding: 5,
		},
		warningIcon: {
			width: 19,
			height: 19,
		},
		toWebIconContainer: {
			marginLeft: 18,
		},
		toWebIcon: {
			width: 15,
			height: 14,
		},
		title: {
			fontSize: 16,
			fontWeight: '500',
			marginBottom: 2,
		},
		description: {
			fontSize: 13,
			lineHeight: 16,
			color: '#525c69',
		},
		rightContainer: {
			flexDirection: 'column',
			flex: 1,
		},
		text: {
			fontSize: 14,
			color: '#525c69',
		},
	};

	module.exports = { WarningBlock, BlockType };

});
