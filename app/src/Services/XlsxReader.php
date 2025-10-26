<?php

namespace App\Services;

use ZipArchive;
use SimpleXMLElement;

class XlsxReader
{
    public static function load(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Unable to open XLSX file.');
        }

        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml !== false) {
            $xml = new SimpleXMLElement($sharedStringsXml);
            foreach ($xml->si as $si) {
                $text = '';
                if (isset($si->t)) {
                    $text = (string) $si->t;
                } else {
                    foreach ($si->r as $run) {
                        $text .= (string) $run->t;
                    }
                }
                $sharedStrings[] = $text;
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            throw new \RuntimeException('Worksheet not found in XLSX');
        }
        $sheet = new SimpleXMLElement($sheetXml);
        $namespaces = $sheet->getNamespaces(true);
        $rows = [];
        foreach ($sheet->sheetData->row as $row) {
            $values = [];
            foreach ($row->c as $c) {
                $type = (string) $c['t'];
                $value = (string) $c->v;
                if ($type === 's') {
                    $values[] = $sharedStrings[(int) $value] ?? '';
                } else {
                    $values[] = $value;
                }
            }
            $rows[] = $values;
        }
        $zip->close();
        $headers = $rows[0] ?? [];
        $data = [];
        foreach (array_slice($rows, 1) as $row) {
            $row = array_pad($row, count($headers), null);
            $data[] = array_combine($headers, $row);
        }
        return $data;
    }
}
