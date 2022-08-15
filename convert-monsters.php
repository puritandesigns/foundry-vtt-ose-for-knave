<?php

$monster_text = file_get_contents('packs/monsters.db');

file_put_contents('packs/converted-monsters.db', '');

$first_monster = extractMonster($monster_text);

//echo __FILE__ . ' on line ' . __LINE__;
//echo '<pre style="background: white; width: 1000px;">' . PHP_EOL;
//print_r($first_monster['data']);
//echo PHP_EOL . '</pre>' . PHP_EOL;


$converted = convertToKnave($first_monster);

file_put_contents('packs/converted-monsters.db', json_encode($converted));

echo __FILE__ . ' on line ' . __LINE__;
echo '<pre style="background: white; width: 1000px;">' . PHP_EOL;
print_r($converted);
echo PHP_EOL . '</pre>' . PHP_EOL;
exit;


function extractMonster(&$text): array
{
    $extract_length = strpos($text, "}\n") + 1;

    $monster_text = substr($text, 0, $extract_length);

    $text = substr($text, $extract_length);

    return json_decode($monster_text, true);
}

function convertToKnave(array $monster): array
{
    $one_to_one_map = ['_id', 'name', 'img', 'sort', 'flags', 'permission', 'token'];

    $converted = ['type' => 'character'];

    foreach ($one_to_one_map as $key) {
        $converted[$key] = $monster[$key];
    }

    $descriptions = [];
    $items = [];

    foreach ($monster['items'] as $item) {
        convertItem($item, $descriptions, $items);
    }
    
    $converted['items'] = $items;

    $data = convertDataKey($monster['data'], $monster);

    if (! empty($descriptions)) {
        $data['biography'] .= "\n" . implode("\n", $descriptions);
    }

    $converted['data'] = $data;

    return $converted;
}

function convertDataKey(array $data, array $item): array
{
    $bonus = (int) round($data['hp']['max'] / 8);
    if ($bonus > 10) {
        $bonus = 10;
    }

    $return = [
        "health" => [
          "value" => $data['hp']['max'],
          "min" => 0,
          "max" => $data['hp']['max'],
        ],
        "armor" => [
          "value" => $data['aac']['value'],
          "bonus" => $data['aac']['value'] - 10
        ],
        "xp" => [
          "value" => $data['details']['xp']
        ],
        "abilities" => [
          "str" => [
            "value" => $bonus,
            "max" => 10
          ],
          "dex" => [
            "value" => $bonus,
            "max" => 10
          ],
          "con" => [
            "value" => $bonus,
            "max" => 10
          ],
          "int" => [
            "value" => $bonus,
            "max" => 10
          ],
          "wis" => [
            "value" => $bonus,
            "max" => 10
          ],
          "cha" => [
            "value" => $bonus,
            "max" => 10
          ]
        ],
        "movement" => $data['movement']['encounter'],
        "morale" => [
          "value" => $data['details']['morale'],
          "max" => 12,
          "min" => 0
        ],
        "biography" => $data['details']['biography'],
        "level" => [
          "value" => $bonus,
          "min" => 1,
          "max" => 10
        ],
        "inventorySlots" => [
          "used" => 0,
          "value" => 0,
          "max" => 20
        ],
        "traits" => [
      "physique" => null,
      "face" => null,
      "skin" => null,
      "hair" => null,
      "clothing" => null,
      "virtue" => null,
      "vice" => null,
      "speech" => null,
      "background" => null,
      "misfortune" => "",
      "alignment" => null,
      "misfortunes" => null
    ]
    ];
    
    // spells?

    return $return;
}

function convertItem(array $item, array &$descriptions, array &$items)
{
    if ('ability' == $item['type']) {
        $text = strtoupper($item['name']) . ": {$item['data']['description']}";

        $descriptions[] = $text;
    } elseif ('weapon' == $item['type'] && $item['data']['melee']) {
        $melee = convertToMelee($item);

        $items[] = $melee;
    } elseif ('weapon' == $item['type']) {
        $ranged = convertToRanged($item);

        $items[] = $ranged;
    } else {
        $items[] = convertToKnaveItem($item);
    }
}

