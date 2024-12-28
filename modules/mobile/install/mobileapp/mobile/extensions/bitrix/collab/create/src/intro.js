/**
 * @module collab/create/src/intro
 */
jn.define('collab/create/src/intro', (require, exports, module) => {
	const { Button, ButtonSize } = require('ui-system/form/buttons');
	const { Text4, Text5 } = require('ui-system/typography/text');
	const { H3 } = require('ui-system/typography/heading');
	const { Area } = require('ui-system/layout/area');
	const { DialogFooter } = require('ui-system/layout/dialog-footer');
	const { Indent, Color } = require('tokens');
	const { Loc } = require('loc');
	const { Icon } = require('assets/icons');
	const { Link4, LinkMode, Ellipsize } = require('ui-system/blocks/link');

	const helpArticleCode = '22706808';

	const CollabCreateIntro = ({ onContinue }) => {
		return View(
			{
				style: {
					flex: 1,
				},
			},
			Area(
				{
					style: {
						flexDirection: 'column',
						flexGrow: 1,
						backgroundColor: Color.bgContentPrimary.toHex(),
					},
				},
				HeroImage(),
				Title({
					text: Loc.getMessage('M_COLLAB_CREATE_INTRO_TITLE')
						.replaceAll('[COLOR]', `[COLOR=${Color.accentMainSuccess.toHex()}]`),
				}),
				FeatureBox(
					Feature({
						text: Loc.getMessage('M_COLLAB_CREATE_INTRO_FEATURE_1'),
						icon: Icon.PERSON_CHECKS,
					}),
					Feature({
						text: Loc.getMessage('M_COLLAB_CREATE_INTRO_FEATURE_2'),
						icon: Icon.CHATS,
					}),
					Feature({
						text: Loc.getMessage('M_COLLAB_CREATE_INTRO_FEATURE_3'),
						icon: Icon.SHIELD,
					}),
				),
				InviteHint(),
			),
			DialogFooter(
				{
					safeArea: true,
					backgroundColor: Color.bgContentPrimary,
				},
				Button({
					testId: 'CollabCreate_IntroScreen_Button',
					size: ButtonSize.L,
					text: Loc.getMessage('M_COLLAB_CREATE_INTRO_CONTINUE'),
					stretched: true,
					backgroundColor: Color.accentMainPrimary,
					onClick: onContinue,
				}),
			),
		);
	};

	const HeroImage = () => View(
		{
			style: {
				alignItems: 'center',
				marginBottom: Indent.XL3.getValue(),
			},
		},
		Image({
			style: {
				width: 143,
				height: 143,
			},
			svg: {
				content: '<svg width="143" height="142" viewBox="0 0 143 142" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M42.2281 37.1637C35.6867 42.4849 37.8741 58.5051 47.0017 58.6242C57.5877 58.7611 58.4835 36.2292 47.0017 35.6161C45.35 35.5268 43.2995 36.2917 42.2281 37.1637ZM61.4893 75.1206C61.5637 76.0759 60.9536 76.8765 60.159 76.8914L33.0588 77.4211C32.3326 77.436 31.7642 76.7009 31.8654 75.8706C33.038 66.3025 40.4603 62.8978 47.0226 62.9336C53.6146 62.9693 60.7393 65.487 61.4893 75.1236V75.1206Z" fill="white"/><g opacity="0.8"><path d="M44.9182 20.5096C48.7573 18.6377 53.4208 19.0276 57.1469 21.4828L80.4407 36.8305C83.8215 39.0566 85.8809 42.9225 85.8809 46.9908V73.7071C85.8809 77.7754 83.8215 81.5967 80.4407 83.7514L57.1469 98.602C53.4208 100.977 48.7573 101.269 44.9182 99.3133L18.3596 85.8048C14.0949 83.6353 11.4402 79.4271 11.4402 74.7874V44.3272C11.4402 39.6875 14.0979 35.5329 18.3596 33.4556L44.9182 20.5096Z" fill="#19CC45"/><path d="M45.1373 20.9591L45.1373 20.9591C48.8067 19.1699 53.2828 19.5354 56.8718 21.9003L57.1469 21.4828L56.8718 21.9003L80.1656 37.248L80.1657 37.2481C83.404 39.3803 85.3809 43.0882 85.3809 46.9908V73.7071C85.3809 77.609 83.405 81.2692 80.1719 83.3297L80.1719 83.3297L56.8781 98.1804C56.8781 98.1804 56.8781 98.1804 56.8781 98.1804C53.2941 100.465 48.8189 100.739 45.1451 98.8677L45.1448 98.8676L18.5863 85.3592C14.4814 83.2709 11.9402 79.2303 11.9402 74.7874V44.3272C11.9402 39.8856 14.4826 35.9016 18.5787 33.905L18.5787 33.905L45.1373 20.9591Z" stroke="white" stroke-opacity="0.18"/></g><path d="M96.7149 48.3361C99.9558 46.4641 103.89 46.6129 107.042 48.6873L126.806 61.6868C129.684 63.5796 131.44 67.0259 131.44 70.746V95.1707C131.44 98.8908 129.684 102.459 126.806 104.554L107.042 118.935C103.89 121.226 99.9529 121.652 96.7149 120.006L74.3943 108.676C70.826 106.864 68.6028 103.147 68.6028 98.9443V71.359C68.6028 67.1568 70.826 63.2849 74.3943 61.2255L96.7149 48.3361Z" fill="#0075FF" fill-opacity="0.78"/><path d="M96.9649 48.7691L96.965 48.769C100.038 46.994 103.769 47.132 106.767 49.1049L106.767 49.105L126.531 62.1045L126.806 61.6868L126.531 62.1045C129.255 63.8958 130.94 67.177 130.94 70.746V95.1707C130.94 98.7403 129.253 102.154 126.512 104.15L106.748 118.53C103.732 120.723 99.9946 121.112 96.9414 119.56L96.9412 119.56L74.6207 108.23C71.2292 106.508 69.1028 102.969 69.1028 98.9443V71.359C69.1028 67.3324 71.2332 63.6272 74.6442 61.6585L74.6443 61.6585L96.9649 48.7691Z" stroke="white" stroke-opacity="0.18"/><g filter="url(#filter0_d_2950_90298)"><path fill-rule="evenodd" clip-rule="evenodd" d="M43.8004 39.1944C36.0548 44.4763 38.6461 60.455 49.4419 60.5692C61.9115 60.6992 62.962 38.3316 49.4419 37.6749C47.4897 37.5797 45.0692 38.3316 43.8004 39.1944ZM66.4931 76.9286C66.5817 77.8771 65.8634 78.6733 64.93 78.6924L32.9318 79.3332C32.0712 79.349 31.3941 78.6194 31.5143 77.7883C32.9065 68.2334 41.7058 64.8264 49.4609 64.8549C57.2318 64.8803 65.6103 67.3705 66.4899 76.9286H66.4931Z" fill="white" fill-opacity="0.9" shape-rendering="crispEdges"/></g><g filter="url(#filter1_d_2950_90298)"><path fill-rule="evenodd" clip-rule="evenodd" d="M101.598 99.3256C103.321 99.2072 104.658 97.7748 104.658 96.0476V86.8425L113.478 86.454C115.169 86.3796 116.5 84.9877 116.5 83.2959C116.5 81.5116 115.025 80.081 113.241 80.1362L104.658 80.4019V72.6474C104.658 70.8216 103.17 69.346 101.344 69.3617C99.5406 69.3772 98.0866 70.8437 98.0866 72.6474V80.6054L89.4805 80.8719C87.6897 80.9274 86.2667 82.3951 86.2667 84.1867C86.2667 86.0756 87.842 87.583 89.729 87.4999L98.0866 87.1319V96.0476C98.0866 97.951 99.6988 99.4561 101.598 99.3256Z" fill="white" fill-opacity="0.9" shape-rendering="crispEdges"/></g><defs><filter id="filter0_d_2950_90298" x="21.5005" y="31.6667" width="55" height="61.6667" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="4"/><feGaussianBlur stdDeviation="5"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.05 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_2950_90298"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_2950_90298" result="shape"/></filter><filter id="filter1_d_2950_90298" x="76.2667" y="63.3616" width="50.2338" height="49.9719" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="4"/><feGaussianBlur stdDeviation="5"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.05 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_2950_90298"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_2950_90298" result="shape"/></filter></defs></svg>',
			},
		}),
	);

	const Title = ({ text }) => View(
		{
			style: {
				marginBottom: Indent.XL3.getValue(),
			},
		},
		H3({
			value: text,
			nativeElement: BBCodeText,
			linksUnderline: false,
			color: Color.base2,
			numberOfLines: 2,
			style: {
				textAlign: 'center',
			},
		}),
	);

	const FeatureBox = (...children) => View(
		{
			style: {
				flexDirection: 'row',
				justifyContent: 'center',
			},
		},
		View(
			{
				style: {
					paddingHorizontal: Indent.XL3.getValue(),
					paddingTop: Indent.XL2.getValue(),
					marginBottom: Indent.XL3.getValue(),
					maxWidth: 340,
				},
			},
			...children,
		),
	);

	const Feature = ({ text, icon }) => View(
		{
			style: {
				flexDirection: 'row',
				justifyContent: 'flex-start',
				marginBottom: Indent.XL2.getValue(),
			},
		},
		Image({
			named: icon.getIconName(),
			tintColor: Color.accentMainSuccess.toHex(),
			style: {
				width: 28,
				height: 28,
				marginRight: Indent.L.getValue(),
			},
		}),
		Text4({
			text: text.replaceAll('#BR#', '\n'),
			numberOfLines: 2,
			color: Color.base2,
			style: {
				flexShrink: 1,
			},
		}),
	);

	const InviteHint = () => View(
		{
			style: {
				alignItems: 'center',
				marginTop: Indent.S.toNumber(),
			},
		},
		Text5({
			text: Loc.getMessage('M_COLLAB_CREATE_INTRO_INVITE'),
			color: Color.base3,
			style: {
				textAlign: 'center',
			},
		}),
		Link4({
			testId: 'CollabCreate_IntroScreen_Link4',
			text: Loc.getMessage('M_COLLAB_CREATE_DETAILS_LINK'),
			ellipsize: Ellipsize.END,
			mode: LinkMode.SOLID,
			color: Color.base4,
			numberOfLines: 1,
			textDecorationLine: 'underline',
			style: {
				marginTop: Indent.XL3.toNumber(),
			},
			onClick: () => helpdesk.openHelpArticle(helpArticleCode),
		}),
	);

	module.exports = { CollabCreateIntro };
});
