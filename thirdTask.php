<?php

CModule::IncludeModule("iblock");

if(intval($_REQUEST["IBLOCK_ID_FIELDS"]) > 0) {
    $bError = false;
    $IBLOCK_ID = intval($_REQUEST["IBLOCK_ID_FIELDS"]);
    $ib = new CIBlock;
    $arFields = CIBlock::GetArrayByID($IBLOCK_ID);
    $arFields["GROUP_ID"] = CIBlock::GetGroupPermissions($IBLOCK_ID);
    $arFields["NAME"] = $arFields["NAME"].". Гласные";
    unset($arFields["ID"]);

    if($_REQUEST["IBLOCK_TYPE_ID"]!="empty") {
        $arFields["IBLOCK_TYPE_ID"]=$_REQUEST["IBLOCK_TYPE_ID"];
    }

    $ID = $ib->Add($arFields);
    if(intval($ID) <= 0) {
        $bError = true;
    }

    if($_REQUEST["IBLOCK_ID_PROPS"]!="empty") {
        $iblock_prop=intval($_REQUEST["IBLOCK_ID_PROPS"]);
    } else {
        $iblock_prop=$IBLOCK_ID;
    }


    $iblock_prop_new = $ID;
    $ibp = new CIBlockProperty;
    $properties = CIBlockProperty::GetList(["sort"=>"asc", "name"=>"asc"], ["ACTIVE"=>"Y", "IBLOCK_ID"=>$iblock_prop]);

    while ($prop_fields = $properties->GetNext()){
        if($prop_fields["PROPERTY_TYPE"] == "L"){
            $property_enums = CIBlockPropertyEnum::GetList(["DEF"=>"DESC", "SORT"=>"ASC"],
                ["IBLOCK_ID"=>$iblock_prop, "CODE"=>$prop_fields["CODE"]]);
            while($enum_fields = $property_enums->GetNext()){
                $prop_fields["VALUES"][] = [
                    "VALUE" => $enum_fields["VALUE"],
                    "DEF" => $enum_fields["DEF"],
                    "SORT" => $enum_fields["SORT"]
                ];
            }
        }

        $prop_fields["IBLOCK_ID"]=$iblock_prop_new;
        unset($prop_fields["ID"]);
        foreach ($prop_fields as $k => $v) {
            if (!is_array($v)) $prop_fields[$k]=trim($v);
            if ($k{0}=='~') unset($prop_fields[$k]);
        }

        $PropID = $ibp->Add($prop_fields);
        if(intval($PropID)<=0) $bError = true;
    }

    $elements = CIBlockElement::GetList(["sort"=>"asc", "name"=>"asc"], ["ACTIVE"=>"Y", "IBLOCK_ID"=>$iblock_prop]);

    while ($ob = $elements->GetNextElement())
    {
        $arFields = $ob->GetFields();
        $arFields['PROPERTIES'] = $ob->GetProperties();

        $broken = preg_split('##u', $arFields["NAME"], -1, PREG_SPLIT_NO_EMPTY);
        if (!preg_match('#[аоуэиыеёяю]#', strtolower($broken[0]))) continue;


        $arFieldsCopy = $arFields;
        $arFieldsCopy["IBLOCK_ID"] = $ID;
        unset($arFieldsCopy["ID"], $arFieldsCopy["~ID"], $arFieldsCopy["TMP_ID"], $arFieldsCopy["WF_LAST_HISTORY_ID"], $arFieldsCopy["SHOW_COUNTER"], $arFieldsCopy["SHOW_COUNTER_START"]);
        foreach ($arFieldsCopy as $key => $elem) {
            if ($elem == NULL) {
                unset($arFieldsCopy[$key]);
            }
        }


        $arFieldsCopy['PROPERTY_VALUES'] = array();

        foreach ($arFields['PROPERTIES'] as $property)
        {
            if ($property['PROPERTY_TYPE']=='L'){
                if ($property['MULTIPLE']=='Y'){
                    $arFieldsCopy['PROPERTY_VALUES'][$property['CODE']] = [];
                    foreach($property['VALUE_ENUM_ID'] as $enumID){
                        $arFieldsCopy['PROPERTY_VALUES'][$property['CODE']][] = [
                            'VALUE' => $enumID
                        ];
                    }
                } else {
                    $arFieldsCopy['PROPERTY_VALUES'][$property['CODE']] = [
                        'VALUE' => $property['VALUE_ENUM_ID']
                    ];
                }
            }

            if ($property['PROPERTY_TYPE']=='F') {
                if ($property['MULTIPLE']=='Y') {
                    if (is_array($property['VALUE'])) {
                        foreach ($property['VALUE'] as $key => $arElEnum)
                            $arFieldsCopy['PROPERTY_VALUES'][$property['CODE']][$key]=CFile::CopyFile($arElEnum);
                    }
                } else {
                    $arFieldsCopy['PROPERTY_VALUES'][$property['CODE']] = CFile::CopyFile($property['VALUE']);
                }
            }

            $arFieldsCopy['PROPERTY_VALUES'][$property['CODE']] = $property['VALUE'];
        }
        $el = new CIBlockElement();
        $NEW_ID = $el->Add($arFieldsCopy);
    }
}


$str .='<form action='.$APPLICATION->GetCurPageParam().' method="post"><table>';
if($_REQUEST["success"]=="Y") $str .='<tr><td><font color="green">ИБ успешно скопирован</font><b></td></tr>';
elseif($_REQUEST["error"]=="Y") $str .='<tr><td><font color="red">Произошла ошибка</font><br/></td></tr>';
$str .='<tr><td]Копируем мета данные ИБ в новый ИБ</b><br/></td></tr>';
$res = CIBlock::GetList(Array(),Array(),true);
while($ar_res = $res->Fetch())
    $arRes[]=$ar_res;
$str .='<tr><td>Копируем ИБ:<br><select name="IBLOCK_ID_FIELDS">';
foreach($arRes as $vRes)
    $str .= '<option value='.$vRes['ID'].'>'.$vRes['NAME'].' ['.$vRes["ID"].']</option>';
$str .='</select></td>';
$str .='<td>Копируем в новый ИБ свойства другого ИБ: *<br><select name="IBLOCK_ID_PROPS">';
$str .='<option value="empty">';
foreach($arRes as $vRes)
    $str .= '<option value='.$vRes['ID'].'>'.$vRes['NAME'].' ['.$vRes["ID"].']</option>';
$str .='</select></td></tr>';
$str .='<tr><td>Копируем ИБ в тип:<br><select name="IBLOCK_TYPE_ID">';
$str .='<option value="empty">';
$db_iblock_type = CIBlockType::GetList();
while($ar_iblock_type = $db_iblock_type->Fetch()){
    if($arIBType = CIBlockType::GetByIDLang($ar_iblock_type["ID"], LANG))
        $str .= '<option value='.$ar_iblock_type["ID"].'>'.htmlspecialcharsex($arIBType["NAME"])."</option>";
}
$str .='</select></td></tr>';
$str .='<tr><td><br/>* если значение не указано мета данные ИБ секции "Свойства" берутся из ИБ первого поля</td></tr>';
$str .='<tr><td><input type="submit" value="копируем"></td></tr>';
$str .='</table></form>';
echo $str;
