<?php

/*
 * EDWIN 3.2.1 update script
 *
 * $LastChangedDate: 2014-12-18 08:10:58 +0100 (Do, 18 Dez 2014) $
 * $LastChangedBy: ulb $
 *
 * @package admin
 * @author Anton Jungwirth
 * @copyright (c) 2012 Q2E GmbH
 */

include '../includes/bootstrap.php';

$tablePrefix = ConfigHelper::get('table_prefix');

executeUpdate($db, $tablePrefix);

$db->close();

// functions -----------------------------------------------------------------//

/**
 * The update entry function
 *
 * @param db $db
 * @param unknown_type $tablePrefix
 *
 * @return void
 */
function executeUpdate(db $db, $tablePrefix)
{
  echo '<pre>';
  updateComments($db, $tablePrefix);
  updateCountriesSetPhoneCodes($db, $tablePrefix);
  echo '</pre>';
}

/**
 * Outputs the SQL statements for updating comments.
 * Sets all unpublished comments to published with a
 * published answer.
 *
 * @param $db
 * @param $tablePrefix
 *
 * @return void
 */
function updateComments(db $db, $tablePrefix)
{
  $out = <<<SQL
/* Update der Kommentare */

SQL;

  // Get employee attribute group for locations
  $sql = " SELECT CID FROM {$tablePrefix}comments "
       . " WHERE CID IN ( "
       . "   SELECT FK_CID FROM {$tablePrefix}comments "
       . " ) "
       . " AND CPublished = 0 ";
  $result = $db->GetCol($sql);

  if ($result) {
    $commentIdsToUpdate = implode(", ", $result);

    $out = <<<SQL
/* Kommentare aktualisieren */
UPDATE {$tablePrefix}comments
   SET CPublished = 1
 WHERE CID IN ( {$commentIdsToUpdate} )
SQL;
  }
  else {
    $out = <<<SQL
Kommentare sind bereits auf den neuesten Stand
SQL;
  }

  echo $out;
}

/**
 * @param $db
 * @param $tablePrefix
 * @return void
 */
