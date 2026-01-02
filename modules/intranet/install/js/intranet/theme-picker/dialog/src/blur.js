import { Type, Browser, Text } from 'main.core';
import { Helpers } from 'ui.uploader.core';

export async function blur(file: File | HTMLImageElement, radius = 7): Promise<{ file: File, color: string }>
{
	const { image, width, height } = (
		Type.isFile(file)
			? await Helpers.loadImage(file)
			: { image: file, width: file.naturalWidth || file.width, height: file.naturalHeight || file.height }
	);

	const canvas = document.createElement('canvas');
	canvas.width = width;
	canvas.height = height;
	const ctx = canvas.getContext('2d');

	if ('filter' in ctx)
	{
		const padding = Math.ceil(radius) * 2;
		const tmpCanvas = document.createElement('canvas');
		const tmpContext = tmpCanvas.getContext('2d');

		const w = image.width;
		const h = image.height;

		tmpCanvas.width = w + padding * 2;
		tmpCanvas.height = h + padding * 2;

		tmpContext.drawImage(image, 0, 0, w, 1, padding, 0, w, padding);
		tmpContext.drawImage(image, 0, h - 1, w, 1, padding, h + padding, w, padding);
		tmpContext.drawImage(image, 0, 0, 1, h, 0, padding, padding, h);
		tmpContext.drawImage(image, w - 1, 0, 1, h, w + padding, padding, padding, h);
		tmpContext.drawImage(image, 0, 0, 1, 1, 0, 0, padding, padding);
		tmpContext.drawImage(image, w - 1, 0, 1, 1, w + padding, 0, padding, padding);
		tmpContext.drawImage(image, 0, h - 1, 1, 1, 0, h + padding, padding, padding);
		tmpContext.drawImage(image, w - 1, h - 1, 1, 1, w + padding, h + padding, padding, padding);

		tmpContext.drawImage(image, padding, padding);
		tmpContext.filter = `blur(${radius}px)`;

		tmpContext.drawImage(
			tmpCanvas,
			0,
			0,
			tmpCanvas.width,
			tmpCanvas.height,
			0,
			0,
			tmpCanvas.width,
			tmpCanvas.height,
		);

		ctx.drawImage(tmpCanvas, padding, padding, w, h, 0, 0, w, h);
	}
	else
	{
		console.log('blur manually');
		ctx.drawImage(image, 0, 0);

		const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
		const blurredImageData = blurManually(imageData, radius);

		ctx.putImageData(blurredImageData, 0, 0);
	}

	const pixel = ctx.getImageData(0, 0, 10, 10);
	const [r, g, b] = pixel.data;
	const hex = `#${[r, g, b].map((x) => x.toString(16).padStart(2, '0')).join('')}`;

	const type = Browser.isSafari() ? 'image/jpeg' : 'image/webp';
	const extension = Browser.isSafari() ? 'jpg' : 'webp';
	const blob = await new Promise((resolve) => {
		canvas.toBlob(resolve, type);
	});

	const fileName = (
		file.name
			? `blurred_${Helpers.getFilenameWithoutExtension(file.name)}.${extension}`
			: `blurred_${Text.getRandom()}.${extension}`
	);

	return {
		file: new File([blob], fileName, { type }),
		color: hex,
	};
}

function blurManually(imageData: ImageData, radius: number): ImageData
{
	if (radius < 1)
	{
		return imageData;
	}

	const data = imageData.data;
	const width = imageData.width;
	const height = imageData.height;

	const intRadius = Math.round(radius);
	const boxes = calculateBoxSizes(intRadius, 3);

	const tempData = new Uint8ClampedArray(data);
	let currentSrc = data;
	let currentDst = tempData;

	for (let i = 0; i < 3; i++)
	{
		const boxRadius = Math.floor((boxes[i] - 1) / 2);
		blurSymmetric(currentSrc, currentDst, width, height, boxRadius);
		[currentSrc, currentDst] = [currentDst, currentSrc];
	}

	if (currentSrc !== data)
	{
		data.set(currentSrc);
	}

	return imageData;
}

function blurSymmetric(
	srcData: Uint8ClampedArray,
	dstData: Uint8ClampedArray,
	width: number,
	height: number,
	radius: number,
)
{
	const tempData = new Uint8ClampedArray(srcData.length);
	blurHorizontal(srcData, tempData, width, height, radius);
	blurVertical(tempData, dstData, width, height, radius);
}

