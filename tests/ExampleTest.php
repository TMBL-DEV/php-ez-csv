<?php

use Devcreations\EzCsv\DataLayer\ContentParser;

it('read rows from string using the header as key values and the row values as values for the key', function (string $csvContent) {
    $content = new ContentParser($csvContent, 0);
    $rows = [];

    $content->each(function (string $key, array $value) use (&$rows) {
        $rows[] = $value;
    });

    expect(count($rows))->tobe(4);
})->with('smallCsv');
