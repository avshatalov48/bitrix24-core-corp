<?php
namespace Bitrix\Mobile\Controller\TextEditor;

use Bitrix\Main\Engine\Controller;

class TextEditor extends Controller
{
    public function getHtmlAction($bbcode): array
    {
        if (is_string($bbcode) && !empty($bbcode))
        {
            $parser = new \CTextParser();
            $html = $parser->convertText($bbcode);

            return [
                'html' => $html,
            ];
        }

        return [
            'html' => '',
        ];
    }
}
