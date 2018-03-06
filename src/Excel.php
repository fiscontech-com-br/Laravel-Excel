<?php

namespace Maatwebsite\Excel;

use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Foundation\Bus\PendingDispatch;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class Excel
{
    const XLSX     = 'Xlsx';

    const CSV      = 'Csv';

    const ODS      = 'Ods';

    const XLS      = 'Xls';

    const SLK      = 'Slk';

    const XML      = 'Xml';

    const GNUMERIC = 'Gnumeric';

    const HTML     = 'Html';

    const Pdf      = 'Pdf';

    /**
     * @var Writer
     */
    protected $writer;

    /**
     * @var QueuedWriter
     */
    protected $queuedWriter;

    /**
     * @var ResponseFactory
     */
    protected $response;

    /**
     * @var FilesystemManager
     */
    protected $filesystem;

    /**
     * @param Writer            $writer
     * @param QueuedWriter      $queuedWriter
     * @param ResponseFactory   $response
     * @param FilesystemManager $filesystem
     */
    public function __construct(
        Writer $writer,
        QueuedWriter $queuedWriter,
        ResponseFactory $response,
        FilesystemManager $filesystem
    ) {
        $this->writer       = $writer;
        $this->response     = $response;
        $this->filesystem   = $filesystem;
        $this->queuedWriter = $queuedWriter;
    }

    /**
     * @param object      $export
     * @param string|null $fileName
     * @param string      $writerType
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @return BinaryFileResponse
     */
    public function download($export, string $fileName, string $writerType = null)
    {
        $file = $this->export($export, $fileName, $writerType);

        return $this->response->download($file, $fileName);
    }

    /**
     * @param object      $export
     * @param string      $filePath
     * @param string|null $disk
     * @param string      $writerType
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @return bool
     */
    public function store($export, string $filePath, string $disk = null, string $writerType = null)
    {
        $file = $this->export($export, $filePath, $writerType);

        return $this->filesystem->disk($disk)->put($filePath, fopen($file, 'r+'));
    }

    /**
     * @param object      $export
     * @param string      $filePath
     * @param string|null $disk
     * @param string      $writerType
     *
     * @return PendingDispatch
     */
    public function queue($export, string $filePath, string $disk = null, string $writerType = null)
    {
        if (null === $writerType) {
            $writerType = $this->findTypeByExtension($filePath);
        }

        return $this->queuedWriter->store($export, $filePath, $disk, $writerType);
    }

    /**
     * @param object      $export
     * @param string|null $fileName
     * @param string      $writerType
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @return string
     */
    protected function export($export, string $fileName, string $writerType = null)
    {
        if (null === $writerType) {
            $writerType = $this->findTypeByExtension($fileName);
        }

        return $this->writer->export($export, $writerType);
    }

    /**
     * @param string $fileName
     *
     * @return string|null
     */
    protected function findTypeByExtension(string $fileName)
    {
        $pathinfo = pathinfo($fileName);
        if (!isset($pathinfo['extension'])) {
            return null;
        }

        switch (strtolower($pathinfo['extension'])) {
            case 'xlsx': // Excel (OfficeOpenXML) Spreadsheet
            case 'xlsm': // Excel (OfficeOpenXML) Macro Spreadsheet (macros will be discarded)
            case 'xltx': // Excel (OfficeOpenXML) Template
            case 'xltm': // Excel (OfficeOpenXML) Macro Template (macros will be discarded)
                return self::XLSX;
            case 'xls': // Excel (BIFF) Spreadsheet
            case 'xlt': // Excel (BIFF) Template
                return self::XLS;
            case 'ods': // Open/Libre Offic Calc
            case 'ots': // Open/Libre Offic Calc Template
                return self::ODS;
            case 'slk':
                return self::SLK;
            case 'xml': // Excel 2003 SpreadSheetML
                return self::XML;
            case 'gnumeric':
                return self::GNUMERIC;
            case 'htm':
            case 'html':
                return self::HTML;
            case 'csv':
                return self::CSV;
            case 'pdf':
                return self::Pdf;
            default:
                return null;
        }
    }
}