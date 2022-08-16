<?php

$monster_text = file_get_contents('../jsonables/monsters.json');

$monsters = json_decode($monster_text, true);

file_put_contents('../packs/monsters.db', '');

$errors = [];

foreach ($monsters as $name => $monster) {
    try {
        file_put_contents(
            '../packs/monsters.db',
            json_encode($monster) . "\n",
            FILE_APPEND
        );
    } catch (\Throwable $e) {
        $errors[] = "{$name} --> " . $e->getMessage() . ' on line ' .
            $e->getLine() . ' of ' . $e->getFile();
    }
}

echo __FILE__ . ' on line ' . __LINE__;
echo '<pre style="background: white; width: 1000px;">' . PHP_EOL;
print_r($errors);
echo PHP_EOL . '</pre>' . PHP_EOL;
exit;
