<?php
/**
 * Tries to find the differences between a (preferably new) Magento installation
 * and this one so we can add untranslated language strings into our translation
 * file. Also keeps the entries sorted.
 *
 * Example: php diffAndSort.php ../magento/app/locale/en_US locale/sv_SE
 *
 * @author jonathan@madepeople.se
 */
if ($_SERVER['argc'] !== 3) {
    printf("Usage: %s <source locale dir (en_US)> <target locale dir>\n", $_SERVER['argv'][0]);
    exit;
}

function getLanguageData($directory)
{
    $dirGlob = $directory . '/*.csv';
    $allData = array();
    foreach (glob($dirGlob) as $file) {
        $fh = fopen($file, 'r');
        while (($data = fgetcsv($fh)) !== false) {
            if (count($data) !== 2) {
                printf("Something is very wrong with '%s', please investigate and try again\n", $file);
                exit;
            }
            if (!isset($allData[basename($file)])) {
                $allData[basename($file)] = array();
            }
            $allData[basename($file)][$data[0]] = $data[1];
        }
        fclose($fh);
    }

    return $allData;
}

function mergeLanguageData($sourceData, $targetData)
{
    $mergedData = array();
    foreach ($sourceData as $file => $data) {
        if (isset($targetData[$file])) {
            $mergedData[$file] = array_merge($data, $targetData[$file]);
        } else {
            $mergedData[$file] = $data;
        }
    }

    return $mergedData;
}

function writeSortedCsvFiles($directory, $csvData)
{
    foreach ($csvData as $file => $data) {
        ksort($data);
        $csvData = array_combine(array_keys($data), array_values($data));
        $fh = fopen($directory . '/' . $file, 'w');
        if (!$fh) {
            printf("Can't open file '%s' for writing, aborting\n", $directory . '/' . $file);
            exit;
        }

        // Sadly, we can't use fputcsv because it doesn't always enclose rows
        foreach ($csvData as $string => $translation)  {
            $csvString = '"' .
                str_replace('"', '""', $string) . '","' .
                str_replace('"', '""', $translation) . '"' . "\n";

            fputs($fh, $csvString);
        }
        fclose($fh);
    }
}

if (!is_dir($_SERVER['argv'][1])) {
    printf("Source directory '%s' doesn't exist\n", $_SERVER['argv'][1]);
    exit;
}

if (!is_dir($_SERVER['argv'][2])) {
    printf("Target directory '%s' doesn't exist\n", $_SERVER['argv'][2]);
    exit;
}

$sourceLanguageData = getLanguageData($_SERVER['argv'][1]);
$targetLanguageData = getLanguageData($_SERVER['argv'][2]);
$mergedLanguageData = mergeLanguageData($sourceLanguageData, $targetLanguageData);

writeSortedCsvFiles($_SERVER['argv'][2], $mergedLanguageData);

echo "Done!\n";