function updateCountriesSetPhoneCodes(db $db, $tablePrefix)
{
  $out = <<<TEXT



/*******************************************************************************
 Update der Länderliste [START]

 > Das neue Feld COCode wird mit dem Code der Telefonvorwahl des jeweiligen
   Landes aktualisiert.

 > Bitte überprüfe die Ausgabe des Skripts genau.
 ******************************************************************************/

TEXT;

  $codes = array(
    // country => phone code
    1 => 43,
    2 => 49,
    3 => 41,
    4 => 33,
    5 => 39,
    6 => 31,
    7 => 48,
    8 => 351,
    9 => 93,
    10 => 355,
    11 => 213,
    12 => 1,
    13 => 376,
    14 => 244,
    15 => 1,
    16 => 672,
    17 => 1,
    18 => 54,
    19 => 374,
    20 => 297,
    21 => 61,
    22 => 994,
    23 => 1,
    24 => 973,
    25 => 880,
    26 => 1,
    27 => 375,
    28 => 32,
    29 => 501,
    30 => 229,
    31 => 1,
    32 => 975,
    33 => 591,
    34 => 387,
    35 => 267,
    36 => 0,
    37 => 55,
    38 => 246,
    39 => 673,
    40 => 359,
    41 => 226,
    42 => 257,
    43 => 855,
    44 => 237,
    45 => 1,
    46 => 238,
    47 => 1,
    48 => 236,
    49 => 235,
    50 => 56,
    51 => 86,
    52 => 61,
    53 => 61,
    54 => 57,
    55 => 269,
    56 => 242,
    57 => 243,
    58 => 682,
    59 => 506,
    60 => 225,
    61 => 385,
    62 => 53,
    63 => 357,
    64 => 420,
    65 => 45,
    66 => 253,
    67 => 1,
    68 => 1,
    69 => 593,
    70 => 20,
    71 => 503,
    72 => 240,
    73 => 291,
    74 => 372,
    75 => 251,
    76 => 500,
    77 => 298,
    78 => 679,
    79 => 358,
    80 => 594,
    81 => 689,
    82 => 0,
    83 => 241,
    84 => 220,
    85 => 995,
    86 => 233,
    87 => 350,
    88 => 30,
    89 => 299,
    90 => 1,
    91 => 590,
    92 => 1,
    93 => 502,
    94 => 44,
    95 => 224,
    96 => 245,
    97 => 592,
    98 => 509,
    99 => 0,
    100 => 379,
    101 => 504,
    102 => 852,
    103 => 36,
    104 => 354,
    105 => 91,
    106 => 62,
    107 => 98,
    108 => 964,
    109 => 353,
    110 => 44,
    111 => 972,
    112 => 1,
    113 => 81,
    114 => 44,
    115 => 962,
    116 => 7,
    117 => 254,
    118 => 686,
    119 => 850,
    120 => 82,
    121 => 965,
    122 => 996,
    123 => 856,
    124 => 371,
    125 => 961,
    126 => 266,
    127 => 231,
    128 => 218,
    129 => 423,
    130 => 370,
    131 => 352,
    132 => 853,
    133 => 389,
    134 => 261,
    135 => 265,
    136 => 60,
    137 => 960,
    138 => 223,
    139 => 356,
    140 => 692,
    141 => 0,
    142 => 222,
    143 => 230,
    144 => 262,
    145 => 52,
    146 => 691,
    147 => 373,
    148 => 377,
    149 => 976,
    150 => 382,
    151 => 1,
    152 => 212,
    153 => 258,
    154 => 95,
    155 => 264,
    156 => 674,
    157 => 977,
    158 => 599,
    159 => 687,
    160 => 64,
    161 => 505,
    162 => 227,
    163 => 234,
    164 => 683,
    165 => 672,
    166 => 1,
    167 => 47,
    168 => 968,
    169 => 92,
    170 => 680,
    171 => 970,
    172 => 507,
    173 => 675,
    174 => 595,
    175 => 51,
    176 => 63,
    177 => 64,
    178 => 1,
    179 => 974,
    180 => 0,
    181 => 40,
    182 => 7,
    183 => 250,
    184 => 290,
    185 => 1,
    186 => 1,
    187 => 508,
    188 => 1,
    189 => 685,
    190 => 378,
    191 => 239,
    192 => 966,
    193 => 221,
    194 => 381,
    195 => 248,
    196 => 232,
    197 => 65,
    198 => 421,
    199 => 386,
    200 => 677,
    201 => 252,
    202 => 27,
    203 => 500,
    204 => 34,
    205 => 94,
    206 => 249,
    207 => 597,
    208 => 4779,
    209 => 268,
    210 => 46,
    211 => 963,
    212 => 886,
    213 => 992,
    214 => 255,
    215 => 66,
    216 => 670,
    217 => 228,
    218 => 690,
    219 => 676,
    220 => 1,
    221 => 216,
    222 => 90,
    223 => 993,
    224 => 1,
    225 => 688,
    226 => 256,
    227 => 380,
    228 => 971,
    229 => 44,
    230 => 1,
    231 => 0,
    232 => 598,
    233 => 998,
    234 => 678,
    235 => 58,
    236 => 84,
    237 => 1,
    238 => 1,
    239 => 681,
    240 => 212,
    241 => 967,
    242 => 260,
    243 => 263,
    244 => 381,
  );

  // Get employee attribute group for locations
  $sql = " SELECT * "
       . " FROM {$tablePrefix}country "
       . " ORDER BY COID ";
  $assoc = $db->GetAssoc($sql);

  foreach ($assoc as $country) {
    $id = (int)$country['COID'];
    if (isset($codes[$id])) {
      $code = $codes[$id];
      unset($codes[$id]);
      $sql = " UPDATE {$tablePrefix}country "
           . " SET COCode = '$code' "
           . " WHERE COID = $id ";
      $db->query($sql);
      $out .= "[{$country['COID']}] {$country['COName']} aktualisiert.\n";
    }
    else {
      $out .= "<b style=\"color:red\">[{$country['COID']}] {$country['COName']} konnte keine Vorwahl zugewiesen werden.</b>\n";
    }
  }

  if (count($codes)) {
    $out .= "\n\n";
    $out .= "ACHTUNG:\n";
    $out .= "Zu folgenden IDs wurden keine Länder in der Datenbank gefunden:\n";
    foreach ($codes as $key => $val) {
      $out .= "id: $key | code: $val\n";
    }
    $out .= "\n\n";
  }

  $out .= <<<TEXT
/*******************************************************************************
 Update der Länderliste [END]
 ******************************************************************************/
TEXT;

  echo $out;
}