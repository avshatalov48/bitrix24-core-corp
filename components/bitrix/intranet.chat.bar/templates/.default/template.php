<?

use Bitrix\Im\V2\Application;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\UI\Extension::load('im.v2.application.quick-access');

$frame = $this->createFrame('im')->begin('');
$application = new Application();

?>
<div class="im-chat-recent-list" id="bx-im-external-recent-list"></div>
<script><?=$application->getTemplate(Application::QUICK_ACCESS_APP_NAME)?></script>
<div id="im-chat-menu" class="im-chat-menu --ui-context-edge-dark"><?

$APPLICATION->includeComponent(
	'bitrix:main.interface.buttons',
	'',
	[
		'ID' => 'chat-menu',
		'ITEMS' => $arResult['MENU_ITEMS'],
		'THEME' => 'air',
	]
);

?>
</div>
<?

$frame->end();
