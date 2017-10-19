<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\XlsxViewer\NumberFormatterWin1251;
use App\XlsxViewer\TableBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class XlsxParserController extends Controller
{
    private $header       = array();
    private $isRow        = false;
    private $currentRow   = array();
    private $isStringCell = false;
    private $cellID       = '';
    private $sharedStrings;
    private $table;


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get html table for xlsx file on server.
     *
     * @param  Request  $request
     * @return Response
     */
    public function getParseData(Request $request)
    {
        if (!Storage::disk('xlsx')->exists($request->filename)) {
            return;
        }

        $filePath = sprintf('%s/%s', storage_path('xlsx'), $request->filename);

        $this->table           = new TableBuilder();
        $this->table->fixedCol = 1;

        $sharedStringsXml    = new \SimpleXMLElement(file_get_contents(sprintf('zip://%s#xl/sharedStrings.xml', $filePath)));
        $this->sharedStrings = new \SplFixedArray(count($sharedStringsXml->si));
        $i                   = 0;
        foreach ($sharedStringsXml->si as $k => $v) {
            $this->sharedStrings[$i++] = $v->t . '';
        }

        $parser = xml_parser_create("UTF-8");
        xml_set_element_handler($parser, 'self::_startHtmlElement', 'self::_endHtmlElement');
        xml_set_character_data_handler($parser, 'self::_contentHtmlHandler');

        $readFileStream = fopen( sprintf('zip://%s#xl/worksheets/sheet1.xml', $filePath), "r");
        while ($data = fread($readFileStream, 8192)) {
            xml_parse($parser, $data, feof($readFileStream));
        }
        fclose($readFileStream);

        return $this->table->getHtml();
    }

    private function _contentHtmlHandler($parser, $data)
    {
        if ($this->isRow) {
            $this->currentRow[$this->cellID] = $this->isStringCell ? $this->sharedStrings[$data] : $data;
        }
    }

    private function _startHtmlElement($parser, $tagname, $attribute)
    {
        if ($tagname == 'ROW') {
            $this->currentRow = array();
            $this->isRow      = true;
        }

        if ($tagname == 'C') {
            $this->isStringCell = isset($attribute['T']) && $attribute['T'] == 's';
            $this->cellID       = rtrim($attribute['R'], '0123456789');
        }
    }

    private function _endHtmlElement($parser, $tagname)
    {
        if ($tagname == 'ROW' && count($this->header)) {
            $this->isRow = false;
            $this->table->addRow($this->currentRow);
        }

        //Первая строчка - заголовок
        if ($tagname == 'ROW' && count($this->header) === 0) {
            $this->isRow = false;
            foreach ($this->currentRow as $field => $name) {
                $this->header[] = array(
                    'ID'        => $field,
                    'PARENT_ID' => null,
                    'NAME'      => $name,
                    'FIELD'     => $field,
                    'DATA_TYPE' => 'number',
                );
            }
            $this->table->setHeaderTree($this->header);
            $this->table->setFormatter('number', new NumberFormatterWin1251());
        }
    }
}
