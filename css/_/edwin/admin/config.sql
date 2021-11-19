/******************************************************************************/
/*                                COUNTRIES                                   */
/******************************************************************************/

/******************************************************************************/
/* (1) Different configuration options (replace column / table names for      */
/*     contenttype specific settings)                                         */
/******************************************************************************/

/******************************************************************************/
/* DACH                                                                       */
/******************************************************************************/

UPDATE mc_country SET COActive = 1 WHERE COID IN (1,2,3);

/******************************************************************************/
/* World                                                                      */
/******************************************************************************/

UPDATE mc_country SET COActive = 1 WHERE COID IN ( 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63,64,65,66,67,68,69,70,71,72,73,74,75,76,77,78,79,80,81,82,83,84,85,86,87,88,89,90,91,92,93,94,95,96,97,98,99,100,101,102,103,104,105,106,107,108,109,110,111,112,113,114,115,116,117,118,119,120,121,122,123,124,125,126,127,128,129,130,131,132,133,134,135,136,137,138,139,140,141,142,143,144,145,146,147,148,149,150,151,152,153,154,155,156,157,158,159,160,161,162,163,164,165,166,167,168,169,170,171,172,173,174,175,176,177,178,179,180,181,182,183,184,185,186,187,188,189,190,191,192,193,194,195,196,197,198,199,200,201,202,203,204,205,206,207,208,209,210,211,212,213,214,215,216,217,218,219,220,221,222,223,224,225,226,227,228,229,230,231,232,233,234,235,236,237,238,239,240,241,242,243,244);

/******************************************************************************/
/* EU + CH + LI                                                               */
/******************************************************************************/

UPDATE mc_country SET COActive = 1 WHERE COID IN (1,2,3,4,5,6,7,8,28,40,61,63,64,65,74,79,88,103,109,124,129,130,131,139,181,198,199,204,210,229);

/******************************************************************************/
/* DACH - Long                                                                */
/******************************************************************************/
 UPDATE mc_country
     SET COName = 'AT - AUSTRIA'
 WHERE COID = 1;
 UPDATE mc_country
     SET COName = 'DE - GERMANY'
 WHERE COID = 2;
 UPDATE mc_country
     SET COName = 'CH - SWITZERLAND'
 WHERE COID = 3;

/******************************************************************************/
/* DACH - Short                                                               */
/******************************************************************************/
 UPDATE mc_country
     SET COName = 'AT'
 WHERE COID = 1;
 UPDATE mc_country
     SET COName = 'DE'
 WHERE COID = 2;
 UPDATE mc_country
     SET COName = 'CH'
 WHERE COID = 3;

/******************************************************************************/
/* DACH - Postcode                                                            */
/******************************************************************************/
 UPDATE mc_country
     SET COName = 'A'
 WHERE COID = 1;
 UPDATE mc_country
     SET COName = 'D'
 WHERE COID = 2;
 UPDATE mc_country
     SET COName = 'CH'
 WHERE COID = 3;

/******************************************************************************/
/* EU - Shortterm                                                             */
/******************************************************************************/
 UPDATE mc_country
     SET COName = 'AT'
 WHERE COID = 1;
 UPDATE mc_country
     SET COName = 'DE'
 WHERE COID = 2;
 UPDATE mc_country
     SET COName = 'CH'
 WHERE COID = 3;
 UPDATE mc_country
     SET COName = 'AL'
 WHERE COID = 10;
 UPDATE mc_country
     SET COName = 'AD'
 WHERE COID = 13;
 UPDATE mc_country
     SET COName = 'BE'
 WHERE COID = 28;
 UPDATE mc_country
     SET COName = 'BA'
 WHERE COID = 34;
 UPDATE mc_country
     SET COName = 'BG'
 WHERE COID = 40;
 UPDATE mc_country
     SET COName = 'HR'
 WHERE COID = 61;
 UPDATE mc_country
     SET COName = 'CZ'
 WHERE COID = 64;
 UPDATE mc_country
     SET COName = 'DK'
 WHERE COID = 65;
 UPDATE mc_country
     SET COName = 'EE'
 WHERE COID = 74;
 UPDATE mc_country
     SET COName = 'FI'
 WHERE COID = 79;
 UPDATE mc_country
     SET COName = 'FR'
 WHERE COID = 4;
 UPDATE mc_country
     SET COName = 'GR'
 WHERE COID = 88;
 UPDATE mc_country
     SET COName = 'GG'
 WHERE COID = 94;
 UPDATE mc_country
     SET COName = 'HU'
 WHERE COID = 103;
 UPDATE mc_country
     SET COName = 'IE'
 WHERE COID = 109;
 UPDATE mc_country
     SET COName = 'IT'
 WHERE COID = 5;
 UPDATE mc_country
     SET COName = 'JE'
 WHERE COID = 114;
 UPDATE mc_country
     SET COName = 'KZ'
 WHERE COID = 116;
 UPDATE mc_country
     SET COName = 'LV'
 WHERE COID = 124;
 UPDATE mc_country
     SET COName = 'LI'
 WHERE COID = 129;
 UPDATE mc_country
     SET COName = 'LT '
 WHERE COID = 130;
 UPDATE mc_country
     SET COName = 'LU'
 WHERE COID = 131;
 UPDATE mc_country
     SET COName = 'MT'
 WHERE COID = 139;
 UPDATE mc_country
     SET COName = 'MD'
 WHERE COID = 147;
 UPDATE mc_country
     SET COName = 'MC'
 WHERE COID = 148;
 UPDATE mc_country
     SET COName = 'ME'
 WHERE COID = 150;
 UPDATE mc_country
     SET COName = 'NL'
 WHERE COID = 6;
 UPDATE mc_country
     SET COName = 'NO'
 WHERE COID = 167;
 UPDATE mc_country
     SET COName = 'PL'
 WHERE COID = 7;
 UPDATE mc_country
     SET COName = 'PT'
 WHERE COID = 8;
 UPDATE mc_country
     SET COName = 'RO'
 WHERE COID = 181;
 UPDATE mc_country
     SET COName = 'RU'
 WHERE COID = 182;
 UPDATE mc_country
     SET COName = 'SK'
 WHERE COID = 198;
 UPDATE mc_country
     SET COName = 'SI'
 WHERE COID = 199;
 UPDATE mc_country
     SET COName = 'SM'
 WHERE COID = 190;
 UPDATE mc_country
     SET COName = 'RS'
 WHERE COID = 194;
 UPDATE mc_country
     SET COName = 'ES'
 WHERE COID = 204;
 UPDATE mc_country
     SET COName = 'SJ'
 WHERE COID = 208;
 UPDATE mc_country
     SET COName = 'SE'
 WHERE COID = 210;
 UPDATE mc_country
     SET COName = 'TR'
 WHERE COID = 222;
 UPDATE mc_country
     SET COName = 'UA'
 WHERE COID = 227;
 UPDATE mc_country
     SET COName = 'GB'
 WHERE COID = 229;
 UPDATE mc_country
     SET COName = 'XK'
 WHERE COID = 244;

