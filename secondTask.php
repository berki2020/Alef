<?php

$arrCards = ["♡6♡8", "♧J♡A", "♢6♢0", "♤6♡A", "♡Q♢K", "♢J♢Q", "♢Q♤0", "♧0♤0", "♢0♤A"];
$arrSuits = ["♡", "♧", "♢", "♤"];
$arrValues = [6, 7, 8, 9, 0, "J", "Q", "K", "A"];

$elem = function ($pair) use ($arrSuits, $arrValues) {
    $broken = preg_split('##u', $pair, -1, PREG_SPLIT_NO_EMPTY);

    if ($broken[0] != $broken[2]) {
        if ($broken[0] == $arrSuits[3] || $broken[2] == $arrSuits[3]) {
            return $broken[0] == $arrSuits[3] ? 0 : 1;
        } else {
            return 2;
        }
    } else {
        if ($broken[1] == $broken[3]) return 2;
        return array_search($broken[1], $arrValues) > array_search($broken[3], $arrValues) ? 0 : 1;
    }
};

$result = "";
foreach ($arrCards as $card) {
    $result .= $elem($card);
}

echo $result;
