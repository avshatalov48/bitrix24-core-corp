/**
 * @module crm/timeline/item/ui/header/info-helper
 */
jn.define('crm/timeline/item/ui/header/info-helper', (require, exports, module) => {
	include('InAppNotifier');

	class InfoHelper extends LayoutComponent
	{
		get blocks()
		{
			return this.props.textBlocks || [];
		}

		get text()
		{
			return this.blocks.map((block) => block.options.text).join('');
		}

		get hasContent()
		{
			return this.blocks.length > 0;
		}

		get action()
		{
			if (this.props.primaryAction)
			{
				return this.props.primaryAction;
			}

			const link = this.blocks.find((block) => block.type === 'link');
			if (link && link.options)
			{
				return link.options.action;
			}

			return null;
		}

		render()
		{
			if (!this.hasContent)
			{
				return null;
			}

			return View(
				{
					style: {
						marginRight: 6,
						flexDirection: 'column',
						justifyContent: 'center',
					},
					onClick: () => {
						if (this.props.primaryAction)
						{
							return this.onAction();
						}

						InAppNotifier.setHandler(() => {
							setTimeout(() => this.onAction(), 100);
						});

						InAppNotifier.showNotification({
							message: this.text,
							time: 3,
							backgroundColor: '#004f69',
							blur: true,
							code: `info_helper_${Random.getString(3)}`,
						});
					},
				},
				Image({
					svg: {
						content: '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8 15.5C12.1421 15.5 15.5 12.1421 15.5 8C15.5 3.85786 12.1421 0.5 8 0.5C3.85786 0.5 0.5 3.85786 0.5 8C0.5 12.1421 3.85786 15.5 8 15.5ZM7.05583 10.465V12.159H8.78283V10.465H7.05583ZM6.90183 6.83496H5.28483C5.29216 6.43895 5.35999 6.07596 5.48833 5.74596C5.61666 5.41595 5.79632 5.12996 6.02733 4.88796C6.25833 4.64595 6.53699 4.45712 6.86333 4.32146C7.18966 4.18579 7.55449 4.11796 7.95783 4.11796C8.47849 4.11796 8.91299 4.18945 9.26133 4.33246C9.60966 4.47546 9.89016 4.65329 10.1028 4.86596C10.3155 5.07862 10.4677 5.30779 10.5593 5.55346C10.651 5.79912 10.6968 6.02829 10.6968 6.24096C10.6968 6.59296 10.651 6.88262 10.5593 7.10996C10.4677 7.33729 10.354 7.53162 10.2183 7.69296C10.0827 7.85429 9.93233 7.99179 9.76733 8.10546C9.60232 8.21912 9.44649 8.33279 9.29983 8.44646C9.15316 8.56012 9.02299 8.69029 8.90933 8.83696C8.79566 8.98362 8.72416 9.16695 8.69483 9.38696V9.80496H7.20983V9.30996C7.23183 8.99462 7.29232 8.73062 7.39132 8.51796C7.49033 8.30529 7.60582 8.12379 7.73783 7.97346C7.86983 7.82312 8.00916 7.69296 8.15583 7.58296C8.30249 7.47295 8.43816 7.36296 8.56283 7.25296C8.68749 7.14295 8.78832 7.02196 8.86532 6.88996C8.94233 6.75796 8.97716 6.59296 8.96983 6.39496C8.96983 6.05762 8.88733 5.80829 8.72233 5.64696C8.55732 5.48562 8.32816 5.40496 8.03483 5.40496C7.83682 5.40496 7.66633 5.44346 7.52333 5.52046C7.38032 5.59746 7.26299 5.70012 7.17133 5.82846C7.07966 5.95679 7.01183 6.10712 6.96783 6.27946C6.92383 6.45179 6.90183 6.63695 6.90183 6.83496Z" fill="#828B95"/></svg>',
					},
					style: {
						width: 16,
						height: 16,
					},
				}),
			);
		}

		onAction()
		{
			if (this.props.onAction && this.action)
			{
				this.props.onAction(this.action);
			}
		}
	}

	module.exports = { InfoHelper };
});
