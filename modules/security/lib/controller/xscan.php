<?php

namespace Bitrix\Security\Controller;

use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Localization\Loc;
use Bitrix\Security\XScanResultTable;

class Xscan extends Controller
{
	protected function processBeforeAction(Action $action): bool
	{
		ini_set('display_errors', '0');
		Loc::loadMessages(__FILE__);

		if (!Controller::getCurrentUser()->isAdmin())
		{
			return false;
		}

		return parent::processBeforeAction($action);
	}

	public function prisonAction(string $file)
	{
		$file = '/' . trim($file, '/');

		if (!$file || !file_exists($file))
		{
			$msg = \CBitrixXscan::ShowMsg(Loc::getMessage("BITRIX_XSCAN_FILE_NOT_FOUND") . htmlspecialcharsbx($file),
				'red');
		}
		else
		{
			$new_f = preg_replace('#\.php[578]?$#i', '.ph_', $file);
			if (rename($file, $new_f))
			{
				$msg = \CBitrixXscan::ShowMsg(Loc::getMessage("BITRIX_XSCAN_RENAMED") . htmlspecialcharsbx($new_f));
			}
			else
			{
				$msg = \CBitrixXscan::ShowMsg(Loc::getMessage("BITRIX_XSCAN_ERR_RENAME") . htmlspecialcharsbx($file),
					'red');
			}
		}

		return $msg;
	}

	public function releaseAction(string $file)
	{
		$file = '/' . trim($file, '/');

		if (!$file || !file_exists($file))
		{
			$msg = \CBitrixXscan::ShowMsg(Loc::getMessage("BITRIX_XSCAN_FILE_NOT_FOUND") . htmlspecialcharsbx($file),
				'red');
		}
		else
		{
			$new_f = preg_replace('#\.ph_$#', '.php', $file);
			if (rename($file, $new_f))
			{
				$msg = \CBitrixXscan::ShowMsg(Loc::getMessage("BITRIX_XSCAN_RENAMED") . htmlspecialcharsbx($new_f));
			}
			else
			{
				$msg = \CBitrixXscan::ShowMsg(Loc::getMessage("BITRIX_XSCAN_ERR_RENAME") . htmlspecialcharsbx($file),
					'red');
			}
		}

		return $msg;
	}

	public function hideAction(string $file)
	{
		$file = '/' . trim($file, '/');
		$msg = '';

		$ent = XScanResultTable::getList(['select' => ['id'], 'filter' => ['src' => $file]])->fetch();

		if ($ent)
		{
			XScanResultTable::delete($ent['id']);
			$msg = \CBitrixXscan::ShowMsg(Loc::getMessage("BITRIX_XSCAN_HIDED") . htmlspecialcharsbx($file));
		}

		return $msg;
	}

	public function hideFilesAction(array $files, string $all='false')
	{
		$all = $all === 'true';

		\Bitrix\Main\Type\Collection::normalizeArrayValuesByInt($files);

		$filter = [];
		if (!$all)
		{
			$filter = ['@id' => $files];
		}

		XScanResultTable::deleteList($filter);

		return '';
	}

	public function addErrorAction(string $file)
	{
		$file = '/' . trim($file, '/');

		if ($file)
		{
			XScanResultTable::add(['type' => 'file', 'src' => $file, 'message' => 'error', 'score' => 0.5]);
		}

		return '';
	}

	public function scanAction(string $start_path, string $break_point = '', string $clean = 'N', int $progress = 0,
		int $total = 0)
	{
		$start_path = $start_path ? $start_path : $_SERVER['DOCUMENT_ROOT'];
		$start_path = rtrim($start_path, '/');

		$scaner = new \CBitrixXscan($progress, $total);
		$scaner->skip_path = $break_point;

		$session = \Bitrix\Main\Application::getInstance()->getSession();

		if (!is_dir($start_path))
		{
			$msg = Loc::getMessage("BITRIX_XSCAN_NACALQNYY_PUTQ_NE_NA");
			return ['error' => $msg];
		}

		if ($clean == 'Y')
		{
			$session['xscan_page'] = 1;
			$session->save();

			$scaner->clean();
			$scaner->CheckEvents();
			$scaner->CheckAgents();
			$scaner->Search($start_path, 'count');
		}
		else
		{
			$session->save();
		}

		$scaner->Search($start_path);
		$scaner->SavetoDB();


		$prc = $scaner->total == 0 ? min(75, (int)($scaner->progress / 1000)) : (int)($scaner->progress * 100 / $scaner->total);

		return [
			'progress' => $scaner->progress,
			'total' => $scaner->total,
			'break_point' => $scaner->break_point,
			'prc' => $prc,
		];
	}

	public function findHtaccessAction(string $break_point = '')
	{
		$localStorage = \Bitrix\Main\Application::getInstance()->getLocalSession('xscan_htaccess');

		if (!$break_point)
		{
			$localStorage->clear();
			$localStorage->set('timestamp', time());
			$localStorage->set('status', 'pending');
		}

		$path = rtrim($_SERVER['DOCUMENT_ROOT'], '/');

		$searcher = new \CBitrixXscanHtaccess();
		$searcher->skip_path = $break_point;
		$searcher->Search($path);

		$files = $localStorage['files'] ?? [];

		if($searcher->result)
		{
			$files = array_merge($files, $searcher->result);
			$localStorage->set('files', $files);
		}

		if (!$searcher->break_point)
		{
			$localStorage->set('status', 'done');
		}


		return [
			'count' => count($files),
			'break_point' => $searcher->break_point
		];

	}
}