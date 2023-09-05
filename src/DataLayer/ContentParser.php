<?php

namespace Devcreations\EzCsv\DataLayer;

use Exception;

// class that you can pass raw string to and it will resolve the csv lines
class ContentParser
{
    public function __construct(private string $content, private int $headerPosition = 0, private ?string $rowSeperator = null, private ?string $columnSeperator = null)
    {
    }

    public function decideLineBreak(): string
    {
        if ($this->rowSeperator) {
            return $this->rowSeperator;
        }

        preg_match("/\r\n|\n|\r/", $this->content, $matches);

        if (! empty($matches)) {
            $this->rowSeperator = $matches[0];

            return $matches[0];
        }

        throw new Exception('no line break found in given content');
    }

    public function lazySplitIterator(string $lineSeperator = null)
    {
        if (! $lineSeperator) {
            $lineSeperator = $this->decideLineBreak();
        }

        $startIndex = 0;
        $length = strlen($this->content);

        while ($startIndex < $length) {
            $splitIndex = strpos($this->content, $lineSeperator, $startIndex);
            if ($splitIndex === false) {
                // If the split character is not found, yield the remaining part of the string
                yield substr($this->content, $startIndex);

                return; // End the iterator
            } else {
                // Yield the substring from the current start index to the split index
                yield substr($this->content, $startIndex, $splitIndex - $startIndex);
                $startIndex = $splitIndex + strlen($lineSeperator);
            }
        }
    }

    public function detectColumnSeperator(string $row)
    {
        if ($this->columnSeperator) {
            return $this->columnSeperator;
        }

        // Define an array of common CSV separators and their corresponding regex patterns
        $separators = [
            ',' => '/,/',
            ';' => '/;/',
            '\t' => '/\t/',
            '|' => '/\|/',
        ];

        foreach ($separators as $separator => $pattern) {
            if (preg_match($pattern, $row)) {
                $this->columnSeperator = $separator;

                return $separator;
            }
        }

        // If none of the common separators are found, return null
        return null;
    }

    public function seperateRow(string $contentRow): array
    {
        return explode($this->detectColumnSeperator($contentRow), $contentRow);
    }

    public function initRow(string $contentRow, array $header): array
    {
        $seperatedRow = $this->seperateRow($contentRow);
        $row = [];

        foreach ($header as $key => $columnName) {
            $row[$columnName] = $seperatedRow[$key];
        }

        return $row;
    }

    public function each(callable $callback): void
    {
        $header = [];

        foreach ($this->lazySplitIterator($this->decideLineBreak()) as $key => $value) {
            if ($this->headerPosition === $key) {
                $header = $this->seperateRow($value);

                continue;
            }

            $callback(
                $key,
                $this->initRow($value, $header)
            );
        }
    }
}
