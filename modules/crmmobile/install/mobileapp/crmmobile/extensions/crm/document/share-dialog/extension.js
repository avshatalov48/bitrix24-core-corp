/**
 * @module crm/document/share-dialog
 */
jn.define('crm/document/share-dialog', (require, exports, module) => {
	const { FadeView } = require('animation/components/fade-view');
	const { Loc } = require('loc');
	const { Alert } = require('alert');
	const AppTheme = require('apptheme');
	const { get } = require('utils/object');
	const { TimelineSchedulerSmsProvider } = require('crm/timeline/scheduler/providers/sms');
	const { Type } = require('crm/type');
	const { showTooltip } = require('crm/document/shared-utils');
	const { copyToClipboard } = require('utils/copy');

	let DialogOpener = null;

	try
	{
		DialogOpener = require('im/messenger/api/dialog-opener').DialogOpener;
	}
	catch (err)
	{
		console.warn('Cannot get DialogOpener module', err);
	}

	const allowOpenlines = false;

	/**
	 * @class CrmDocumentShareDialog
	 */
	class CrmDocumentShareDialog extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.layoutWidget = props.layoutWidget;

			this.state = {
				publicUrl: props.document.publicUrl,
			};
		}

		static open(props)
		{
			const parentWidget = props.parentWidget || PageManager;

			parentWidget
				.openWidget('layout', {
					modal: true,
					backgroundColor: AppTheme.colors.bgSecondary,
					backdrop: {
						onlyMediumPosition: true,
						showOnTop: false,
						forceDismissOnSwipeDown: true,
						mediumPositionPercent: 60,
						swipeAllowed: true,
						swipeContentAllowed: true,
						horizontalSwipeAllowed: false,
						hideNavigationBar: false,
						navigationBarColor: AppTheme.colors.bgSecondary,
					},
					enableNavigationBarBorder: false,
					title: Loc.getMessage('M_CRM_DOCUMENT_SHARE_DIALOG_TITLE'),
				})
				.then((layoutWidget) => {
					layoutWidget.enableNavigationBarBorder(false);
					layoutWidget.showComponent(new CrmDocumentShareDialog({
						...props,
						layoutWidget,
					}));
				});
		}

		render()
		{
			return View(
				{},
				new FadeView({
					visible: false,
					fadeInOnMount: true,
					style: {
						flexGrow: 1,
					},
					slot: () => this.renderContent(),
				}),
			);
		}

		renderContent()
		{
			return ScrollView(
				{
					style: {
						flexDirection: 'column',
						flexGrow: 1,
					},
				},
				View(
					{
						safeArea: { bottom: true },
					},
					this.renderChannels(),
					this.renderSystemShareDialogButton(),
					this.props.isSigningEnabled && this.renderSignButton(),
					this.renderCancelButton(),
				),
			);
		}

		renderChannels()
		{
			const hasPhoneChannel = this.hasPhoneChannel();
			const hasEmailChannel = this.hasEmailChannel();
			const hasOpenlineChannel = this.hasOpenlineChannel();

			let phoneSubtitle = null;
			if (!hasPhoneChannel)
			{
				phoneSubtitle = this.hasPhoneContact()
					? Loc.getMessage('M_CRM_DOCUMENT_SHARE_DIALOG_CHANNEL_NOT_AVAILABLE')
					: Loc.getMessage('M_CRM_DOCUMENT_SHARE_DIALOG_PHONE_NOT_FOUND');
			}

			let emailSubtitle = null;
			if (!hasEmailChannel)
			{
				emailSubtitle = this.hasEmailContact()
					? Loc.getMessage('M_CRM_DOCUMENT_SHARE_DIALOG_CHANNEL_NOT_AVAILABLE')
					: Loc.getMessage('M_CRM_DOCUMENT_SHARE_DIALOG_EMAIL_NOT_FOUND');
			}

			let openlineSubtitle = null;
			if (!hasOpenlineChannel)
			{
				openlineSubtitle = this.hasOpenlineContact()
					? Loc.getMessage('M_CRM_DOCUMENT_SHARE_DIALOG_CHANNEL_NOT_AVAILABLE')
					: Loc.getMessage('M_CRM_DOCUMENT_SHARE_DIALOG_OPENLINE_NOT_FOUND');
			}

			return View(
				{
					style: {
						flexDirection: 'column',
						marginBottom: 12,
						borderRadius: 12,
						backgroundColor: AppTheme.colors.bgContentPrimary,
					},
				},
				this.renderButton({
					border: true,
					onClick: () => this.openSmsSender(),
					leftIcon: SvgIcons.sms,
					title: Loc.getMessage('M_CRM_DOCUMENT_SHARE_DIALOG_SMS_TITLE'),
					subtitle: phoneSubtitle,
					rightIcon: SvgIcons.arrowRight,
					disabled: !hasPhoneChannel,
				}),
				this.renderButton({
					border: true,
					onClick: () => this.openEmailSender(),
					leftIcon: SvgIcons.email,
					title: Loc.getMessage('M_CRM_DOCUMENT_SHARE_DIALOG_EMAIL_TITLE'),
					subtitle: emailSubtitle,
					rightIcon: SvgIcons.arrowRight,
					disabled: !hasEmailChannel,
				}),
				allowOpenlines && this.renderButton({
					border: true,
					onClick: () => this.openOpenlineSender(),
					leftIcon: SvgIcons.openline,
					title: Loc.getMessage('M_CRM_DOCUMENT_SHARE_DIALOG_OPENLINE_TITLE'),
					subtitle: openlineSubtitle,
					rightIcon: SvgIcons.arrowRight,
					disabled: !hasOpenlineChannel,
				}),
				this.renderButton({
					onClick: () => this.copyLink(),
					leftIcon: SvgIcons.link,
					title: Loc.getMessage('M_CRM_DOCUMENT_SHARE_DIALOG_COPY_LINK_TITLE'),
					subtitle: Loc.getMessage('M_CRM_DOCUMENT_SHARE_DIALOG_COPY_LINK_SUBTITLE'),
				}),
			);
		}

		renderSystemShareDialogButton()
		{
			return View(
				{
					style: {
						marginBottom: 12,
					},
				},
				this.renderButton({
					onClick: () => this.openShareDialog(),
					title: {
						text: Loc.getMessage('M_CRM_DOCUMENT_SHARE_DIALOG_SYSTEM_SHARE_TITLE'),
						color: AppTheme.colors.base1,
						fontSize: 16,
					},
					subtitle: {
						text: Loc.getMessage('M_CRM_DOCUMENT_SHARE_DIALOG_SYSTEM_SHARE_SUBTITLE'),
						color: AppTheme.colors.base2,
						fonstSize: 13,
					},
					rightIcon: SvgIcons.share,
					rounded: true,
				}),
			);
		}

		renderSignButton()
		{
			return View(
				{
					style: {
						marginBottom: 12,
						opacity: this.props.isSigningEnabledInCurrentTariff ? 1 : 0.75,
					},
				},
				this.renderButton({
					onClick: () => this.signDocument(),
					title: {
						text: Loc.getMessage('M_CRM_DOCUMENT_SHARE_DIALOG_SIGN_TITLE'),
						color: AppTheme.colors.base1,
						fontSize: 16,
					},
					subtitle: {
						text: Loc.getMessage('M_CRM_DOCUMENT_SHARE_DIALOG_SIGN_SUBTITLE'),
						color: AppTheme.colors.base2,
						fonstSize: 13,
					},
					rightIcon: this.props.isSigningEnabledInCurrentTariff ? SvgIcons.arrowRight : SvgIcons.tariffLock,
					rounded: true,
				}),
			);
		}

		renderCancelButton()
		{
			return View(
				{
					style: {
						opacity: 0.75,
					},
				},
				this.renderButton({
					onClick: () => this.layoutWidget.close(),
					leftIcon: SvgIcons.cancel,
					title: Loc.getMessage('M_CRM_DOCUMENT_SHARE_DIALOG_CANCEL'),
					rounded: true,
				}),
			);
		}

		renderButton({ title, subtitle, leftIcon, rightIcon, onClick, border, rounded, disabled })
		{
			return View(
				{
					onClick: () => {
						if (!disabled)
						{
							onClick();
						}
					},
					style: {
						flexDirection: 'row',
						justifyContent: 'space-between',
						alignItems: 'center',
						paddingVertical: 10,
						paddingHorizontal: 16,
						minHeight: 58,
						borderBottomColor: AppTheme.colors.bgSecondary,
						borderBottomWidth: border ? 1 : 0,
						borderRadius: rounded ? 12 : 0,
						opacity: disabled ? 0.5 : 1,
					},
				},
				leftIcon && View(
					{
						style: {
							marginRight: 16,
						},
					},
					Image({
						svg: {
							content: typeof leftIcon === 'function' ? leftIcon(disabled) : leftIcon,
						},
						style: {
							width: 31,
							height: 31,
						},
					}),
				),
				View(
					{
						style: {
							flexGrow: 1,
						},
					},
					Text({
						text: title.text || title,
						numberOfLines: 1,
						ellipsize: 'end',
						style: {
							color: title.color || AppTheme.colors.base1,
							fontSize: title.fontSize || 18,
						},
					}),
					subtitle && Text({
						text: subtitle.text || subtitle,
						numberOfLines: 1,
						ellipsize: 'end',
						style: {
							color: subtitle.color || AppTheme.colors.base2,
							fontSize: subtitle.fontSize || 13,
						},
					}),
				),
				rightIcon && !disabled && View(
					{},
					Image({
						svg: {
							content: rightIcon,
						},
						style: {
							width: 25,
							height: 24,
						},
					}),
				),
			);
		}

		openSmsSender()
		{
			this.getPublicUrl()
				.then((publicUrl) => {
					const initialText = Loc.getMessage('M_CRM_DOCUMENT_SHARE_DIALOG_CHECK_OUT_FILE_BY_LINK', {
						'#URL#': publicUrl,
					});
					TimelineSchedulerSmsProvider.open({
						scheduler: {
							entity: {
								typeId: this.props.entityTypeId,
								id: this.props.entityId,
							},
							parentWidget: this.layoutWidget,
							onActivityCreate: () => this.layoutWidget.close(),
						},
						context: {
							initialText,
						},
					});
				})
				.catch(() => {
					Alert.alert('', Loc.getMessage('M_CRM_DOCUMENT_SHARE_DIALOG_COPY_LINK_ERROR'));
				});
		}

		openEmailSender()
		{
			const subject = this.props.document.title;
			const attachment = get(this.props, 'channelSelector.emailAttachment', null);
			const prepareBody = () => new Promise((resolve) => {
				if (attachment)
				{
					resolve(Loc.getMessage('M_CRM_DOCUMENT_SHARE_DIALOG_EMAIL_FILE_ATTACHED'));

					return;
				}
				this.getPublicUrl()
					.then((publicUrl) => {
						const message = Loc.getMessage('M_CRM_DOCUMENT_SHARE_DIALOG_CHECK_OUT_FILE_BY_LINK', {
							'#URL#': publicUrl,
						});
						resolve(message);
					})
					.catch(() => {
						Alert.alert('', Loc.getMessage('M_CRM_DOCUMENT_SHARE_DIALOG_COPY_LINK_ERROR'));
					});
			});

			prepareBody().then(async (body) => {
				const { MailOpener } = await requireLazy('crm:mail/opener');

				MailOpener.openSend({
					subject,
					body,
					isSendFiles: true,
					files: attachment ? [attachment] : [],
					owner: {
						ownerId: this.props.entityId,
						ownerType: Type.resolveNameById(this.props.entityTypeId),
					},
				}, {}, this.layoutWidget);
			}).catch(console.error);
		}

		openOpenlineSender()
		{
			const dialogId = get(this.props, 'channelSelector.communications.IM.VALUE', null);
			const title = Loc.getMessage('M_CRM_DOCUMENT_SHARED_PHRASES_LOADING');

			if (DialogOpener && dialogId)
			{
				this.copyLink().then(() => {
					DialogOpener.openLine({
						userCode: dialogId,
						dialogTitleParams: {
							name: title,
						},
					}).then(() => this.onImDialogOpened()).catch(console.error());
				});
			}
		}

		onImDialogOpened()
		{
			if (this.props.onImDialogOpened)
			{
				this.props.onImDialogOpened();
			}
		}

		copyLink()
		{
			return this.getPublicUrl()
				.then((publicUrl) => {
					copyToClipboard(publicUrl, Loc.getMessage('M_CRM_DOCUMENT_SHARE_DIALOG_COPY_LINK_DONE'));
				})
				.catch(() => {
					Alert.alert('', Loc.getMessage('M_CRM_DOCUMENT_SHARE_DIALOG_COPY_LINK_ERROR'));
				});
		}

		/**
		 * @return {Promise<string>}
		 */
		getPublicUrl()
		{
			if (this.state.publicUrl)
			{
				return Promise.resolve(this.state.publicUrl);
			}

			return new Promise((resolve, reject) => {
				BX.ajax.runAction('crm.documentgenerator.document.enablePublicUrl', {
					data: {
						status: 1,
						id: Number(this.props.document.id),
					},
				}).then((response) => {
					this.state.publicUrl = response.data.publicUrl;
					resolve(this.state.publicUrl);
				}).catch((err) => {
					console.error(err);
					reject(err);
				});
			});
		}

		openShareDialog()
		{
			dialogs.showSharingDialog({ uri: this.props.localPdfPath });
		}

		signDocument()
		{
			if (!this.props.isSigningEnabledInCurrentTariff)
			{
				// todo better use helpdesk article - but find article code first
				// helpdesk.openHelpArticle(this.props.signingInfoHelperSliderCode);
				showTooltip(Loc.getMessage('M_CRM_DOCUMENT_SHARE_DIALOG_TARIFF_RESTRICTION'));

				return;
			}

			this.redirectToEntityDetails();
		}

		redirectToEntityDetails()
		{
			qrauth.open({
				title: Loc.getMessage('M_CRM_DOCUMENT_SHARED_PHRASES_DESKTOP_VERSION'),
				redirectUrl: this.props.entityDetailUrl,
				layout: this.layoutWidget,
				analyticsSection: 'crm',
			});
		}

		hasPhoneChannel()
		{
			/** @type {object[]} */
			const channels = get(this.props, 'channelSelector.channels', []);

			return channels.some((item) => item.type === 'PHONE' && item.isAvailable === true);
		}

		hasEmailChannel()
		{
			/** @type {object[]} */
			const channels = get(this.props, 'channelSelector.channels', []);

			return channels.some((item) => item.type === 'EMAIL' && item.isAvailable === true);
		}

		hasOpenlineChannel()
		{
			/** @type {object[]} */
			const channels = get(this.props, 'channelSelector.channels', []);

			return channels.some((item) => item.type === 'IM' && item.isAvailable === true);
		}

		hasPhoneContact()
		{
			/** @type {object} */
			const communications = get(this.props, 'channelSelector.communications', {});

			return communications.hasOwnProperty('PHONE');
		}

		hasEmailContact()
		{
			/** @type {object} */
			const communications = get(this.props, 'channelSelector.communications', {});

			return communications.hasOwnProperty('EMAIL');
		}

		hasOpenlineContact()
		{
			const dialogId = get(this.props, 'channelSelector.communications.IM.VALUE', null);
			const dialogType = get(this.props, 'channelSelector.communications.IM.VALUE_TYPE', null);

			return dialogId && dialogType === 'OPENLINE';
		}
	}

	const SvgIcons = {
		arrowRight: '<svg width="25" height="24" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.78543 6.34358L13.3125 10.8706L14.4851 12L13.3125 13.13L8.78543 17.6571L10.3829 19.2546L17.6371 12.0004L10.3829 4.74624L8.78543 6.34358Z" fill="#767C87"/></svg>',
		cancel: '<svg width="31" height="30" viewBox="0 0 31 30" fill="none" xmlns="http://www.w3.org/2000/svg"><g opacity="0.5"><path d="M21.3542 6.97913L23.6458 9.27078L9.8959 23.0207L7.60425 20.729L21.3542 6.97913Z" fill="#525C69"/><path d="M23.6458 20.729L21.3542 23.0207L7.60425 9.27078L9.8959 6.97913L23.6458 20.729Z" fill="#525C69"/></g></svg>',
		sms: (disabled) => `<svg width="31" height="31" viewBox="0 0 31 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M5.63092 10.539C5.63091 9.1583 6.7502 8.039 8.13092 8.039H23.2597C24.6404 8.039 25.7597 9.15828 25.7597 10.539L25.7598 20.436C25.7598 21.8167 24.6405 22.936 23.2598 22.936H13.8868L11.1009 25.9105C10.791 26.2414 10.236 26.0221 10.236 25.5687V22.936H8.131C6.7503 22.936 5.63102 21.8168 5.631 20.4361L5.63092 10.539ZM11.7069 16.5059C11.7069 16.8492 11.6198 17.154 11.4456 17.4203C11.2715 17.6866 11.0203 17.8936 10.6921 18.0413C10.364 18.189 9.97903 18.2628 9.53728 18.2628C9.16873 18.2628 8.85951 18.2369 8.6096 18.1852C8.35969 18.1334 8.09969 18.0432 7.82959 17.9145V16.5816C8.11484 16.728 8.41144 16.8423 8.71941 16.9243C9.02737 17.0063 9.31009 17.0474 9.56757 17.0474C9.78971 17.0474 9.95252 17.0089 10.056 16.9319C10.1595 16.8549 10.2113 16.7558 10.2113 16.6346C10.2113 16.5589 10.1904 16.4926 10.1488 16.4359C10.1071 16.3791 10.0402 16.3216 9.94811 16.2636C9.85597 16.2055 9.61048 16.0869 9.21164 15.9076C8.85067 15.7436 8.57994 15.5845 8.39945 15.4306C8.21896 15.2766 8.08518 15.0999 7.99809 14.9005C7.911 14.701 7.86746 14.465 7.86746 14.1924C7.86746 13.6825 8.05299 13.2849 8.42406 12.9997C8.79514 12.7144 9.30504 12.5718 9.95378 12.5718C10.5268 12.5718 11.1112 12.7043 11.7069 12.9694L11.2487 14.1242C10.7313 13.8869 10.2845 13.7683 9.90835 13.7683C9.71398 13.7683 9.57262 13.8024 9.48427 13.8705C9.39592 13.9387 9.35174 14.0233 9.35174 14.1242C9.35174 14.2328 9.40791 14.33 9.52024 14.4158C9.63257 14.5016 9.93737 14.6581 10.4347 14.8853C10.9118 15.0999 11.2431 15.3302 11.4286 15.5763C11.6141 15.8224 11.7069 16.1323 11.7069 16.5059ZM14.9748 18.1866L13.8426 14.1995H13.8085C13.8616 14.8786 13.8881 15.4061 13.8881 15.7823V18.1866H12.5628V12.6509H14.5545L15.7093 16.5812H15.7396L16.8718 12.6509H18.8672V18.1866H17.4927V15.7595C17.4927 15.6333 17.4946 15.4932 17.4984 15.3392C17.5022 15.1853 17.5193 14.8079 17.5495 14.2071H17.5155L16.3985 18.1866H14.9748ZM23.4192 17.4203C23.5934 17.154 23.6805 16.8492 23.6805 16.5059C23.6805 16.1323 23.5877 15.8224 23.4022 15.5763C23.2167 15.3302 22.8854 15.0999 22.4083 14.8853C21.911 14.6581 21.6062 14.5016 21.4938 14.4158C21.3815 14.33 21.3253 14.2328 21.3253 14.1242C21.3253 14.0233 21.3695 13.9387 21.4579 13.8705C21.5462 13.8024 21.6876 13.7683 21.882 13.7683C22.2581 13.7683 22.7049 13.8869 23.2224 14.1242L23.6805 12.9694C23.0848 12.7043 22.5004 12.5718 21.9274 12.5718C21.2786 12.5718 20.7687 12.7144 20.3977 12.9997C20.0266 13.2849 19.8411 13.6825 19.8411 14.1924C19.8411 14.465 19.8846 14.701 19.9717 14.9005C20.0588 15.0999 20.1926 15.2766 20.3731 15.4306C20.5535 15.5845 20.8243 15.7436 21.1852 15.9076C21.5841 16.0869 21.8296 16.2055 21.9217 16.2636C22.0138 16.3216 22.0807 16.3791 22.1224 16.4359C22.164 16.4926 22.1849 16.5589 22.1849 16.6346C22.1849 16.7558 22.1331 16.8549 22.0296 16.9319C21.9261 17.0089 21.7633 17.0474 21.5412 17.0474C21.2837 17.0474 21.001 17.0063 20.693 16.9243C20.385 16.8423 20.0884 16.728 19.8032 16.5816V17.9145C20.0733 18.0432 20.3333 18.1334 20.5832 18.1852C20.8331 18.2369 21.1423 18.2628 21.5109 18.2628C21.9526 18.2628 22.3376 18.189 22.6657 18.0413C22.9939 17.8936 23.2451 17.6866 23.4192 17.4203Z" fill="${disabled ? AppTheme.colors.base5 : AppTheme.colors.base3}"/></svg>`,
		email: (disabled) => `<svg width="31" height="31" viewBox="0 0 31 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.22115 9.25L15.625 14.3368L23.0288 9.25H8.22115ZM24.375 10.5217V10.7833L15.625 17.2384L6.875 10.7833V20.6484C6.875 21.2576 7.43864 21.75 8.13342 21.75H23.1165C23.813 21.75 24.375 21.2569 24.375 20.6484V10.7833L24.375 10.7833L24.375 10.5217Z" fill="${disabled ? AppTheme.colors.base5 : AppTheme.colors.base3}"/></svg>`,
		openline: (disabled) => `<svg width="31" height="31" viewBox="0 0 31 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M22.1525 8.25435C25.7833 11.5126 26.4071 16.8913 23.6066 20.7769L24.3135 23.2533C24.375 23.4719 24.3165 23.7045 24.1583 23.8662C24.001 24.0262 23.7677 24.0887 23.5468 24.0309L20.9868 23.3529C20.9443 23.3343 20.9049 23.3111 20.8676 23.2836C16.7529 25.736 11.3665 24.6434 8.47908 20.7705C5.55942 16.8833 6.01911 11.505 9.54983 8.2497C13.0606 5.02018 18.5429 5.02229 22.1525 8.25435ZM18.147 16.8218C18.3694 16.7598 18.6104 16.8293 18.7645 17.0004L20.1996 18.5098C20.4511 18.7865 20.4578 19.1976 20.2149 19.4743L18.8328 20.9814C18.6834 21.1524 18.4448 21.2224 18.2209 21.1599C17.9914 21.0885 17.8172 20.9043 17.7632 20.6765C17.7003 20.443 17.759 20.1968 17.9198 20.0174L18.2353 19.6725H12.3278C12.0314 19.657 11.7995 19.4123 11.8076 19.1243L11.8033 18.8574C11.7866 18.5695 12.0104 18.3252 12.3058 18.3087H18.209L17.8826 17.9648C17.7155 17.7849 17.6492 17.5383 17.705 17.3052C17.7518 17.0774 17.9207 16.8932 18.147 16.8218ZM13.7553 13.4309C13.6445 13.5568 13.4818 13.6291 13.3105 13.6291C13.1382 13.6291 12.9735 13.5568 12.8585 13.4309L11.4282 11.9247C11.1767 11.649 11.17 11.2374 11.4129 10.9617L12.795 9.45128C12.9067 9.32538 13.069 9.2535 13.2408 9.25444C13.4126 9.25491 13.5768 9.32772 13.6908 9.4541C13.9428 9.72893 13.95 10.1395 13.7085 10.4153L13.3926 10.7601H19.3C19.5964 10.7747 19.8288 11.0199 19.8202 11.3084L19.8245 11.5743C19.8422 11.8627 19.6179 12.107 19.322 12.1225H13.414L13.74 12.4669C13.991 12.7436 13.9977 13.1546 13.7553 13.4309ZM12.4452 14.5758L12.4466 14.5748H19.5864C19.8837 14.5908 20.1156 14.837 20.1061 15.1268L20.1109 15.3946C20.128 15.6831 19.9037 15.9274 19.6078 15.9429H12.4667C12.1708 15.9274 11.9384 15.6831 11.9465 15.3946L11.9422 15.124C11.925 14.8356 12.1493 14.5908 12.4452 14.5758Z" fill="${disabled ? AppTheme.colors.base5 : AppTheme.colors.base3}"/></svg>`,
		link: (disabled) => `<svg width="31" height="31" viewBox="0 0 31 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M16.4605 17.1142L17.1535 16.4213C18.6874 17.3272 20.6962 17.1212 22.0141 15.8033L24.8425 12.9748C26.4046 11.4128 26.4046 8.88009 24.8425 7.318L24.1354 6.61089C22.5733 5.04879 20.0407 5.04879 18.4786 6.61089L15.6502 9.43932C14.3322 10.7572 14.1262 12.766 15.0322 14.3L14.3392 14.9929C12.8053 14.087 10.7965 14.293 9.47858 15.6109L6.65015 18.4393C5.08805 20.0014 5.08805 22.5341 6.65015 24.0962L7.35726 24.8033C8.91935 26.3654 11.452 26.3654 13.0141 24.8033L15.8425 21.9748C17.1605 20.6569 17.3665 18.6481 16.4605 17.1142ZM19.8928 8.0251C20.6738 7.24405 21.9402 7.24405 22.7212 8.0251L23.4283 8.73221C24.2094 9.51326 24.2094 10.7796 23.4283 11.5606L20.5999 14.3891C19.8189 15.1701 18.5525 15.1701 17.7715 14.3891L17.0644 13.682C16.2833 12.9009 16.2833 11.6346 17.0644 10.8535L19.8928 8.0251ZM10.8928 17.0251C11.6738 16.2441 12.9402 16.2441 13.7212 17.0251L14.4283 17.7322C15.2094 18.5133 15.2094 19.7796 14.4283 20.5606L11.5999 23.3891C10.8188 24.1701 9.55252 24.1701 8.77147 23.3891L8.06436 22.682C7.28331 21.9009 7.28331 20.6346 8.06436 19.8535L10.8928 17.0251Z" fill="${disabled ? AppTheme.colors.base5 : AppTheme.colors.base3}"/></svg>`,
		share: '<svg width="25" height="24" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13.7767 13.5747C13.7767 13.7956 13.5976 13.9747 13.3767 13.9747H11.9091C11.6882 13.9747 11.5091 13.7956 11.5091 13.5747V8.02642H9.49426C9.22104 8.02642 9.09004 7.69096 9.29096 7.50581L12.4301 4.61298C12.545 4.50713 12.7218 4.50713 12.8367 4.61298L15.9758 7.50581C16.1767 7.69096 16.0457 8.02642 15.7725 8.02642H13.7767V13.5747Z" fill="#6A737F"/><path d="M5.625 13.1066C5.625 12.8857 5.80409 12.7066 6.025 12.7066H7.50137C7.72228 12.7066 7.90137 12.8857 7.90137 13.1066V15.7623C7.90137 16.3146 8.34908 16.7623 8.90137 16.7623H16.3486C16.9009 16.7623 17.3486 16.3146 17.3486 15.7623V13.1066C17.3486 12.8857 17.5277 12.7066 17.7486 12.7066H19.225C19.4459 12.7066 19.625 12.8857 19.625 13.1066V16.6996C19.625 17.9698 18.5953 18.9996 17.325 18.9996H7.925C6.65475 18.9996 5.625 17.9698 5.625 16.6996V13.1066Z" fill="#6A737F"/></svg>',
		tariffLock: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M12.1457 15.0268V16.619H10.8846V15.0268C10.6577 14.8418 10.5124 14.5592 10.5124 14.2424C10.5124 13.6852 10.9614 13.2335 11.5152 13.2335C12.069 13.2335 12.518 13.6852 12.518 14.2424C12.518 14.5592 12.3727 14.8418 12.1457 15.0268ZM8.76969 8.55812C8.76969 7.03251 9.99888 5.79577 11.5151 5.79577C13.0314 5.79577 14.2606 7.03251 14.2606 8.55812V10.8932H8.76969V8.55812ZM15.7115 10.8932V8.55812C15.7115 6.22625 13.8327 4.33594 11.5151 4.33594C9.19758 4.33594 7.31877 6.22625 7.31877 8.55812V10.8932H6.0448V18.8895H16.9855V10.8932H15.7115Z" fill="#828B95"/></svg>',
	};

	module.exports = { CrmDocumentShareDialog };
});
