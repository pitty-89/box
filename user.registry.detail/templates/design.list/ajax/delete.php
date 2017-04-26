<?php
define('STOP_STATISTICS', true);
define('NOT_CHECK_PERMISSIONS', true);

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use \Bitrix\Main\Application;

$arPost = Application::getInstance()->getContext()->getRequest()->getPostList()->toArray();
$docRoot = Application::getDocumentRoot();

if(!empty($arPost['F_DELETE']) && $arPost['F_DELETE'] == 'Y') {
    $folder = urldecode($arPost['F_PATH']);
    unlink($docRoot . $folder . $arPost['F_NAME']);
    $files = scandir($docRoot . $folder);
    $countFiles = 0;
    foreach($files as $iFile => $nFile) {
        if($nFile != '..' & $nFile != '.' && !stristr($nFile, 'thumbnail')) {
            $countFiles++;
        }
    }
    if($countFiles == 0) {
        system("rm -rf " . $docRoot . $folder);
    }
    echo $countFiles;
}