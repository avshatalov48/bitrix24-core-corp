type CrmDocumentProps = {
	id: string,
	title: string,
	publicUrl: string | null,
	publicUrlView?: {
		time: string,
	},
	downloadUrl: string,
	pdfUrl?: string,
	imageUrl?: string,
	pullTag: string,
	createTime: string,
	updateTime: string,
	isTransformationError: boolean,
	qrCodeEnabled: boolean,
	changeQrCodeDisabledReason?: string,
	stampsEnabled: boolean,
	changeStampsEnabled: boolean,
};
