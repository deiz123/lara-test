<?php

namespace App\XlsxViewer;

class TableBuilder
{

    private $fields          = array();
    private $dataHtml        = '<tbody>';
    private $dataArray       = array();
    private $combineColArray = array();
    private $combineRowArray = array();
    private $lastCombineRow  = array();
    private $dataHeaderArray = array();
    private $directory       = array();
    private $modifier        = array();
    private $order           = array();
    private $_wasCombination = false;
    //Временное решение с проблемой: 2 /tbody
    private $_fixEnd = false;
    /* Количество фиксированных колонок */
    public $fixedCol = 0;

    /* Проставлять класс типа данных в ячейку */
    public $setDataTypeClass = false;
    private $dataTypeList    = array();
    private $dataType        = array();

    /**
     * Import a PHP array in a <b>TableBuilder</b> instance
     *
     * @param array $array <p>
     * The array to import.
     * </p>
     *
     * @return bool.
     */
    public function fromArray($array)
    {
        foreach ($array as $v) {
            $this->addRow($v);
        }
    }

    /**
     * Import one row a PHP array in a <b>TableBuilder</b> instance
     *
     * @param array $array <p>
     * The array to import.
     * </p>
     *
     * @return bool.
     */
    public function addRow($array)
    {
        //Объединяем ячейки роуспаном
        if (count($this->combineColArray) !== 0 && count($this->lastCombineRow) !== 0) {
            end($this->dataArray);
            $endRowKey = key($this->dataArray) + 1;

            $unsetField     = array();
            $skipCellCount  = 0;
            $lastCombineRow = $this->lastCombineRow;

            $this->_wasCombination = false;
            foreach ($this->fields as $field) {
                if (!array_key_exists($field, $array)) {
                    continue;
                }
                $k = $field;
                //Эти ячейки не объединяются роуспаном
                if (!isset($this->combineColArray[$k])) {
                    continue;
                }

                if ($skipCellCount) {
                    $skipCellCount--;
                    $lastCombineRow[$k] = $endRowKey;
                    continue;
                }

                if (!isset($this->dataArray[$this->lastCombineRow[$k]][$k])) {
                    continue;
                }

                $lastCombineCell = $this->dataArray[$this->lastCombineRow[$k]][$k];
                //Если предыдущая ячейка уже объеденена колспаном
                if (isset($lastCombineCell['colspan'])) {
                    $lastCombineRow[$k] = $endRowKey;
                    if (isset($lastCombineCell['colspan'])) {
                        $skipCellCount = $lastCombineCell['colspan'] - 1;
                    }
                    continue;
                }
                $lastValue     = '#';
                $currentValue  = '?';
                $combaineField = $this->combineColArray[$k];

                if (!array_key_exists($combaineField, $array)) {
                    continue;
                }

                if (array_key_exists('value', $lastCombineCell) && $lastCombineRow[$combaineField] <= $lastCombineRow[$k]) {
                    //Комбинируем относительно другой колонки
                    if ($k !== $combaineField && array_key_exists('value', $this->dataArray[$this->lastCombineRow[$combaineField]][$combaineField])) {
                        $lastValue    = $lastCombineCell['value'] . $this->dataArray[$this->lastCombineRow[$combaineField]][$combaineField]['value'];
                        $currentValue = $array[$k] . $array[$combaineField];
                    } else {
                        $lastValue    = $lastCombineCell['value'];
                        $currentValue = $array[$combaineField];
                    }
                }

                //Данные разные, не объединять
                if ($lastValue !== $currentValue) {

                    $lastCombineRow[$k] = $endRowKey;
                    continue;
                }

                $unsetField[] = $k;
                if (!isset($this->dataArray[$lastCombineRow[$k]][$k]['rowspan'])) {
                    $this->dataArray[$lastCombineRow[$k]][$k]['rowspan'] = 1;
                }
                $this->dataArray[$lastCombineRow[$k]][$k]['rowspan']++;
                $this->_wasCombination = true;
            }
            $this->lastCombineRow = $lastCombineRow;
            foreach ($unsetField as $key) {
                unset($array[$key]);
            }
        }
        //Если это первая строка, заполним массив нулями по количеству полей
        if (count($this->lastCombineRow) === 0) {
            $this->lastCombineRow = array_fill_keys(array_keys($this->combineColArray), 0);
        }

        //Добавляем в данные
        $lastColKey = null;
        foreach ($this->fields as $k) {
            if (!array_key_exists($k, $array)) {
                continue;
            }

            $v = array('value' => $array[$k]);
            //Строки, которые нужно объеденить колспаном
            if (count($this->combineRowArray) !== 0 && isset($this->combineRowArray[$k]) && isset($array[$lastColKey])) {
                if ($v['value'] === $array[$lastColKey]['value']) {
                    if (!isset($array[$lastColKey]['colspan'])) {
                        $array[$lastColKey]['colspan'] = 1;
                    }
                    $array[$lastColKey]['colspan']++;
                    $array[$k] = null;
                    continue;
                }
            }
            $lastColKey = $k;
            $array[$k]  = $v;
        }
        $this->dataArray[] = $array;
        $this->_make();
    }

