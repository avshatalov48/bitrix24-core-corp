<?

use Bitrix\Intranet\Integration\Templates\Air\AirTemplate;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Page\AssetLocation;
use Bitrix\Main\Page\AssetMode;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (defined('ADMIN_SECTION') && ADMIN_SECTION === true)
{
	return [];
}

if (
	!defined('SITE_TEMPLATE_ID')
	|| !in_array(SITE_TEMPLATE_ID, ['bitrix24', 'air', 'desktop_app', 'login', 'pub', 'landing24', 'dashboard_detail'])
)
{
	return [];
}

$server = Context::getCurrent()->getServer();
$userAgent = $server->get('HTTP_USER_AGENT') ?? '';
$jsInjection = '';
if (preg_match('/Linux/i', $userAgent) && !preg_match('/Android/i', $userAgent))
{
	$js = <<<JS
		(function() {
			const ua = navigator.userAgent;
			if (!/Linux/i.test(ua) || /Android/i.test(ua))
			{
				return;
			}

			const text = 'QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm';
			const fonts = {
				regular: { weight: 400, width: 0 },
				medium: { weight: 460, width: 0 },
				semiBold: { weight: 560, width: 0 },
				bold: { weight: 700, width: 0 }
			};

			Object.keys(fonts).forEach(name => {
				const font = fonts[name];
				const context = document.createElement('canvas').getContext('2d');
				context.font = 'normal ' + font.weight + ' 12px system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Ubuntu, "Helvetica Neue", Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol"';
				const textMetrics = context.measureText(text);
				font.width = Math.round(textMetrics.width);
			});

			const html = document.documentElement;
			if (fonts.medium.width > fonts.regular.width)
			{
				html.classList.add('bx-font-medium');
			}

			if (fonts.semiBold.width < fonts.bold.width && fonts.semiBold.width > fonts.medium.width)
			{
				html.classList.add('bx-font-semi-bold');
			}
		})();
	JS;

	$jsInjection = '<script data-skip-moving="true">' . str_replace(["\n", "\t"], '', $js) . '</script>';
	$asset = Asset::getInstance();
	$asset->addString($jsInjection, false, AssetLocation::BEFORE_CSS, AssetMode::ALL);
}

$detectGPU = <<<JS
(function() {
	const canvas = document.createElement('canvas');
	let gl;

	try
	{
		gl = canvas.getContext('webgl2') || canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
	}
	catch (e)
	{
		return;
	}

	if (!gl)
	{
		return;
	}

	const result = {
		vendor: gl.getParameter(gl.VENDOR),
		renderer: gl.getParameter(gl.RENDERER),
	};

	const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
	if (debugInfo)
	{
		result.unmaskedVendor = gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL);
		result.unmaskedRenderer = gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL);
	}

	function isLikelyIntegratedGPU(gpuInfo)
	{
		const renderer = (gpuInfo.unmaskedRenderer || gpuInfo.renderer || '').toLowerCase();
		const vendor = (gpuInfo.unmaskedVendor || gpuInfo.vendor || '').toLowerCase();

		const integratedPatterns = [
			'intel',
			'hd graphics',
			'uhd graphics',
			'iris',
			'apple gpu',
			'adreno',
			'mali',
			'powervr',
			'llvmpipe',
			'swiftshader',
			'hd 3200 graphics',
			'rs780'
		];

		return integratedPatterns.some(pattern => renderer.includes(pattern) || vendor.includes(pattern));
	}

	const isLikelyIntegrated = isLikelyIntegratedGPU(result);
	if (isLikelyIntegrated)
	{
		const html = document.documentElement;
		html.classList.add('bx-integrated-gpu', '--ui-reset-bg-blur');
	}
})();
JS;

$detectGpuScript = '<script data-skip-moving="true">' . str_replace(["\n", "\t"], '', $detectGPU) . '</script>';
$asset = Asset::getInstance();
$asset->addString($detectGpuScript, false, AssetLocation::BEFORE_CSS, AssetMode::ALL);

$eventManager = \Bitrix\Main\EventManager::getInstance();
$eventManager->addEventHandler(
	'fileman',
	'HtmlEditor:onBeforeBuild',
	function (\Bitrix\Main\Event $event) use ($jsInjection)
	{
		$addGlobalClass = <<<JS
			(function() {
				if (parent.BX && parent.BX.Browser && parent.BX.Browser.addGlobalClass.length > 0)
				{
					parent.BX.Browser.addGlobalClass(document.documentElement);
				} 
			})();
		JS;

		$jsInjection = '<script>' . str_replace(["\n", "\t"], '', $addGlobalClass) . '</script>' . $jsInjection;

		$editor = $event->getParameters()[0];
		$editor->setOption('headHtml', $jsInjection);
	}
);

if (Loader::includeModule('intranet'))
{
	if (AirTemplate::isEnabled())
	{
		return [
			'css' =>[
				'air-design-tokens.css',
			],
			'skip_core' => true,
		];
	}
}

return [
	'css' =>[
		'bitrix24-design-tokens.css',
	],
	'skip_core' => true,
];