function convertToRanged(array $item): array
{
    $return = convertToMelee($item);

    $return['type'] = 'weaponRanged';
    $return['img'] = 'icons/svg/dice-target.svg';
    $return['data']['ammo'] = [
        'value' => 1,
        'min' => 0,
        'max' => 1,
    ];

    return $return;
/*
 {
      "_id": "7zw9ixN8C1rDuBZL",
      "name": "Pistol",
      "type": "weaponRanged",
      "img": "icons/svg/dice-target.svg",
      "data": {
        "name": "",
        "description": "<p>On an attack roll of 1 (Critical Failure), the gun explodes and causes 1d6 damage to the wielder.</p>\n<p>Takes 1 minute to reload.</p>",
        "quantity": 1,
        "slots": 1,
        "coppers": 1,
        "quality": {
          "value": 1,
          "min": 0,
          "max": 1
        },
        "damageDice": "2d6",
        "hands": 1,
        "ammo": {
          "value": 1,
          "min": 0,
          "max": 1
        }
      },
      "effects": [],
      "folder": null,
      "sort": 0,
      "permission": {
        "default": 0,
        "Trh82GWlRRte8jPO": 3
      },
      "flags": {
        "core": {
          "sourceId": "Item.2gTGlEAigD6puyaB"
        }
      }
    },
 */
}

function convertToMelee(array $item): array
{
    $return = [
        '_id' => $item['_id'],
        'name' => $item['name'],
        'type' => 'weaponMelee',
        'img' => 'icons/creatures/abilities/fangs-teeth-bite.webp',
        'data' => [
            'name' => '',
            'description' => $item['data']['description'],
            'damageDice' => $item['data']['damage'],
            'hands' => 1,
            'quantity' => 1,
            'slots' => 1,
            'coppers' => 1,
            'quality' => [
                'value' => 1,
                'min' => 0,
                'max' => 1,
            ],
        ],
        "effects" => [],
        "folder" => null,
        "sort" => 0,
        "permission" => [
            "default" => 0,
            "Trh82GWlRRte8jPO" => 3
        ],
    ];

    return $return;
    /*
{
  "_id": "3BiM1VeVFRfEFE5R",
  "name": "Attack",
  "type": "weaponMelee",
  "img": "icons/creatures/abilities/fangs-teeth-bite.webp",
  "data": {
    "name": "",
    "description": "",
    "quantity": 1,
    "slots": 1,
    "coppers": 1,
    "quality": {
      "value": 1,
      "min": 0,
      "max": 1
    },
    "damageDice": "2d4",
    "hands": 1
  },
"effects": [],
  "folder": "cNfhh5NYBYrtnrRs",
  "sort": 0,
  "permission": {
    "default": 0,
    "Trh82GWlRRte8jPO": 3
  },
  "flags": {
    "core": {
      "sourceId": "Item.2QKj7SYoWZJZykcL"
    }
  }
     */
}

function convertToKnaveItem(array $item): array
{
    $return = [
        '_id' => $item['_id'],
        'name' => $item['name'],
        'type' => 'item',
        'img' => 'icons/svg/item-bag.svg',
        'data' => [
            'name' => '',
            'description' => $item['data']['description'],
            'quantity' => 1,
            'slots' => 1,
            'coppers' => 1,
        ],
        "effects" => [],
        "folder" => null,
        "sort" => 0,
        "permission" => [
            "default" => 0,
            "Trh82GWlRRte8jPO" => 3
        ],
    ];

    return $return;
/*

    


    {
      "_id": "jBcetZEMMJmGSzJB",
      "name": "Powder & Shot",
      "type": "item",
      "img": "icons/svg/item-bag.svg",
      "data": {
        "name": "",
        "description": "",
        "quantity": 5,
        "slots": 1,
        "coppers": 1
      },
      "effects": [],
      "folder": null,
      "sort": 0,
      "permission": {
        "default": 0,
        "Trh82GWlRRte8jPO": 3
      },
      "flags": {
        "core": {
          "sourceId": "Item.HKea0hM2QlzANuUh"
        }
      }
    },
*/
}

