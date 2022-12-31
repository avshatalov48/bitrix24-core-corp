/**
 * @module crm/timeline/item/ui/sections/note
 */
jn.define('crm/timeline/item/ui/note', (require, exports, module) => {

	const { throttle } = require('utils/function');

	class TimelineItemNote extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				edit: false,
				text: this.props.comment,
			};

			this.onChangeText = throttle((text) => {
				this.setState({
					text,
				}, () => {
					this.onChange();
				});
			}, 500, this);
		}

		onChange()
		{
			const { onChange } = this.props;

			if (typeof onChange === 'function')
			{
				onChange(this.state.text);
			}
		}

		render()
		{
			const { edit } = this.state;

			return View(
				{
					style: styles.wrapper,
				},
				View(
					{
						style: styles.container(edit),
					},
					this.renderText(),
					this.renderDeleteButton(),
				),
				View(
					{
						style: styles.noteIconContainer,
					},
					Image(
						{
							style: styles.noteIcon,
							svg: {
								content: svgImages.noteIcon(edit),
							}
						},
					),
				),
			);
		}

		renderText()
		{
			const { edit, text } = this.state;
			if (edit)
			{
				return View(
					{
						style: styles.textContainer,
					},
					TextField(
						{
							style: styles.text,
							placeholder: BX.message('CRM_TIMELINE_ITEM_BASE_NOTE_PLACEHOLDER'),
							value: text,
							focus: edit,
							onChangeText: this.onChangeText(text),
							onSubmitEditing: () => {
								this.setState({
									edit: false,
								});
							},
						},
					),
				);
			}

			return View(
				{
					style: styles.textContainer,
					onClick: () => {
						this.setState({
							edit: true,
						});
					},
				},
				Text(
					{
						style: styles.text,
						text: text,
					},
				),
				this.renderEditButton(),
			);
		}

		renderEditButton()
		{
			return View(
				{
					style: styles.editButton,
				},
				Image(
					{
						style: styles.editButtonImage,
						svg: {
							content: svgImages.editButtonImage,
						},
					},
				),
			);
		}

		renderDeleteButton()
		{
			return View(
				{
					style: styles.deleteButton,
					onClick: () => {
						this.setState({
							text: '',
						});
					},
				},
				Image(
					{
						style: styles.deleteButtonImage,
						svg: {
							content: svgImages.deleteButtonImage,
						},
					},
				),
			);
		}
	}

	const styles = {
		wrapper: {
			width: '100%',
		},
		container: (edit) => ({
			borderColor: edit ? '#e0ca8d' : '#f0e4bd',
			borderWidth: 1,
			borderRadius: 6,
			backgroundColor: edit ? '#fbf4c9' : '#fdfae3',
			flexDirection: 'row',
			alignItems: 'center',
			padding: 4,
		}),
		textContainer: {
			flexGrow: 2,
			paddingLeft: 12,
			paddingTop: 8,
			paddingBottom: 10,
			flexDirection: 'row',
			alignItems: 'center',
		},
		text: {
			color: '#000000',
			fontSize: 13,
			marginRight: 5,
		},
		editButton: {
			width: 15,
			height: 15,
			justifyContent: 'center',
			alignItems: 'center',
		},
		editButtonImage: {
			width: 9,
			height: 9,
		},
		deleteButton: {
			width: 24,
			height: 24,
			justifyContent: 'center',
			alignItems: 'center',
		},
		deleteButtonImage: {
			width: 8,
			height: 8,
		},
		noteIconContainer: {
			alignSelf: 'flex-end',
			marginRight: 50,
			marginTop: -1,
		},
		noteIcon: {
			width: 14,
			height: 8,
		},
	};

	const svgImages = {
		deleteButtonImage: `<svg width="8" height="8" viewBox="0 0 8 8" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7.05882 0.000222688L8 0.941373L0.941178 8L1.38837e-06 7.05885L7.05882 0.000222688Z" fill="black" fill-opacity="0.36"/><path d="M0 0.94115L0.941176 0L8 7.05863L7.05882 7.99978L0 0.94115Z" fill="black" fill-opacity="0.36"/></svg>`,
		editButtonImage: `<svg width="9" height="9" viewBox="0 0 9 9" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.27242 0.146759L8.86652 1.75765L2.63048 7.97691L1.03637 6.36602L7.27242 0.146759ZM0.140989 8.67039C0.125915 8.72744 0.142066 8.78772 0.182982 8.8297C0.224975 8.87168 0.285272 8.88783 0.342339 8.87168L2.12433 8.3916L0.621213 6.88894L0.140989 8.67039Z" fill="black" fill-opacity="0.14"/></svg>`,
		noteIcon: (edit) => {
			const backgroundColor = edit ? '#fbf4c9' : '#fdfae3';
			const borderColor = edit ? '#e0ca8d' : '#f0e4bd';

			return `<svg width="14" height="8" viewBox="0 0 14 8" fill="none" xmlns="http://www.w3.org/2000/svg"><polygon points="0,0 6,6 12,0 12,1 6,7 0,1" style="fill: ${borderColor}; stroke-width: 1"></polygon><polygon points="0,0 6,6 12,0" style="fill: ${backgroundColor}"></polygon></svg>`
		},
	};

	module.exports = { TimelineItemNote };
});