/******************************************************************************/
/* World                                                                      */
/******************************************************************************/
 UPDATE mc_country
     SET COName = 'AT - AUSTRIA'
 WHERE COID = 1;
 UPDATE mc_country
     SET COName = 'DE - GERMANY'
 WHERE COID = 2;
 UPDATE mc_country
     SET COName = 'CH - SWITZERLAND'
 WHERE COID = 3;
 UPDATE mc_country
     SET COName = 'FR - FRANCE'
 WHERE COID = 4;
 UPDATE mc_country
     SET COName = 'IT - ITALY'
 WHERE COID = 5;
 UPDATE mc_country
     SET COName = 'NL - NETHERLANDS'
 WHERE COID = 6;
 UPDATE mc_country
     SET COName = 'PL - POLAND'
 WHERE COID = 7;
 UPDATE mc_country
     SET COName = 'PT - PORTUGAL'
 WHERE COID = 8;
 UPDATE mc_country
     SET COName = 'AF - AFGHANISTAN'
 WHERE COID = 9;
 UPDATE mc_country
     SET COName = 'AL - ALBANIA'
 WHERE COID = 10;
 UPDATE mc_country
     SET COName = 'DZ - ALGERIA'
 WHERE COID = 11;
 UPDATE mc_country
     SET COName = 'AS - AMERICAN SAMOA'
 WHERE COID = 12;
 UPDATE mc_country
     SET COName = 'AD - ANDORRA'
 WHERE COID = 13;
 UPDATE mc_country
     SET COName = 'AO - ANGOLA'
 WHERE COID = 14;
 UPDATE mc_country
     SET COName = 'AI - ANGUILLA'
 WHERE COID = 15;
 UPDATE mc_country
     SET COName = 'AQ - ANTARCTICA'
 WHERE COID = 16;
 UPDATE mc_country
     SET COName = 'AG - ANTIGUA AND BARBUDA'
 WHERE COID = 17;
 UPDATE mc_country
     SET COName = 'AR - ARGENTINA'
 WHERE COID = 18;
 UPDATE mc_country
     SET COName = 'AM - ARMENIA'
 WHERE COID = 19;
 UPDATE mc_country
     SET COName = 'AW - ARUBA'
 WHERE COID = 20;
 UPDATE mc_country
     SET COName = 'AU - AUSTRALIA'
 WHERE COID = 21;
 UPDATE mc_country
     SET COName = 'AZ - AZERBAIJAN'
 WHERE COID = 22;
 UPDATE mc_country
     SET COName = 'BS - BAHAMAS'
 WHERE COID = 23;
 UPDATE mc_country
     SET COName = 'BH - BAHRAIN'
 WHERE COID = 24;
 UPDATE mc_country
     SET COName = 'BD - BANGLADESH'
 WHERE COID = 25;
 UPDATE mc_country
     SET COName = 'BB - BARBADOS'
 WHERE COID = 26;
 UPDATE mc_country
     SET COName = 'BY - BELARUS'
 WHERE COID = 27;
 UPDATE mc_country
     SET COName = 'BE - BELGIUM'
 WHERE COID = 28;
 UPDATE mc_country
     SET COName = 'BZ - BELIZE'
 WHERE COID = 29;
 UPDATE mc_country
     SET COName = 'BJ - BENIN'
 WHERE COID = 30;
 UPDATE mc_country
     SET COName = 'BM - BERMUDA'
 WHERE COID = 31;
 UPDATE mc_country
     SET COName = 'BT - BHUTAN'
 WHERE COID = 32;
 UPDATE mc_country
     SET COName = 'BO - BOLIVIA'
 WHERE COID = 33;
 UPDATE mc_country
     SET COName = 'BA - BOSNIA AND HERZEGOV.'
 WHERE COID = 34;
 UPDATE mc_country
     SET COName = 'BW - BOTSWANA'
 WHERE COID = 35;
 UPDATE mc_country
     SET COName = 'BV - BOUVET ISLAND'
 WHERE COID = 36;
 UPDATE mc_country
     SET COName = 'BR - BRAZIL'
 WHERE COID = 37;
 UPDATE mc_country
     SET COName = 'IO - BRITISH INDIAN OCEAN T.'
 WHERE COID = 38;
 UPDATE mc_country
     SET COName = 'BN - BRUNEI DARUSSALAM'
 WHERE COID = 39;
 UPDATE mc_country
     SET COName = 'BG - BULGARIA'
 WHERE COID = 40;
 UPDATE mc_country
     SET COName = 'BF - BURKINA FASO'
 WHERE COID = 41;
 UPDATE mc_country
     SET COName = 'BI - BURUNDI'
 WHERE COID = 42;
 UPDATE mc_country
     SET COName = 'KH - CAMBODIA'
 WHERE COID = 43;
 UPDATE mc_country
     SET COName = 'CM - CAMEROON'
 WHERE COID = 44;
 UPDATE mc_country
     SET COName = 'CA - CANADA'
 WHERE COID = 45;
 UPDATE mc_country
     SET COName = 'CV - CAPE VERDE'
 WHERE COID = 46;
 UPDATE mc_country
     SET COName = 'KY - CAYMAN ISLANDS'
 WHERE COID = 47;
 UPDATE mc_country
     SET COName = 'CF - CENTRAL AFRICAN REP.'
 WHERE COID = 48;
 UPDATE mc_country
     SET COName = 'TD - CHAD'
 WHERE COID = 49;
 UPDATE mc_country
     SET COName = 'CL - CHILE'
 WHERE COID = 50;
 UPDATE mc_country
     SET COName = 'CN - CHINA'
 WHERE COID = 51;
 UPDATE mc_country
     SET COName = 'CX - CHRISTMAS ISLAND'
 WHERE COID = 52;
 UPDATE mc_country
     SET COName = 'CC - COCOS (K.) ISLANDS'
 WHERE COID = 53;
 UPDATE mc_country
     SET COName = 'CO - COLOMBIA'
 WHERE COID = 54;
 UPDATE mc_country
     SET COName = 'KM - COMOROS'
 WHERE COID = 55;
 UPDATE mc_country
     SET COName = 'CG - CONGO'
 WHERE COID = 56;
 UPDATE mc_country
     SET COName = 'CD - CONGO, THE DEM. REP.'
 WHERE COID = 57;
 UPDATE mc_country
     SET COName = 'CK - COOK ISLANDS'
 WHERE COID = 58;
 UPDATE mc_country
     SET COName = 'CR - COSTA RICA'
 WHERE COID = 59;
 UPDATE mc_country
     SET COName = 'CI - COTE D\'IVOIRE'
 WHERE COID = 60;
 UPDATE mc_country
     SET COName = 'HR - CROATIA'
 WHERE COID = 61;
 UPDATE mc_country
     SET COName = 'CU - CUBA'
 WHERE COID = 62;
 UPDATE mc_country
     SET COName = 'CY - CYPRUS'
 WHERE COID = 63;
 UPDATE mc_country
     SET COName = 'CZ - CZECH REPUBLIC'
 WHERE COID = 64;
 UPDATE mc_country
     SET COName = 'DK - DENMARK'
 WHERE COID = 65;
 UPDATE mc_country
     SET COName = 'DJ - DJIBOUTI'
 WHERE COID = 66;
 UPDATE mc_country
     SET COName = 'DM - DOMINICA'
 WHERE COID = 67;
 UPDATE mc_country
     SET COName = 'DO - DOMINICAN REPUBLIC'
 WHERE COID = 68;
 UPDATE mc_country
     SET COName = 'EC - ECUADOR'
 WHERE COID = 69;
 UPDATE mc_country
     SET COName = 'EG - EGYPT'
 WHERE COID = 70;
 UPDATE mc_country
     SET COName = 'SV - EL SALVADOR'
 WHERE COID = 71;
 UPDATE mc_country
     SET COName = 'GQ - EQUATORIAL GUINEA'
 WHERE COID = 72;
 UPDATE mc_country
     SET COName = 'ER - ERITREA'
 WHERE COID = 73;
 UPDATE mc_country
     SET COName = 'EE - ESTONIA'
 WHERE COID = 74;
 UPDATE mc_country
     SET COName = 'ET - ETHIOPIA'
 WHERE COID = 75;
 UPDATE mc_country
     SET COName = 'FK - FALKLAND ISLANDS'
 WHERE COID = 76;
 UPDATE mc_country
     SET COName = 'FO - FAROE ISLANDS'
 WHERE COID = 77;
 UPDATE mc_country
     SET COName = 'FJ - FIJI'
 WHERE COID = 78;
 UPDATE mc_country
     SET COName = 'FI - FINLAND'
 WHERE COID = 79;
 UPDATE mc_country
     SET COName = 'GF - FRENCH GUIANA'
 WHERE COID = 80;
 UPDATE mc_country
     SET COName = 'PF - FRENCH POLYNESIA'
 WHERE COID = 81;
 UPDATE mc_country
     SET COName = 'TF - FRENCH SOUTHERN T.'
 WHERE COID = 82;
 UPDATE mc_country
     SET COName = 'GA - GABON'
 WHERE COID = 83;
 UPDATE mc_country
     SET COName = 'GM - GAMBIA'
 WHERE COID = 84;
 UPDATE mc_country
     SET COName = 'GE - GEORGIA'
 WHERE COID = 85;
 UPDATE mc_country
     SET COName = 'GH - GHANA'
 WHERE COID = 86;
 UPDATE mc_country
     SET COName = 'GI - GIBRALTAR'
 WHERE COID = 87;
 UPDATE mc_country
     SET COName = 'GR - GREECE'
 WHERE COID = 88;
 UPDATE mc_country
     SET COName = 'GL - GREENLAND'
 WHERE COID = 89;
 UPDATE mc_country
     SET COName = 'GD - GRENADA'
 WHERE COID = 90;
 UPDATE mc_country
     SET COName = 'GP - GUADELOUPE'
 WHERE COID = 91;
 UPDATE mc_country
     SET COName = 'GU - GUAM'
 WHERE COID = 92;
 UPDATE mc_country
     SET COName = 'GT - GUATEMALA'
 WHERE COID = 93;
 UPDATE mc_country
     SET COName = 'GG - GUERNSEY'
 WHERE COID = 94;
 UPDATE mc_country
     SET COName = 'GN - GUINEA'
 WHERE COID = 95;
 UPDATE mc_country
     SET COName = 'GW - GUINEA-BISSAU'
 WHERE COID = 96;
 UPDATE mc_country
     SET COName = 'GY - GUYANA'
 WHERE COID = 97;
 UPDATE mc_country
     SET COName = 'HT - HAITI'
 WHERE COID = 98;
 UPDATE mc_country
     SET COName = 'HM - HEARD ISLAND'
 WHERE COID = 99;
 UPDATE mc_country
     SET COName = 'VA - HOLY SEE'
 WHERE COID = 100;
 UPDATE mc_country
     SET COName = 'HN - HONDURAS'
 WHERE COID = 101;
 UPDATE mc_country
     SET COName = 'HK - HONG KONG'
 WHERE COID = 102;
 UPDATE mc_country
     SET COName = 'HU - HUNGARY'
 WHERE COID = 103;
 UPDATE mc_country
     SET COName = 'IS - ICELAND'
 WHERE COID = 104;
 UPDATE mc_country
     SET COName = 'IN - INDIA'
 WHERE COID = 105;
 UPDATE mc_country
     SET COName = 'ID - INDONESIA'
 WHERE COID = 106;
 UPDATE mc_country
     SET COName = 'IR - IRAN, ISLAMIC REP.'
 WHERE COID = 107;
 UPDATE mc_country
     SET COName = 'IQ - IRAQ'
 WHERE COID = 108;
 UPDATE mc_country
     SET COName = 'IE - IRELAND'
 WHERE COID = 109;
 UPDATE mc_country
     SET COName = 'IM - ISLE OF MAN'
 WHERE COID = 110;
 UPDATE mc_country
     SET COName = 'IL - ISRAEL'
 WHERE COID = 111;
 UPDATE mc_country
     SET COName = 'JM - JAMAICA'
 WHERE COID = 112;
 UPDATE mc_country
     SET COName = 'JP - JAPAN'
 WHERE COID = 113;
 UPDATE mc_country
     SET COName = 'JE - JERSEY'
 WHERE COID = 114;
 UPDATE mc_country
     SET COName = 'JO - JORDAN'
 WHERE COID = 115;
 UPDATE mc_country
     SET COName = 'KZ - KAZAKHSTAN'
 WHERE COID = 116;
 UPDATE mc_country
     SET COName = 'KE - KENYA'
 WHERE COID = 117;
 UPDATE mc_country
     SET COName = 'KI - KIRIBATI'
 WHERE COID = 118;
 UPDATE mc_country
     SET COName = 'KP - KOREA, DEM. PEO. REP.'
 WHERE COID = 119;
 UPDATE mc_country
     SET COName = 'KR - KOREA, REPUBLIC OF'
 WHERE COID = 120;
 UPDATE mc_country
     SET COName = 'KW - KUWAIT'
 WHERE COID = 121;
 UPDATE mc_country
     SET COName = 'KG - KYRGYZSTAN'
 WHERE COID = 122;
 UPDATE mc_country
     SET COName = 'LA - LAO PEOPLE\'S DEM. REP.'
 WHERE COID = 123;
 UPDATE mc_country
     SET COName = 'LV - LATVIA'
 WHERE COID = 124;
 UPDATE mc_country
     SET COName = 'LB - LEBANON'
 WHERE COID = 125;
 UPDATE mc_country
     SET COName = 'LS - LESOTHO'
 WHERE COID = 126;
 UPDATE mc_country
     SET COName = 'LR - LIBERIA'
 WHERE COID = 127;
 UPDATE mc_country
     SET COName = 'LY - LIBYAN ARAB JAM.'
 WHERE COID = 128;
 UPDATE mc_country
     SET COName = 'LI - LIECHTENSTEIN'
 WHERE COID = 129;
 UPDATE mc_country
     SET COName = 'LT - LITHUANIA'
 WHERE COID = 130;
 UPDATE mc_country
     SET COName = 'LU - LUXEMBOURG'
 WHERE COID = 131;
 UPDATE mc_country
     SET COName = 'MO - MACAO'
 WHERE COID = 132;
 UPDATE mc_country
     SET COName = 'MK - MACEDONIA.'
 WHERE COID = 133;
 UPDATE mc_country
     SET COName = 'MG - MADAGASCAR'
 WHERE COID = 134;
 UPDATE mc_country
     SET COName = 'MW - MALAWI'
 WHERE COID = 135;
 UPDATE mc_country
     SET COName = 'MY - MALAYSIA'
 WHERE COID = 136;
 UPDATE mc_country
     SET COName = 'MV - MALDIVES'
 WHERE COID = 137;
 UPDATE mc_country
     SET COName = 'ML - MALI'
 WHERE COID = 138;
 UPDATE mc_country
     SET COName = 'MT - MALTA'
 WHERE COID = 139;
 UPDATE mc_country
     SET COName = 'MH - MARSHALL ISLANDS'
 WHERE COID = 140;
 UPDATE mc_country
     SET COName = 'MQ - MARTINIQUE'
 WHERE COID = 141;
 UPDATE mc_country
     SET COName = 'MR - MAURITANIA'
 WHERE COID = 142;
 UPDATE mc_country
     SET COName = 'MU - MAURITIUS'
 WHERE COID = 143;
 UPDATE mc_country
     SET COName = 'YT - MAYOTTE'
 WHERE COID = 144;
 UPDATE mc_country
     SET COName = 'MX - MEXICO'
 WHERE COID = 145;
 UPDATE mc_country
     SET COName = 'FM - MICRONESIA'
 WHERE COID = 146;
 UPDATE mc_country
     SET COName = 'MD - MOLDOVA, REP.'
 WHERE COID = 147;
 UPDATE mc_country
     SET COName = 'MC - MONACO'
 WHERE COID = 148;
 UPDATE mc_country
     SET COName = 'MN - MONGOLIA'
 WHERE COID = 149;
 UPDATE mc_country
     SET COName = 'ME - MONTENEGRO'
 WHERE COID = 150;
 UPDATE mc_country
     SET COName = 'MS - MONTSERRAT'
 WHERE COID = 151;
 UPDATE mc_country
     SET COName = 'MA - MOROCCO'
 WHERE COID = 152;
 UPDATE mc_country
     SET COName = 'MZ - MOZAMBIQUE'
 WHERE COID = 153;
 UPDATE mc_country
     SET COName = 'MM - MYANMAR'
 WHERE COID = 154;
 UPDATE mc_country
     SET COName = 'NA - NAMIBIA'
 WHERE COID = 155;
 UPDATE mc_country
     SET COName = 'NR - NAURU'
 WHERE COID = 156;
 UPDATE mc_country
     SET COName = 'NP - NEPAL'
 WHERE COID = 157;
 UPDATE mc_country
     SET COName = 'AN - NETHERLANDS ANT.'
 WHERE COID = 158;
 UPDATE mc_country
     SET COName = 'NC - NEW CALEDONIA'
 WHERE COID = 159;
 UPDATE mc_country
     SET COName = 'NZ - NEW ZEALAND'
 WHERE COID = 160;
 UPDATE mc_country
     SET COName = 'NI - NICARAGUA'
 WHERE COID = 161;
 UPDATE mc_country
     SET COName = 'NE - NIGER'
 WHERE COID = 162;
 UPDATE mc_country
     SET COName = 'NG - NIGERIA'
 WHERE COID = 163;
 UPDATE mc_country
     SET COName = 'NU - NIUE'
 WHERE COID = 164;
 UPDATE mc_country
     SET COName = 'NF - NORFOLK ISLAND'
 WHERE COID = 165;
 UPDATE mc_country
     SET COName = 'MP - NORTHERN MARIANA ISL.'
 WHERE COID = 166;
 UPDATE mc_country
     SET COName = 'NO - NORWAY'
 WHERE COID = 167;
 UPDATE mc_country
     SET COName = 'OM - OMAN'
 WHERE COID = 168;
 UPDATE mc_country
     SET COName = 'PK - PAKISTAN'
 WHERE COID = 169;
 UPDATE mc_country
     SET COName = 'PW - PALAU'
 WHERE COID = 170;
 UPDATE mc_country
     SET COName = 'PS - PALESTINIAN TERR.'
 WHERE COID = 171;
 UPDATE mc_country
     SET COName = 'PA - PANAMA'
 WHERE COID = 172;
 UPDATE mc_country
     SET COName = 'PG - PAPUA NEW GUINEA'
 WHERE COID = 173;
 UPDATE mc_country
     SET COName = 'PY - PARAGUAY'
 WHERE COID = 174;
 UPDATE mc_country
     SET COName = 'PE - PERU'
 WHERE COID = 175;
 UPDATE mc_country
     SET COName = 'PH - PHILIPPINES'
 WHERE COID = 176;
 UPDATE mc_country
     SET COName = 'PN - PITCAIRN'
 WHERE COID = 177;
 UPDATE mc_country
     SET COName = 'PR - PUERTO RICO'
 WHERE COID = 178;
 UPDATE mc_country
     SET COName = 'QA - QATAR'
 WHERE COID = 179;
 UPDATE mc_country
     SET COName = 'RE - REUNION'
 WHERE COID = 180;
 UPDATE mc_country
     SET COName = 'RO - ROMANIA'
 WHERE COID = 181;
 UPDATE mc_country
     SET COName = 'RU - RUSSIAN FEDERATION'
 WHERE COID = 182;
 UPDATE mc_country
     SET COName = 'RW - RWANDA'
 WHERE COID = 183;
 UPDATE mc_country
     SET COName = 'SH - SAINT HELENA'
 WHERE COID = 184;
 UPDATE mc_country
     SET COName = 'KN - SAINT KITTS AND NEVIS'
 WHERE COID = 185;
 UPDATE mc_country
     SET COName = 'LC - SAINT LUCIA'
 WHERE COID = 186;
 UPDATE mc_country
     SET COName = 'PM - SAINT PIERRE AND MIQU.'
 WHERE COID = 187;
 UPDATE mc_country
     SET COName = 'VC - SAINT VINCENT'
 WHERE COID = 188;
 UPDATE mc_country
     SET COName = 'WS - SAMOA'
 WHERE COID = 189;
 UPDATE mc_country
     SET COName = 'SM - SAN MARINO'
 WHERE COID = 190;
 UPDATE mc_country
     SET COName = 'ST - SAO TOME AND PRINCIPE'
 WHERE COID = 191;
 UPDATE mc_country
     SET COName = 'SA - SAUDI ARABIA'
 WHERE COID = 192;
 UPDATE mc_country
     SET COName = 'SN - SENEGAL'
 WHERE COID = 193;
 UPDATE mc_country
     SET COName = 'RS - SERBIA'
 WHERE COID = 194;
 UPDATE mc_country
     SET COName = 'SC - SEYCHELLES'
 WHERE COID = 195;
 UPDATE mc_country
     SET COName = 'SL - SIERRA LEONE'
 WHERE COID = 196;
 UPDATE mc_country
     SET COName = 'SG - SINGAPORE'
 WHERE COID = 197;
 UPDATE mc_country
     SET COName = 'SK - SLOVAKIA'
 WHERE COID = 198;
 UPDATE mc_country
     SET COName = 'SI - SLOVENIA'
 WHERE COID = 199;
 UPDATE mc_country
     SET COName = 'SB - SOLOMON ISLANDS'
 WHERE COID = 200;
 UPDATE mc_country
     SET COName = 'SO - SOMALIA'
 WHERE COID = 201;
 UPDATE mc_country
     SET COName = 'ZA - SOUTH AFRICA'
 WHERE COID = 202;
 UPDATE mc_country
     SET COName = 'GS - SOUTH GEORGIA '
 WHERE COID = 203;
 UPDATE mc_country
     SET COName = 'ES - SPAIN'
 WHERE COID = 204;
 UPDATE mc_country
     SET COName = 'LK - SRI LANKA'
 WHERE COID = 205;
 UPDATE mc_country
     SET COName = 'SD - SUDAN'
 WHERE COID = 206;
 UPDATE mc_country
     SET COName = 'SR - SURINAME'
 WHERE COID = 207;
 UPDATE mc_country
     SET COName = 'SJ - SVALBARD.'
 WHERE COID = 208;
 UPDATE mc_country
     SET COName = 'SZ - SWAZILAND'
 WHERE COID = 209;
 UPDATE mc_country
     SET COName = 'SE - SWEDEN'
 WHERE COID = 210;
 UPDATE mc_country
     SET COName = 'SY - SYRIAN ARAB REP.'
 WHERE COID = 211;
 UPDATE mc_country
     SET COName = 'TW - TAIWAN, PROV.OF CHINA'
 WHERE COID = 212;
 UPDATE mc_country
     SET COName = 'TJ - TAJIKISTAN'
 WHERE COID = 213;
 UPDATE mc_country
     SET COName = 'TZ - TANZANIA, UN. REP.'
 WHERE COID = 214;
 UPDATE mc_country
     SET COName = 'TH - THAILAND'
 WHERE COID = 215;
 UPDATE mc_country
     SET COName = 'TL - TIMOR-LESTE'
 WHERE COID = 216;
 UPDATE mc_country
     SET COName = 'TG - TOGO'
 WHERE COID = 217;
 UPDATE mc_country
     SET COName = 'TK - TOKELAU'
 WHERE COID = 218;
 UPDATE mc_country
     SET COName = 'TO - TONGA'
 WHERE COID = 219;
 UPDATE mc_country
     SET COName = 'TT - TRINIDAD AND TOBAGO'
 WHERE COID = 220;
 UPDATE mc_country
     SET COName = 'TN - TUNISIA'
 WHERE COID = 221;
 UPDATE mc_country
     SET COName = 'TR - TURKEY'
 WHERE COID = 222;
 UPDATE mc_country
     SET COName = 'TM - TURKMENISTAN'
 WHERE COID = 223;
 UPDATE mc_country
     SET COName = 'TC - TURKS'
 WHERE COID = 224;
 UPDATE mc_country
     SET COName = 'TV - TUVALU'
 WHERE COID = 225;
 UPDATE mc_country
     SET COName = 'UG - UGANDA'
 WHERE COID = 226;
 UPDATE mc_country
     SET COName = 'UA - UKRAINE'
 WHERE COID = 227;
 UPDATE mc_country
     SET COName = 'AE - UNITED ARAB EMIRATES'
 WHERE COID = 228;
 UPDATE mc_country
     SET COName = 'GB - UNITED KINGDOM'
 WHERE COID = 229;
 UPDATE mc_country
     SET COName = 'US - UNITED STATES'
 WHERE COID = 230;
 UPDATE mc_country
     SET COName = 'UM – UNITED STATES MINOR OUTLYING ISLANDS'
 WHERE COID = 231;
 UPDATE mc_country
     SET COName = 'UY - URUGUAY'
 WHERE COID = 232;
 UPDATE mc_country
     SET COName = 'UZ - UZBEKISTAN'
 WHERE COID = 233;
 UPDATE mc_country
     SET COName = 'VU - VANUATU'
 WHERE COID = 234;
 UPDATE mc_country
     SET COName = 'VE - VENEZUELA'
 WHERE COID = 235;
 UPDATE mc_country
     SET COName = 'VN - VIET NAM'
 WHERE COID = 236;
 UPDATE mc_country
     SET COName = 'VG - VIRGIN ISLANDS, BRIT.'
 WHERE COID = 237;
 UPDATE mc_country
     SET COName = 'VI - VIRGIN ISLANDS, U.S.'
 WHERE COID = 238;
 UPDATE mc_country
     SET COName = 'WF - WALLIS AND FUTUNA'
 WHERE COID = 239;
 UPDATE mc_country
     SET COName = 'EH - WESTERN SAHARA'
 WHERE COID = 240;
 UPDATE mc_country
     SET COName = 'YE - YEMEN'
 WHERE COID = 241;
 UPDATE mc_country
     SET COName = 'ZM - ZAMBIA'
 WHERE COID = 242;
 UPDATE mc_country
     SET COName = 'ZW - ZIMBABWE'
 WHERE COID = 243;
 UPDATE mc_country
     SET COName = 'XK - KOSOVO'
 WHERE COID = 243;