    /**
     * Указывает на колонки которые нужно объединить роуспаном
     *
     * @param string $field<p>
     * Поле которое требуется объединять
     * </p>
     * @param string || array $combine [optional]<p>
     * Если указано другое поле вторым параметром,
     * то объединение будет зависеть от значения этого поля.
     * @return bool.
     */
    public function combineCol($field, $combine = null)
    {
        if (is_null($combine)) {
            $combine = $field;
        }
        $this->combineColArray[$field] = $combine;
    }

    /**
     * Указывает на колонки которые нужно объединить колспаном
     *
     * @param array $fields<p>
     * Поля которое требуется объединять
     * </p>
     * @param array $values [optional]<p>
     * Набор значений, которые могут объединяться.
     * Если не указан, будут объединяться все одинаковые
     * @return bool.
     */
    public function combineRow($fields, $values = false)
    {
        foreach ($fields as $field) {
            $this->combineRowArray[$field] = $values;
        }
    }

    /**
     * При формировании html будет использовать значение из массива для
     * заполнения колонки значением.
     * Используется в паре с кешируемыми справочниками.
     *
     * @param string $field
     * @param array $array
     */
    public function setDirectory($field, $array)
    {
        $directoryClass = get_parent_class($array);
        if ($directoryClass === 'ModelDirectoryBase' || $directoryClass === 'Core\Model') {
            $array = $array->map('NAME', true);
        }
        $this->directory[$field] = $array;
    }

    /**
     * Устанавливает форматирования для определенного типа данных
     *
     * @param string $type
     * @param \NumberFormatter $formatter
     */
    public function setFormatter($type, $formatter)
    {
        foreach ($this->dataType as $k => $v) {
            if ($v === $type) {
                $this->dataType[$k] = $formatter;
            }
        }
    }

    /**
     * Устанавливает форматирования для определенного столбца
     *
     * @param string $field
     * @param \NumberFormatter $formatter
     */
    public function setColFormatter($field, \NumberFormatter $formatter)
    {
        $this->dataType[$field] = $formatter;
    }

