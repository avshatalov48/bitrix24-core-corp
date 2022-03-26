(() => {
	const DEFAULT_AVATAR = '/bitrix/mobileapp/mobile/extensions/bitrix/layout/ui/fields/user/images/default-avatar.png';
	const DEFAULT_SELECTOR_AVATAR = '/bitrix/mobileapp/mobile/extensions/bitrix/selector/providers/common/images/user.png';

	/**
	 * @class Fields.User
	 */
	class User extends Fields.EntitySelector
	{
		getConfig()
		{
			const config = super.getConfig();

			return {
				...config,
				selectorType: config.selectorType === '' ? 'user' : config.selectorType
			};
		}

		renderEmptyEntity()
		{
			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'row',
						alignItems: 'center'
					}
				},
				Image({
					style: this.styles.userImage,
					uri: this.getImageUrl(DEFAULT_AVATAR)
				}),
				Text({
					style: this.styles.emptyEntity,
					numberOfLines: 1,
					ellipsize: 'end',
					text: BX.message('FIELDS_USER_SELECT')
				})
			);
		}

		renderGroupedEntities()
		{
			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				Image({
					style: this.styles.userImage,
					uri: this.getImageUrl(DEFAULT_AVATAR),
				}),
				Text({
					style: this.styles.userText,
					numberOfLines: 1,
					ellipsize: 'end',
					text: this.getGroupedEntitiesText(),
				})
			);
		}

		renderUngroupedEntities()
		{
			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'column',
						flexWrap: 'wrap'
					}
				},
				...this.prepareEntities()
			);
		}

		renderEntity(user = {}, showPadding = false)
		{
			const onClick = this.isReadOnly() && this.openEntity.bind(this, user.id);

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						paddingBottom: showPadding ? 5 : undefined
					}
				},
				Image({
					style: this.styles.userImage,
					uri: this.getImageUrl(user.imageUrl || DEFAULT_AVATAR),
					onClick
				}),
				View(
					{onClick},
					Text({
						style: this.styles.userText,
						numberOfLines: 1,
						ellipsize: 'end',
						text: user.title
					})
				)
			);
		}

		getImageUrl(imageUrl)
		{
			if (imageUrl.indexOf(currentDomain) !== 0)
			{
				imageUrl = encodeURI(imageUrl);
				imageUrl = imageUrl.replace(`${currentDomain}`, '');
				imageUrl = (imageUrl.indexOf('http') !== 0 ? `${currentDomain}${imageUrl}` : imageUrl);
			}

			if (imageUrl === (currentDomain + DEFAULT_SELECTOR_AVATAR))
			{
				imageUrl = currentDomain + DEFAULT_AVATAR;
			}

			return imageUrl;
		}

		openEntity(userId)
		{
			const parentWidget = (this.getConfig().parentWidget || PageManager);
			parentWidget.openWidget('list', {
				backdrop: {
					bounceEnable: false,
					swipeAllowed: true,
					showOnTop: true,
					hideNavigationBar: false,
					horizontalSwipeAllowed: false,
				},
				onReady: list => ProfileView.open({userId, isBackdrop: true}, list),
				onError: error => console.log(error),
			});
		}

		getDefaultStyles()
		{
			const styles = super.getDefaultStyles();

			return {
				...styles,
				entityContent: {
					...styles.entityContent,
					flexDirection: 'column'
				},
				userImage: {
					width: 20,
					height: 20,
					borderRadius: 10
				},
				userText: {
					color: '#0b66c3',
					fontSize: 16,
					marginLeft: 5
				},
				emptyEntity: {
					...styles.emptyValue,
					marginLeft: 5
				},
				wrapper: {
					paddingTop: 7,
					paddingBottom: this.hasErrorMessage() ? 5 : 10
				},
				readOnlyWrapper: {
					paddingTop: 7,
					paddingBottom: this.hasErrorMessage() ? 5 : 10
				},
			}
		}
	}


	this.Fields = this.Fields || {};
	this.Fields.User = User;
})();
