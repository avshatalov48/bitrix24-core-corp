/**
 * @module crm/timeline/item/ui/body/blocks/avatars-stack-steps
 */
jn.define('crm/timeline/item/ui/body/blocks/avatars-stack-steps', (require, exports, module) => {
	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');
	const { Color, Indent, Corner } = require('tokens');
	const { Type } = require('type');
	const AppTheme = require('apptheme');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const {
		ElementsStackSteps,
		Step,
		Text,
		TextStub,
		Stack,
		Avatar,
		AvatarStub,
		Duration,
		Stick,
	} = require('layout/ui/elements-stack-steps');

	/**
	 * @class TimelineItemBodyAvatarsStackSteps
	 */
	class TimelineItemBodyAvatarsStackSteps extends TimelineItemBodyBlock
	{
		render()
		{
			if (Type.isNil(this.props.steps) || !Type.isArrayFilled(this.props.steps))
			{
				return null;
			}

			let steps = this.props.steps.map((step) => Step(
				{},
				this.renderLabel(step.header),
				this.renderPhotos(step.stack),
				this.renderTime(step.footer),
			));

			const needRenderMoreTasksIcon = this.props.steps?.some((step) => step.progressBox);
			if (needRenderMoreTasksIcon)
			{
				steps = [
					...steps.slice(0, 1),
					this.#renderMoreTasksIcon(),
					...steps.slice(1),
				];
			}

			return View(
				{
					style: {
						borderStyle: 'solid',
						borderWidth: 1,
						borderColor: AppTheme.colors.base6,
						borderRadius: Corner.M.toNumber(),
						padding: Indent.XL.toNumber(),
					},
				},
				ElementsStackSteps(
					{},
					Stick({
						leftOffset: '16%',
						rightOffset: '16%',
						maxWidth: 313,
						style: {
							backgroundColor: AppTheme.colors.base6,
						},
					}),
					...steps,
				),
			);
		}

		renderLabel(header)
		{
			if (header.type === 'stub')
			{
				return TextStub();
			}

			return Text({
				text: header.data?.text || '',
			});
		}

		renderPhotos(stack)
		{
			const images = [];

			stack.images.forEach((image) => {
				if (image.type === 'user')
				{
					images.push(Avatar({ id: image.data?.userId }));
				}
				else if (image.type === 'icon')
				{
					const { icon, color } = this.#convertIconData(image.data?.icon);

					if (icon)
					{
						images.push(
							View(
								{
									style: {
										width: 30,
										height: 30,
										borderRadius: Corner.XL.toNumber(),
										alignItems: 'center',
										justifyContent: 'center',
										backgroundColor: AppTheme.colors.bgContentPrimary,
										borderColor: AppTheme.colors.base6,
										borderWidth: 1,
									},
								},
								IconView({ icon, color, size: 24 }),
							),
						);
					}
				}
				else
				{
					images.push(AvatarStub());
				}
			});

			return Stack(
				{ status: this.#renderIcon(stack) },
				...images,
			);
		}

		#renderMoreTasksIcon()
		{
			const dots = `
				<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
					<rect y="2" width="16" height="12" rx="6" fill="${AppTheme.colors.base7}"/>
					<path
						d="M6.60827 7.86957C6.60827 8.34981 6.21895 8.73913 5.73871 8.73913C5.25846 8.73913 4.86914 8.34981 4.86914 7.86957C4.86914 7.38932 5.25846 7 5.73871 7C6.21895 7 6.60827 7.38932 6.60827 7.86957Z"
						fill="${AppTheme.colors.base3}"
					/>
					<path
						d="M9.04305 7.86957C9.04305 8.34981 8.65374 8.73913 8.17349 8.73913C7.69324 8.73913 7.30392 8.34981 7.30392 7.86957C7.30392 7.38932 7.69324 7 8.17349 7C8.65374 7 9.04305 7.38932 9.04305 7.86957Z"
						fill="${AppTheme.colors.base3}"
					/>
					<path
						d="M11.4778 7.86957C11.4778 8.34981 11.0885 8.73913 10.6083 8.73913C10.128 8.73913 9.73871 8.34981 9.73871 7.86957C9.73871 7.38932 10.128 7 10.6083 7C11.0885 7 11.4778 7.38932 11.4778 7.86957Z"
						fill="${AppTheme.colors.base3}"
					/>
				</svg>
			`;

			return View(
				{ style: { alignSelf: 'center' } },
				Image({
					style: { width: 16, height: 16 },
					svg: { content: dots },
				}),
			);
		}

		#renderIcon(stack)
		{
			const { icon, color } = this.#getIconData(stack.status?.type);
			if (!icon)
			{
				return null;
			}

			return View(
				{
					style: {
						width: 16,
						height: 16,
						borderRadius: Corner.XL.toNumber(),
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
			const iconMap = {
				ok: { icon: Icon.CIRCLE_CHECK, color: Color.accentMainSuccess },
				cancel: { icon: Icon.CIRCLE_CROSS, color: Color.base3 },
				wait: { icon: Icon.CLOCK, color: Color.accentMainPrimary },
			};

			return iconMap[iconType] || { icon: null, color: null };
		}

		#convertIconData(iconName)
		{
			const iconData = Object.freeze({
				'circle-check': { icon: Icon.CIRCLE_CHECK, color: Color.accentMainSuccess },
				'black-clock': { icon: Icon.CLOCK, color: Color.accentMainPrimary },
				bp: { icon: Icon.BUSINES_PROCESS_STAGES, color: Color.base5 },
			});

			return iconData[iconName] || { icon: null, color: null };
		}

		renderTime(footer)
		{
			if (footer.type === 'text')
			{
				return Text({
					text: footer.data.text,
				});
			}

			if (footer.type === 'duration')
			{
				return Duration({ duration: footer.data.duration });
			}

			return TextStub();
		}
	}

	module.exports = { TimelineItemBodyAvatarsStackSteps };
});
