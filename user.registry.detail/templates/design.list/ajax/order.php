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

//region ���������� �������� ���������
$statusWMS = $arPost['STATUS'];                     // WMS ������ ��
$action = $arPost['ACTION'];                        // �������� ����������� ������������� (Y - ��������� ��, N - ������ ��������� ��)
$contract = $arPost['CONTRACT_ID'];                 // ID �������� ���������� ��
$unitID = $arPost['ID'];                            // ID ��
$checkUri = $arPost['CHECK_URI'];                   // ����� ��� ������ "�������� ����������"
$checkCur = $arPost['CHECK_CUR'];                   // �������� ������������ GET ������ �� ����� ������ �����������
$multiSelect = $arPost['MULTISELECT'];              // �������� ������������ ���������� �� ��� �������� ������������ ������� (���� == Y �� �������� �������)
//endregion
//region ���������� ������ �� ������ �������
$cOrder = new Orders();                             // �������������� ����� ��� ��������� ������ ��� ���������� ��� � ������ ������������
$userID = $cOrder->getUserID();                     // ID ������������
$typesOrder = $cOrder->checkPermission($statusWMS); // ���� ������� ����������� ������������ � ������ ������� WMS ���������� ��
//endregion

//region �������� ������
$resHTML = '';
if($action == 'ADD') {
    // ��������� � ������ ������ � ���������� ������
    $cOrder->addUnitToList($contract, $unitID, $statusWMS);
    // �������� ������� WMS
    if($statusWMS) {
        $typesOrder = $cOrder->checkPermission($statusWMS);
    } else {
        $typesOrder = $cOrder->checkAllowStatus();
    }
    // ���� �� ������� ������� �� ������ ������������ �����
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
    // ������� ����������� ������ ����� �������
    else {
        $resHTML .= $cOrder->getHTMLFromContract($contract);
    }
}
//endregion
//region ����� ��������� ������
elseif($arPost['ACTION'] == 'REMOVE') {
    // ������� �� ������ ������ � ���������� ������
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
//region ���������� ��� ��������
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
//region ����� ���� ���������� ���������
elseif($arPost['ACTION'] == 'RESET') {
    $cOrder->resetUnits();
}
//endregion
//region ���������� ������
elseif($arPost['ACTION'] == 'CREATE' || $arPost['ACTION'] == 'ADD_TO') {
    $typeOrder = $arPost['TYPE'];           // ��� ������
    $contractId = $arPost['CONTRACT'];      // id ��������
    $request = $arPost['REQUEST'];          // Y - �������� ��� ������, N - �������� ��� �������
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