/*
{
  "_id": "2Ug2LELifA6lLkPu",
  "name": "_Beast(Large)",
  "type": "character",
  "img": "icons/svg/mystery-man.svg",
  "data": {
    "health": {
      "value": 8,
      "min": 0,
      "max": 8
    },
    "armor": {
      "value": 12,
      "bonus": 1
    },
    "xp": {
      "value": 0
    },
    "abilities": {
      "str": {
        "value": 3,
        "max": 10
      },
      "dex": {
        "value": 2,
        "max": 10
      },
      "con": {
        "value": 2,
        "max": 10
      },
      "int": {
        "value": -5,
        "max": 10
      },
      "wis": {
        "value": 0,
        "max": 10
      },
      "cha": {
        "value": 2,
        "max": 10
      }
    },
    "movement": "40",
    "morale": {
      "value": 12,
      "max": 12,
      "min": 0
    },
    "biography": "",
    "level": {
      "value": 1,
      "min": 1,
      "max": 10
    },
    "inventorySlots": {
      "used": 0,
      "value": 0,
      "max": 20
    },
    "traits": {
      "physique": null,
      "face": null,
      "skin": null,
      "hair": null,
      "clothing": null,
      "virtue": null,
      "vice": null,
      "speech": null,
      "background": null,
      "misfortune": "",
      "alignment": null,
      "misfortunes": null
    }
  },
  "token": {
    "name": "_SmallBeast",
    "img": "icons/svg/mystery-man.svg",
    "displayName": 0,
    "actorLink": false,
    "width": 1,
    "height": 1,
    "scale": 1,
    "mirrorX": false,
    "mirrorY": false,
    "lockRotation": false,
    "rotation": 0,
    "alpha": 1,
    "vision": false,
    "dimSight": 0,
    "brightSight": 0,
    "sightAngle": 0,
    "light": {
      "alpha": 0.5,
      "angle": 0,
      "bright": 0,
      "coloration": 1,
      "dim": 0,
      "gradual": true,
      "luminosity": 0.5,
      "saturation": 0,
      "contrast": 0,
      "shadows": 0,
      "animation": {
        "speed": 5,
        "intensity": 5,
        "reverse": false
      },
      "darkness": {
        "min": 0,
        "max": 1
      }
    },
    "disposition": -1,
    "displayBars": 0,
    "bar1": {
      "attribute": "health"
    },
    "bar2": {
      "attribute": null
    },
    "flags": {},
    "randomImg": false
  },
  "items": [
    {
      "_id": "3BiM1VeVFRfEFE5R",
      "name": "Attack",
      "type": "weaponMelee",
      "img": "icons/creatures/abilities/fangs-teeth-bite.webp",
      "data": {
        "name": "",
        "description": "",
        "quantity": 1,
        "slots": 1,
        "coppers": 1,
        "quality": {
          "value": 1,
          "min": 0,
          "max": 1
        },
        "damageDice": "2d4",
        "hands": 1
      },
      "effects": [],
      "folder": "cNfhh5NYBYrtnrRs",
      "sort": 0,
      "permission": {
        "default": 0,
        "Trh82GWlRRte8jPO": 3
      },
      "flags": {
        "core": {
          "sourceId": "Item.2QKj7SYoWZJZykcL"
        }
      }
    }
  ],
  "effects": [],
  "folder": "cowM2wFHfxi09bZW",
  "sort": 25000,
  "permission": {
    "default": 0,
    "Trh82GWlRRte8jPO": 3
  },
  "flags": {}
}
*/
