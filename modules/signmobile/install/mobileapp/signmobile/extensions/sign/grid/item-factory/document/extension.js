/**
 * @module sign/grid/item-factory/document
 */
jn.define('sign/grid/item-factory/document', (require, exports, module) => {
	const { ChipStatus, ChipStatusDesign, ChipStatusMode } = require('ui-system/blocks/chips/chip-status');
	const { Button, ButtonSize, ButtonDesign } = require('ui-system/form/buttons/button');
	const { BadgeCounter, BadgeCounterDesign } = require('ui-system/blocks/badges/counter');
	const { makeLibraryImagePath } = require('ui-system/blocks/status-block');
	const { Base } = require('layout/ui/simple-list/items/base');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { H5 } = require('ui-system/typography/heading');
	const { Text6 } = require('ui-system/typography/text');
	const { Avatar } = require('ui-system/blocks/avatar');
	const { ProfileView } = require('user/profile');
	const { InitiatedByType } = require('sign/type/initiated-by-type');
	const { ActionStatus } = require('sign/type/action-status');
	const { MemberStatus } = require('sign/type/member-status');
	const { DocumentStatus } = require('sign/type/document-status');
	const { MemberRole } = require('sign/type/member-role');
	const { downloadFile } = require('sign/download-file');
	const { SignOpener } = require('sign/opener');
	const { FriendlyDate } = require('layout/ui/friendly-date');
	const { date } = require('utils/date/formats');
	const { Moment } = require('utils/date');
	const { Indent, Color, Component } = require('tokens');
	const { useCallback } = require('utils/function');
	const { Loc } = require('loc');

	const DOCUMENT_IMAGE_NAMES = { default: 'sign-doc.svg', pdf: 'pdf-doc.svg', zip: 'zip-doc.svg' };

	class Document extends Base
	{
		renderItemContent()
		{
			return View(
				{
					style: {
						position: 'relative',
						paddingLeft: Component.cardPaddingLr.toNumber(),
						paddingRight: Component.cardPaddingLr.toNumber(),
						paddingBottom: Component.cardPaddingB.toNumber(),
						paddingTop: Component.cardPaddingT.toNumber(),
						borderRadius: Component.cardCorner.toNumber(),
						borderColor: Color.bgSeparatorPrimary.toHex(),
						marginBottom: 15,
						marginLeft: 15,
						marginRight: 13,
						borderWidth: 1,
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
						},
					},
					this.renderImage(),
					this.renderBody(),
					this.renderDownloadButton(),
				),
				this.renderActionButton(),
				this.renderBadge()
			);
		}

		renderImage()
		{
			const imageName = ActionStatus.isDownloadStatus(this.getAction())
				? DOCUMENT_IMAGE_NAMES[this.getFileExtension()] ?? DOCUMENT_IMAGE_NAMES.default
				: DOCUMENT_IMAGE_NAMES.default
			;

			return Image(
				{
					svg: {
						uri: makeLibraryImagePath(imageName, 'empty-states', 'sign'),
					},
					style: {
						width: 40,
						height: 40,
					},
				},
			);
		}

		renderBody()
		{
			return View(
				{
					style: {
						flex: 1,
						paddingLeft: Indent.XL.toNumber(),
						paddingBottom: Indent.M.toNumber(),
					},
				},
				this.renderTitle(),
				this.renderDescription(),
				this.renderSecondSide(),
			);
		}

		renderTitle()
		{
			return this.renderText({
				type: 'title',
				text: this.getDocumentTitle(),
				typography: H5,
				style: {
					marginBottom: Indent.XS.toNumber(),
					marginRight: 15,
				},
			});
		}

		renderDescription()
		{
			if (InitiatedByType.isInitiatedByEmployee(this.getDocumentInitiatedType())
				&& !DocumentStatus.isFinalStatus(this.getDocumentStatus())
				&& this.isInitiatorCurrentUser()
				&& !ActionStatus.isActionStatus(this.getAction()))
			{
				return this.renderText({
					type: 'description',
					text: Loc.getMessage(
						'SIGN_MOBILE_GRID_DOCUMENT_DATE_SEND',
						{ '#DATE#': this.getDocumentSendDate() },
					),
					typography: Text6,
					style: {
						marginTop: Indent.M.toNumber(),
						marginBottom: Indent.M.toNumber(),
					},
				});
			}

			return View();
		}

		renderSecondSide()
		{
			if (!ActionStatus.isActionStatus(this.getAction()))
			{
				const { secondSideText, SecondSideDesign, secondSideMemberId } = this.prepareSecondSideData();


				return View(
					{
						style: {
							flexDirection: 'row',
							marginTop: 10,
						},
						onClick: useCallback(() => this.#onSecondSideButtonClickHandler(secondSideMemberId), [secondSideMemberId]),
					},
					Avatar({
						id: secondSideMemberId,
						testId: `USER_AVATAR_${this.getMemberId(0)}`,
						size: 24,
						withRedux: true,
						onClick: useCallback(() => this.#onSecondSideButtonClickHandler(secondSideMemberId), [secondSideMemberId]),
					}),
					ChipStatus({
						text: secondSideText,
						design: SecondSideDesign,
						mode: ChipStatusMode.OUTLINE,
						style: {
							marginLeft: 10,
						},
					}),
				);
			}

			return View();
		}

		renderDownloadButton()
		{
			if (ActionStatus.isDownloadStatus(this.getAction()) && this.getFileUrl())
			{
				return View(
					{
						style: {
							display: 'flex',
							align: 'center',
							justifyContent: 'center',
						},
					},
					IconView({
						size: {
							height: 40,
							width: 40,
						},
						icon: Icon.CHEVRON_TO_THE_RIGHT_SIZE_S,
						color: Color.base1,
						opacity: 0.5,
						onClick: this.#onDownloadButtonClickHandler,
					}),
				);
			}

			return View();
		}

		renderBadge()
		{
			if (!ActionStatus.isActionStatus(this.getAction()))
			{
				return View();
			}

			return View(
				{
					style: {
						position: 'absolute',
						top: Indent.S.toNumber(),
						right: Indent.S.toNumber(),
					},
				},
				BadgeCounter({value: 1, design: BadgeCounterDesign.ALERT}),
			);
		}

		renderActionButton()
		{
			let buttonText = '';
			switch (this.getAction())
			{
				case ActionStatus.SIGN.value:
					buttonText = Loc.getMessage('SIGN_MOBILE_GRID_SIGN_BUTTON_TEXT');
					break;
				case ActionStatus.APPROVE.value:
					buttonText = Loc.getMessage('SIGN_MOBILE_GRID_APPROVE_BUTTON_TEXT');
					break;
				case ActionStatus.EDIT.value:
					buttonText = Loc.getMessage('SIGN_MOBILE_GRID_EDIT_BUTTON_TEXT');
					break;
				default:
					return View();
			}

			return Button({
				text: buttonText,
				testId: 'Button',
				size: ButtonSize.M,
				design: ButtonDesign.OUTLINE_ACCENT_2,
				stretched: true,
				onClick: this.#onSignButtonClickHandler,
				style: {
					width: '50%',
					marginTop: 10,
				},
			});
		}

		prepareSecondSideData()
		{
			let secondSideText = '';
			let SecondSideDesign = ChipStatusDesign.NEUTRAL;
			let secondSideMemberId = this.getMemberUserId(0);

			if (InitiatedByType.isInitiatedByEmployee(this.getDocumentInitiatedType())
				&& this.isInitiatorCurrentUser()
				&& this.isMemberCurrentUser()
				&& !DocumentStatus.isFinalStatus(this.getDocumentStatus())
				&& !MemberStatus.isDoneStatus(this.getMemberStatus(0))
			)
			{
				switch (this.getMemberRole(0))
				{
					case MemberRole.ASSIGNEE.value:
					case MemberRole.SIGNER.value:
						secondSideText = Loc.getMessage(
							'SIGN_MOBILE_GRID_SECOND_SIDE_STATUS_SIGNING',
						);
						break;
					case MemberRole.EDITOR.value:
						secondSideText = Loc.getMessage(
							'SIGN_MOBILE_GRID_SECOND_SIDE_STATUS_EDITING',
						);
						break;
					case MemberRole.REVIEWER.value:
						secondSideText = Loc.getMessage(
							'SIGN_MOBILE_GRID_SECOND_SIDE_STATUS_APPROVING',
						);
						break;
					default:
						break;
				}

				return { secondSideText, SecondSideDesign, secondSideMemberId };
			}

			if (MemberStatus.isCanceledStatus(this.getMemberStatus(0)) || DocumentStatus.isStopped(this.getDocumentStatus()))
			{
				if (this.isCurrentUserCanceled())
				{
					return {
						secondSideText: Loc.getMessage(
							'SIGN_MOBILE_GRID_SECOND_SIDE_STATUS_REFUSED_BY_YOU',
							{ '#DATE#': String(this.getDocumentCancelledDate()) },
						),
						SecondSideDesign: ChipStatusDesign.WARNING,
						secondSideMemberId: this.getInitiatorUserId(),
					};
				}
				if (InitiatedByType.isInitiatedByEmployee(this.getDocumentInitiatedType())
					&& ((this.isInitiatorCurrentUser() && !this.isMemberCurrentUser()) || this.isSecondSideStopped(0))
				)
				{
					return {
						secondSideText: Loc.getMessage(
							'SIGN_MOBILE_GRID_SECOND_SIDE_STATUS_REFUSED',
							{ '#DATE#': String(this.getDocumentCancelledDate()) },
						),
						SecondSideDesign: ChipStatusDesign.WARNING,
						secondSideMemberId,
					};
				}
			}

			switch (this.getMemberRole(0))
			{
				case MemberRole.ASSIGNEE.value:
				case MemberRole.SIGNER.value:
					if (MemberStatus.isDoneStatus(this.getMemberStatus(0)))
					{
						SecondSideDesign = ChipStatusDesign.SUCCESS;

						secondSideText = Loc.getMessage(
							this.isMemberCurrentUser()
								? 'SIGN_MOBILE_GRID_SECOND_SIDE_STATUS_SIGN_BY_YOU'
								: 'SIGN_MOBILE_GRID_SECOND_SIDE_STATUS_SIGN',
							{ '#DATE#': String(this.getDocumentSignDate()) },
						);
					}
					else
					{
						secondSideText = Loc.getMessage(
							'SIGN_MOBILE_GRID_SECOND_SIDE_STATUS_SIGNING',
						);
					}
					break;
				case MemberRole.EDITOR.value:
					if (MemberStatus.isDoneStatus(this.getMemberStatus(0)))
					{
						secondSideText = Loc.getMessage(
							this.isMemberCurrentUser()
								? 'SIGN_MOBILE_GRID_SECOND_SIDE_STATUS_EDIT_BY_YOU'
								: 'SIGN_MOBILE_GRID_SECOND_SIDE_STATUS_EDIT',
							{ '#DATE#': String(this.getDocumentEditDate()) },
						);
					}
					else
					{
						secondSideText = Loc.getMessage(
							'SIGN_MOBILE_GRID_SECOND_SIDE_STATUS_EDITING',
						);
					}
					break;
				case MemberRole.REVIEWER.value:
					if (MemberStatus.isDoneStatus(this.getMemberStatus(0)))
					{
						secondSideText = Loc.getMessage(
							this.isMemberCurrentUser()
								? 'SIGN_MOBILE_GRID_SECOND_SIDE_STATUS_APPROVE_BY_YOU'
								: 'SIGN_MOBILE_GRID_SECOND_SIDE_STATUS_APPROVE',
							{ '#DATE#': String(this.getDocumentApprovedDate()) },
						);
					}
					else
					{
						secondSideText = Loc.getMessage(
							'SIGN_MOBILE_GRID_SECOND_SIDE_STATUS_APPROVING',
						);
					}
					break;
				default:
					break;
			}

			secondSideMemberId = this.isMemberCurrentUser() && MemberStatus.isDoneStatus(this.getMemberStatus(0))
				? this.getInitiatorUserId()
				: secondSideMemberId;

			return { secondSideText, SecondSideDesign, secondSideMemberId };
		}

		renderText({ text, typography, style })
		{
			return typeof text === 'string'
				? typography({ text, style })
				: View({ style }, text);
		}

		isCurrentUserCanceled()
		{
			if (this.isMemberCurrentUser() && MemberStatus.isCanceledStatus(this.getMemberStatus(0)))
			{
				return true;
			}
			else if (this.isMyMemberCurrentUser() && MemberStatus.isCanceledStatus(this.getMyMemberStatus()))
			{
				return true;
			}

			return false;
		}

		isMyMemberCurrentUser()
		{
			return Number(env.userId) === this.getMyMemberUserId()
				&& this.getMemberUserId(0) === this.getMyMemberUserId()
			;
		}

		isMemberCurrentUser()
		{
			return Number(env.userId) === this.getMemberUserId(0);
		}

		isInitiatorCurrentUser()
		{
			return Number(env.userId) === this.getInitiatorUserId();
		}

		isSecondSideStopped(id)
		{
			return this.props.item?.members[id]?.isStopped;
		}

		#onSecondSideButtonClickHandler = (userId) => {
			ProfileView.open({
				userId,
			});
		};

		#onDownloadButtonClickHandler = () => {
			return downloadFile(currentDomain + String(this.getFileUrl()));
		};

		#onSignButtonClickHandler = () => {
			SignOpener.openSigning({
				memberId: this.getMemberId(0),
			});
		};

		getFileExtension()
		{
			return this.props.item?.file?.ext;
		}

		getDocumentTitle()
		{
			return this.props.item?.document?.title;
		}

		getDocumentInitiatedType()
		{
			return this.props.item?.document?.initiatedByType;
		}

		getDocumentSendDate()
		{
			return this.getFormattedDate(this.props.item?.document?.sendDate);
		}

		getDocumentCancelledDate()
		{
			return this.getFormattedDate(this.props.item?.document?.cancelledDate);
		}

		getDocumentSignDate()
		{
			return this.getFormattedDate(this.props.item?.document?.signDate);
		}

		getDocumentEditDate()
		{
			return this.getFormattedDate(this.props.item?.document?.editDate);
		}

		getDocumentApprovedDate()
		{
			return this.getFormattedDate(this.props.item?.document?.approvedDate);
		}

		getFormattedDate(sourceDate)
		{
			if (!sourceDate)
			{
				return '';
			}

			const moment = new Moment(sourceDate);

			return (new FriendlyDate({
				moment,
				defaultFormat: date(),
				useTimeAgo: true,
			})).makeText(moment);
		}

		getAction()
		{
			return this.props.item?.action;
		}

		getDocumentStatus()
		{
			return this.props.item?.document?.status;
		}

		getFileUrl()
		{
			return this.props.item?.file?.url;
		}

		getMemberStatus(id)
		{
			return this.props.item?.members[id]?.status;
		}

		getMyMemberStatus()
		{
			return this.props.item?.myMemberInProcess?.status;
		}

		getMyMemberUserId()
		{
			return this.props.item?.myMemberInProcess?.userId;
		}

		getMemberRole(id)
		{
			return this.props.item?.members[id]?.role;
		}

		getMemberUserId(id)
		{
			return this.props.item?.members[id]?.userId;
		}

		getMemberId(id)
		{
			return this.props.item?.members[id]?.id;
		}

		getInitiatorUserId()
		{
			return this.props.item?.document?.initiator?.userId
		}
	}

	module.exports = { Document };
});