function calculateBoxSizes(sigma, n): number[]
{
	const wIdeal = Math.sqrt((12 * sigma * sigma / n) + 1);
	let wl = Math.floor(wIdeal);
	if (wl % 2 === 0)
	{
		wl--;
	}

	const wu = wl + 2;

	const mIdeal = (12 * sigma * sigma - n * wl * wl - 4 * n * wl - 3 * n) / (-4 * wl - 4);
	const m = Math.round(mIdeal);

	const sizes = [];
	for (let i = 0; i < n; i++)
	{
		sizes.push(i < m ? wl : wu);
	}

	return sizes;
}

function blurHorizontal(
	srcData: Uint8ClampedArray,
	dstData: Uint8ClampedArray,
	width: number,
	height: number,
	radius: number,
)
{
	if (radius === 0)
	{
		dstData.set(srcData);

		return;
	}

	const kernelSize = radius * 2 + 1;
	const iarr = 1 / kernelSize;

	for (let y = 0; y < height; y++)
	{
		const rowStart = y * width * 4;
		let r = 0;
		let g = 0;
		let b = 0;
		let a = 0;

		for (let j = -radius; j <= radius; j++)
		{
			const x = Math.max(0, Math.min(width - 1, j));
			const offset = rowStart + x * 4;
			r += srcData[offset];
			g += srcData[offset + 1];
			b += srcData[offset + 2];
			a += srcData[offset + 3];
		}

		let dstOffset = rowStart;
		dstData[dstOffset] = Math.round(r * iarr);
		dstData[dstOffset + 1] = Math.round(g * iarr);
		dstData[dstOffset + 2] = Math.round(b * iarr);
		dstData[dstOffset + 3] = Math.round(a * iarr);

		for (let x = 1; x < width; x++)
		{
			const leftX = Math.max(0, Math.min(width - 1, x - radius - 1));
			const leftOffset = rowStart + leftX * 4;
			r -= srcData[leftOffset];
			g -= srcData[leftOffset + 1];
			b -= srcData[leftOffset + 2];
			a -= srcData[leftOffset + 3];

			const rightX = Math.max(0, Math.min(width - 1, x + radius));
			const rightOffset = rowStart + rightX * 4;
			r += srcData[rightOffset];
			g += srcData[rightOffset + 1];
			b += srcData[rightOffset + 2];
			a += srcData[rightOffset + 3];

			dstOffset = rowStart + x * 4;
			dstData[dstOffset] = Math.round(r * iarr);
			dstData[dstOffset + 1] = Math.round(g * iarr);
			dstData[dstOffset + 2] = Math.round(b * iarr);
			dstData[dstOffset + 3] = Math.round(a * iarr);
		}
	}
}

function blurVertical(
	srcData: Uint8ClampedArray,
	dstData: Uint8ClampedArray,
	width: number,
	height: number,
	radius: number,
)
{
	if (radius === 0)
	{
		dstData.set(srcData);

		return;
	}

	const kernelSize = radius * 2 + 1;
	const iarr = 1 / kernelSize;
	const widthBytes = width * 4;

	for (let x = 0; x < width; x++)
	{
		const colStart = x * 4;

		let r = 0;
		let g = 0;
		let b = 0;
		let a = 0;

		for (let j = -radius; j <= radius; j++)
		{
			const y = Math.max(0, Math.min(height - 1, j));
			const offset = y * widthBytes + colStart;
			r += srcData[offset];
			g += srcData[offset + 1];
			b += srcData[offset + 2];
			a += srcData[offset + 3];
		}

		let dstOffset = colStart;
		dstData[dstOffset] = Math.round(r * iarr);
		dstData[dstOffset + 1] = Math.round(g * iarr);
		dstData[dstOffset + 2] = Math.round(b * iarr);
		dstData[dstOffset + 3] = Math.round(a * iarr);

		for (let y = 1; y < height; y++)
		{
			const topY = Math.max(0, Math.min(height - 1, y - radius - 1));
			const topOffset = topY * widthBytes + colStart;
			r -= srcData[topOffset];
			g -= srcData[topOffset + 1];
			b -= srcData[topOffset + 2];
			a -= srcData[topOffset + 3];

			const bottomY = Math.max(0, Math.min(height - 1, y + radius));
			const bottomOffset = bottomY * widthBytes + colStart;
			r += srcData[bottomOffset];
			g += srcData[bottomOffset + 1];
			b += srcData[bottomOffset + 2];
			a += srcData[bottomOffset + 3];

			dstOffset = y * widthBytes + colStart;
			dstData[dstOffset] = Math.round(r * iarr);
			dstData[dstOffset + 1] = Math.round(g * iarr);
			dstData[dstOffset + 2] = Math.round(b * iarr);
			dstData[dstOffset + 3] = Math.round(a * iarr);
		}
	}
}