    /**
     * Дерево. Заголовок таблицы (thead).
     *
     * Структура массива
     * [
     *   ["ID"]=>        string
     *   ["PARENT_ID"]=> string
     *   ["FIELD"]=>     string "ID поля для привязки данных"
     *   ["NAME"]=>      string
     *   ["DATA_TYPE"]=> string "Тип данных для столбца. Например, textnumber"
     *   ["CHILDREN"]=>  array  "Дети в таком же формате. Если детей нет, не указывать"
     * ]
     *
     * @param array $treeArray
     */
    public function setHeaderTree($treeArray)
    {
        $tree = array();
        $flat = array();
        $ii   = 0;
        while ($current = array_shift($treeArray)) {
            if (isset($current['CHILDREN']) && count($current['CHILDREN']) > 0) {
                array_splice($treeArray, count($treeArray), count($current['CHILDREN']), $current['CHILDREN']);
                reset($treeArray);
            }
            $current['_childCount'] = 0;
            $flat[$current['ID']]   = $current;
            $connectRoorTarget      = $current;
            $connectRootArray       = array();
            while (isset($flat[$connectRoorTarget['PARENT_ID']])) {
                $connectRoorTarget  = $flat[$connectRoorTarget['PARENT_ID']];
                $connectRootArray[] = $connectRoorTarget['ID'];
            }

            if ($current['PARENT_ID'] && !isset($current['CHILDREN'])) {
                $flat[$current['PARENT_ID']]['LINK'][$current['ID']] = array();
                foreach (array_reverse($connectRootArray) as $id) {
                    $flat[$id]['_childCount']++;
                }
            } else {
                $treeLink = &$tree;
                foreach (array_reverse($connectRootArray) as $id) {
                    $treeLink = &$treeLink[$id];
                }
                $treeLink[$current['ID']]     = array();
                $flat[$current['ID']]['LINK'] = &$treeLink[$current['ID']];
            }
            unset($current['CHILDREN']);
        }

        function recursiveAddIterator($arr, $depth, &$dataHeaderArray, &$flat, &$fields, $order)
        {
            foreach ($arr as $key => $v) {
                //Увеличиваем уровень вложености шапки
                if (!isset($dataHeaderArray[$depth])) {
                    $dataHeaderArray[$depth] = array();
                }
                $count = count($v);
                if ($count > 0) {
                    recursiveAddIterator($v, $depth + 1, $dataHeaderArray, $flat, $fields, $order);
                }
                if ($count === 0) {
                    // Нет детей - к этому полю привяжем данные
                    $fields[$flat[$key]['FIELD']] = $flat[$key]['FIELD'];
                }

                // Если была включена сортировка, вставим html контролов
                if ($count === 0 && count($order) !== 0) {
                    list($orderFn, $orderField, $orderWay) = $order;
                    $fieldId                               = $flat[$key]['ID'];
                    $flat[$key]['NAME'] .= $orderField == $fieldId ? '<div class="grid-sort-div ' . $orderWay . '">' : '<div class="grid-sort-div">';
                    $flat[$key]['NAME'] .= '<i class="icon_mkr_sortup" title="Сортировка по возрастанию" onclick="' . $orderFn . '(\'' . $fieldId . '\',1)"> </i>'
                        . '<i class="icon_mkr_sortdown" title="Сортировка по убыванию" onclick="' . $orderFn . '(\'' . $fieldId . '\',-1)"> </i>'
                        . '</div>';

                    if (!isset($flat[$key]['S_CLASS'])) {
                        $flat[$key]['S_CLASS'] = '';
                    }
                    $flat[$key]['S_CLASS'] .= ' is_sortable';
                }

                $dataHeaderArray[$depth][$key] = $flat[$key];
            }
        }

        recursiveAddIterator($tree, 0, $this->dataHeaderArray, $flat, $this->fields, $this->order);
        $this->_makeHeader();
    }

    /**
     * Возвращает сформированный html
     *
     * @return string
     */
    public function getHtml()
    {
        $this->_make(true);
        return $this->dataHtml . '</table>';
    }