/******************************************************************************/
/* (2) Data for contenttype specific settings                                 */
/*     - replace <site_id> by site id or 0 (all sites)                        */
/*     - replace <content_type_id> content type id or 0 (all content types)   */
/******************************************************************************/

INSERT INTO mc_country_contenttype (FK_COID, FK_SID, FK_CTID, COCName, COCPosition, COCActive) VALUES
( 1, '<site_id>', '<content_type_id>', 'AUSTRIA', 501, 0),
( 2, '<site_id>', '<content_type_id>', 'GERMANY', 502, 0),
( 3, '<site_id>', '<content_type_id>', 'SWITZERLAND', 503, 0),
( 4, '<site_id>', '<content_type_id>', 'FRANCE', 504, 0),
( 5, '<site_id>', '<content_type_id>', 'ITALY', 505, 0),
( 6, '<site_id>', '<content_type_id>', 'NETHERLANDS', 506, 0),
( 7, '<site_id>', '<content_type_id>', 'POLAND', 507, 0),
( 8, '<site_id>', '<content_type_id>', 'PORTUGAL', 508, 0),
( 9, '<site_id>', '<content_type_id>', 'AFGHANISTAN', 509, 0),
( 10, '<site_id>', '<content_type_id>', 'ALBANIA', 510, 0),
( 11, '<site_id>', '<content_type_id>', 'ALGERIA', 511, 0),
( 12, '<site_id>', '<content_type_id>', 'AMERICAN SAMOA', 512, 0),
( 13, '<site_id>', '<content_type_id>', 'ANDORRA', 513, 0),
( 14, '<site_id>', '<content_type_id>', 'ANGOLA', 514, 0),
( 15, '<site_id>', '<content_type_id>', 'ANGUILLA', 515, 0),
( 16, '<site_id>', '<content_type_id>', 'ANTARCTICA', 516, 0),
( 17, '<site_id>', '<content_type_id>', 'ANTIGUA AND BARBUDA', 517, 0),
( 18, '<site_id>', '<content_type_id>', 'ARGENTINA', 518, 0),
( 19, '<site_id>', '<content_type_id>', 'ARMENIA', 519, 0),
( 20, '<site_id>', '<content_type_id>', 'ARUBA', 520, 0),
( 21, '<site_id>', '<content_type_id>', 'AUSTRALIA', 521, 0),
( 22, '<site_id>', '<content_type_id>', 'AZERBAIJAN', 522, 0),
( 23, '<site_id>', '<content_type_id>', 'BAHAMAS', 523, 0),
( 24, '<site_id>', '<content_type_id>', 'BAHRAIN', 524, 0),
( 25, '<site_id>', '<content_type_id>', 'BANGLADESH', 525, 0),
( 26, '<site_id>', '<content_type_id>', 'BARBADOS', 526, 0),
( 27, '<site_id>', '<content_type_id>', 'BELARUS', 527, 0),
( 28, '<site_id>', '<content_type_id>', 'BELGIUM', 528, 0),
( 29, '<site_id>', '<content_type_id>', 'BELIZE', 529, 0),
( 30, '<site_id>', '<content_type_id>', 'BENIN', 530, 0),
( 31, '<site_id>', '<content_type_id>', 'BERMUDA', 531, 0),
( 32, '<site_id>', '<content_type_id>', 'BHUTAN', 532, 0),
( 33, '<site_id>', '<content_type_id>', 'BOLIVIA', 533, 0),
( 34, '<site_id>', '<content_type_id>', 'BOSNIA AND HERZEGOV.', 534, 0),
( 35, '<site_id>', '<content_type_id>', 'BOTSWANA', 535, 0),
( 36, '<site_id>', '<content_type_id>', 'BOUVET ISLAND', 536, 0),
( 37, '<site_id>', '<content_type_id>', 'BRAZIL', 537, 0),
( 38, '<site_id>', '<content_type_id>', 'BRITISH INDIAN OCEAN T.', 538, 0),
( 39, '<site_id>', '<content_type_id>', 'BRUNEI DARUSSALAM', 539, 0),
( 40, '<site_id>', '<content_type_id>', 'BULGARIA', 540, 0),
( 41, '<site_id>', '<content_type_id>', 'BURKINA FASO', 541, 0),
( 42, '<site_id>', '<content_type_id>', 'BURUNDI', 542, 0),
( 43, '<site_id>', '<content_type_id>', 'CAMBODIA', 543, 0),
( 44, '<site_id>', '<content_type_id>', 'CAMEROON', 544, 0),
( 45, '<site_id>', '<content_type_id>', 'CANADA', 545, 0),
( 46, '<site_id>', '<content_type_id>', 'CAPE VERDE', 546, 0),
( 47, '<site_id>', '<content_type_id>', 'CAYMAN ISLANDS', 547, 0),
( 48, '<site_id>', '<content_type_id>', 'CENTRAL AFRICAN REP.', 548, 0),
( 49, '<site_id>', '<content_type_id>', 'CHAD', 549, 0),
( 50, '<site_id>', '<content_type_id>', 'CHILE', 550, 0),
( 51, '<site_id>', '<content_type_id>', 'CHINA', 551, 0),
( 52, '<site_id>', '<content_type_id>', 'CHRISTMAS ISLAND', 552, 0),
( 53, '<site_id>', '<content_type_id>', 'COCOS (K.) ISLANDS', 553, 0),
( 54, '<site_id>', '<content_type_id>', 'COLOMBIA', 554, 0),
( 55, '<site_id>', '<content_type_id>', 'COMOROS', 555, 0),
( 56, '<site_id>', '<content_type_id>', 'CONGO', 556, 0),
( 57, '<site_id>', '<content_type_id>', 'CONGO, THE DEM. REP.', 557, 0),
( 58, '<site_id>', '<content_type_id>', 'COOK ISLANDS', 558, 0),
( 59, '<site_id>', '<content_type_id>', 'COSTA RICA', 559, 0),
( 60, '<site_id>', '<content_type_id>', 'COTE D\'IVOIRE', 560, 0),
( 61, '<site_id>', '<content_type_id>', 'CROATIA', 561, 0),
( 62, '<site_id>', '<content_type_id>', 'CUBA', 562, 0),
( 63, '<site_id>', '<content_type_id>', 'CYPRUS', 563, 0),
( 64, '<site_id>', '<content_type_id>', 'CZECH REPUBLIC', 564, 0),
( 65, '<site_id>', '<content_type_id>', 'DENMARK', 565, 0),
( 66, '<site_id>', '<content_type_id>', 'DJIBOUTI', 566, 0),
( 67, '<site_id>', '<content_type_id>', 'DOMINICA', 567, 0),
( 68, '<site_id>', '<content_type_id>', 'DOMINICAN REPUBLIC', 568, 0),
( 69, '<site_id>', '<content_type_id>', 'ECUADOR', 569, 0),
( 70, '<site_id>', '<content_type_id>', 'EGYPT', 570, 0),
( 71, '<site_id>', '<content_type_id>', 'EL SALVADOR', 571, 0),
( 72, '<site_id>', '<content_type_id>', 'EQUATORIAL GUINEA', 572, 0),
( 73, '<site_id>', '<content_type_id>', 'ERITREA', 573, 0),
( 74, '<site_id>', '<content_type_id>', 'ESTONIA', 574, 0),
( 75, '<site_id>', '<content_type_id>', 'ETHIOPIA', 575, 0),
( 76, '<site_id>', '<content_type_id>', 'FALKLAND ISLANDS', 576, 0),
( 77, '<site_id>', '<content_type_id>', 'FAROE ISLANDS', 577, 0),
( 78, '<site_id>', '<content_type_id>', 'FIJI', 578, 0),
( 79, '<site_id>', '<content_type_id>', 'FINLAND', 579, 0),
( 80, '<site_id>', '<content_type_id>', 'FRENCH GUIANA', 580, 0),
( 81, '<site_id>', '<content_type_id>', 'FRENCH POLYNESIA', 581, 0),
( 82, '<site_id>', '<content_type_id>', 'FRENCH SOUTHERN T.', 582, 0),
( 83, '<site_id>', '<content_type_id>', 'GABON', 583, 0),
( 84, '<site_id>', '<content_type_id>', 'GAMBIA', 584, 0),
( 85, '<site_id>', '<content_type_id>', 'GEORGIA', 585, 0),
( 86, '<site_id>', '<content_type_id>', 'GHANA', 586, 0),
( 87, '<site_id>', '<content_type_id>', 'GIBRALTAR', 587, 0),
( 88, '<site_id>', '<content_type_id>', 'GREECE', 588, 0),
( 89, '<site_id>', '<content_type_id>', 'GREENLAND', 589, 0),
( 90, '<site_id>', '<content_type_id>', 'GRENADA', 590, 0),
( 91, '<site_id>', '<content_type_id>', 'GUADELOUPE', 591, 0),
( 92, '<site_id>', '<content_type_id>', 'GUAM', 592, 0),
( 93, '<site_id>', '<content_type_id>', 'GUATEMALA', 593, 0),
( 94, '<site_id>', '<content_type_id>', 'GUERNSEY', 594, 0),
( 95, '<site_id>', '<content_type_id>', 'GUINEA', 595, 0),
( 96, '<site_id>', '<content_type_id>', 'GUINEA', 596, 0),
( 97, '<site_id>', '<content_type_id>', 'GUYANA', 597, 0),
( 98, '<site_id>', '<content_type_id>', 'HAITI', 598, 0),
( 99, '<site_id>', '<content_type_id>', 'HEARD ISLAND', 599, 0),
( 100, '<site_id>', '<content_type_id>', 'HOLY SEE', 600, 0),
( 101, '<site_id>', '<content_type_id>', 'HONDURAS', 601, 0),
( 102, '<site_id>', '<content_type_id>', 'HONG KONG', 602, 0),
( 103, '<site_id>', '<content_type_id>', 'HUNGARY', 603, 0),
( 104, '<site_id>', '<content_type_id>', 'ICELAND', 604, 0),
( 105, '<site_id>', '<content_type_id>', 'INDIA', 605, 0),
( 106, '<site_id>', '<content_type_id>', 'INDONESIA', 606, 0),
( 107, '<site_id>', '<content_type_id>', 'IRAN, ISLAMIC REP.', 607, 0),
( 108, '<site_id>', '<content_type_id>', 'IRAQ', 608, 0),
( 109, '<site_id>', '<content_type_id>', 'IRELAND', 609, 0),
( 110, '<site_id>', '<content_type_id>', 'ISLE OF MAN', 610, 0),
( 111, '<site_id>', '<content_type_id>', 'ISRAEL', 611, 0),
( 112, '<site_id>', '<content_type_id>', 'JAMAICA', 612, 0),
( 113, '<site_id>', '<content_type_id>', 'JAPAN', 613, 0),
( 114, '<site_id>', '<content_type_id>', 'JERSEY', 614, 0),
( 115, '<site_id>', '<content_type_id>', 'JORDAN', 615, 0),
( 116, '<site_id>', '<content_type_id>', 'KAZAKHSTAN', 616, 0),
( 117, '<site_id>', '<content_type_id>', 'KENYA', 617, 0),
( 118, '<site_id>', '<content_type_id>', 'KIRIBATI', 618, 0),
( 119, '<site_id>', '<content_type_id>', 'KOREA, DEM. PEO. REP.', 619, 0),
( 120, '<site_id>', '<content_type_id>', 'KOREA, REPUBLIC OF', 620, 0),
( 121, '<site_id>', '<content_type_id>', 'KUWAIT', 621, 0),
( 122, '<site_id>', '<content_type_id>', 'KYRGYZSTAN', 622, 0),
( 123, '<site_id>', '<content_type_id>', 'LAO PEOPLE\'S DEM. REP.', 623, 0),
( 124, '<site_id>', '<content_type_id>', 'LATVIA', 624, 0),
( 125, '<site_id>', '<content_type_id>', 'LEBANON', 625, 0),
( 126, '<site_id>', '<content_type_id>', 'LESOTHO', 626, 0),
( 127, '<site_id>', '<content_type_id>', 'LIBERIA', 627, 0),
( 128, '<site_id>', '<content_type_id>', 'LIBYAN ARAB JAM.', 628, 0),
( 129, '<site_id>', '<content_type_id>', 'LIECHTENSTEIN', 629, 0),
( 130, '<site_id>', '<content_type_id>', 'LITHUANIA', 630, 0),
( 131, '<site_id>', '<content_type_id>', 'LUXEMBOURG', 631, 0),
( 132, '<site_id>', '<content_type_id>', 'MACAO', 632, 0),
( 133, '<site_id>', '<content_type_id>', 'MACEDONIA.', 633, 0),
( 134, '<site_id>', '<content_type_id>', 'MADAGASCAR', 634, 0),
( 135, '<site_id>', '<content_type_id>', 'MALAWI', 635, 0),
( 136, '<site_id>', '<content_type_id>', 'MALAYSIA', 636, 0),
( 137, '<site_id>', '<content_type_id>', 'MALDIVES', 637, 0),
( 138, '<site_id>', '<content_type_id>', 'MALI', 638, 0),
( 139, '<site_id>', '<content_type_id>', 'MALTA', 639, 0),
( 140, '<site_id>', '<content_type_id>', 'MARSHALL ISLANDS', 640, 0),
( 141, '<site_id>', '<content_type_id>', 'MARTINIQUE', 641, 0),
( 142, '<site_id>', '<content_type_id>', 'MAURITANIA', 642, 0),
( 143, '<site_id>', '<content_type_id>', 'MAURITIUS', 643, 0),
( 144, '<site_id>', '<content_type_id>', 'MAYOTTE', 644, 0),
( 145, '<site_id>', '<content_type_id>', 'MEXICO', 645, 0),
( 146, '<site_id>', '<content_type_id>', 'MICRONESIA', 646, 0),
( 147, '<site_id>', '<content_type_id>', 'MOLDOVA, REP.', 647, 0),
( 148, '<site_id>', '<content_type_id>', 'MONACO', 648, 0),
( 149, '<site_id>', '<content_type_id>', 'MONGOLIA', 649, 0),
( 150, '<site_id>', '<content_type_id>', 'MONTENEGRO', 650, 0),
( 151, '<site_id>', '<content_type_id>', 'MONTSERRAT', 651, 0),
( 152, '<site_id>', '<content_type_id>', 'MOROCCO', 652, 0),
( 153, '<site_id>', '<content_type_id>', 'MOZAMBIQUE', 653, 0),
( 154, '<site_id>', '<content_type_id>', 'MYANMAR', 654, 0),
( 155, '<site_id>', '<content_type_id>', 'NAMIBIA', 655, 0),
( 156, '<site_id>', '<content_type_id>', 'NAURU', 656, 0),
( 157, '<site_id>', '<content_type_id>', 'NEPAL', 657, 0),
( 158, '<site_id>', '<content_type_id>', 'NETHERLANDS ANT.', 658, 0),
( 159, '<site_id>', '<content_type_id>', 'NEW CALEDONIA', 659, 0),
( 160, '<site_id>', '<content_type_id>', 'NEW ZEALAND', 660, 0),
( 161, '<site_id>', '<content_type_id>', 'NICARAGUA', 661, 0),
( 162, '<site_id>', '<content_type_id>', 'NIGER', 662, 0),
( 163, '<site_id>', '<content_type_id>', 'NIGERIA', 663, 0),
( 164, '<site_id>', '<content_type_id>', 'NIUE', 664, 0),
( 165, '<site_id>', '<content_type_id>', 'NORFOLK ISLAND', 665, 0),
( 166, '<site_id>', '<content_type_id>', 'NORTHERN MARIANA ISL.', 666, 0),
( 167, '<site_id>', '<content_type_id>', 'NORWAY', 667, 0),
( 168, '<site_id>', '<content_type_id>', 'OMAN', 668, 0),
( 169, '<site_id>', '<content_type_id>', 'PAKISTAN', 669, 0),
( 170, '<site_id>', '<content_type_id>', 'PALAU', 670, 0),
( 171, '<site_id>', '<content_type_id>', 'PALESTINIAN TERR.', 671, 0),
( 172, '<site_id>', '<content_type_id>', 'PANAMA', 672, 0),
( 173, '<site_id>', '<content_type_id>', 'PAPUA NEW GUINEA', 673, 0),
( 174, '<site_id>', '<content_type_id>', 'PARAGUAY', 674, 0),
( 175, '<site_id>', '<content_type_id>', 'PERU', 675, 0),
( 176, '<site_id>', '<content_type_id>', 'PHILIPPINES', 676, 0),
( 177, '<site_id>', '<content_type_id>', 'PITCAIRN', 677, 0),
( 178, '<site_id>', '<content_type_id>', 'PUERTO RICO', 678, 0),
( 179, '<site_id>', '<content_type_id>', 'QATAR', 679, 0),
( 180, '<site_id>', '<content_type_id>', 'REUNION', 680, 0),
( 181, '<site_id>', '<content_type_id>', 'ROMANIA', 681, 0),
( 182, '<site_id>', '<content_type_id>', 'RUSSIAN FEDERATION', 682, 0),
( 183, '<site_id>', '<content_type_id>', 'RWANDA', 683, 0),
( 184, '<site_id>', '<content_type_id>', 'SAINT HELENA', 684, 0),
( 185, '<site_id>', '<content_type_id>', 'SAINT KITTS AND NEVIS', 685, 0),
( 186, '<site_id>', '<content_type_id>', 'SAINT LUCIA', 686, 0),
( 187, '<site_id>', '<content_type_id>', 'SAINT PIERRE AND MIQU.', 687, 0),
( 188, '<site_id>', '<content_type_id>', 'SAINT VINCENT', 688, 0),
( 189, '<site_id>', '<content_type_id>', 'SAMOA', 689, 0),
( 190, '<site_id>', '<content_type_id>', 'SAN MARINO', 690, 0),
( 191, '<site_id>', '<content_type_id>', 'SAO TOME AND PRINCIPE', 691, 0),
( 192, '<site_id>', '<content_type_id>', 'SAUDI ARABIA', 692, 0),
( 193, '<site_id>', '<content_type_id>', 'SENEGAL', 693, 0),
( 194, '<site_id>', '<content_type_id>', 'SERBIA', 694, 0),
( 195, '<site_id>', '<content_type_id>', 'SEYCHELLES', 695, 0),
( 196, '<site_id>', '<content_type_id>', 'SIERRA LEONE', 696, 0),
( 197, '<site_id>', '<content_type_id>', 'SINGAPORE', 697, 0),
( 198, '<site_id>', '<content_type_id>', 'SLOVAKIA', 698, 0),
( 199, '<site_id>', '<content_type_id>', 'SLOVENIA', 699, 0),
( 200, '<site_id>', '<content_type_id>', 'SOLOMON ISLANDS', 700, 0),
( 201, '<site_id>', '<content_type_id>', 'SOMALIA', 701, 0),
( 202, '<site_id>', '<content_type_id>', 'SOUTH AFRICA', 702, 0),
( 203, '<site_id>', '<content_type_id>', 'SOUTH GEORGIA', 703, 0),
( 204, '<site_id>', '<content_type_id>', 'SPAIN', 704, 0),
( 205, '<site_id>', '<content_type_id>', 'SRI LANKA', 705, 0),
( 206, '<site_id>', '<content_type_id>', 'SUDAN', 706, 0),
( 207, '<site_id>', '<content_type_id>', 'SURINAME', 707, 0),
( 208, '<site_id>', '<content_type_id>', 'SVALBARD.', 708, 0),
( 209, '<site_id>', '<content_type_id>', 'SWAZILAND', 709, 0),
( 210, '<site_id>', '<content_type_id>', 'SWEDEN', 710, 0),
( 211, '<site_id>', '<content_type_id>', 'SYRIAN ARAB REP.', 711, 0),
( 212, '<site_id>', '<content_type_id>', 'TAIWAN, PROV.OF CHINA', 712, 0),
( 213, '<site_id>', '<content_type_id>', 'TAJIKISTAN', 713, 0),
( 214, '<site_id>', '<content_type_id>', 'TANZANIA, UN. REP.', 714, 0),
( 215, '<site_id>', '<content_type_id>', 'THAILAND', 715, 0),
( 216, '<site_id>', '<content_type_id>', 'TIMOR', 716, 0),
( 217, '<site_id>', '<content_type_id>', 'TOGO', 717, 0),
( 218, '<site_id>', '<content_type_id>', 'TOKELAU', 718, 0),
( 219, '<site_id>', '<content_type_id>', 'TONGA', 719, 0),
( 220, '<site_id>', '<content_type_id>', 'TRINIDAD AND TOBAGO', 720, 0),
( 221, '<site_id>', '<content_type_id>', 'TUNISIA', 721, 0),
( 222, '<site_id>', '<content_type_id>', 'TURKEY', 722, 0),
( 223, '<site_id>', '<content_type_id>', 'TURKMENISTAN', 723, 0),
( 224, '<site_id>', '<content_type_id>', 'TURKS', 724, 0),
( 225, '<site_id>', '<content_type_id>', 'TUVALU', 725, 0),
( 226, '<site_id>', '<content_type_id>', 'UGANDA', 726, 0),
( 227, '<site_id>', '<content_type_id>', 'UKRAINE', 727, 0),
( 228, '<site_id>', '<content_type_id>', 'UNITED ARAB EMIRATES', 728, 0),
( 229, '<site_id>', '<content_type_id>', 'UNITED KINGDOM', 729, 0),
( 230, '<site_id>', '<content_type_id>', 'UNITED STATES', 730, 0),
( 231, '<site_id>', '<content_type_id>', 'UNITED STATES', 731, 0),
( 232, '<site_id>', '<content_type_id>', 'URUGUAY', 732, 0),
( 233, '<site_id>', '<content_type_id>', 'UZBEKISTAN', 733, 0),
( 234, '<site_id>', '<content_type_id>', 'VANUATU', 734, 0),
( 235, '<site_id>', '<content_type_id>', 'VENEZUELA', 735, 0),
( 236, '<site_id>', '<content_type_id>', 'VIET NAM', 736, 0),
( 237, '<site_id>', '<content_type_id>', 'VIRGIN ISLANDS, BRIT.', 737, 0),
( 238, '<site_id>', '<content_type_id>', 'VIRGIN ISLANDS, U.S.', 738, 0),
( 239, '<site_id>', '<content_type_id>', 'WALLIS AND FUTUNA', 739, 0),
( 240, '<site_id>', '<content_type_id>', 'WESTERN SAHARA', 740, 0),
( 241, '<site_id>', '<content_type_id>', 'YEMEN', 741, 0),
( 242, '<site_id>', '<content_type_id>', 'ZAMBIA', 742, 0),
( 243, '<site_id>', '<content_type_id>', 'ZIMBABWE', 743, 0),
( 244, '<site_id>', '<content_type_id>', 'KOSOVO', 744, 0);

