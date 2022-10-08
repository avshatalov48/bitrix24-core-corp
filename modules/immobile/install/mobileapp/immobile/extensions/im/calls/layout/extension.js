(function ()
{
	include("Calls");

	const pathToExtension = currentDomain + "/bitrix/mobileapp/immobile/extensions/im/calls/layout/";

	const LARGE_AVATAR_SIZE = 213;
	const GRAY_MENU_OPTION = "#828B95";

	const Icons = {
		cameraOff: `<svg width="20" height="16" viewBox="0 0 20 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.5588 13.7376L2.0295 1.20829C1.70686 0.885649 1.18376 0.885649 0.861119 1.20829C0.538481 1.53093 0.538481 2.05403 0.861119 2.37666L13.3904 14.9059C13.713 15.2286 14.2361 15.2286 14.5588 14.9059C14.8814 14.5833 14.8814 14.0602 14.5588 13.7376ZM9.38184 13.1442L1.51486 5.27634L1.5153 12.2642C1.5153 12.7505 1.90955 13.1448 2.39587 13.1448L9.38184 13.1442ZM19.2407 3.90959C19.2521 3.93968 19.258 3.9716 19.258 4.00379V11.8444C19.258 11.9911 19.1391 12.11 18.9924 12.11C18.9602 12.11 18.9283 12.1041 18.8982 12.0927L14.8907 10.5722C14.7875 10.533 14.7193 10.4342 14.7193 10.3239V5.52431C14.7193 5.41399 14.7875 5.31515 14.8907 5.27601L18.8982 3.7555C19.0353 3.70347 19.1887 3.77246 19.2407 3.90959ZM12.9843 2.51886L5.75892 2.51835L13.8645 10.6231L13.8648 3.39942C13.8648 2.9131 13.4706 2.51886 12.9843 2.51886Z" fill="#FF5752"/></svg>`,
		microphoneOff: `<svg width="20" height="25" viewBox="0 0 20 25" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.1741 11.8802V11.5285L10.0427 15.397C9.93208 15.4075 9.82002 15.4128 9.70672 15.4128C7.75571 15.4128 6.1741 13.8312 6.1741 11.8802ZM9.71784 17.6283C10.5546 17.6283 11.2904 17.5116 11.9358 17.2902L13.9031 19.2575C13.083 19.6807 12.1467 19.9849 11.0966 20.1277L11.0956 21.8311L11.6568 21.8315C12.3014 21.8315 12.824 22.354 12.824 22.9987C12.824 23.6433 12.3014 24.1659 11.6568 24.1659H7.75133C7.10671 24.1659 6.58413 23.6433 6.58413 22.9987C6.58413 22.354 7.10671 21.8315 7.75133 21.8315L8.31072 21.8311L8.31062 20.1266C3.62798 19.4942 1.23424 15.7275 1.27062 12.3587C1.27834 11.6434 1.8645 11.0698 2.57982 11.0774C3.24747 11.0847 3.79167 11.5958 3.85498 12.2455L3.86106 12.3867C3.8538 13.0587 4.22913 14.349 4.92184 15.3525C5.91637 16.7933 7.44527 17.6283 9.71784 17.6283ZM15.6351 11.902L17.9247 14.1916C18.0787 13.5725 18.15 12.9492 18.1372 12.3452C18.122 11.63 17.5299 11.0625 16.8147 11.0777C16.2752 11.0892 15.8198 11.4288 15.6351 11.902ZM6.55966 2.82655L13.2393 9.50623V4.43301C13.2393 2.482 11.6577 0.900391 9.70672 0.900391C8.33421 0.900391 7.14451 1.68312 6.55966 2.82655ZM0.9589 2.00146C0.594429 2.36593 0.594429 2.95685 0.958901 3.32133L17.8599 20.2223C18.2244 20.5868 18.8153 20.5868 19.1798 20.2223L19.4141 19.988C19.7786 19.6235 19.7786 19.0326 19.4141 18.6681L2.5131 1.76713C2.14863 1.40266 1.5577 1.40266 1.19323 1.76713L0.9589 2.00146Z" fill="#FF5752"/></svg>`,
		arrowDown: `<svg width="13" height="8" viewBox="0 0 13 8" fill="none" xmlns="http://www.w3.org/2000/svg"><path opacity="0.8" fill-rule="evenodd" clip-rule="evenodd" d="M11.4592 0.131348L7.49805 4.09252L6.5001 5.07502L5.52106 4.09252L1.55989 0.131348L0.162109 1.52913L6.5095 7.87652L12.8569 1.52913L11.4592 0.131348Z" fill="white"/></svg>`,
		arrowLeft: `<svg width="34" height="34" fill="none" xmlns="http://www.w3.org/2000/svg"><g filter="url(#filter0_d)"><path fill-rule="evenodd" clip-rule="evenodd" d="M22.47 25.014l-6.414-6.413L14.45 17l1.606-1.6 6.413-6.413-2.263-2.263L9.93 17l10.276 10.277 2.264-2.263z" fill="#fff"/></g><defs><filter id="filter0_d" x="7.93" y="5.724" width="16.54" height="24.553" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"/><feOffset dy="1"/><feGaussianBlur stdDeviation="1"/><feColorMatrix values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.06 0"/><feBlend in2="BackgroundImageFix" result="effect1_dropShadow"/><feBlend in="SourceGraphic" in2="effect1_dropShadow" result="shape"/></filter></defs></svg>`,
		arrowRight: `<svg width="34" height="34" fill="none" xmlns="http://www.w3.org/2000/svg"><g filter="url(#filter0_d)"><path fill-rule="evenodd" clip-rule="evenodd" d="M11.56 8.987l6.414 6.413 1.661 1.6-1.661 1.6-6.414 6.414 2.264 2.263L24.1 17 13.824 6.724 11.56 8.987z" fill="#fff"/></g><defs><filter id="filter0_d" x="9.561" y="5.724" width="16.54" height="24.553" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"/><feOffset dy="1"/><feGaussianBlur stdDeviation="1"/><feColorMatrix values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.06 0"/><feBlend in2="BackgroundImageFix" result="effect1_dropShadow"/><feBlend in="SourceGraphic" in2="effect1_dropShadow" result="shape"/></filter></defs></svg>`,
		emptyAvatar: `<svg width="55" height="54" viewBox="0 0 55 54" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="27.5" cy="27" r="26.7" fill="#C4C4C4"/><path fill-rule="evenodd" clip-rule="evenodd" d="M24.8275 14.6978C23.8798 13.203 31.7392 11.9567 32.4892 16.3612L32.5183 16.5653C32.7304 17.966 32.7304 19.3895 32.5183 20.7902L32.5867 20.7894C32.8227 20.8016 33.5583 20.9762 32.9875 22.7497L32.8546 23.1586C32.7064 23.592 32.3202 24.513 31.7915 24.2264L31.7946 24.4308C31.7902 24.965 31.6952 26.3813 30.8251 26.667L30.9021 27.8537L31.8025 27.9876L31.8015 28.1998C31.8052 28.4797 31.8304 28.9453 31.9548 29.0148C32.7762 29.5429 33.6766 29.9433 34.6238 30.2014C37.3262 30.8843 38.7429 32.0399 38.8344 33.0749L38.8391 33.1815L39.5901 36.9888C36.3543 38.3389 32.5989 39.1464 28.5826 39.2309H27.1789C23.1717 39.1466 19.4242 38.3425 16.1934 36.998L16.2873 36.3541C16.4186 35.4854 16.573 34.5999 16.7316 33.9845C17.1569 32.3336 19.5496 31.1074 21.7512 30.1646C22.8907 29.6764 23.1375 29.3835 24.2842 28.884C24.3325 28.656 24.3591 28.4244 24.3638 28.1919L24.3612 27.9593L25.3364 27.8441L25.3489 27.8578C25.3754 27.8715 25.4218 27.7921 25.2589 26.7124L25.2019 26.692C24.9764 26.5984 24.156 26.13 24.1122 24.2579L24.0509 24.272C23.8687 24.303 23.3369 24.3106 23.2487 23.3711L23.2386 23.2148C23.2051 22.3624 22.5612 21.617 23.3893 20.9935L23.5116 20.9092L22.9972 19.5438L22.9709 19.2023C22.8993 18.0413 22.825 14.3374 24.8275 14.6978Z" fill="white"/></svg>`,
		emptyAvatar2: `<svg width="55" height="54" viewBox="0 0 55 54" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="27.5" cy="27" r="26.7" /><path fill-rule="evenodd" clip-rule="evenodd" d="M24.8275 14.6978C23.8798 13.203 31.7392 11.9567 32.4892 16.3612L32.5183 16.5653C32.7304 17.966 32.7304 19.3895 32.5183 20.7902L32.5867 20.7894C32.8227 20.8016 33.5583 20.9762 32.9875 22.7497L32.8546 23.1586C32.7064 23.592 32.3202 24.513 31.7915 24.2264L31.7946 24.4308C31.7902 24.965 31.6952 26.3813 30.8251 26.667L30.9021 27.8537L31.8025 27.9876L31.8015 28.1998C31.8052 28.4797 31.8304 28.9453 31.9548 29.0148C32.7762 29.5429 33.6766 29.9433 34.6238 30.2014C37.3262 30.8843 38.7429 32.0399 38.8344 33.0749L38.8391 33.1815L39.5901 36.9888C36.3543 38.3389 32.5989 39.1464 28.5826 39.2309H27.1789C23.1717 39.1466 19.4242 38.3425 16.1934 36.998L16.2873 36.3541C16.4186 35.4854 16.573 34.5999 16.7316 33.9845C17.1569 32.3336 19.5496 31.1074 21.7512 30.1646C22.8907 29.6764 23.1375 29.3835 24.2842 28.884C24.3325 28.656 24.3591 28.4244 24.3638 28.1919L24.3612 27.9593L25.3364 27.8441L25.3489 27.8578C25.3754 27.8715 25.4218 27.7921 25.2589 26.7124L25.2019 26.692C24.9764 26.5984 24.156 26.13 24.1122 24.2579L24.0509 24.272C23.8687 24.303 23.3369 24.3106 23.2487 23.3711L23.2386 23.2148C23.2051 22.3624 22.5612 21.617 23.3893 20.9935L23.5116 20.9092L22.9972 19.5438L22.9709 19.2023C22.8993 18.0413 22.825 14.3374 24.8275 14.6978Z" fill="white"/></svg>`,
		incomingDecline: `<svg width="66" height="66" viewBox="0 0 66 66" fill="none" xmlns="http://www.w3.org/2000/svg"><circle opacity="0.8" cx="32.9991" cy="33.0001" r="32.3077" fill="#E22620"/><path fill-rule="evenodd" clip-rule="evenodd" d="M49.998 35.0409C49.9992 36.7145 47.8366 39.3784 47.4179 38.955C46.9992 38.5316 40.5017 35.8767 40.0826 35.4537C39.6634 35.0307 39.6611 32.0143 39.6615 31.1328C39.6619 30.2514 33.1179 29.8113 32.9948 29.8028L31.6987 29.9064C29.7587 30.0827 26.3285 30.4844 26.3307 31.1162L26.327 32.0277C26.3107 33.2684 26.2355 35.1139 25.9159 35.437C25.4979 35.8596 19.0043 38.5017 18.5848 38.926C18.1653 39.3502 15.9992 36.6799 15.998 35.0063L16.007 34.7467C16.1245 32.9368 17.5155 27.1848 27.0345 25.4819C27.9868 25.1999 29.6975 25.0188 32.4746 25.0014L33.0006 25C36.075 25.0038 37.9339 25.1931 38.9426 25.4939C48.9874 27.3097 49.9969 33.6109 49.998 35.0409Z" fill="white"/></svg>`,
		incomingAnswer: `<svg width="66" height="66" viewBox="0 0 66 66" fill="none" xmlns="http://www.w3.org/2000/svg"><circle opacity="0.6" cx="32.9991" cy="33.0001" r="32.3077" fill="#A4D212"/><path fill-rule="evenodd" clip-rule="evenodd" d="M20.9245 19.5963C22.1662 18.3528 25.7973 18.0277 25.8029 18.6622C25.8086 19.2966 28.8036 26.2344 28.8099 26.8688C28.8162 27.5032 26.5782 29.7448 25.9234 30.399C25.2687 31.0531 29.9438 36.3818 30.0316 36.4823L31.0992 37.396C32.7129 38.748 35.6332 41.0716 36.1006 40.6008L36.7802 39.9268C37.714 39.018 39.1417 37.7052 39.626 37.7096C40.2592 37.7153 47.1845 40.7169 47.8201 40.7225C48.4558 40.7282 48.1287 44.3666 46.8869 45.6101L46.6873 45.7961C45.2536 47.0502 39.9194 50.2579 31.3791 44.2464C30.4418 43.7279 28.9998 42.5548 26.8641 40.4451L26.461 40.044C24.1139 37.6912 22.8336 36.1298 22.2859 35.1355C15.9564 26.1093 19.8635 20.659 20.9245 19.5963Z" fill="white"/></svg>`,
		incomingAnswerWithVideo: `<svg width="66" height="66" viewBox="0 0 66 66" fill="none" xmlns="http://www.w3.org/2000/svg"><circle opacity="0.6" cx="32.9991" cy="33.0001" r="32.3077" fill="#A4D212"/><path d="M22.0828 24.3457C20.0156 24.3457 18.3398 26.0075 18.3398 28.0574V39.1926C18.3398 41.2426 20.0156 42.9044 22.0828 42.9044H37.0545C39.1216 42.9044 40.7974 41.2426 40.7974 39.1926V28.0574C40.7974 26.0075 39.1216 24.3457 37.0545 24.3457H22.0828Z" fill="white"/><path d="M50.1547 26.2016L42.6689 29.6686V37.9974L50.1547 41.0485V26.2016Z" fill="white"/></svg>`,
		buttonMic: `<svg width="18" height="25" viewBox="0 0 18 25" fill="none" xmlns="http://www.w3.org/2000/svg"><path opacity="0.9" fill-rule="evenodd" clip-rule="evenodd" d="M15.8494 11.1142C16.5655 11.099 17.1583 11.6672 17.1736 12.3833C17.2434 15.6738 14.8195 19.5374 10.124 20.1756L10.123 21.8812L10.6849 21.8816C11.3304 21.8816 11.8536 22.4048 11.8536 23.0503C11.8536 23.6957 11.3304 24.219 10.6849 24.219H6.77451C6.12906 24.219 5.60583 23.6957 5.60583 23.0503C5.60583 22.4048 6.12906 21.8816 6.77451 21.8816L7.33461 21.8812L7.33451 20.1745C2.64592 19.5414 0.249139 15.7699 0.285565 12.3968C0.2933 11.6806 0.880196 11.1062 1.59643 11.1139C2.26492 11.1212 2.80982 11.6329 2.87321 12.2834L2.87929 12.4248C2.87203 13.0977 3.24783 14.3896 3.94142 15.3944C4.93722 16.8371 6.46806 17.6731 8.74352 17.6731C11.0064 17.6731 12.5318 16.8209 13.5294 15.3506C14.1663 14.4117 14.5374 13.2188 14.577 12.5765L14.5803 12.4384C14.5651 11.7222 15.1333 11.1294 15.8494 11.1142ZM8.72972 0.923828C10.6832 0.923828 12.2668 2.50744 12.2668 4.46093V11.9175C12.2668 13.871 10.6832 15.4547 8.72972 15.4547C6.77623 15.4547 5.19261 13.871 5.19261 11.9175V4.46093C5.19261 2.50744 6.77623 0.923828 8.72972 0.923828Z" fill="white"/></svg>`,
		buttonMicOff: `<svg width="20" height="25" viewBox="0 0 20 25" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.1741 11.8802V11.5285L10.0427 15.397C9.93208 15.4075 9.82002 15.4128 9.70672 15.4128C7.75571 15.4128 6.1741 13.8312 6.1741 11.8802ZM9.71784 17.6283C10.5546 17.6283 11.2904 17.5116 11.9358 17.2902L13.9031 19.2575C13.083 19.6807 12.1467 19.9849 11.0966 20.1277L11.0956 21.8311L11.6568 21.8315C12.3014 21.8315 12.824 22.354 12.824 22.9987C12.824 23.6433 12.3014 24.1659 11.6568 24.1659H7.75133C7.10671 24.1659 6.58413 23.6433 6.58413 22.9987C6.58413 22.354 7.10671 21.8315 7.75133 21.8315L8.31072 21.8311L8.31062 20.1266C3.62798 19.4942 1.23424 15.7275 1.27062 12.3587C1.27834 11.6434 1.8645 11.0698 2.57982 11.0774C3.24747 11.0847 3.79167 11.5958 3.85498 12.2455L3.86106 12.3867C3.8538 13.0587 4.22913 14.349 4.92184 15.3525C5.91637 16.7933 7.44527 17.6283 9.71784 17.6283ZM15.6351 11.902L17.9247 14.1916C18.0787 13.5725 18.15 12.9492 18.1372 12.3452C18.122 11.63 17.5299 11.0625 16.8147 11.0777C16.2752 11.0892 15.8198 11.4288 15.6351 11.902ZM6.55966 2.82655L13.2393 9.50623V4.43301C13.2393 2.482 11.6577 0.900391 9.70672 0.900391C8.33421 0.900391 7.14451 1.68312 6.55966 2.82655ZM0.9589 2.00146C0.594429 2.36593 0.594429 2.95685 0.958901 3.32133L17.8599 20.2223C18.2244 20.5868 18.8153 20.5868 19.1798 20.2223L19.4141 19.988C19.7786 19.6235 19.7786 19.0326 19.4141 18.6681L2.5131 1.76713C2.14863 1.40266 1.5577 1.40266 1.19323 1.76713L0.9589 2.00146Z" fill="#FF5752"/></svg>`,
		buttonCamera: `<svg width="24" height="15" viewBox="0 0 24 15" fill="none" xmlns="http://www.w3.org/2000/svg"><g opacity="0.9"><path d="M1.9255 0.992188C1.29571 0.992188 0.785156 1.50274 0.785156 2.13253V13.536C0.785156 14.1658 1.29571 14.6763 1.9255 14.6763H15.4744C16.1042 14.6763 16.6147 14.1658 16.6147 13.536V2.13253C16.6147 1.50274 16.1042 0.992188 15.4744 0.992188H1.9255Z" fill="white"/><path d="M17.9661 4.56337C17.8333 4.61378 17.7454 4.7411 17.7454 4.88322V11.066C17.7454 11.2081 17.8333 11.3355 17.9661 11.3859L23.1286 13.3446C23.3524 13.4295 23.5921 13.2642 23.5921 13.0248V2.92449C23.5921 2.68507 23.3524 2.5197 23.1286 2.60463L17.9661 4.56337Z" fill="white"/></g></svg>`,
		buttonCameraOff: `<svg width="25" height="19" viewBox="0 0 25 19" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M18.5438 16.4254L2.48184 0.363429C2.06823 -0.0501795 1.39764 -0.0501795 0.984034 0.363429C0.570426 0.777037 0.570426 1.44763 0.984034 1.86124L17.046 17.9232C17.4596 18.3368 18.1302 18.3368 18.5438 17.9232C18.9574 17.5096 18.9574 16.839 18.5438 16.4254ZM11.9075 15.6649L1.82239 5.57863L1.82296 14.5368C1.82296 15.1602 2.32836 15.6656 2.95181 15.6656L11.9075 15.6649ZM24.5456 3.82634C24.5603 3.86492 24.5678 3.90584 24.5678 3.94711V13.9984C24.5678 14.1864 24.4153 14.3389 24.2273 14.3389C24.186 14.3389 24.1451 14.3314 24.1065 14.3167L18.9691 12.3675C18.8369 12.3173 18.7494 12.1906 18.7494 12.0492V5.89635C18.7494 5.75491 18.8369 5.62821 18.9691 5.57804L24.1065 3.6288C24.2823 3.5621 24.4789 3.65054 24.5456 3.82634ZM16.5265 2.04337L7.26388 2.04272L17.6549 12.4326L17.6553 3.17222C17.6553 2.54877 17.1499 2.04337 16.5265 2.04337Z" fill="#FF5752"/></svg>`,
		buttonMenu: `<svg width="20" height="15" viewBox="0 0 20 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path opacity="0.9" fill-rule="evenodd" clip-rule="evenodd" d="M0 1.5C0 0.671573 0.671573 0 1.5 0H18.5C19.3284 0 20 0.671573 20 1.5C20 2.32843 19.3284 3 18.5 3H1.5C0.671573 3 0 2.32843 0 1.5ZM0 7.5C0 6.67157 0.671573 6 1.5 6H18.5C19.3284 6 20 6.67157 20 7.5C20 8.32843 19.3284 9 18.5 9H1.5C0.671573 9 0 8.32843 0 7.5ZM1.5 12C0.671573 12 0 12.6716 0 13.5C0 14.3284 0.671573 15 1.5 15H18.5C19.3284 15 20 14.3284 20 13.5C20 12.6716 19.3284 12 18.5 12H1.5Z" fill="white"/></svg>`,
		buttonChat: `<svg width="24" height="21" viewBox="0 0 24 21" fill="none" xmlns="http://www.w3.org/2000/svg"><g opacity="0.9"><path d="M0 2.55312C0 1.6781 0.709349 0.96875 1.58438 0.96875H13.3986C14.2736 0.96875 14.983 1.6781 14.983 2.55312V10.9098C14.983 11.7848 14.2736 12.4941 13.3986 12.4941H6.9898L3.45761 16.0595V12.4941H1.58438C0.70935 12.4941 0 11.7848 0 10.9098V2.55312Z" fill="white"/><path d="M8.16313 14.7992V15.5199C8.16313 16.3949 8.87248 17.1043 9.74751 17.1043H16.1563L19.6885 20.6697V17.1043H21.5617C22.4368 17.1043 23.1461 16.3949 23.1461 15.5199V7.16328C23.1461 6.28825 22.4368 5.5789 21.5618 5.5789H17.2881V13.2148C17.2881 14.0899 16.5787 14.7992 15.7037 14.7992H8.16313Z" fill="white"/></g></svg>`,
		buttonHangup: `<svg width="26" height="10" viewBox="0 0 26 10" fill="none" xmlns="http://www.w3.org/2000/svg"><path opacity="0.9" fill-rule="evenodd" clip-rule="evenodd" d="M25.0449 7.11438C25.0458 8.2799 23.4954 10.1351 23.1952 9.84027C22.895 9.54539 18.2369 7.6964 17.9364 7.40182C17.6359 7.10724 17.6343 5.00651 17.6345 4.39266C17.6347 3.94807 15.1739 3.66472 13.7832 3.53999C13.1652 3.48457 12.5445 3.4905 11.9259 3.53854C10.5351 3.66132 8.07592 3.94107 8.07753 4.38106L8.07486 5.01588C8.06317 5.87995 8.00925 7.16518 7.78012 7.39021C7.48048 7.68449 2.82514 9.52456 2.52439 9.82003C2.22365 10.1155 0.670755 8.2558 0.669922 7.09028L0.676347 6.90943C0.760542 5.64898 1.75779 1.64315 8.58211 0.457222C9.2648 0.260787 10.4912 0.134686 12.4821 0.122535L12.8592 0.121582C15.0633 0.12426 16.396 0.256076 17.1191 0.465554C24.3204 1.73016 25.0441 6.11847 25.0449 7.11438Z" fill="#E22620"/></svg>`,
		switchCamera: `<svg width="15" height="12" viewBox="0 0 15 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path opacity="0.6" fill-rule="evenodd" clip-rule="evenodd" d="M13.1417 2.01551H10.0753C9.85629 1.15325 9.63727 0.291016 8.76114 0.291016H5.25669C4.38058 0.291016 4.16154 1.15325 3.94252 2.01551H0.876106C0.394252 2.01551 0 2.40352 0 2.87774V10.6379C0 11.1121 0.394252 11.5002 0.876106 11.5002H13.1417C13.6236 11.5002 14.0179 11.1121 14.0179 10.6379V2.87774C14.0178 2.40352 13.6236 2.01551 13.1417 2.01551ZM8.0728 6.80269L8.07286 6.80314L9.71169 8.46443L11.3786 6.80314H10.3054C10.3054 4.9908 8.83597 3.51534 7.02362 3.51534C6.18515 3.51534 5.41997 3.82982 4.83992 4.347L5.50493 5.01161C5.91395 4.66308 6.44435 4.45276 7.02366 4.45276C8.31801 4.45276 9.36755 5.50795 9.36755 6.80229L8.0728 6.80269ZM2.63672 6.80309L3.74197 6.80314C3.74197 8.61549 5.21138 10.0788 7.02372 10.0788C7.83775 10.0788 8.58245 9.7824 9.15615 9.29166L8.49034 8.62545C8.08893 8.94793 7.57897 9.14062 7.0237 9.14062C5.72936 9.14062 4.67981 8.09703 4.67981 6.80268H5.90606L4.27195 5.16304L2.63672 6.80309Z" fill="white"/></svg>`,
		floorRequest: `<svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8.5 17C13.1944 17 17 13.1944 17 8.5C17 3.80558 13.1944 0 8.5 0C3.80558 0 0 3.80558 0 8.5C0 13.1944 3.80558 17 8.5 17Z" fill="#F7A700"/><path fill-rule="evenodd" clip-rule="evenodd" d="M8.37435 3.26611C8.68431 3.26611 8.94411 3.48084 9.01313 3.76965L9.0131 7.90677C9.0131 7.97488 9.06717 8.03037 9.13472 8.03265L9.13905 8.03272H9.19303C9.26114 8.03272 9.31663 7.97866 9.31891 7.9111L9.31898 7.90677L9.31898 4.94143C9.41798 4.71684 9.64256 4.56007 9.90376 4.56007C10.2045 4.56007 10.4567 4.76788 10.5245 5.04772L10.5245 8.57251C10.5245 8.64062 10.5786 8.69611 10.6461 8.69839L10.6505 8.69846H10.7044C10.7726 8.69846 10.828 8.6444 10.8303 8.57684L10.8304 8.57251V6.71938C10.8304 6.41629 11.0761 6.17059 11.3792 6.17059C11.6823 6.17059 11.8692 6.41629 11.8692 6.71938V8.3386C11.8645 9.10517 11.8428 9.67086 11.8039 10.0357C11.7456 10.5829 11.5311 12.0348 10.7199 13.3105C10.6781 13.3763 10.6064 13.4167 10.5288 13.4189H10.5225H6.91763C6.84578 13.4189 6.77805 13.3859 6.73378 13.3296L6.72982 13.3244L4.06101 9.72947C3.97587 9.61479 3.99483 9.45363 4.10425 9.36183C4.23257 9.25417 4.36666 9.18999 4.50653 9.16929L4.57855 9.15862C4.84625 9.11953 5.02602 9.1073 5.39882 9.32738C5.66559 9.48486 5.90797 9.76714 6.12595 10.1742L6.13422 10.1897V4.92892C6.13422 4.56621 6.42825 4.27218 6.79096 4.27218H6.80896C7.11891 4.27218 7.37872 4.4869 7.44773 4.77571L7.44771 7.61888C7.44771 7.68699 7.50177 7.74248 7.56933 7.74476L7.62764 7.74483C7.69575 7.74483 7.75123 7.69077 7.75352 7.62321L7.75359 7.61888L7.75358 3.66173C7.85456 3.42894 8.08644 3.26611 8.35636 3.26611H8.37435Z" fill="white"/></svg>`,
		participants: `<svg width="18" height="15" viewBox="0 0 18 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11.7079 10.4283C11.7079 10.4283 12.3535 12.9213 12.6001 14.3516C10.9205 14.7642 8.97483 15 6.90076 15C4.31096 15 1.92133 14.6323 0 14.012C0.20326 12.9327 0.499352 11.392 0.633052 10.8505C0.846968 9.98404 2.04555 9.34018 3.14797 8.84532C3.43616 8.71603 3.61001 8.6128 3.78571 8.50847C3.95779 8.40629 4.13163 8.30306 4.41638 8.17329C4.44851 8.01399 4.46143 7.85125 4.45484 7.68873L4.94332 7.62816C4.94332 7.62816 5.00753 7.7499 4.90427 7.03396C4.90427 7.03396 4.35602 6.88405 4.33057 5.74412C4.33057 5.74412 3.91872 5.88737 3.89386 5.19656C3.88881 5.0584 3.85475 4.92565 3.82214 4.79853C3.74403 4.49404 3.67422 4.22189 4.03115 3.98516L3.77344 3.26922C3.77344 3.26922 3.50302 0.499946 4.69065 0.725267C4.20897 -0.0703231 8.27248 -0.732356 8.54232 1.70681C8.64852 2.44207 8.64852 3.18933 8.54232 3.92459C8.54232 3.92459 9.14945 3.8519 8.7441 5.05604C8.7441 5.05604 8.52102 5.92159 8.1784 5.72625C8.1784 5.72625 8.23402 6.82166 7.69435 7.00731C7.69435 7.00731 7.73281 7.5906 7.73281 7.63028L8.18402 7.70205C8.18402 7.70205 8.17159 8.18843 8.26035 8.24113C8.67164 8.51836 9.1225 8.72852 9.59681 8.86409C10.9978 9.23478 11.7079 9.87107 11.7079 10.4283Z" fill="white"/><path d="M17.9348 10.9761C17.9544 11.3761 17.9789 11.8754 18 12.3109C16.7327 12.7469 15.2453 13.0945 13.6075 13.3277H13.1698C13.1435 12.9475 12.8076 11.6309 12.6126 10.8669C12.5353 10.564 12.4802 10.3479 12.4753 10.3116C12.4502 9.59198 11.8327 8.94933 10.777 8.5102C10.8569 8.39953 10.9253 8.28068 10.9812 8.15556C11.1292 7.96723 11.3223 7.82119 11.5416 7.73157L11.5584 7.16826L10.3995 6.79697C10.3995 6.79697 10.1016 6.65433 10.072 6.65433C10.1063 6.56766 10.1492 6.48484 10.2001 6.4072C10.2223 6.34663 10.3625 5.89477 10.3625 5.89477C10.1938 6.117 9.99583 6.31431 9.77433 6.48109C9.97708 6.11397 10.149 5.72989 10.2883 5.33298C10.3801 4.95095 10.4417 4.56193 10.4723 4.16973C10.5517 3.457 10.6756 2.75022 10.8433 2.05371C10.9637 1.70615 11.1758 1.39953 11.4561 1.16787C11.8702 0.873322 12.3536 0.697166 12.8564 0.657566H12.9156C13.4193 0.696835 13.9037 0.872998 14.3186 1.16787C14.5992 1.39908 14.8114 1.70557 14.9317 2.0531C15.0992 2.74966 15.2232 3.45643 15.303 4.16913C15.3387 4.55262 15.4033 4.93272 15.4962 5.30603C15.6354 5.70994 15.8043 6.10245 16.0016 6.48019C15.7796 6.31383 15.5813 6.11682 15.4122 5.89477C15.4122 5.89477 15.5214 6.30483 15.5433 6.3654C15.6032 6.45693 15.6566 6.55278 15.703 6.6522C15.6743 6.6522 15.3755 6.79485 15.3755 6.79485L14.2166 7.16614L14.2331 7.72975C14.4526 7.8191 14.6457 7.96519 14.7935 8.15374C14.8636 8.33471 14.9747 8.4961 15.1175 8.62468C15.3971 8.72437 15.6667 8.85135 15.9226 9.00385C16.31 9.2245 16.7351 9.36719 17.175 9.4242C17.6188 9.49901 17.8987 10.2334 17.8987 10.2334C17.8987 10.2406 17.9139 10.5498 17.9345 10.9703L17.9348 10.9761Z" fill="white"/></svg>`,
	};

	const styles = {
		root: {
			width: "100%",
			height: "100%",
			backgroundColor: "#3A414B",
			//backgroundImage: "https://img4.goodfon.ru/wallpaper/nbig/5/c6/google-nexus-nexus-5-linii-kraski.jpg",
			//backgroundBlurRadius: 7,
			backgroundResizeMode: "stretch",
			alignItems: "center",
		},
		overlay: {
			position: "absolute",
			height: "100%",
			opacity: 1.0,
			width: "100%",
			alignItems: "center",
			justifyContent: "flex-end",
		},
		remoteVideo: {
			position: "absolute",
			width: "100%",
			height: "100%",
			backgroundColor: "#3A414B",
			flex: 1,
		},
		localVideo: {
			width: 100,
			top: 10,
			right: 10,
			position: "absolute",
			height: 180,
			borderRadius: 10,
			backgroundColor: "#00000000",
		},
		localVideoLandscape: {
			width: 180,
			height: 100,
		},
		participantsButton: {
			flexDirection: "row",
			alignItems: "center",
			height: 50, // 20(height) + 15(top padding) + 15 (bottom padding)
		},
		participantsButtonIcon: {
			width: 18,
			height: 15,
			marginRight: 8,
		},
		participantsButtonText: {
			fontWeight: 500,
			fontSize: 17,
			color: "#FFFFFF",
			marginRight: 7,
		},
		participantsButtonArrow: {
			backgroundImageSvgUrl: pathToExtension + "img/arrow.svg",
			width: 15,
			height: 10,
		},
		userSelector: {
			position: "absolute",
			height: 19,
			paddingLeft: 8,
			paddingRight: 7,
			flexDirection: "row",
			alignItems: "center",
			alignSelf: "center",
			justifyContent: "center",
			backgroundColor: "#33000000",
			borderRadius: 9.5,
			// borderWidth: 1,
			// borderColor: "#00FF00"
		},
		userDotOuter: {
			width: 13,
			height: 13,
			alignItems: "center",
			justifyContent: "center",
			marginRight: 1,
		},
		userDotInner: {
			width: 7,
			height: 7,
			borderRadius: 3.5,
		},
		centralUser: {
			position: "absolute",
			flexDirection: "row",
			alignItems: "center",
			justifyContent: "center",
			/*borderWidth: 1,
			borderColor: "#00FF00"*/
		},
		center: {
			position: "absolute",
			flexDirection: "row",
			flex: 1,
			justifyContent: "space-between",
			alignItems: "center",
			alignContent: "center",
			width: "100%",
			height: "100%",
			clickable: false,
			bottom: 30,
			// borderWidth: 1,
			// borderColor: "#FFFF00",
		},
		leftArrow: {
			width: 34,
			height: 44,
		},
		rightArrow: {
			width: 34,
			height: 44,
		},

		largeAvatar: {
			width: LARGE_AVATAR_SIZE,
			height: LARGE_AVATAR_SIZE,
			borderRadius: 106,
			borderWidth: 8,
			borderColor: "#99FFFFFF",
		},

		centralUserAvatar: {
			width: 47,
			height: 47,
			marginRight: 10,
			borderRadius: 23.5,
		},
		centralUserAvatarImage: {
			width: 41,
			height: 41,
			borderRadius: 20.5,
			marginLeft: 3,
			marginTop: 3,
		},
		centralUserDescription: {
			height: 47,
			justifyContent: "center",
		},
		centralUserDescriptionTop: {
			flexDirection: "row",
			alignItems: "center",
			height: 20,
		},
		centralUserDescriptionName: {
			fontWeight: 500,
			fontSize: 17,
			height: 20,
			color: "#FFFFFF",
		},
		centralUserMicOff: {
			width: 14,
			height: 20,
			marginRight: 6,
		},
		centralUserCameraOff: {
			width: 18,
			height: 20,
			marginRight: 6,
		},
		centralUserArrow: {
			width: 13,
			height: 8,
			marginLeft: 7,
			marginTop: 8,
			marginBottom: 4,
		},
		centralUserDescriptionBottom: {
			height: 16,
			color: "#6FFFFFFF",
		},
		bottomButtonContainer: {
			marginLeft: 5,
			marginRight: 5,
			height: 60,
			minWidth: 58,
			alignSelf: "flex-end",
			borderRadius: 10,
			marginBottom: 10,
			//borderWidth: 1,
			//borderColor: "#0000FF",
			justifyContent: "center",
			color: "#ffffff",
		},
		bottomButtonImage: {
			width: 30,
			height: 30,
			alignSelf: "center",
		},
		bottomButtonText: {
			alignSelf: "center",
			fontSize: 11,
			marginTop: 6,
			fontWeight: "bold",
			color: "#ffffff"
		},
		bottomButtonCounter: {
			position: "absolute",
			top: 4,
			right: 6,
			height: 14,
			paddingLeft: 7,
			paddingRight: 7,

			borderRadius: 7,
			backgroundColor: "#FF5752",

		},
		bottomButtonCounterText: {
			color: "#ffffff",
			fontWeight: "bold",
			fontSize: 11,
		},
		videoPausedOverlay: {
			position: "absolute",
			height: "100%",
			opacity: 0.6,
			width: "100%",
			alignItems: "center",
			justifyContent: "center",
			backgroundColor: "#000000"
		}
	};

	const EventName = {
		Close: "close",
		Destroy: "destroy",
		MicButtonClick: "micButtonClick",
		CameraButtonClick: "cameraButtonClick",
		MenuButtonClick: "menuButtonClick",
		FloorRequestButtonClick: "floorRequestButtonClick",
		ChatButtonClick: "chatButtonClick",
		PrivateChatButtonClick: "privateChatButtonClick",
		AnswerButtonClick: "answerButtonClick",
		HangupButtonClick: "hangupButtonClick",
		DeclineButtonClick: "declineButtonClick",
		BodyClick: "bodyClick",
		ReplaceCamera: "replaceCamera",
		ReplaceSpeaker: "replaceSpeaker",
		SetCentralUser: "setCentralUser",
		SelectAudioDevice: "selectAudioDevice",
	};

	class CallLayout extends LayoutComponent
	{
		constructor(props = {})
		{
			super(props);

			/*const _setState = this.setState;
			this.setState = function()
			{
				console.trace("setState", arguments);
				_setState.apply(this, arguments)
			}.bind(this)*/

			this.userRegistry = new UserRegistry();
			this.lastPosition = 0;
			this.userId = env.userId;
			this.centralUserId = env.userId;

			this.presenterId = env.userId;
			this.userData = {};
			this.videoStreams = {};

			this.switchPresenterTimeout = null;

			this.state = {
				screenWidth: 0,
				screenHeight: 0,
				width: "100%",
				height: "100%",
				status: props.status || "none",
				panelVisible: true,
				microphoneState: true,
				cameraState: props.cameraState === true,
				centralUserVideoPaused: false,
				remoteStream: null,
				localStream: null,
				mirrorLocalVideo: true,
				showParticipants: true,
				showUserSelector: true,
				centralUserId: this.userId,
				pinnedUserId: 0,
				users: [],
				connectedUsers: [],
				floorRequestUsers: [],
				isGroupCall: props.isGroupCall === true,
				isVideoCall: props.isVideoCall === true,
				associatedEntityName: props.associatedEntityName,
				associatedEntityAvatar: props.associatedEntityAvatar,
				chatCounter: props.chatCounter || 0,
			};

			this.localUserModel = new UserModel({
				id: this.userId,
				localUser: true,
				state: BX.Call.UserState.Connected,
			});
			this.userRegistry.push(this.localUserModel);
		}

		appendUsers(userStates)
		{
			if (!BX.type.isPlainObject(userStates))
			{
				return;
			}

			let userIds = Object.keys(userStates);
			for (let i = 0; i < userIds.length; i++)
			{
				let userId = userIds[i];
				this.addUser(userId, userStates[userId] ? userStates[userId] : BX.Call.UserState.Idle);
			}
		};

		addUser(userId, state)
		{
			if (this.userRegistry.get(userId))
			{
				return;
			}
			this.userRegistry.push(new UserModel({
				id: userId,
				name: this.userData[userId] ? this.userData[userId].name : "",
				avatar: this.userData[userId] ? this.userData[userId].avatar_hr : "",
				state: state || BX.Call.UserState.Idle,
				order: this.getNextPosition(),
			}));
			this.setState({
				users: this.getUsers(),
				connectedUsers: this.getConnectedUsers(),
			});
		}

		updateUserData(userData)
		{
			for (let userId in userData)
			{
				if (!userData.hasOwnProperty(userId))
				{
					continue;
				}
				if (!this.userData[userId])
				{
					this.userData[userId] = {
						name: "",
						avatar_hr: "",
						gender: "M",
					};
				}
				if (userData[userId].name)
				{
					this.userData[userId].name = userData[userId].name;
				}
				if (userData[userId].avatar_hr)
				{
					this.userData[userId].avatar_hr = isAvatarBlank(userData[userId].avatar_hr) ? "" : userData[userId].avatar_hr;
				}
				if (userData[userId].gender)
				{
					this.userData[userId].gender = userData[userId].gender === "F" ? "F" : "M";
				}

				let userModel = this.userRegistry.get(userId);
				if (userModel)
				{
					userModel.name = this.userData[userId].name;
					userModel.avatar = this.userData[userId].avatar_hr;
					userModel.gender = this.userData[userId].gender;
				}
			}
			this.setState({});
		}

		getNextPosition()
		{
			return this.lastPosition++;
		}

		setCentralUser(userId)
		{
			if (this.centralUserId == userId)
			{
				return;
			}
			/*if (userId == this.userId && this.getUsersWithVideo().length > 0)
			{
				return;
			}*/
			let userModel  = this.userRegistry.get(userId);
			if (!userModel)
			{
				return;
			}

			this.userRegistry.users.forEach(function (userModel)
			{
				userModel.centralUser = (userModel.id == userId);
			});
			this.centralUserId = userId;
			this.setState({
				centralUserId: userId,
				centralUserVideoPaused: userModel.videoPaused,
				remoteStream: this.videoStreams.hasOwnProperty(userId) ? this.videoStreams[userId] : null,
				localStream: this.videoStreams.hasOwnProperty(this.userId) ? this.videoStreams[this.userId] : null,
			});
			this.emit(EventName.SetCentralUser, [userId]);
		}

		pinUser(userId)
		{
			if (!this.userRegistry.get(userId))
			{
				console.error("User " + userId + " is not known");
				return;
			}
			this.userRegistry.users.forEach(userModel => userModel.pinned = userModel.id == userId);
			this.setCentralUser(userId);
			this.setState({
				pinnedUserId: userId,
			});
		}

		unpinUser()
		{
			this.userRegistry.users.forEach(function (userModel)
			{
				userModel.pinned = false;
			});

			this.setCentralUser(this.getPresenterUserId());
			this.setState({
				pinnedUserId: 0,
			});
		}

		getPresenterUserId()
		{
			let currentPresenterId = this.presenterId;
			if (currentPresenterId == this.userId)
			{
				currentPresenterId = 0;
			}

			let currentPresenterModel = this.userRegistry.get(currentPresenterId);

			// 1. Current user, who is sharing screen has top priority
			if (currentPresenterModel && currentPresenterModel.screenState === true)
			{
				return currentPresenterId;
			}

			// 2. If current user is not sharing screen, but someone is sharing - he should become presenter
			let sharingUser = this.userRegistry.users.find(userModel => userModel.screenState);
			if (sharingUser)
			{
				return sharingUser.id;
			}

			// 3. If current user is talking, or stopped talking less then one second ago - he should stay presenter
			if (currentPresenterModel && currentPresenterModel.wasTalkingAgo() < 1000)
			{
				return currentPresenterId;
			}

			// 4. Return currently talking user
			let usersByTalking = this.userRegistry.users
				.filter(userModel => userModel.state === BX.Call.UserState.Connected && !userModel.localUser)
				.sort((user1, user2) =>
				{
					return user1.wasTalkingAgo() - user2.wasTalkingAgo();
				});

			if (usersByTalking.length > 0)
			{
				return usersByTalking[0].id;
			}

			// return current presenter
			return this.presenterId;
		}

		getUsers()
		{
			return this.userRegistry.users.filter(userModel => !userModel.localUser);
		}

		getConnectedUserCount()
		{
			return this.getConnectedUsers().length;
		}

		getConnectedUsers()
		{
			return this.userRegistry.users.filter(
				userModel => userModel.state == BX.Call.UserState.Connected && !userModel.localUser,
			);
		}

		getFloorRequestUsers()
		{
			return this.userRegistry.users.filter(
				userModel => userModel.state == BX.Call.UserState.Connected && userModel.floorRequestState,
			);
		}

		getLeftUser(userId)
		{
			let candidateUserId = null;
			for (let i = 0; i < this.userRegistry.users.length; i++)
			{
				let userModel = this.userRegistry.users[i];
				if (userModel.id == userId && candidateUserId)
				{
					return candidateUserId;
				}
				if (!userModel.localUser && userModel.state == BX.Call.UserState.Connected)
				{
					candidateUserId = userModel.id;
				}
			}
			return candidateUserId;
		}

		getRightUser(userId)
		{
			let candidateUserId = null;
			for (let i = this.userRegistry.users.length - 1; i >= 0; i--)
			{
				let userModel = this.userRegistry.users[i];
				if (userModel.id == userId && candidateUserId)
				{
					return candidateUserId;
				}
				if (!userModel.localUser && userModel.state == BX.Call.UserState.Connected)
				{
					candidateUserId = userModel.id;
				}
			}

			return candidateUserId;
		}

		switchPresenter()
		{
			let newPresenterId = this.getPresenterUserId();
			if (!newPresenterId || newPresenterId == this.presenterId)
			{
				return;
			}

			this.presenterId = newPresenterId;
			this.userRegistry.users.forEach(userModel => userModel.presenter = userModel.id == this.presenterId);

			if (!this.state.pinnedUserId)
			{
				this.setCentralUser(newPresenterId);
			}
		}

		switchPresenterDeferred()
		{
			clearTimeout(this.switchPresenterTimeout);
			this.switchPresenterTimeout = setTimeout(this.switchPresenter.bind(this), 1000);
		}

		cancelSwitchPresenter()
		{
			clearTimeout(this.switchPresenterTimeout);
		}

		setChatCounter(chatCounter)
		{
			this.setState({
				chatCounter: chatCounter,
			});
		}

		setCameraState(cameraState)
		{
			cameraState = !!cameraState;
			this.localUserModel.cameraState = cameraState;
			this.setState({
				cameraState: cameraState,
			});
		}

		setMirrorLocalVideo(mirrorLocalVideo)
		{
			this.setState({
				mirrorLocalVideo: mirrorLocalVideo
			});
		}

		setMuted(isMuted)
		{
			this.localUserModel.microphoneState = !isMuted;
			this.setState({
				microphoneState: !isMuted,
			});
		}

		setUserState(userId, newState)
		{
			/** @type {UserModel} */
			let user = this.userRegistry.get(userId);
			let newCallStatus = this.state.status;
			if (!user || user.state == newState)
			{
				return;
			}

			user.state = newState;

			if (newState == BX.Call.UserState.Connected)
			{
				newCallStatus = "call";
			}
			if (newState == BX.Call.UserState.Connecting && this.getConnectedUserCount() === 0)
			{
				newCallStatus = this.state.status == "call" ? "call" : "connecting";
			}

			// maybe switch central user
			if (this.centralUserId == this.userId && newState == BX.Call.UserState.Connected)
			{
				this.setCentralUser(userId);
			}
			if (userId == this.state.pinnedUserId)
			{
				if (newState != BX.Call.UserState.Connected)
				{
					this.unpinUser();
				}
			}
			else if (userId == this.centralUserId)
			{
				if (newState == BX.Call.UserState.Connected)
				{
					//this.centralUser.blurVideo(false);
				}
				else
				{
					this.switchPresenter();
				}
			}
			this.setState({
				connectedUsers: this.getConnectedUsers(),
				status: newCallStatus,
			});
		}

		getUserTalking(userId)
		{
			let user = this.userRegistry.get(userId);
			if (!user)
			{
				return false;
			}

			return !!user.talking;
		}

		setUserTalking(userId, talking)
		{
			/** @type {UserModel} */
			let user = this.userRegistry.get(userId);
			if (user)
			{
				user.talking = talking;
				user.floorRequestState = false;
			}
			this.setState({
				floorRequestUsers: this.getFloorRequestUsers(),
			});
			if (this.presenterId == userId && !talking)
			{
				this.switchPresenterDeferred();
			}
			else
			{
				this.switchPresenter();
			}
		}

		setUserMicrophoneState(userId, isMicrophoneOn)
		{
			/** @type {UserModel} */
			var user = this.userRegistry.get(userId);
			if (user)
			{
				user.microphoneState = isMicrophoneOn;
			}
			if (userId == this.centralUserId)
			{
				this.setState({});
			}
		}

		setUserVideoPaused(userId, videoPaused)
		{
			/** @type {UserModel} */
			var user = this.userRegistry.get(userId);
			if (user && user.videoPaused != videoPaused)
			{
				user.videoPaused = videoPaused;

				if (userId == this.centralUserId)
				{
					this.setState({centralUserVideoPaused: videoPaused});
				}
			}

		}

		getUserFloorRequestState(userId)
		{
			let user = this.userRegistry.get(userId);
			if (!user)
			{
				return false;
			}

			return !!user.floorRequestState;
		}

		setUserFloorRequestState(userId, userFloorRequestState)
		{
			/** @type {UserModel} */
			var user = this.userRegistry.get(userId);
			if (!user)
			{
				return;
			}

			if (user.floorRequestState != userFloorRequestState)
			{
				user.floorRequestState = userFloorRequestState;

				this.setState({
					floorRequestUsers: this.getFloorRequestUsers(),
				});
			}
		}

		setUserScreenState(userId, screenState)
		{
			/** @type {UserModel} */
			let user = this.userRegistry.get(userId);
			if (!user)
			{
				return;
			}

			user.screenState = screenState;
			if (userId != this.userId)
			{
				this.switchPresenter();
			}
		}

		setVideoStream(userId, stream, mirrorLocalVideo = false)
		{
			let userModel = this.userRegistry.get(userId);
			if (!userModel)
			{
				console.error(`User ${userId} is not known`);
				return;
			}
			if (stream)
			{
				this.videoStreams[userId] = stream;
				userModel.cameraState = true;
			}
			else
			{
				delete this.videoStreams[userId];
				userModel.cameraState = false;
			}

			let remoteStream = this.state.remoteStream;
			if (userId == this.centralUserId && userId != env.userId)
			{
				remoteStream = stream;
			}
			let localStream = this.state.localStream;
			let cameraState = this.state.cameraState;
			if (userId == this.userId /*&& userId != this.centralUserId*/)
			{
				localStream = stream;
				cameraState = !!stream;
			}
			this.setState({
				remoteStream: remoteStream,
				localStream: localStream,
				cameraState: cameraState,
				mirrorLocalVideo: (userId == this.userId) ? mirrorLocalVideo : this.state.mirrorLocalVideo
			});
		}

		renderRemoteStream()
		{
			let rendererParams = {};
			if ("getVideoTracks" in this.state.remoteStream)
			{
				rendererParams.source = this.state.remoteStream.getVideoTracks()[0];
			}
			else
			{
				rendererParams.source = this.state.remoteStream;
			}
			return VideoRenderer({
					testId: 'callsRemoteVideo_' + this.state.centralUserId,
					resizeMode: "center", // ? do we need it?
					style: styles.remoteVideo,
					...rendererParams,
				},
			);
		}

		renderLocalStream(showInFrame)
		{
			if (!this.state.localStream)
			{
				return null;
			}
			showInFrame = !!showInFrame;
			let rendererParams = {
				source: ("getVideoTracks" in this.state.localStream)
					? this.state.localStream.getVideoTracks()[0]
					: this.state.localStream,
			};
			let hasFloorRequest = this.state.floorRequestUsers.some(userModel => userModel.localUser);
			let isLandscape = this.state.screenWidth > this.state.screenHeight;
			let style;
			if (!showInFrame)
			{
				style = styles.remoteVideo;
			}
			else
			{
				style = (isLandscape) ? Object.assign({}, styles.localVideo, styles.localVideoLandscape) : styles.localVideo;
				style.marginTop = (isLandscape ? 5 : 0) + getSafeArea().top;
				if (isLandscape)
				{
					style.marginRight = 5;
				}
			}
			return DraggableView(
				{
					style: style,
					enabled: showInFrame,
				},

				View(
					{
						testId: showInFrame ? 'callsLocalVideoCenter' : 'callsLocalVideoFrame',
						onClick: () =>
						{
							if (showInFrame)
							{
								this.emit(EventName.ReplaceCamera);
							}
						},
					},
					VideoRenderer({
						style: {width: "100%", height: "100%"},
						mirror: this.state.mirrorLocalVideo,
						local: true,
						...rendererParams,
					}),
					hasFloorRequest && Image({
						style: {
							position: "absolute",
							right: 7,
							top: 6,
							width: 40,
							height: 40,
						},
						svg: {content: Icons.floorRequest},
					}),
					showInFrame && Image({
						style: {
							position: "absolute",
							bottom: 9,
							alignSelf: "center",
							//left: isLandscape ? 20 : 40,
							width: 20,
							height: 16,
						},
						resizeMode: "cover",
						clickable: false,
						svg: {content: Icons.switchCamera},
					}),
				),
			);
		}

		renderOverlay()
		{
			return View(
				{
					style: styles.overlay,
					clickable: false,
				},
				this.renderTopPanel(),

				this.renderBottomPanel(),
			);
		}

		renderVideoPaused()
		{
			const userName = this.userRegistry.get(this.state.centralUserId).name;
			return View(
				{
					style: styles.videoPausedOverlay,
					clickable: false,
				},
				Text({
					style: {fontSize: 15, color: "#FFFFFF"},
					text: BX.message("MOBILE_CALL_USER_PAUSED_VIDEO").replace("#NAME#", userName),
				})
			);
		}

		renderCenter()
		{
			if (this.state.centralUserId == this.userId)
			{
				return null;
			}
			const showLargeAvatar = this.state.centralUserId != this.userId && !this.state.remoteStream && !this.state.centralUserVideoPaused;
			const showArrows = this.state.connectedUsers.length > 1;

			const avatar = this.userRegistry.get(this.state.centralUserId).avatar ?
				{uri: encodeURI(this.userRegistry.get(this.state.centralUserId).avatar)}
				: {svg: {content: Icons.emptyAvatar}};

			return View({
					style: styles.center,
					clickable: false,
				},
				showArrows && Image({
					testId: 'callsArrowLeft',
					style: {
						display: this.state.panelVisible ? "flex" : "none",
						marginLeft: (getSafeArea().left || 4),
						...styles.leftArrow,
					},
					resizeMode: "center",
					svg: {content: Icons.arrowLeft},
					onClick: () =>
					{
						const userId = this.getLeftUser(this.centralUserId);
						userId == this.presenterId ? this.unpinUser() : this.pinUser(userId);
					},
				}),
				showLargeAvatar && View(
				{
					style: {
						alignItems: "center",
						justifyContent: "center",
						flexGrow: 1,
					},
				},
				Image({
					style: styles.largeAvatar,
					resizeMode: "cover",
					...avatar,
				}),
				),
				showArrows && Image({
					testId: 'callsArrowRight',
					style: {
						display: this.state.panelVisible ? "flex" : "none",
						marginRight: (getSafeArea().right || 4),
						...styles.rightArrow,
					},
					resizeMode: "center",
					svg: {content: Icons.arrowRight},
					onClick: () =>
					{
						const userId = this.getRightUser(this.centralUserId);
						userId == this.presenterId ? this.unpinUser() : this.pinUser(userId);
					},
				}),
			);
		}

		render()
		{
			switch (this.state.status)
			{
				case "incoming":
					return this.renderIncoming();
				case "outgoing":
					return this.renderOutgoing();
				case "connecting":
					return this.renderOutgoing();
				case "call":
					return this.renderCall();
			}
		}

		renderIncoming()
		{
			const avBack = this.state.associatedEntityAvatar && !isAvatarBlank(this.state.associatedEntityAvatar) ?
				{backgroundImage: encodeURI(this.state.associatedEntityAvatar)} // todo
				: {backgroundImageSvg: Icons.emptyAvatar2};

			return View({
					style: {
						backgroundColor: "#2e323a",
						alignItems: "center",
					},
				},
				this.state.localStream ?
					this.renderLocalStream(false)
					:
					Image({
							style: {
								position: "absolute",
								width: "100%",
								height: "100%",
								backgroundResizeMode: "cover",
								backgroundBlurRadius: 8,
								...avBack,
							},
						},
					),
				View({
					style: {
						position: "absolute",
						width: "100%",
						height: "100%",
						backgroundColor: "#464D55",
						opacity: 0.5,
					},
				}),
				this.renderIncomingTop({
					text: this.state.isGroupCall ? BX.message("MOBILE_CALL_LAYOUT_INCOMING_GROUP_CALL_FROM") : BX.message("MOBILE_CALL_LAYOUT_INCOMING_CALL_FROM"),
				}),
				this.state.isGroupCall && this.renderIncomingAvatars(),
				View({
					style: {
						flex: 1,
					},
				}),
				this.renderIncomingButtons(),
			);
		}

		/**
		 * @param {object} props
		 * @param {string} props.text
		 * @param {integer} props.paddingTop
		 */
		renderIncomingTop(props)
		{
			const avatar = this.state.associatedEntityAvatar && !isAvatarBlank(this.state.associatedEntityAvatar) ?
				{uri: encodeURI(this.state.associatedEntityAvatar)} // todo
				: {svg: {content: Icons.emptyAvatar}};

			return View(
				{
					style: {marginTop: 66, alignItems: "center", paddingTop: props.paddingTop || 0},
				},
				Text({
					style: {fontSize: 15, color: "#FFFFFF"},
					text: props.text,
				}),
				View(
					{
						style: {marginTop: 30, flexDirection: "row", maxWidth: "90%"},
					},
					Image({
						style: {width: 53, height: 53, borderRadius: 26.5},
						resizeMode: "cover",
						...avatar,
					}),
					Text({
						style: {fontSize: 20, marginLeft: 12, color: "#FFFFFF", maxWidth: "80%"},
						numberOfLines: 20,
						text: BX.utils.html.htmlDecode(this.state.associatedEntityName),
					}),
				),
			);
		}

		renderIncomingAvatars()
		{
			let to = this.state.users.length > 9 ? 8 : this.state.users.length;
			let avatars = this.state.users.slice(0, to).map(userModel =>
			{
				const avatar = userModel.avatar && !isAvatarBlank(userModel.avatar) ?
					{uri: encodeURI(userModel.avatar)}
					: {svg: {content: Icons.emptyAvatar}};

				return Image(
					{
						style: {
							width: 26,
							height: 26,
							borderRadius: 13,
							marginRight: 4,
						},
						resizeMode: "cover",
						...avatar,
					},
				);
			});

			return View(
				{
					style: {
						flexDirection: "row",
						marginTop: 24,
					},
				},

				...avatars,
				this.state.users.length > to ?
					Text({
						style: {
							color: "#FFFFFF",
							marginLeft: 8,
							fontSize: 14,
						},
						text: BX.message("MOBILE_CALL_LAYOUT_ANOTHER").replace("#COUNT#", this.state.users.length - to),
					})
					:
					null,
			);
		}

		renderIncomingButtons()
		{
			return View(
				{
					style: {
						flexDirection: "row",
						marginBottom: 78,
						justifyContent: "center",
						alignItems: "center",
						width: "100%",
					},
				},
				ImageButton({
					testId: 'callsIncomingDeclineBtn',
					style: {
						width: 66,
						height: 66,
						marginLeft: 40,
					},
					svg: {content: Icons.incomingDecline},
					onClick: () => this.emit(EventName.DeclineButtonClick),
				}),
				Button({
					testId: 'callsIncomingAnswerAudioBtn',
					style: {
						color: this.state.isVideoCall ? "#FFFFFF" : "#00FFFFFF",
						fontSize: 15,
						height: 46,
						paddingLeft: 16,
						paddingRight: 16,
						marginLeft: 16,
						marginRight: 16,
						borderWidth: 1,
						borderRadius: 23,
						borderColor: this.state.isVideoCall ? "#7FFFFFFF" : "#00FFFFFF",
						backgroundColor: this.state.isVideoCall ? "#33FFFFFF" : "#00FFFFFF",
						flexShrink: 1,
						textAlign: "center",

					},
					text: BX.message("MOBILE_CALL_LAYOUT_ANSWER_WITHOUT_VIDEO"),
					onClick: () => this.emit(EventName.AnswerButtonClick, [false]),
				}),
				ImageButton({
					testId: 'callsIncomingAnswerBtn',
					style: {
						width: 66,
						height: 66,
						marginRight: 40,
					},
					svg: {content: this.state.isVideoCall ? Icons.incomingAnswerWithVideo : Icons.incomingAnswer},
					onClick: () => this.emit(EventName.AnswerButtonClick, [this.state.isVideoCall]),
				}),
			);
		}

		renderOutgoing()
		{
			const avBack = this.state.associatedEntityAvatar && !isAvatarBlank(this.state.associatedEntityAvatar) ?
				{backgroundImage: encodeURI(CallUtil.makeAbsolute(this.state.associatedEntityAvatar))} // todo
				: {backgroundImageSvg: Icons.emptyAvatar2};

			return View({
					style: {
						backgroundColor: "#2e323a",
						alignItems: "center",
					},
				},
				this.state.localStream ?
					this.renderLocalStream(false)
					:
					View({
						style: {
							position: "absolute",
							width: "100%",
							height: "100%",
							backgroundResizeMode: "cover",
							backgroundBlurRadius: 6,
							...avBack,
						},
					}),
				this.renderIncomingTop({
					text: this.state.status == "outgoing" ? BX.message("MOBILE_CALL_LAYOUT_WAITING_FOR_ANSWER") : BX.message("MOBILE_CALL_LAYOUT_CONNECTING_TO"),
				}),
				this.state.isGroupCall && this.renderIncomingAvatars(),
				View(
					{
						style: {
							flex: 1,
							width: "100%",
							justifyContent: "flex-end",
						},
					},
					this.renderBottomPanel(),
				),
			);
		}

		renderCall()
		{
			const needCentralUser = this.state.centralUserId != this.userId;
			let showLocalVideoInFrame = this.state.centralUserId !== this.userId;

			let avatar = {};
			if (!this.state.remoteStream)
			{
				if (this.userRegistry.get(this.state.centralUserId).avatar && !isAvatarBlank(this.userRegistry.get(this.state.centralUserId).avatar))
				{
					avatar.backgroundImage = this.userRegistry.get(this.state.centralUserId).avatar;
				}
				else
				{
					avatar.backgroundImageSvg = Icons.emptyAvatar2;
				}
			}

			return View(
				{
					style: {
						flex: 1,
						backgroundColor: "#3A414B",
						backgroundResizeMode: "cover",
						backgroundBlurRadius: 3,
						...avatar,
					},
					onClick: () =>
					{
						this.setState({panelVisible: !this.state.panelVisible});
					},
					onLayout: ({width, height}) =>
					{
						if (width != this.state.screenWidth || height != this.state.screenHeight)
						{
							this.setState({
								screenHeight: height,
								screenWidth: width,
							});
						}
					},
				},
				!!this.state.remoteStream && this.renderRemoteStream(),
				this.state.centralUserVideoPaused && this.renderVideoPaused(),
				needCentralUser && this.renderCenter(),
				!showLocalVideoInFrame && this.renderLocalStream(showLocalVideoInFrame),
				this.renderOverlay(),
				showLocalVideoInFrame && this.renderLocalStream(showLocalVideoInFrame),
				this.renderFloorRequests(),
				this.renderUserSelector(),
				needCentralUser && this.renderCurrentUser(this.userRegistry.get(this.state.centralUserId)),
				(this.userRegistry.get(this.centralUserId).state === BX.Call.UserState.Connecting) ?
					this.renderIncomingTop({
						text: BX.message("MOBILE_CALL_LAYOUT_RESTORING_CONNECTION"),
						paddingTop: 50
					})
					: null,
			);
		}

		renderTopPanel()
		{
			let isLandscape = this.state.screenWidth > this.state.screenHeight;
			return View({
					style: {
						display: this.state.panelVisible ? "flex" : "none",
						height: 204,
						width: "100%",
						backgroundImage: isLandscape ? undefined : pathToExtension + "img/top-gradient.png",
						backgroundResizeMode: "stretch",
						justifyContent: "flex-start",
						position: this.state.panelVisible ? "absolute" : "relative", // display: none + position: absolute does not work
						top: 0,
					},
					clickable: false,
				},
				this.renderParticipantsButton(),
			);
		}

		renderBottomPanel()
		{
			let isLandscape = this.state.screenWidth > this.state.screenHeight;
			return View({
					style: {
						display: this.state.panelVisible ? "flex" : "none",
						height: isLandscape ? 167 : 214,
						width: "100%",
						backgroundImage: pathToExtension + "img/1.png",
						justifyContent: "flex-end",
					},
					clickable: false,
				},
				// this.infoContainer(),
				this.buttonContainer(),
			);
		}

		onButtonClick()
		{
			const {counter} = this.state;
			this.setState({
				counter: counter + 1,
			});
		}

		onResetClick()
		{
			this.setState({
				counter: 0,
			});
		}

		incoming()
		{
			this.setState({status: "incoming"});
		}

		infoContainer()
		{
			return View({
					style: {
						height: 70,
						width: "100%",
						flexDirection: "row",
						alignContent: "center",
						justifyContent: "space-between",
					},
				},

				this.like(),
			);
		}

		buttonContainer()
		{
			return View({
					style: {
						height: 70,
						width: "100%",
						flexDirection: "row",
						alignContent: "center",
						alignSelf: "flex-end",
						resizeMode: "cover",
						justifyContent: "center",
						marginBottom: getSafeArea().bottom > 0 ? Math.min(getSafeArea().bottom, 10) : 0,
					},
				},
				this.button(
					BX.message("MOBILE_CALL_LAYOUT_BUTTON_MICROPHONE"),
					(this.state.microphoneState ? Icons.buttonMic : Icons.buttonMicOff),
					() => this.emit(EventName.MicButtonClick),
					'callsButtonMic_' + this.state.microphoneState ? 'on' : 'off',
				),
				this.button(
					BX.message("MOBILE_CALL_LAYOUT_BUTTON_CAMERA"),
					(this.state.cameraState ? Icons.buttonCamera : Icons.buttonCameraOff),
					() => this.emit(EventName.CameraButtonClick),
					'callsButtonCamera_' + this.state.cameraState ? 'on' : 'off',
				),
				this.button(
					BX.message("MOBILE_CALL_LAYOUT_BUTTON_MENU"),
					Icons.buttonMenu,
					() => this.showCallMenu()
				),
				this.buttonWithCounter(
					BX.message("MOBILE_CALL_LAYOUT_BUTTON_CHAT"),
					this.state.chatCounter,
					Icons.buttonChat,
					() => this.emit(EventName.ChatButtonClick)
				),
				this.button(
					BX.message("MOBILE_CALL_LAYOUT_BUTTON_HANGUP"),
					Icons.buttonHangup,
					() =>
					{
						this.emit(EventName.HangupButtonClick);
					},
				),
			);
		}

		button(text, svgContent, click, testId)
		{
			testId = testId || '';
			return View(
				{
					testId: testId,
					onClick: click,
					style: styles.bottomButtonContainer,
				},
				View({},
					Image({
						onClick: click,
						style: styles.bottomButtonImage,
						svg: {content: svgContent},
						resizeMode: "center",
					}),
				),
				Text({
					style: styles.bottomButtonText,
					text: text,
				})
			);
		}

		buttonWithCounter(text, counter, svgContent, click)
		{
			return View(
				{
					onClick: click,
					style: styles.bottomButtonContainer,
				},
				View({},
					Image({
						onClick: click,
						style: styles.bottomButtonImage,
						svg: {content: svgContent},
						resizeMode: "center",
					}),
				),
				(counter > 0) && View({
					style: styles.bottomButtonCounter
				},
				Text({
					style: styles.bottomButtonCounterText,
					text: counter.toString()
				})
				),
				Text({
					style: styles.bottomButtonText,
					text: text,
				}));
		}

		like()
		{
			return View({
					style: {
						marginLeft: 5,
						marginRight: 5,
						height: 22,
						flexDirection: "row",
					},
				}, Image({
					style: {backgroundColor: "#00ffffff", width: 18, height: 18},
					uri: pathToExtension + "img/like.png",
				}),
				Text({
					style: {fontSize: 16, marginLeft: 4, fontWeight: "bold", color: "#A1ffffff", opacity: 0.2},
					text: BX.message("MOBILE_CALL_LAYOUT_LIKE"),
				}));
		}

		renderParticipantsButton()
		{
			let isLandscape = this.state.screenWidth > this.state.screenHeight;
			return View(
				{
					style: {
						display: this.state.showParticipants ? "flex" : "none",
						marginTop: (isLandscape ? 5 : 0) + getSafeArea().top,
						marginLeft: 16 + getSafeArea().left,
						...styles.participantsButton,
					},
					onClick: () => this.showParticipantsMenu(),
				},
				Image({
					style: styles.participantsButtonIcon,
					svg: {content: Icons.participants}
				}),
				Text({
					style: styles.participantsButtonText,
					text: BX.message("MOBILE_CALL_LAYOUT_PARTICIPANTS").replace("#COUNT#", this.state.connectedUsers.length + 1),
				}),
				View({
					style: styles.participantsButtonArrow,
				}),
			);
		}

		renderFloorRequests()
		{
			const floorRequests = this.state.floorRequestUsers.map(userModel => this.renderFloorRequest(userModel));
			if (floorRequests.length === 0)
			{
				return null;
			}
			return View({
					style: {
						position: "absolute",
						//width: "100%",
						left: 14,
						bottom: 177,
					},
				},
				...floorRequests,
			);
		}

		renderFloorRequest(userModel)
		{
			const avatar = userModel.avatar ? {uri: encodeURI(userModel.avatar)} : {svg: {content: Icons.emptyAvatar}};

			return View(
				{
					style: {
						marginTop: 8,
						height: 23,
						flexDirection: "row",
						borderRadius: 11.5,
						backgroundColor: "#19000000",
						//justifyContent: "center",
						alignItems: "center",
					},
				},
				Image({
					style: {
						width: 17,
						height: 17,
						marginLeft: 3,
						borderRadius: 8.5,
					},
					resizeMode: "cover",
					...avatar,
				}),
				Image({
					style: {
						width: 17,
						height: 17,
						marginLeft: -4,
					},
					svg: {content: Icons.floorRequest},
				}),
				Text({
					style: {
						color: "#ffffff",
						fontSize: 12,
						marginLeft: 5,
						marginRight: 9,
					},
					text: userModel.name,
				}),
			);
		}

		renderUserSelector()
		{
			if (!this.state.showUserSelector)
			{
				return null;
			}

			let dots = this.getConnectedUsers().map(userModel => this.renderUserDot(userModel.centralUser, userModel.talking));
			if (dots.length < 2)
			{
				return null;
			}

			let isLandscape = this.state.screenWidth > this.state.screenHeight;

			return View(
				{
					style: {
						bottom: (isLandscape ? 99 : 146) + (getSafeArea().bottom > 0 ? Math.min(getSafeArea().bottom, 10) : 0),
						...styles.userSelector,
						...(this.state.panelVisible ? {} : {
							display: "none",
							position: "relative", // display: none + position: absolute does not work
						}),
					},
				},
				...dots,
			);
		}

		renderUserDot(active, talking)
		{
			return View(
				{
					style: {
						...styles.userDotOuter,
						backgroundImageSvgUrl: (talking ? pathToExtension + "img/talking-circle.svg" : ""),
					},
				},
				View({
					style: {...styles.userDotInner, backgroundColor: (active ? "#FFFFFF" : "#55FFFFFF")},
				}),
			);
		}

		renderCurrentUser(userModel)
		{
			const avatar = userModel.avatar ? {uri: encodeURI(userModel.avatar)} : {svg: {content: Icons.emptyAvatar}};

			return View(
				{
					style: {
						left: 16 + getSafeArea().left,
						bottom: 89 + (getSafeArea().bottom > 0 ? Math.min(getSafeArea().bottom, 10) : 0),
						...styles.centralUser,
						...(this.state.panelVisible ? {} : {
							display: "none",
							position: "relative", // display: none + position: absolute does not work
						}),
					},
					onClick: () => this.showUserMenu(this.centralUserId),
				},
				View(
					{
						style: {
							...styles.centralUserAvatar,
							backgroundColor: (userModel.talking ? "#2FC6F6" : "#FFFFFF"),
						},
					},
					Image(
						{
							style: styles.centralUserAvatarImage,
							resizeMode: "cover",
							...avatar,
						},
					),
				),
				userModel.floorRequestState && Image({
					style: {
						position: "absolute",
						left: 20,
						bottom: 0,
						width: 27,
						height: 27,
					},
					svg: {content: Icons.floorRequest},
				}),
				View(
					{
						style: styles.centralUserDescription,
					},
					View(
						{
							style: styles.centralUserDescriptionTop,
						},
						!userModel.cameraState && Image({
							testId: 'callsRemoteUserCameraOff',
							style: styles.centralUserCameraOff,
							resizeMode: "contain",
							svg: {content: Icons.cameraOff},
						}),
						!userModel.microphoneState && Image({
							testId: 'callsRemoteUserMicOff',
							style: styles.centralUserMicOff,
							resizeMode: "contain",
							svg: {content: Icons.microphoneOff},
						}),
						Text({
							style: styles.centralUserDescriptionName,
							text: userModel.name,
						}),
						Image({
							style: styles.centralUserArrow,
							resizeMode: "contain",
							svg: {content: Icons.arrowDown},
						}),
					),
					Text({
						style: styles.centralUserDescriptionBottom,
						text: userModel.pinned ? BX.message("MOBILE_CALL_LAYOUT_PINNED_USER") : BX.message("MOBILE_CALL_LAYOUT_CURRENT_PRESENTER"),
					}),
				),
			);
		}

		showCallMenu()
		{
			let callMenu;
			let menuItems = [];
			if (this.getConnectedUserCount() > 1)
			{
				menuItems.push({
					text: BX.message("MOBILE_CALL_MENU_WANT_TO_SAY"),
					iconClass: "hand",
					onClick: () =>
					{
						if (callMenu)
						{
							callMenu.close();
						}
						this.emit(EventName.FloorRequestButtonClick);
					},
				});
			}
			if (this.state.pinnedUserId)
			{
				menuItems.push({
					text: BX.message("MOBILE_CALL_MENU_PARTICIPANTS_RETURN_TO_SPEAKER"),
					iconClass: "returnToSpeaker",
					onClick: () =>
					{
						if (callMenu)
						{
							callMenu.close().then(() => this.unpinUser());
						}
					},
				});
			}

			menuItems.push({
				text: BX.message("MOBILE_CALL_MENU_PARTICIPANTS_LIST"),
				iconClass: "participants",
				onClick: () =>
				{
					if (callMenu)
					{
						callMenu.close().then(() => this.showParticipantsMenu());
					}
				},
			});
			/*menuItems.push({
				text: BX.message("MOBILE_CALL_MENU_COPY_INVITE"),
				iconClass: "copyInvite",
				onClick: () => console.log("e")
			});*/
			menuItems.push({
				text: BX.message("MOBILE_CALL_MENU_SOUND_OUTPUT")
					.replace("#DEVICE_NAME#", this.getSoundDeviceName(JNVIAudioManager.currentDevice)),
				iconClass: this.getSoundDeviceIcon(JNVIAudioManager.currentDevice),
				onClick: () =>
				{
					if (callMenu)
					{
						callMenu.close().then(() => this.showSoundOutputMenu());
					}
				},
			});
			menuItems.push({
				separator: true,
			});
			menuItems.push({
				text: BX.message("MOBILE_CALL_MENU_CANCEL"),
				color: GRAY_MENU_OPTION,
				onClick: () =>
				{
					if (callMenu)
					{
						callMenu.close();
					}
				},
			});

			callMenu = new CallMenu({
				items: menuItems,
				onClose: menu => menu.destroy(),
				onDestroy: () => callMenu = null,
			});

			callMenu.show();
		}

		getSoundDeviceName(deviceAlias)
		{
			switch (deviceAlias)
			{
				case "receiver":
					return BX.message("MOBILE_CALL_SOUND_PHONE");
				case "speaker":
					return BX.message("MOBILE_CALL_SOUND_SPEAKER");
				case "bluetooth":
					return BX.message("MOBILE_CALL_SOUND_BLUETOOTH");
				case "wired":
					return BX.message("MOBILE_CALL_SOUND_WIRED");
				case "none":
					return BX.message("MOBILE_CALL_SOUND_NONE");
			}
		}

		getSoundDeviceIcon(deviceAlias)
		{
			switch (deviceAlias)
			{
				case "receiver":
					return "devicePhone";
				case "speaker":
					return "deviceSpeaker";
				case "bluetooth":
					return "deviceBlueTooth";
				case "wired":
					return "deviceWired";
			}
		}

		showSoundOutputMenu()
		{
			let soundMenu;
			let menuItems = JNVIAudioManager.availableAudioDevices.map(deviceAlias =>
			{
				return {
					text: this.getSoundDeviceName(deviceAlias),
					iconClass: this.getSoundDeviceIcon(deviceAlias),
					selected: deviceAlias === JNVIAudioManager.currentDevice,
					onClick: () =>
					{
						if (soundMenu)
						{
							soundMenu.close();
						}
						this.emit(EventName.SelectAudioDevice, [deviceAlias]);
					},
				};
			});
			menuItems.push({
				separator: true,
			});
			menuItems.push({
				text: BX.message("MOBILE_CALL_MENU_CANCEL"),
				color: GRAY_MENU_OPTION,
				onClick: () =>
				{
					if (soundMenu)
					{
						soundMenu.close();
					}
				},
			});

			soundMenu = new CallMenu({
				items: menuItems,
				onClose: () => soundMenu.destroy(),
				onDestroy: () => soundMenu = null,
			});
			soundMenu.show();
		}

		showUserMenu(userId)
		{
			// todo: move userRegistry to controller maybe?
			let userModel = this.userRegistry.get(userId);
			if (!userModel)
			{
				return false;
			}
			let userMenu;
			let pinItem;
			if (this.state.pinnedUserId == userId)
			{
				pinItem = {
					text: BX.message("MOBILE_CALL_MENU_UNPIN"),
					iconClass: "unpin",
					onClick: () =>
					{
						if (userMenu)
						{
							userMenu.close();
						}
						this.unpinUser();
					},
				};
			}
			else
			{
				pinItem = {
					text: BX.message("MOBILE_CALL_MENU_PIN"),
					iconClass: "pin",
					onClick: () =>
					{
						if (userMenu)
						{
							userMenu.close();
						}
						this.pinUser(userId);
					},
				};
			}

			let menuItems = [
				{
					userModel: userModel,
					color: GRAY_MENU_OPTION,
				},
				{
					separator: true,
				},
				pinItem,
				{
					text: BX.message("MOBILE_CALL_MENU_WRITE_TO_PRIVATE_CHAT"),
					iconClass: "chat",
					onClick: () =>
					{
						if (userMenu)
						{
							userMenu.close();
						}
						this.emit(EventName.PrivateChatButtonClick, [userId]);
					},
				},
				/*{
					// TODO:
					text: "Remove user",
					iconClass: "remove-user"
				},*/
				{
					separator: true,
				},
				{
					text: BX.message("MOBILE_CALL_MENU_CANCEL"),
					color: GRAY_MENU_OPTION,
					onClick: () =>
					{
						if (userMenu)
						{
							userMenu.close();
						}
					},
				},
			];

			userMenu = new CallMenu({
				items: menuItems,
				onClose: () => userMenu.destroy(),
				onDestroy: () => userMenu = null,
			});
			userMenu.show();
		}

		showParticipantsMenu()
		{
			let participantsMenu;
			let menuItems = [];
			this.userRegistry.users.forEach((userModel) =>
			{
				if (userModel.state != BX.Call.UserState.Connected)
				{
					return;
				}
				if (menuItems.length > 0)
				{
					menuItems.push({
						separator: true,
					});
				}
				menuItems.push({
					userModel: userModel,
					showSubMenu: userModel.id != env.userId,
					onClick: () =>
					{
						if (userModel.id == env.userId)
						{
							return;
						}
						if (participantsMenu)
						{
							participantsMenu.close().then(() => this.showUserMenu(userModel.id));
						}
					},
				});
			});

			if (menuItems.length === 0)
			{
				return false;
			}

			participantsMenu = new CallMenu({
				items: menuItems,
				header: BX.message("MOBILE_CALL_MENU_PARTICIPANTS").replace("#COUNT#", this.getConnectedUserCount() + 1),
				largeIcons: true,

				onClose: (menu) => menu.destroy(),
				onDestroy: () => participantsMenu = null,
			});
			participantsMenu.show();
		}

		destroy()
		{
			this.videoStreams = null;
			this.state = {};

			this.emit(EventName.Destroy);
		}
	}

	BX.prop =
		{
			get: function (object, key, defaultValue)
			{
				return object && object.hasOwnProperty(key) ? object[key] : defaultValue;
			},
			getObject: function (object, key, defaultValue)
			{
				return object && BX.type.isPlainObject(object[key]) ? object[key] : defaultValue;
			},
			getElementNode: function (object, key, defaultValue)
			{
				return object && BX.type.isElementNode(object[key]) ? object[key] : defaultValue;
			},
			getArray: function (object, key, defaultValue)
			{
				return object && BX.type.isArray(object[key]) ? object[key] : defaultValue;
			},
			getFunction: function (object, key, defaultValue)
			{
				return object && BX.type.isFunction(object[key]) ? object[key] : defaultValue;
			},
			getNumber: function (object, key, defaultValue)
			{
				if (!(object && object.hasOwnProperty(key)))
				{
					return defaultValue;
				}

				var value = object[key];
				if (BX.type.isNumber(value))
				{
					return value;
				}

				value = parseFloat(value);
				return !isNaN(value) ? value : defaultValue;
			},
			getInteger: function (object, key, defaultValue)
			{
				if (!(object && object.hasOwnProperty(key)))
				{
					return defaultValue;
				}

				var value = object[key];
				if (BX.type.isNumber(value))
				{
					return value;
				}

				value = parseInt(value);
				return !isNaN(value) ? value : defaultValue;
			},
			getBoolean: function (object, key, defaultValue)
			{
				if (!(object && object.hasOwnProperty(key)))
				{
					return defaultValue;
				}

				var value = object[key];
				return (BX.type.isBoolean(value)
						? value
						: (BX.type.isString(value) ? (value.toLowerCase() === "true") : !!value)
				);
			},
			getString: function (object, key, defaultValue)
			{
				if (!(object && object.hasOwnProperty(key)))
				{
					return defaultValue;
				}

				var value = object[key];
				return BX.type.isString(value) ? value : (value ? value.toString() : "");
			},
			extractDate: function (datetime)
			{
				if (!BX.type.isDate(datetime))
				{
					datetime = new Date();
				}

				datetime.setHours(0);
				datetime.setMinutes(0);
				datetime.setSeconds(0);
				datetime.setMilliseconds(0);

				return datetime;
			},
		};

	const blankAvatar = "/bitrix/js/im/images/blank.gif";
	const isAvatarBlank = (url) =>
	{
		return typeof (url) !== "string" || url == "" || url.endsWith(blankAvatar);
	};

	function getSafeArea()
	{
		if (device.screen.safeArea)
		{
			return device.screen.safeArea;
		}
		else
		{
			return {
				top: 0,
				bottom: 0,
				left: 0,
				right: 0,
			}
		}
	}

	window.CallLayout = CallLayout;
	window.CallLayout.Event = EventName;
})();

