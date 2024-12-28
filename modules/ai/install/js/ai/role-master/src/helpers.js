export async function loadImageAsFile(imgPath: string): Promise<File>
{
	const fileName = imgPath.split('/').pop();
	const response = await fetch(imgPath);
	const mimeType = response.headers.get('Content-Type') || 'application/octet-stream';

	const data = await response.blob();
	const blob = new Blob([data], { type: mimeType });

	return new File([blob], fileName, { type: mimeType });
}