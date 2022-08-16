<?php

require_once '../helpers/functions.php';

$monster_text = file_get_contents('../original-dbs/monsters.db');

file_put_contents('../packs/monsters.db', '');

$errors = [];

$converteds = [];
$indexed = [];

$failsafe_break = 0;
while (false !== ($monster = extractChunk($monster_text, $errors))) {
    if (! is_array($monster)) {
        break;
    }

    $converteds[$monster['name']] = convertMonsterToKnave($monster);
    $indexed[$failsafe_break] = $monster['name'];

    $failsafe_break++;

    if ($failsafe_break > 1000) {
        break;
    }
}

try {
    file_put_contents(
        '../jsonables/monsters.json',
        json_encode($converteds, JSON_PRETTY_PRINT) . "\n"
    );

    file_put_contents(
        '../indexed/monsters.json',
        json_encode($indexed, JSON_PRETTY_PRINT) . "\n"
    );
} catch (\Throwable $e) {
    $errors[] = $e->getMessage() . ' on line ' . $e->getLine() . ' of ' . $e->getFile();
}

//echo __FILE__ . ' on line ' . __LINE__;
//echo '<pre style="background: white; width: 1000px;">' . PHP_EOL;
//print_r($first_monster['data']);
//echo PHP_EOL . '</pre>' . PHP_EOL;



echo __FILE__ . ' on line ' . __LINE__;
echo '<pre style="background: white; width: 1000px;">' . PHP_EOL;
print_r($errors);
echo PHP_EOL . '</pre>' . PHP_EOL;
exit;