/******************************************************************************/
/* Insert country table for specific languages                                */
/* All countries are deactivated:                                             */
/* when using this insert script, activate the required countries             */
/******************************************************************************/

/* German uppercase */

INSERT INTO MC_COUNTRY (COID, CONAME, COSYMBOL, COCODE, COPOSITION, COACTIVE) VALUES
(1, 'ÖSTERREICH', 'AT', 43, 501, 0),
(2, 'DEUTSCHLAND', 'DE', 49, 502, 0),
(3, 'SCHWEIZ', 'CH', 41, 503, 0),
(4, 'FRANKREICH', 'FR', 33, 504, 0),
(5, 'ITALIEN', 'IT', 39, 505, 0),
(6, 'NIEDERLANDE', 'NL', 31, 506, 0),
(7, 'POLEN', 'PL', 48, 507, 0),
(8, 'PORTUGAL', 'PT', 351, 508, 0),
(9, 'AFGHANISTAN', 'AF', 93, 509, 0),
(10, 'ALBANIEN', 'AL', 355, 510, 0),
(11, 'ALGERIEN', 'DZ', 213, 511, 0),
(12, 'AMERIKANISCH-SAMOA', 'AS', 1, 512, 0),
(13, 'ANDORRA', 'AD', 376, 513, 0),
(14, 'ANGOLA', 'AO', 244, 514, 0),
(15, 'ANGUILLA', 'AI', 1, 515, 0),
(16, 'ANTARKTIS', 'AQ', 672, 516, 0),
(17, 'ANTIGUA UND BARBUDA', 'AG', 1, 517, 0),
(18, 'ARGENTINIEN', 'AR', 54, 518, 0),
(19, 'ARMENIEN', 'AM', 374, 519, 0),
(20, 'ARUBA', 'AW', 297, 520, 0),
(21, 'AUSTRALIEN', 'AU', 61, 521, 0),
(22, 'ASERBAIDSCHAN', 'AZ', 994, 522, 0),
(23, 'BAHAMAS', 'BS', 1, 523, 0),
(24, 'BAHRAIN', 'BH', 973, 524, 0),
(25, 'BANGLADESCH', 'BD', 880, 525, 0),
(26, 'BARBADOS', 'BB', 1, 526, 0),
(27, 'WEISSRUSSLAND', 'BY', 375, 527, 0),
(28, 'BELGIEN', 'BE', 32, 528, 0),
(29, 'BELIZE', 'BZ', 501, 529, 0),
(30, 'BENIN', 'BJ', 229, 530, 0),
(31, 'BERMUDA', 'BM', 1, 531, 0),
(32, 'BHUTAN', 'BT', 975, 532, 0),
(33, 'BOLIVIEN', 'BO', 591, 533, 0),
(34, 'BOSNIEN UND HERZEGOWINA', 'BA', 387, 534, 0),
(35, 'BOTSWANA', 'BW', 267, 535, 0),
(36, 'BOUVETINSEL', 'BV', 0, 536, 0),
(37, 'BRASILIEN', 'BR', 55, 537, 0),
(38, 'BRITISCHES TERRITORIUM IM INDISCHEN OZEAN', 'IO', 246, 538, 0),
(39, 'BRUNEI DARUSSALAM', 'BN', 673, 539, 0),
(40, 'BULGARIEN', 'BG', 359, 540, 0),
(41, 'BURKINA FASO', 'BF', 226, 541, 0),
(42, 'BURUNDI', 'BI', 257, 542, 0),
(43, 'KAMBODSCHA', 'KH', 855, 543, 0),
(44, 'KAMERUN', 'CM', 237, 544, 0),
(45, 'CANADA', 'CA', 1, 545, 0),
(46, 'KAP VERDE', 'CV', 238, 546, 0),
(47, 'KAIMANINSELN', 'KY', 1, 547, 0),
(48, 'ZENTRALAFRIKANISCHE REPUBLIK', 'CF', 236, 548, 0),
(49, 'TSCHAD', 'TD', 235, 549, 0),
(50, 'CHILE', 'CL', 56, 550, 0),
(51, 'CHINA', 'CN', 86, 551, 0),
(52, 'WEIHNACHTSINSEL', 'CX', 61, 552, 0),
(53, 'KOKOSINSEL (KEELING)', 'CC', 61, 553, 0),
(54, 'KOLUMBIEN', 'CO', 57, 554, 0),
(55, 'KOMOREN', 'KM', 269, 555, 0),
(56, 'KONGO', 'CG', 242, 556, 0),
(57, 'DEM. REP. KONGO', 'CD', 243, 557, 0),
(58, 'COOKINSELN', 'CK', 682, 558, 0),
(59, 'COSTA RICA', 'CR', 506, 559, 0),
(60, 'ELFENBEINKÜSTE', 'CI', 225, 560, 0),
(61, 'KROATIEN', 'HR', 385, 561, 0),
(62, 'KUBA', 'CU', 53, 562, 0),
(63, 'ZYPERN', 'CY', 357, 563, 0),
(64, 'TSCHECHIEN', 'CZ', 420, 564, 0),
(65, 'DÄNEMARK', 'DK', 45, 565, 0),
(66, 'DSCHIBUTI', 'DJ', 253, 566, 0),
(67, 'DOMINICA', 'DM', 1, 567, 0),
(68, 'DOMINIKANISCHE REPUBLIK', 'DO', 1, 568, 0),
(69, 'ECUADOR', 'EC', 593, 569, 0),
(70, 'ÄGYPTEN', 'EG', 20, 570, 0),
(71, 'EL SALVADOR', 'SV', 503, 571, 0),
(72, 'ÄQUATORIALGUINEA', 'GQ', 240, 572, 0),
(73, 'ERITREA', 'ER', 291, 573, 0),
(74, 'ESTLAND', 'EE', 372, 574, 0),
(75, 'ÄTHIOPIEN', 'ET', 251, 575, 0),
(76, 'FALKLANDINSELN', 'FK', 500, 576, 0),
(77, 'FÄRÖER', 'FO', 298, 577, 0),
(78, 'FIDSCHI', 'FJ', 679, 578, 0),
(79, 'FINNLAND', 'FI', 358, 579, 0),
(80, 'FRANZÖSISCH-GUAYANA', 'GF', 594, 580, 0),
(81, 'FRANZÖSISCH-POLYNESIEN', 'PF', 689, 581, 0),
(82, 'FRANZÖSISCHE SÜD- UND ANTARKTISGEBIETE', 'TF', 0, 582, 0),
(83, 'GABUN', 'GA', 241, 583, 0),
(84, 'GAMBIA', 'GM', 220, 584, 0),
(85, 'GEORGIEN', 'GE', 995, 585, 0),
(86, 'GHANA', 'GH', 233, 586, 0),
(87, 'GIBRALTAR', 'GI', 350, 587, 0),
(88, 'GRIECHENLAND', 'GR', 30, 588, 0),
(89, 'GRÖNLAND', 'GL', 299, 589, 0),
(90, 'GRENADA', 'GD', 1, 590, 0),
(91, 'GUADELOUPE', 'GP', 590, 591, 0),
(92, 'GUAM', 'GU', 1, 592, 0),
(93, 'GUATEMALA', 'GT', 502, 593, 0),
(94, 'GUERNSEY', 'GG', 44, 594, 0),
(95, 'GUINEA', 'GN', 224, 595, 0),
(96, 'GUINEA BISSAU', 'GW', 245, 596, 0),
(97, 'GUYANA', 'GY', 592, 597, 0),
(98, 'HAITI', 'HT', 509, 598, 0),
(99, 'HEARD UND MCDONALDINSELN', 'HM', 0, 599, 0),
(100, 'VATIKANSTADT', 'VA', 379, 600, 0),
(101, 'HONDURAS', 'HN', 504, 601, 0),
(102, 'HONGKONG', 'HK', 852, 602, 0),
(103, 'UNGARN', 'HU', 36, 603, 0),
(104, 'ISLAND', 'IS', 354, 604, 0),
(105, 'INDIEN', 'IN', 91, 605, 0),
(106, 'INDONESIEN', 'ID', 62, 606, 0),
(107, 'IRAN, ISLAMIC REP.', 'IR', 98, 607, 0),
(108, 'IRAK', 'IQ', 964, 608, 0),
(109, 'IRLAND', 'IE', 353, 609, 0),
(110, 'ISLE OF MAN', 'IM', 44, 610, 0),
(111, 'ISRAEL', 'IL', 972, 611, 0),
(112, 'JAMAIKA', 'JM', 1, 612, 0),
(113, 'JAPAN', 'JP', 81, 613, 0),
(114, 'JERSEY', 'JE', 44, 614, 0),
(115, 'JORDAN', 'JO', 962, 615, 0),
(116, 'KASACHSTAN', 'KZ', 7, 616, 0),
(117, 'KENIA', 'KE', 254, 617, 0),
(118, 'KIRIBATI', 'KI', 686, 618, 0),
(119, 'NORDKOREA', 'KP', 850, 619, 0),
(120, 'SÜD KOREA', 'KR', 82, 620, 0),
(121, 'KUWAIT', 'KW', 965, 621, 0),
(122, 'KIRGISISTAN', 'KG', 996, 622, 0),
(123, 'LAOS', 'LA', 856, 623, 0),
(124, 'LETTLAND', 'LV', 371, 624, 0),
(125, 'LIBANON', 'LB', 961, 625, 0),
(126, 'LESOTHO', 'LS', 266, 626, 0),
(127, 'LIBERIA', 'LR', 231, 627, 0),
(128, 'LIBYEN', 'LY', 218, 628, 0),
(129, 'LIECHTENSTEIN', 'LI', 423, 629, 0),
(130, 'LITAUEN', 'LT', 370, 630, 0),
(131, 'LUXEMBURG', 'LU', 352, 631, 0),
(132, 'MACAO', 'MO', 853, 632, 0),
(133, 'MAZEDONIEN', 'MK', 389, 633, 0),
(134, 'MADAGASKAR', 'MG', 261, 634, 0),
(135, 'MALAWI', 'MW', 265, 635, 0),
(136, 'MALAYSIA', 'MY', 60, 636, 0),
(137, 'MALEDIVEN', 'MV', 960, 637, 0),
(138, 'MALI', 'ML', 223, 638, 0),
(139, 'MALTA', 'MT', 356, 639, 0),
(140, 'MARSHALLINSELN', 'MH', 692, 640, 0),
(141, 'MARTINIQUE', 'MQ', 0, 641, 0),
(142, 'MAURETANIEN', 'MR', 222, 642, 0),
(143, 'MAURITIUS', 'MU', 230, 643, 0),
(144, 'MAYOTTE', 'YT', 262, 644, 0),
(145, 'MEXIKO', 'MX', 52, 645, 0),
(146, 'MIKRONESIEN', 'FM', 691, 646, 0),
(147, 'MOLDAWIEN, REP.', 'MD', 373, 647, 0),
(148, 'MONACO', 'MC', 377, 648, 0),
(149, 'MONGOLEI', 'MN', 976, 649, 0),
(150, 'MONTENEGRO', 'ME', 382, 650, 0),
(151, 'MONTSERRAT', 'MS', 1, 651, 0),
(152, 'MAROKKO', 'MA', 212, 652, 0),
(153, 'MOSAMBIK', 'MZ', 258, 653, 0),
(154, 'MYANMAR', 'MM', 95, 654, 0),
(155, 'NAMIBIA', 'NA', 264, 655, 0),
(156, 'NAURU', 'NR', 674, 656, 0),
(157, 'NEPAL', 'NP', 977, 657, 0),
(158, 'NIEDERLÄNDISCHE ANTILLEN', 'AN', 599, 658, 0),
(159, 'NEUKALEDONIEN', 'NC', 687, 659, 0),
(160, 'NEUSEELAND', 'NZ', 64, 660, 0),
(161, 'NICARAGUA', 'NI', 505, 661, 0),
(162, 'NIGER', 'NE', 227, 662, 0),
(163, 'NIGERIA', 'NG', 234, 663, 0),
(164, 'NIUE', 'NU', 683, 664, 0),
(165, 'NORFOLKINSEL', 'NF', 672, 665, 0),
(166, 'NÖRDLICHE MARIANEN', 'MP', 1, 666, 0),
(167, 'NORWEGEN', 'NO', 47, 667, 0),
(168, 'OMAN', 'OM', 968, 668, 0),
(169, 'PAKISTAN', 'PK', 92, 669, 0),
(170, 'PALAU', 'PW', 680, 670, 0),
(171, 'PALÄSTINA', 'PS', 970, 671, 0),
(172, 'PANAMA', 'PA', 507, 672, 0),
(173, 'PAPUA-NEUGUINEA', 'PG', 675, 673, 0),
(174, 'PARAGUAY', 'PY', 595, 674, 0),
(175, 'PERU', 'PE', 51, 675, 0),
(176, 'PHILIPPINEN', 'PH', 63, 676, 0),
(177, 'PITCAIRNINSELN', 'PN', 64, 677, 0),
(178, 'PUERTO RICO', 'PR', 1, 678, 0),
(179, 'KATAR', 'QA', 974, 679, 0),
(180, 'RÉUNION', 'RE', 0, 680, 0),
(181, 'RUMÄNIEN', 'RO', 40, 681, 0),
(182, 'RUSSLAND', 'RU', 7, 682, 0),
(183, 'RUANDA', 'RW', 250, 683, 0),
(184, 'ST. HELENA', 'SH', 290, 684, 0),
(185, 'ST. KITTS UND NEVIS', 'KN', 1, 685, 0),
(186, 'ST. LUCIA', 'LC', 1, 686, 0),
(187, 'SAINT-PIERRE UND MIQUELON', 'PM', 508, 687, 0),
(188, 'SAINT-VINCENT', 'VC', 1, 688, 0),
(189, 'SAMOA', 'WS', 685, 689, 0),
(190, 'SAN MARINO', 'SM', 378, 690, 0),
(191, 'SÃO TOMÉ UND PRÍNCIPE', 'ST', 239, 691, 0),
(192, 'SAUDI-ARABIEN', 'SA', 966, 692, 0),
(193, 'SENEGAL', 'SN', 221, 693, 0),
(194, 'SERBIEN', 'RS', 381, 694, 0),
(195, 'SEYCHELLEN', 'SC', 248, 695, 0),
(196, 'SIERRA LEONE', 'SL', 232, 696, 0),
(197, 'SINGAPUR', 'SG', 65, 697, 0),
(198, 'SLOWAKEI', 'SK', 421, 698, 0),
(199, 'SLOWENIEN', 'SI', 386, 699, 0),
(200, 'SALOMONEN', 'SB', 677, 700, 0),
(201, 'SOMALIA', 'SO', 252, 701, 0),
(202, 'SÜDAFRIKA', 'ZA', 27, 702, 0),
(203, 'SÜDGEORGIEN UND DIE SÜDLICHEN SANDWICHINSELN', 'GS', 500, 703, 0),
(204, 'SPANIEN', 'ES', 34, 704, 0),
(205, 'SRI LANKA', 'LK', 94, 705, 0),
(206, 'SUDAN', 'SD', 249, 706, 0),
(207, 'SURINAME', 'SR', 597, 707, 0),
(208, 'SVALBARD UND JAN MAYEN', 'SJ', 4779, 708, 0),
(209, 'SWASILAND', 'SZ', 268, 709, 0),
(210, 'SCHWEDEN', 'SE', 46, 710, 0),
(211, 'SYRIEN', 'SY', 963, 711, 0),
(212, 'TAIWAN', 'TW', 886, 712, 0),
(213, 'TADSCHIKISTAN', 'TJ', 992, 713, 0),
(214, 'TANSANIA', 'TZ', 255, 714, 0),
(215, 'THAILAND', 'TH', 66, 715, 0),
(216, 'TIMOR', 'TL', 670, 716, 0),
(217, 'TOGO', 'TG', 228, 717, 0),
(218, 'TOKELAU', 'TK', 690, 718, 0),
(219, 'TONGA', 'TO', 676, 719, 0),
(220, 'TRINIDAD UND TOBAGO', 'TT', 1, 720, 0),
(221, 'TUNESIEN', 'TN', 216, 721, 0),
(222, 'TÜRKEI', 'TR', 90, 722, 0),
(223, 'TURKMENISTAN', 'TM', 993, 723, 0),
(224, 'TURKS- UND CAICOSINSELN', 'TC', 1, 724, 0),
(225, 'TUVALU', 'TV', 688, 725, 0),
(226, 'UGANDA', 'UG', 256, 726, 0),
(227, 'UKRAINE', 'UA', 380, 727, 0),
(228, 'VEREINIGTE ARABISCHE EMIRATE', 'AE', 971, 728, 0),
(229, 'GROSSBRITANNIEN', 'GB', 44, 729, 0),
(230, 'USA', 'US', 1, 730, 0),
(231, 'USA-INSELN', 'UM', 0, 731, 0),
(232, 'URUGUAY', 'UY', 598, 732, 0),
(233, 'USBEKISTAN', 'UZ', 998, 733, 0),
(234, 'VANUATU', 'VU', 678, 734, 0),
(235, 'VENEZUELA', 'VE', 58, 735, 0),
(236, 'VIETNAM', 'VN', 84, 736, 0),
(237, 'VIRGIN ISLANDS (BRITISH)', 'VG', 1, 737, 0),
(238, 'VIRGIN ISLANDS (USA)', 'VI', 1, 738, 0),
(239, 'WALLIS UND FUTUNA', 'WF', 681, 739, 0),
(240, 'WESTSAHARA', 'EH', 212, 740, 0),
(241, 'JEMEN', 'YE', 967, 741, 0),
(242, 'SAMBIA', 'ZM', 260, 742, 0),
(243, 'SIMBABWE', 'ZW', 263, 743, 0),
(244, 'KOSOVO', 'XK', 381, 744, 0);

