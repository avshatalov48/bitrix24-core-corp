/**
 * @module crm/mail/message/elements/contact/card
 */
jn.define('crm/mail/message/elements/contact/card', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { EntityDetailOpener } = require('crm/entity-detail/opener');
	const { ProfileView } = require('user/profile');

	function Email(props)
	{
		const {
			value,
			format,
			maxWidthTextFiled,
		} = props;

		if (!value)
		{
			return null;
		}

		const styles = {
			little: {
				fontSize: 13,
				color: '#525C69',
			},
			big: {
				fontSize: 15,
				color: '#5b5b5b',
			},
		};

		return View(
			{},
			Text({
				maxWidth: maxWidthTextFiled,
				style: styles[format],
				text: value,
			}),
		);
	}

	function Name(props)
	{
		const {
			maxWidthTextFiled,
			format,
			value,
		} = props;

		if (!value)
		{
			return null;
		}

		const styles = {
			little: {
				fontSize: 13,
				fontWeight: '400',
				color: '#525C69',
			},
			big: {
				fontSize: 15,
				fontWeight: '500',
			},
		};

		return Text({
			ellipsize: 'end',
			style: {
				textAlignVertical: 'center',
				maxWidth: maxWidthTextFiled,
				...styles[format],
			},
			numberOfLines: 1,
			text: value,
		});
	}

	function Capsule(props)
	{
		const {
			name,
			email,
			format,
			clickable,
		} = props;

		let {
			maxWidthTextFiled,
		} = props;

		let contactFiled;

		const stylesIcon = {
			little: {
				width: 13,
				height: '100%',
			},
			big: {
				width: 16,
				height: '100%',
			},
		};

		maxWidthTextFiled -= stylesIcon[format].width;

		const isIOS = Application.getPlatform() === 'ios';

		let arrowIcon = null;

		if (clickable)
		{
			arrowIcon = Image({
				resizeMode: 'stretch',
				svg: format === 'big' ? svgImages.showArrowBig : svgImages.showArrowSmall,
				style: {
					marginTop: isIOS ? 1 : 2,
					...stylesIcon[format],
				},
			});
		}

		if (name)
		{
			contactFiled = Name({
				maxWidthTextFiled,
				format,
				value: name,
			});
		}
		else
		{
			contactFiled = Email({
				maxWidthTextFiled,
				format,
				value: email,
			});
		}
		return View(
			{
				style: {
					alignItems: 'center',
					flexDirection: 'row',
				},
			},
			contactFiled,
			arrowIcon,
		);
	}

	function openUserProfile(userId)
	{
		PageManager.openWidget('list', {
			groupStyle: true,
			backdrop: {
				bounceEnable: false,
				swipeAllowed: true,
				showOnTop: true,
				hideNavigationBar: false,
				horizontalSwipeAllowed: false,
			},
		}).then((list) => ProfileView.open({ userId, isBackdrop: true }, list));
	}

	function openDetail(id, typeNameId, isUser)
	{
		if (isUser)
		{
			openUserProfile(id);
		}
		else
		{
			EntityDetailOpener.open({ entityTypeId: typeNameId, entityId: id });
		}
	}

	class ContactCard extends PureComponent
	{
		render()
		{
			const {
				maxWidthTextFiled,
				name,
				email,
				format,
				id,
				typeNameId,
				isUser,
			} = this.props;

			let onClick = null;

			if (Number(id) !== 0)
			{
				onClick = openDetail.bind(null, id, typeNameId, isUser);
			}

			return View(
				{
					onClick,
				},
				Capsule({
					maxWidthTextFiled,
					name,
					email,
					format,
					clickable: !!id,
				}),
			);
		}
	}

	const svgImages = {
		showArrowBig: {
			content: `<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path fill-rule="evenodd" clip-rule="evenodd" d="M5.44031 4.22908L8.45834 7.24711L9.24006 8.00002L8.45834 8.75338L5.44031 11.7714L6.50529 12.8364L11.3414 8.00029L6.50529 3.16418L5.44031 4.22908Z" fill="#A8ADB4"/>
			</svg>`,
		},
		showArrowSmall: {
			content: `<svg width="13" height="13" viewBox="0 0 13 13" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path fill-rule="evenodd" clip-rule="evenodd" d="M4.42023 3.43615L6.87238 5.8883L7.50753 6.50004L6.87238 7.11215L4.42023 9.5643L5.28552 10.4296L9.21486 6.50026L5.28552 2.57092L4.42023 3.43615Z" fill="#A8ADB4"/>
			</svg>`,
		},
	};

	module.exports = { ContactCard };
});
