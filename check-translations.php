<?php

// Adapted from: https://github.com/barryvdh/laravel-translation-manager/blob/master/src/Manager.php
$translationKeys = [];
$functions = [
    'trans',
    'trans_choice',
    'Lang::get',
    'Lang::choice',
    'Lang::trans',
    'Lang::transChoice',
    '@lang',
    '@choice',
    '__',
    '$trans.get',
];
$stringPattern =
    "[^\w]" .                                       // Must not have an alphanum before real method
    '(' . implode('|', $functions) . ')' .          // Must start with one of the functions
    "\(\s*" .                                       // Match opening parenthesis
    "(?P<quote>['\"])" .                            // Match " or ' and store in {quote}
    "(?P<string>(?:\\\k{quote}|(?!\k{quote}).)*)" . // Match any string that can be {quote} escaped
    "\k{quote}" .                                   // Match " or ' previously matched
    "\s*[\),]";                                     // Close parentheses or new parameter

$files = [];
$iterator = new RecursiveDirectoryIterator('resources');
foreach (new RecursiveIteratorIterator($iterator) as $file) {
    if (strpos($file, '.blade.php') !== false) {
        $files[] = $file->getRealPath();
    }
}

foreach ($files as $file) {
    $contents = file_get_contents($file);
    if (preg_match_all("/{$stringPattern}/siU", $contents, $matches)) {
        foreach ($matches['string'] as $key) {
            $translationKeys[] = $key;
        }
    }
}

$languages = glob('lang/*.json');

if (! $languages) {
    error_log('No language files found.');
    exit(2);
}

$missing = [];
foreach ($languages as $language) {
    $language = basename($language, '.json');
    $translationKeys = array_unique($translationKeys);

    $translations = (array) json_decode(file_get_contents("lang/$language.json"));
    $vendorfiles = array_merge(
        glob("vendor/*/*/lang/$language.json"),
        glob("vendor/*/*/resources/lang/$language.json")
    );
    foreach($vendorfiles as $file) {
        $translations = array_merge(
            $translations,
            (array) json_decode(file_get_contents($file))
        );
    }

    $missing[$language] = 0;
    foreach ($translationKeys as $key) {
        if (! array_key_exists($key, $translations)) {
            $missing[$language]++;
            error_log("missing translation in language '$language': \"{$key}\"");
        }
    }
}

$totalMissing = 0;
foreach($missing as $language => $missingLang) {
    if ($missingLang > 0) {
        if ($missingLang == 1) {
            error_log("1 translation is missing in language '$language'.");
        } else {
            error_log("$missingLang translations are missing in language '$language'.");
        }

        $totalMissing += $missingLang;
    }
}

if($totalMissing > 0) {
    exit(1);
}

echo('No missing translations found! :)');
