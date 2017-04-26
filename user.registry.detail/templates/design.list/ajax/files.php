<?php
define('STOP_STATISTICS', true);
define('NOT_CHECK_PERMISSIONS', true);

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use \Bitrix\Main\Application,
    \Bitrix\Main\Localization\Loc;

Loc::loadLanguageFile(__FILE__);
global $APPLICATION;

$context = Application::getInstance()->getContext();
$docRoot = $context->getServer()->getDocumentRoot();
$arPost = $context->getRequest()->getPostList()->toArray();

$arData = array();
if($arPost['SFILES'] == 'Y') {
    $arCurFile = pathinfo(GetPagePath());
    $sFolder = urldecode($arPost['FOLDER']);
    $folder = $docRoot . $sFolder;
    $files = scandir($folder);
    $tmpHtml = '<div><ol>';
    $arComponentFiles = array();
    $numbFile = 1;
    foreach($files as $iFile => $nFile) {
        if($nFile != '..' & $nFile != '.' && !stristr($nFile, 'thumbnail')) {
            $arComponentFiles[] = $nFile;
            $extFile = pathinfo($nFile);
            $fileName = iconv('cp1251', 'utf-8', transliterateOut($extFile['filename']));
            $arData['FILES'][$iFile] = $extFile;
            $clsActiveFile = '';
            if($numbFile == 1) {
                $clsActiveFile = ' js-file-active';
            }
            $tmpHtml .= '
                <li>
                    <a class="js-file-iframe js-file' . $clsActiveFile . '" 
                        data-files="' . $arPost['FOLDER'] . '" 
                        data-f-number="' . $numbFile . '" 
                        href="' . urldecode($extFile['basename']) . '">' . $fileName . '</a>
                    <a class="js-file-delete" 
                        href="#" 
                        data-ajax-delete="' . $arCurFile['dirname'] . '/delete.php"
                        data-ajax-files="' . $arCurFile['dirname'] . '/files.php"
                        data-lang-t="' . iconv('cp1251', 'utf-8', Loc::getMessage('FILE_DELETE_T')) . '" 
                        data-lang-q="' . iconv('cp1251', 'utf-8', Loc::getMessage('FILE_DELETE_Q')) . '" 
                        data-lang-y="' . iconv('cp1251', 'utf-8', Loc::getMessage('FILE_DELETE_Y')) . '" 
                        data-lang-n="' . iconv('cp1251', 'utf-8', Loc::getMessage('FILE_DELETE_N')) . '" 
                        title="' . iconv('cp1251', 'utf-8', Loc::getMessage('FILE_DELETE_T')) . '">
                        <i class="fa fa-remove"></i>
                    </a>
                </li>';
            $numbFile++;
        }
    }
    $tmpHtml .= '</ol></div>';
    $arData['HTML_LIST'] = $tmpHtml;
    $useScrypt = $arPost['LOAD_SCRYPTS'];
    $htmlView = $APPLICATION->IncludeComponent(
        'box:box.file.viewer',
        '',
        array(
            'FILE_FOLDER' => $sFolder,          // путь к директории с файлами
            'USE_ADD_SCRIPTS' => $useScrypt,    // ѕараметр определ€ющий использовать стили и js скрипты или нет (пр.: при повторном запросе они уже будут "вредны")
            'FILE_LIST' => $arComponentFiles,   // массив с файлами дл€ вывода в просмотровщике
            'FILE_IMG_EXTENSION' => 'png',      // расширение дл€ изображений формируемых из pdf (по умолчанию jpg)
            'USE_HTML_COMPONENT' => 'Y'         // использовать html формируемый в компоненте
        ));
    $arData['HTML_VIEWER'] = $htmlView;
    $arData['FOLDER'] = $folder;
    echo json_encode($arData);
}