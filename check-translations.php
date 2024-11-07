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
$duplicates = [];
foreach ($languages as $language) {
    $language = basename($language, '.json');
    $missing[$language] = 0;
    $duplicates[$language] = 0;
    $translations = [];

    // Find all vendor translation files and merge them into one array
    $vendorfiles = array_merge(
        glob("vendor/*/*/lang/$language.json"),
        glob("vendor/*/*/resources/lang/$language.json")
    );
    foreach ($vendorfiles as $file) {
        $translations = array_merge(
            $translations,
            (array) json_decode(file_get_contents($file)),
        );
    }

    // Check for duplicate translations only after merging in all the vendor translations
    $packageTranslations = (array) json_decode(file_get_contents("lang/$language.json"));
    $duplicateKeys = array_intersect(array_keys($packageTranslations), array_keys($translations));
    foreach ($duplicateKeys as $duplicate) {
        $duplicates[$language]++;
        error_log("a vendor package already accounted for translation key in language '$language': \"{$duplicate}\"");
    }
    $translations = array_merge($translations, $packageTranslations);

    // Match existing translations with found translation strings
    foreach (array_unique($translationKeys) as $key) {
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

$totalDuplicates = 0;
foreach($duplicates as $language => $duplicatesLang) {
    if ($duplicatesLang > 0) {
        if ($duplicatesLang == 1) {
            error_log("1 translation is a duplicate of a vendor translation in language '$language'.");
        } else {
            error_log("$duplicatesLang translations are duplicates of vendor translations in language '$language'.");
        }

        $totalDuplicates += $duplicatesLang;
    }
}

if ($totalMissing + $totalDuplicates > 0) {
    exit(1);
}

echo('No missing translations found! :)');
