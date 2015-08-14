<?php
namespace mnshankar\CSV;

class CSV
{
    protected $source;
    protected $handle;
    protected $headerRowExists = true;
    protected $delimiter = ',';
    protected $enclosure = '"';

    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;

        return $this;
    }

    public function setHeaderRowExists($headerFlag = true)
    {
        $this->headerRowExists = $headerFlag;

        return $this;
    }

    public function setEnclosure($enclosure)
    {
        $this->enclosure = $enclosure;

        return $this;
    }

    public function setFileHandle($stream = 'php://output', $mode = 'r+')
    {
        $this->handle = fopen('php://output', $mode);

        return $this;
    }

    public function with($source, $headerRowExists = true, $mode = 'r+')
    {
        if (is_array($source)) { // fromArray
            $this->source = $source;
        } else {
            if (is_string($source)) { // fromfile
                $this->fromFile($source, $headerRowExists, $mode);
            } else {
                throw new \Exception('Source must be either an array or a file name');
            }
        }

        return $this;
    }

    public function fromArray($arr)
    {
        $this->source = $arr;

        return $this;
    }

    public function toArray()
    {
        return $this->source;
    }

    public function fromFile($filePath, $headerRowExists = true, $mode = 'r+')
    {
        $from = fopen($filePath, $mode);
        $arr = array();
        $this->headerRowExists = $headerRowExists;

        if ($headerRowExists) {
            // first header row
            $header = fgetcsv($from, 0, $this->delimiter, $this->enclosure);
        }
        while (($data = fgetcsv($from, 0, $this->delimiter, $this->enclosure)) !== false) {
            $arr[] = $headerRowExists ? array_combine($header, $data) : $data;
        }

        fclose($from);
        $this->source = $arr;

        return $this;
    }

    public function put($filePath, $mode = 'w+')
    {
        $this->handle = fopen($filePath, $mode);
        fwrite($this->handle, $this->toString());
        fclose($this->handle);
    }

    public function render($filename = 'export.csv', $mode = 'r+')
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: private');
        header('pragma: cache');

        echo $this->toString();
    }


    /**
     * Use PHP's inbuilt fputcsv to generate csv
     */
    private function getCSV()
    {
        if ($this->headerRowExists) {
            $longest_row = max($this->source);
            $header = array_keys(static::dot($longest_row));
            fputcsv($this->handle, $header, $this->delimiter, $this->enclosure);
        }

        foreach ($this->source as $key => $row) {
            fputcsv($this->handle, static::dot($row), $this->delimiter, $this->enclosure);
        }
    }

    //this method is used by unit tests. So it is public.
    public function toString()
    {
        ob_start(); // buffer the output ...
        $this->handle = fopen('php://output', 'r+');
        $this->getCSV();
        fclose($this->handle);
        return ob_get_clean(); //then return it as a string
    }

    /**
     * Copied from illuminate array to avoid dependence on illuminate/support
     * Flatten a multi-dimensional associative array with dots.
     *
     * @param  array $array
     * @param  string $prepend
     * @return array
     */
    public static function dot($array, $prepend = '')
    {
        $results = array();

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $results = array_merge($results, static::dot($value, $prepend . $key . '.'));
            } else {
                $results[$prepend . $key] = $value;
            }
        }

        return $results;
    }
}
