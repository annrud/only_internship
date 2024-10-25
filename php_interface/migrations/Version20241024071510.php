<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20241024071510 extends Version
{
    protected $description = "Создание инфоблока LOG для логирования изменений в инфоблоках";

    /**
     * @throws HelperException
     */
    public function up()
    {
        $helper = new HelperManager();

        $arIBlockType = array(
            'ID' => 'IBLOCK_LOGS',
            'SECTIONS' => 'Y',
            'IN_RSS' => 'N',
            'SORT' => 100,
            'LANG' => array(
                'ru' => array(
                    'NAME' => 'Логи изменений',
                    'SECTION_NAME' => 'Разделы',
                    'ELEMENT_NAME' => 'Элементы'
                ),
                'en' => array(
                    'NAME' => 'Change logs',
                    'SECTION_NAME' => 'Sections',
                    'ELEMENT_NAME' => 'Elements'
                ),
            ),
        );

        $helper->Iblock()->addIblockTypeIfNotExists($arIBlockType);

        $iIBlockID = $helper->Iblock()->addIblockIfNotExists(array(
            'LID' => 's1',
            'IBLOCK_TYPE_ID' => 'IBLOCK_LOGS',
            'CODE' => 'LOG',
            'NAME' => 'Логи изменений',
            'ACTIVE' => 'Y',
            'SORT' => 500,
        ));

        if ($iIBlockID) {
            $helper->AdminIblock()->buildElementForm($iIBlockID, [
                'Элемент' => [
                    'ACTIVE',
                    'NAME',
                    'SORT',
                    'CODE',
                    'ACTIVE_FROM',
                ],
                'Дополнительно' => [
                    'PREVIEW_TEXT',
                ],
            ]);

        }
    }

    public function down()
    {
        $helper = new HelperManager();
        $helper->Iblock()->deleteIblockTypeIfExists('IBLOCK_LOGS');
        $helper->Iblock()->deleteIblockIfExists('LOG', 'IBLOCK_LOGS');
    }
}
