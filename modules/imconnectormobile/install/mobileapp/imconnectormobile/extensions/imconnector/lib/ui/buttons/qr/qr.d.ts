type QrButtonStyle = {
	borderRadius: number,
	width: number,
	height: number,
};

type QrButtonProps = {
	text: string,
	image: string,
	parentWidget: Object,
	onClick: Function,
	style: QrButtonStyle,
};