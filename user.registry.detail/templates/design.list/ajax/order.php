<?php
define('STOP_STATISTICS', true);
define('NOT_CHECK_PERMISSIONS', true);

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use \Bitrix\Main\Application,
    \Bitrix\Main\Localization\Loc;

Loc::loadLanguageFile(__FILE__);

$context = Application::getInstance()->getContext();
$arPost = $context->getRequest()->getPostList()->toArray();
$arGet = $context->getRequest()->getQueryList()->toArray();

//region Определяем входящие параметры
$statusWMS = $arPost['STATUS'];                     // WMS статус ЕУ
$action = $arPost['ACTION'];                        // Действие выполненное пользователем (Y - выделение ЕУ, N - снятие выделения ЕУ)
$contract = $arPost['CONTRACT_ID'];                 // ID договора выделенной ЕУ
$unitID = $arPost['ID'];                            // ID ЕУ
$checkUri = $arPost['CHECK_URI'];                   // Адрес для кнопки "Показать выделенные"
$checkCur = $arPost['CHECK_CUR'];                   // Параметр определяющий GET запрос на показ только выделеннных
$multiSelect = $arPost['MULTISELECT'];              // Параметр определяющий количество ЕУ над которыми производится действи (если == Y то выделили пакетом)
//endregion
//region Определяем данные по классу заказов
$cOrder = new Orders();                             // Инициализируем класс для обработки заказа для добавления его в сессию пользователя
$userID = $cOrder->getUserID();                     // ID пользователя
$typesOrder = $cOrder->checkPermission($statusWMS); // Типы заказов разрешенные пользователю с учетом статуса WMS выделенной ЕУ
//endregion

//region Выделили ячейку
$resHTML = '';
if($action == 'ADD') {
    // Добавляем в сессию данные о выделенной ячейке
    $cOrder->addUnitToList($contract, $unitID, $statusWMS);
    // Проверка статуса WMS
    if($statusWMS) {
        $typesOrder = $cOrder->checkPermission($statusWMS);
    } else {
        $typesOrder = $cOrder->checkAllowStatus();
    }
    // Если по статусу текущей ЕУ нельзя сформировать заказ
    if(!$typesOrder) {
        $resHTML .= '
            <div class="control__actions">
                <div class="form__row__name"></div>
                <div class="form__row__col__item">
                        <a href="#" class="js-btn-reset">' . Loc::getMessage('BUTTON_RESET') .'</a>
                    </div>
                <div class="form__row__col__item _size2 frn-danger">
                    ' . Loc::getMessage('ERROR_CURENT_STATUS') . '
                </div>
            </div>';
        $cOrder->setFlag(false);
    }
    // Имеется разрешенный список типов заказов
    else {
        $resHTML .= $cOrder->getHTMLFromContract($contract);
    }
}
//endregion
//region Сняли выделение ячейки
elseif($arPost['ACTION'] == 'REMOVE') {
    // Удаляем из сессии данные о выделенной ячейке
    $cOrder->removeUnitToList($contract, $unitID);
    $countOrders = $cOrder->getCountContracts();
    if($countOrders > 1) {
        $resHTML .= '
                <div class="control__actions">
                    <div class="form__row__name"></div>
                    <div class="form__row__col__item">
                        <a href="#" class="js-btn-reset">' . Loc::getMessage('BUTTON_RESET') .'</a>
                    </div>
                    <div class="form__row__col__item _size4 frn-danger">
                        ' . Loc::getMessage('ERROR_FEW_CONTRACTS') . '
                    </div>                    
                </div>';
    } elseif(!$cOrder->checkAllowStatus()) {
        $resHTML .= '
            <div class="control__actions">
                <div class="form__row__name"></div>
                <div class="form__row__col__item">
                    <a href="#" class="js-btn-reset">' . Loc::getMessage('BUTTON_RESET') .'</a>
                </div>
                <div class="form__row__col__item _size2 frn-danger">
                    ' . Loc::getMessage('ERROR_EXIST_STATUS_UNIT') . '
                </div>                
            </div>';
    } else {
        $resHTML .= $cOrder->getHTMLFromContract($contract);
    }
}
//endregion
//region Обновление при загрузке
elseif($arPost['ACTION'] == 'REFRESH') {
    if(!$cOrder->checkAllowStatus()) {
        $resHTML .= '
            <div class="control__actions">
                <div class="form__row__name"></div>                
                <div class="form__row__col__item">
                    <a href="#" class="js-btn-reset">' . Loc::getMessage('BUTTON_RESET') .'</a>
                </div>
                <div class="form__row__col__item _size2 frn-danger">
                    ' . Loc::getMessage('ERROR_EXIST_STATUS_UNIT') . '
                </div>
            </div>';
    } else {
        $contract = $cOrder->getFirstContract();
        $resHTML .= $cOrder->getHTMLFromContract($contract);
    }
}
//endregion
//region Сброс всех выделенных элементов
elseif($arPost['ACTION'] == 'RESET') {
    $cOrder->resetUnits();
}
//endregion
//region Добавление заказа
elseif($arPost['ACTION'] == 'CREATE' || $arPost['ACTION'] == 'ADD_TO') {
    $typeOrder = $arPost['TYPE'];           // тип заказа
    $contractId = $arPost['CONTRACT'];      // id договора
    $request = $arPost['REQUEST'];          // Y - черновик для заказа, N - черновик для запроса
    if($arPost['ACTION'] == 'CREATE') {
        $cOrder->addToOrderForming($typeOrder, $contractId, $request, 'Y');
    } else {
        $cOrder->addUnitToFormingOrder($typeOrder, $contractId);
    }
    //$contract = $cOrder->addUnitsFromListRegistry($typeOrder);
    $cOrder->resetUnits();
    $resHTML .= '
            <div class="control__actions">
                <div class="form__row__name"></div>
                <div class="form__row__col__item _size3 frn-success">
                    ' . Loc::getMessage('SUCCESS_ADD_UNITS_1') . '
                    <a href="/client/orders/">' . Loc::getMessage('SUCCESS_ADD_UNITS_2') . '</a>
                </div>
            </div>';
}
//endregion
if($cOrder->checkExistUnits()) {
    $htmlReset = '
        <div class="control__actions">
            <div class="form__row__name"></div>
            <div class="form__row__col__item">                
                <a href="' . $checkUri . '">';
    if($checkCur == 'Y') {
        $htmlReset .= Loc::getMessage('SHOW_ALL_UNITS');
    } else {
        $htmlReset .= Loc::getMessage('SHOW_ONLY_SELECTED_UNITS');
    }
    $htmlReset .= '</a>
            </div>
        </div>';
    if($arPost['ACTION'] != 'CREATE') {
        $resHTML = $htmlReset . $resHTML;
    }
}

$resHTML .= '';
echo $resHTML;