/* German */

INSERT INTO mc_country (COID, COName, COSymbol, COCode, COPosition, COActive) VALUES
(1, 'Österreich', 'AT', 43, 501, 0),
(2, 'Deutschland', 'DE', 49, 502, 0),
(3, 'Schweiz', 'CH', 41, 503, 0),
(4, 'Frankreich', 'FR', 33, 504, 0),
(5, 'Italien', 'IT', 39, 505, 0),
(6, 'Niederlande', 'NL', 31, 506, 0),
(7, 'Polen', 'PL', 48, 507, 0),
(8, 'Portugal', 'PT', 351, 508, 0),
(9, 'Afghanistan', 'AF', 93, 509, 0),
(10, 'Albanien', 'AL', 355, 510, 0),
(11, 'Algerien', 'DZ', 213, 511, 0),
(12, 'Amerikanisch-Samoa', 'AS', 1, 512, 0),
(13, 'Andorra', 'AD', 376, 513, 0),
(14, 'Angola', 'AO', 244, 514, 0),
(15, 'Anguilla', 'AI', 1, 515, 0),
(16, 'Antarktis', 'AQ', 672, 516, 0),
(17, 'Antigua und Barbuda', 'AG', 1, 517, 0),
(18, 'Argentinien', 'AR', 54, 518, 0),
(19, 'Armenien', 'AM', 374, 519, 0),
(20, 'Aruba', 'AW', 297, 520, 0),
(21, 'Australien', 'AU', 61, 521, 0),
(22, 'Aserbaidschan', 'AZ', 994, 522, 0),
(23, 'Bahamas', 'BS', 1, 523, 0),
(24, 'Bahrain', 'BH', 973, 524, 0),
(25, 'Bangladesch', 'BD', 880, 525, 0),
(26, 'Barbados', 'BB', 1, 526, 0),
(27, 'Weißrussland', 'BY', 375, 527, 0),
(28, 'Belgien', 'BE', 32, 528, 0),
(29, 'Belize', 'BZ', 501, 529, 0),
(30, 'Benin', 'BJ', 229, 530, 0),
(31, 'Bermuda', 'BM', 1, 531, 0),
(32, 'Bhutan', 'BT', 975, 532, 0),
(33, 'Bolivien', 'BO', 591, 533, 0),
(34, 'Bosnien und Herzegowina', 'BA', 387, 534, 0),
(35, 'Botswana', 'BW', 267, 535, 0),
(36, 'Bouvetinsel', 'BV', 0, 536, 0),
(37, 'Brasilien', 'BR', 55, 537, 0),
(38, 'Britisches Territorium im Indischen Ozean', 'IO', 246, 538, 0),
(39, 'Brunei Darussalam', 'BN', 673, 539, 0),
(40, 'Bulgarien', 'BG', 359, 540, 0),
(41, 'Burkina Faso', 'BF', 226, 541, 0),
(42, 'Burundi', 'BI', 257, 542, 0),
(43, 'Kambodscha', 'KH', 855, 543, 0),
(44, 'Kamerun', 'CM', 237, 544, 0),
(45, 'Canada', 'CA', 1, 545, 0),
(46, 'Kap Verde', 'CV', 238, 546, 0),
(47, 'Kaimaninseln', 'KY', 1, 547, 0),
(48, 'Zentralafrikanische Republik', 'CF', 236, 548, 0),
(49, 'Tschad', 'TD', 235, 549, 0),
(50, 'Chile', 'CL', 56, 550, 0),
(51, 'China', 'CN', 86, 551, 0),
(52, 'Weihnachtsinsel', 'CX', 61, 552, 0),
(53, 'Kokosinsel (Keeling)', 'CC', 61, 553, 0),
(54, 'Kolumbien', 'CO', 57, 554, 0),
(55, 'Komoren', 'KM', 269, 555, 0),
(56, 'Kongo', 'CG', 242, 556, 0),
(57, 'Dem. Rep. Kongo', 'CD', 243, 557, 0),
(58, 'Cookinseln', 'CK', 682, 558, 0),
(59, 'Costa Rica', 'CR', 506, 559, 0),
(60, 'Elfenbeinküste', 'CI', 225, 560, 0),
(61, 'Kroatien', 'HR', 385, 561, 0),
(62, 'Kuba', 'CU', 53, 562, 0),
(63, 'Zypern', 'CY', 357, 563, 0),
(64, 'Tschechien', 'CZ', 420, 564, 0),
(65, 'Dänemark', 'DK', 45, 565, 0),
(66, 'Dschibuti', 'DJ', 253, 566, 0),
(67, 'Dominica', 'DM', 1, 567, 0),
(68, 'Dominikanische Republik', 'DO', 1, 568, 0),
(69, 'Ecuador', 'EC', 593, 569, 0),
(70, 'Ägypten', 'EG', 20, 570, 0),
(71, 'El Salvador', 'SV', 503, 571, 0),
(72, 'Äquatorialguinea', 'GQ', 240, 572, 0),
(73, 'Eritrea', 'ER', 291, 573, 0),
(74, 'Estland', 'EE', 372, 574, 0),
(75, 'Äthiopien', 'ET', 251, 575, 0),
(76, 'Falklandinseln', 'FK', 500, 576, 0),
(77, 'Färöer', 'FO', 298, 577, 0),
(78, 'Fidschi', 'FJ', 679, 578, 0),
(79, 'Finnland', 'FI', 358, 579, 0),
(80, 'Französisch-Guayana', 'GF', 594, 580, 0),
(81, 'Französisch-Polynesien', 'PF', 689, 581, 0),
(82, 'Französische Süd- und Antarktisgebiete', 'TF', 0, 582, 0),
(83, 'Gabun', 'GA', 241, 583, 0),
(84, 'Gambia', 'GM', 220, 584, 0),
(85, 'Georgien', 'GE', 995, 585, 0),
(86, 'Ghana', 'GH', 233, 586, 0),
(87, 'Gibraltar', 'GI', 350, 587, 0),
(88, 'Griechenland', 'GR', 30, 588, 0),
(89, 'Grönland', 'GL', 299, 589, 0),
(90, 'Grenada', 'GD', 1, 590, 0),
(91, 'Guadeloupe', 'GP', 590, 591, 0),
(92, 'Guam', 'GU', 1, 592, 0),
(93, 'Guatemala', 'GT', 502, 593, 0),
(94, 'Guernsey', 'GG', 44, 594, 0),
(95, 'Guinea', 'GN', 224, 595, 0),
(96, 'Guinea Bissau', 'GW', 245, 596, 0),
(97, 'Guyana', 'GY', 592, 597, 0),
(98, 'Haiti', 'HT', 509, 598, 0),
(99, 'Heard und McDonaldinseln', 'HM', 0, 599, 0),
(100, 'Vatikanstadt', 'VA', 379, 600, 0),
(101, 'Honduras', 'HN', 504, 601, 0),
(102, 'Hongkong', 'HK', 852, 602, 0),
(103, 'Ungarn', 'HU', 36, 603, 0),
(104, 'Island', 'IS', 354, 604, 0),
(105, 'Indien', 'IN', 91, 605, 0),
(106, 'Indonesien', 'ID', 62, 606, 0),
(107, 'Iran, ISLAMIC REP.', 'IR', 98, 607, 0),
(108, 'Irak', 'IQ', 964, 608, 0),
(109, 'Irland', 'IE', 353, 609, 0),
(110, 'Isle of Man', 'IM', 44, 610, 0),
(111, 'Israel', 'IL', 972, 611, 0),
(112, 'Jamaika', 'JM', 1, 612, 0),
(113, 'Japan', 'JP', 81, 613, 0),
(114, 'Jersey', 'JE', 44, 614, 0),
(115, 'Jordan', 'JO', 962, 615, 0),
(116, 'Kasachstan', 'KZ', 7, 616, 0),
(117, 'Kenia', 'KE', 254, 617, 0),
(118, 'Kiribati', 'KI', 686, 618, 0),
(119, 'Nordkorea', 'KP', 850, 619, 0),
(120, 'Süd Korea', 'KR', 82, 620, 0),
(121, 'Kuwait', 'KW', 965, 621, 0),
(122, 'Kirgisistan', 'KG', 996, 622, 0),
(123, 'Laos', 'LA', 856, 623, 0),
(124, 'Lettland', 'LV', 371, 624, 0),
(125, 'Libanon', 'LB', 961, 625, 0),
(126, 'Lesotho', 'LS', 266, 626, 0),
(127, 'Liberia', 'LR', 231, 627, 0),
(128, 'Libyen', 'LY', 218, 628, 0),
(129, 'Liechtenstein', 'LI', 423, 629, 0),
(130, 'Litauen', 'LT', 370, 630, 0),
(131, 'Luxemburg', 'LU', 352, 631, 0),
(132, 'Macao', 'MO', 853, 632, 0),
(133, 'Mazedonien', 'MK', 389, 633, 0),
(134, 'Madagaskar', 'MG', 261, 634, 0),
(135, 'Malawi', 'MW', 265, 635, 0),
(136, 'Malaysia', 'MY', 60, 636, 0),
(137, 'Malediven', 'MV', 960, 637, 0),
(138, 'Mali', 'ML', 223, 638, 0),
(139, 'Malta', 'MT', 356, 639, 0),
(140, 'Marshallinseln', 'MH', 692, 640, 0),
(141, 'Martinique', 'MQ', 0, 641, 0),
(142, 'Mauretanien', 'MR', 222, 642, 0),
(143, 'Mauritius', 'MU', 230, 643, 0),
(144, 'Mayotte', 'YT', 262, 644, 0),
(145, 'Mexiko', 'MX', 52, 645, 0),
(146, 'Mikronesien', 'FM', 691, 646, 0),
(147, 'Moldawien, REP.', 'MD', 373, 647, 0),
(148, 'Monaco', 'MC', 377, 648, 0),
(149, 'Mongolei', 'MN', 976, 649, 0),
(150, 'Montenegro', 'ME', 382, 650, 0),
(151, 'Montserrat', 'MS', 1, 651, 0),
(152, 'Marokko', 'MA', 212, 652, 0),
(153, 'Mosambik', 'MZ', 258, 653, 0),
(154, 'Myanmar', 'MM', 95, 654, 0),
(155, 'Namibia', 'NA', 264, 655, 0),
(156, 'Nauru', 'NR', 674, 656, 0),
(157, 'Nepal', 'NP', 977, 657, 0),
(158, 'Niederländische Antillen', 'AN', 599, 658, 0),
(159, 'Neukaledonien', 'NC', 687, 659, 0),
(160, 'Neuseeland', 'NZ', 64, 660, 0),
(161, 'Nicaragua', 'NI', 505, 661, 0),
(162, 'Niger', 'NE', 227, 662, 0),
(163, 'Nigeria', 'NG', 234, 663, 0),
(164, 'Niue', 'NU', 683, 664, 0),
(165, 'Norfolkinsel', 'NF', 672, 665, 0),
(166, 'Nördliche Marianen', 'MP', 1, 666, 0),
(167, 'Norwegen', 'NO', 47, 667, 0),
(168, 'Oman', 'OM', 968, 668, 0),
(169, 'Pakistan', 'PK', 92, 669, 0),
(170, 'Palau', 'PW', 680, 670, 0),
(171, 'Palästina', 'PS', 970, 671, 0),
(172, 'Panama', 'PA', 507, 672, 0),
(173, 'Papua-Neuguinea', 'PG', 675, 673, 0),
(174, 'Paraguay', 'PY', 595, 674, 0),
(175, 'Peru', 'PE', 51, 675, 0),
(176, 'Philippinen', 'PH', 63, 676, 0),
(177, 'Pitcairninseln', 'PN', 64, 677, 0),
(178, 'Puerto Rico', 'PR', 1, 678, 0),
(179, 'Katar', 'QA', 974, 679, 0),
(180, 'Réunion', 'RE', 0, 680, 0),
(181, 'Rumänien', 'RO', 40, 681, 0),
(182, 'Russland', 'RU', 7, 682, 0),
(183, 'Ruanda', 'RW', 250, 683, 0),
(184, 'St. Helena', 'SH', 290, 684, 0),
(185, 'St. Kitts und Nevis', 'KN', 1, 685, 0),
(186, 'St. Lucia', 'LC', 1, 686, 0),
(187, 'Saint-Pierre und Miquelon', 'PM', 508, 687, 0),
(188, 'Saint-Vincent', 'VC', 1, 688, 0),
(189, 'Samoa', 'WS', 685, 689, 0),
(190, 'San Marino', 'SM', 378, 690, 0),
(191, 'São Tomé und Príncipe', 'ST', 239, 691, 0),
(192, 'Saudi-Arabien', 'SA', 966, 692, 0),
(193, 'Senegal', 'SN', 221, 693, 0),
(194, 'Serbien', 'RS', 381, 694, 0),
(195, 'Seychellen', 'SC', 248, 695, 0),
(196, 'Sierra Leone', 'SL', 232, 696, 0),
(197, 'Singapur', 'SG', 65, 697, 0),
(198, 'Slowakei', 'SK', 421, 698, 0),
(199, 'Slowenien', 'SI', 386, 699, 0),
(200, 'Salomonen', 'SB', 677, 700, 0),
(201, 'Somalia', 'SO', 252, 701, 0),
(202, 'Südafrika', 'ZA', 27, 702, 0),
(203, 'Südgeorgien und die Südlichen Sandwichinseln', 'GS', 500, 703, 0),
(204, 'Spanien', 'ES', 34, 704, 0),
(205, 'Sri Lanka', 'LK', 94, 705, 0),
(206, 'Sudan', 'SD', 249, 706, 0),
(207, 'Suriname', 'SR', 597, 707, 0),
(208, 'Svalbard und Jan Mayen', 'SJ', 4779, 708, 0),
(209, 'Swasiland', 'SZ', 268, 709, 0),
(210, 'Schweden', 'SE', 46, 710, 0),
(211, 'Syrien', 'SY', 963, 711, 0),
(212, 'Taiwan', 'TW', 886, 712, 0),
(213, 'Tadschikistan', 'TJ', 992, 713, 0),
(214, 'Tansania', 'TZ', 255, 714, 0),
(215, 'Thailand', 'TH', 66, 715, 0),
(216, 'Timor', 'TL', 670, 716, 0),
(217, 'Togo', 'TG', 228, 717, 0),
(218, 'Tokelau', 'TK', 690, 718, 0),
(219, 'Tonga', 'TO', 676, 719, 0),
(220, 'Trinidad und Tobago', 'TT', 1, 720, 0),
(221, 'Tunesien', 'TN', 216, 721, 0),
(222, 'Türkei', 'TR', 90, 722, 0),
(223, 'Turkmenistan', 'TM', 993, 723, 0),
(224, 'Turks- und Caicosinseln', 'TC', 1, 724, 0),
(225, 'Tuvalu', 'TV', 688, 725, 0),
(226, 'Uganda', 'UG', 256, 726, 0),
(227, 'Ukraine', 'UA', 380, 727, 0),
(228, 'Vereinigte Arabische Emirate', 'AE', 971, 728, 0),
(229, 'Großbritannien', 'GB', 44, 729, 0),
(230, 'USA', 'US', 1, 730, 0),
(231, 'USA-Inseln', 'UM', 0, 731, 0),
(232, 'Uruguay', 'UY', 598, 732, 0),
(233, 'Usbekistan', 'UZ', 998, 733, 0),
(234, 'Vanuatu', 'VU', 678, 734, 0),
(235, 'Venezuela', 'VE', 58, 735, 0),
(236, 'Vietnam', 'VN', 84, 736, 0),
(237, 'Virgin Islands (British)', 'VG', 1, 737, 0),
(238, 'Virgin Islands (USA)', 'VI', 1, 738, 0),
(239, 'Wallis und Futuna', 'WF', 681, 739, 0),
(240, 'Westsahara', 'EH', 212, 740, 0),
(241, 'Jemen', 'YE', 967, 741, 0),
(242, 'Sambia', 'ZM', 260, 742, 0),
(243, 'Simbabwe', 'ZW', 263, 743, 0),
(244, 'Kosovo', 'XK', 381, 744, 0);

