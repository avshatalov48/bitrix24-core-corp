<? use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
	die();
} ?>
<script id="search-progress" type="text/html">
	<div class="disk-search-progress" {{^isTimeToShow}} style="display: none" {{/isTimeToShow}}><?= Loc::getMessage('DISK_FOLDER_LIST_SEARCH_PROGRESS_LABEL') ?>: <span class="disk-search-progress-info">{{current}} / {{total}}</span></div>
</script>