    private function _makeHeader()
    {
        $this->dataHtml = '<table><thead>';
        $rowCount       = count($this->dataHeaderArray);
        $depth          = 0;
        while ($row = array_shift($this->dataHeaderArray)) {
            $this->dataHtml .= '<tr>';
            foreach ($row as $cell) {
                $value = $cell['NAME'];
                $attr  = '';
                if ($cell['_childCount'] > 1) {
                    $attr .= ' colspan="' . $cell['_childCount'] . '" ';
                }
                if (isset($cell['S_CLASS']) && $cell['S_CLASS']) {
                    $attr .= ' class="' . $cell['S_CLASS'] . '" ';
                }
                if (isset($cell['DATA_TYPE']) && !isset($this->dataType[$cell['FIELD']])) {
                    $this->dataType[$cell['FIELD']]     = $cell['DATA_TYPE'];
                    $this->dataTypeList[$cell['FIELD']] = $cell['DATA_TYPE'];
                }
                if ($cell['_childCount'] < 1 && ($rowCount - $depth) > 1) {
                    $attr .= ' rowspan="' . ($rowCount - $depth) . '" ';
                }
                $this->dataHtml .= "<th $attr>$value</th>";
            }
            $this->dataHtml .= '</tr>';
            $depth++;
        }
        $this->dataHtml .= '</thead><tbody>';
    }

    private function _make($end = false)
    {
        // Проверяем, если текущая строка уже не нужна для объединения, собираем html
        // $minCombineRow = count($this->combineColArray) === 0 ? PHP_INT_MAX : min($this->lastCombineRow);
        $fixedFields = array_slice($this->fields, 0, $this->fixedCol);
        end($this->dataArray);
        $endRowKey = key($this->dataArray);

        if (($this->_wasCombination || $endRowKey === 0) && !$end) {
            return;
        }

        while (count($this->dataArray)) {
            $row    = reset($this->dataArray);
            $rowKey = key($this->dataArray);

            if (!$end && ($endRowKey - 1) <= $rowKey) {
                break;
            }

            $html = '';
            foreach ($this->fields as $field) {
                if (!isset($row[$field]) || is_null($row[$field])) {
                    continue;
                }

                //Все остальные аттрибуты
                $attr = '';
                if (isset($this->dataType[$field])) {
                    if (is_string($this->dataType[$field])) {
                        $attr .= ' abbr="' . $this->dataType[$field] . '" ';
                    } else {
                        $row[$field]['value'] = $this->dataType[$field]->format($row[$field]['value']);
                    }
                    if ($this->setDataTypeClass) {
                        $row[$field]['class'] = isset($row[$field]['class']) ? ($row[$field]['class'] . ' ' . $this->dataTypeList[$field]) : $this->dataTypeList[$field];
                    }
                }
                if (isset($this->modifier[$field])) {
                    $row[$field] = $this->modifier[$field]($row[$field], $row);
                }

                $value = $row[$field]['value'];
                if (isset($this->directory[$field])) {
                    if (isset($this->directory[$field][$value])) {
                        $value = $this->directory[$field][$value];
                    } elseif (isset($this->directory[$field]['DEFAULT'])) {
                        $value = $this->directory[$field]['DEFAULT'];
                    }
                }

                foreach ($row[$field] as $k => $v) {
                    if ($k !== 'value') {
                        $attr .= ' ' . $k . '="' . $v . '" ';
                    }
                }

                $html .= in_array($field, $fixedFields) ? "<th $attr>$value</th>" : "<td $attr>$value</td>";
            }

            $this->dataHtml .= '<tr>' . $html . '</tr>';
            unset($this->dataArray[$rowKey]);
        }

        if ($end && !$this->_fixEnd) {
            $this->dataHtml .= '</tbody>';
            $this->_fixEnd = true;
        }
    }

    /**
     * Возвращает часть сгенеророванного html, очищая внутренню переменную
     *
     * @return string
     */
    public function eraseHtml()
    {
        $html           = $this->dataHtml;
        $this->dataHtml = '';
        return $html;
    }

    /**
     * Устанавливает JS функцию для сортировки
     *
     * @param string $fn
     * @param string $orderCol
     * @param string $orderWay
     */
    public function useSorting($fn, $orderCol = null, $orderWay = 0)
    {
        $this->order = array($fn, $orderCol, $orderWay);
    }

    public function modifyCol($field, $fn)
    {
        $this->modifier[$field] = $fn;
    }

}