/******************************************************************************/
/* Data for english contact form                                              */
/*     - replace <site_id> by site id                                         */
/*     - replace <campaign_id> by new campaign id                             */
/*     - replace <type_id> by new type id                                     */
/*     - replace <table_prefix> by database table prefix                      */
/******************************************************************************/

INSERT INTO `<table_prefix>_campaign_type` (`CGTID`, `CGTName`, `CGTPosition`, `FK_SID`) VALUES
(<type_id>, 'Miscellaneous', 1, '<site_id>');

INSERT INTO `<table_prefix>_campaign` (`CGID`, `CGName`, `CGPosition`, `CGStatus`, `FK_CGTID`, `FK_SID`) VALUES
(<campaign_id>, 'Contact form', 1, 1, <type_id>, '<site_id>');

INSERT INTO `<table_prefix>_campaign_data` (`CGDType`, `CGDName`, `CGDValue`, `CGDRequired`, `CGDDependency`, `CGDPosition`, `CGDValidate`, `CGDPredefined`, `CGDPrechecked`, `CGDMinLength`, `CGDMaxLength`, `CGDMinValue`, `CGDMaxValue`, `CGDDisabled`, `CGDClientData`, `FK_CGID`) VALUES
(3, 'Salutation', 'Mrs.$Mr.$Company', 1, NULL, 3, 0, 0, '', 0, 0, 0, 0, 0, 1, <campaign_id>),
(1, 'Title', '', 0, NULL, 4, 0, 0, '', 0, 50, 0, 0, 0, 1, <campaign_id>),
(1, 'First Name', '', 1, NULL, 5, 0, 0, '', 0, 150, 0, 0, 0, 1, <campaign_id>),
(1, 'Last Name', '', 1, NULL, 6, 0, 0, '', 2, 150, 0, 0, 0, 1, <campaign_id>),
(1, 'Street', '', 1, NULL, 11, 0, 0, '', 0, 150, 0, 0, 0, 1, <campaign_id>),
(3, 'Country', '', 1, NULL, 8, 0, 1, '', 0, 0, 0, 0, 0, 1, <campaign_id>),
(1, 'Postal Code', '', 1, NULL, 9, 1, 0, '', 0, 0, 0, 0, 0, 1, <campaign_id>),
(1, 'City', '', 1, NULL, 10, 0, 0, '', 0, 150, 0, 0, 0, 1, <campaign_id>),
(1, 'E-mail address', '', 1, NULL, 13, 2, 0, '', 0, 0, 0, 0, 0, 1, <campaign_id>),
(1, 'Telephone number', '', 1, NULL, 12, 0, 0, '', 0, 0, 0, 0, 0, 1, <campaign_id>),
(5, 'Which product do you like?', 'Product 1$Product 2$Product 3$Product 4$Product 5$Product 6', 0, NULL, 100, 0, 0, '1$2', 0, 0, 0, 0, 0, 0, <campaign_id>),
(2, 'Message', '', 0, NULL, 101, 0, 0, '', 0, 0, 0, 0, 0, 0, <campaign_id>),
(4, 'I would like to receive more information', '', 0, NULL, 102, 0, 0, '', 0, 0, 0, 0, 0, 1, <campaign_id>);

/************/
/* optional */
/************/

INSERT INTO `<table_prefix>_campaign_status` (`CGSID`, `CGSName`, `CGSPosition`, `FK_CGID`) VALUES
(100, 'Processing open', 1, 0),
(101, 'Client contacted', 2, 0),
(102, 'Contact closed', 3, 0);
