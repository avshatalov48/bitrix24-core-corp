/**
 * @module bizproc/workflow/faces/column-view
 * */
jn.define('bizproc/workflow/faces/column-view', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Type } = require('type');
	const { Color, Component, Indent } = require('tokens');
	const { PureComponent } = require('layout/pure-component');
	const { Step, TextStub, Text, Avatar, AvatarStub, Stack, Duration } = require('layout/ui/elements-stack-steps');
	const { IconView, Icon } = require('ui-system/blocks/icon');

	class WorkflowFacesColumn extends PureComponent
	{
		render()
		{
			return Step(
				{},
				this.renderLabel(),
				this.renderPhotos(),
				this.renderTime(),
			);
		}

		renderLabel()
		{
			if (Type.isNil(this.props.label))
			{
				return TextStub();
			}

			return Text({ text: this.props.label });
		}

		renderPhotos()
		{
			if (Type.isNil(this.props.avatars) || !Type.isArrayFilled(this.props.avatars))
			{
				return Stack({}, this.renderProcessStatus());
			}

			return Stack(
				{
					status: this.#renderTaskStatus(),
				},
				...(
					this.props.avatars
						.map((id) => Avatar({ id, testId: `bizproc-workflow-faces-column-user-${id}` }))
				),
			);
		}

		renderProcessStatus()
		{
			const size = 30;
			const icon = this.renderIcon(size);
			if (!icon)
			{
				return AvatarStub();
			}

			return View(
				{
					style: {
						width: size + Indent.XS2.toNumber(),
						height: size + Indent.XS2.toNumber(),
						borderRadius: Component.elementAccentCorner.toNumber(),
						alignItems: 'center',
						justifyContent: 'center',
						backgroundColor: Color.bgSeparatorSecondary.toHex(),
					},
				},
				icon,
			);
		}

		#renderTaskStatus()
		{
			return this.renderIcon(16);
		}

		renderIcon(size)
		{
			const { icon, color } = this.#getIconData(this.props.status);
			if (!icon)
			{
				return null;
			}

			return View(
				{
					style: {
						width: size,
						height: size,
						borderRadius: Component.elementAccentCorner.toNumber(),
						alignItems: 'center',
						justifyContent: 'center',
						backgroundColor: AppTheme.colors.bgContentPrimary,
					},
				},
				IconView({ icon, color, size: 20 }),
			);
		}

		#getIconData(iconType)
		{
			let icon = null;
			let color = null;

			switch (iconType)
			{
				case 'bp':
					icon = Icon.BUSINES_PROCESS_STAGES;
					color = Color.base5;
					break;
				case 'success':
					icon = Icon.CIRCLE_CHECK;
					color = Color.accentMainSuccess;
					break;
				case 'not-success':
					icon = Icon.CIRCLE_CROSS;
					color = Color.base3;
					break;
				case 'wait':
					icon = Icon.CLOCK;
					color = Color.accentMainPrimary;
					break;
				default:
					icon = null;
					color = null;
			}

			return { icon, color };
		}

		renderTime()
		{
			if (Type.isNil(this.props.duration))
			{
				return TextStub();
			}

			if (Type.isString(this.props.duration))
			{
				return Text({ text: this.props.duration });
			}

			return Duration({ duration: this.props.duration });
		}
	}

	module.exports = { WorkflowFacesColumn };
});
