<?php

namespace TradusBundle\Service\Countries;

class CountriesService
{
    /**
     * @param array
     */
    private $countries;

    public function __construct()
    {
        $this->countries = $this->buildCountries();
    }

    /**
     * @return mixed
     */
    public function getCountry(string $isoCode)
    {
        if (isset($this->countries[$isoCode])) {
            return $this->countries[$isoCode];
        }
    }

    protected function buildCountries()
    {
        $country['AF'] = 'Afghanistan';
        $country['AX'] = 'Aland Islands';
        $country['AL'] = 'Albania';
        $country['DZ'] = 'Algeria';
        $country['AS'] = 'American Samoa';
        $country['AD'] = 'Andorra';
        $country['AO'] = 'Angola';
        $country['AI'] = 'Anguilla';
        $country['AQ'] = 'Antarctica';
        $country['AG'] = 'Antigua and Barbuda';
        $country['AR'] = 'Argentina';
        $country['AM'] = 'Armenia';
        $country['AW'] = 'Aruba';
        $country['AU'] = 'Australia';
        $country['AT'] = 'Austria';
        $country['AZ'] = 'Azerbaijan';
        $country['BS'] = 'Bahamas';
        $country['BH'] = 'Bahrain';
        $country['BD'] = 'Bangladesh';
        $country['BB'] = 'Barbados';
        $country['BY'] = 'Belarus';
        $country['BE'] = 'Belgium';
        $country['BZ'] = 'Belize';
        $country['BJ'] = 'Benin';
        $country['BM'] = 'Bermuda';
        $country['BT'] = 'Bhutan';
        $country['BO'] = 'Bolivia';
        $country['BA'] = 'Bosnia and Herzegovina';
        $country['BW'] = 'Botswana';
        $country['BV'] = 'Bouvet Island';
        $country['BR'] = 'Brazil';
        $country['VG'] = 'British Virgin Islands';
        $country['IO'] = 'British Indian Ocean Territory';
        $country['BN'] = 'Brunei Darussalam';
        $country['BG'] = 'Bulgaria';
        $country['BF'] = 'Burkina Faso';
        $country['BI'] = 'Burundi';
        $country['KH'] = 'Cambodia';
        $country['CM'] = 'Cameroon';
        $country['CA'] = 'Canada';
        $country['CV'] = 'Cape Verde';
        $country['KY'] = 'Cayman Islands';
        $country['CF'] = 'Central African Republic';
        $country['TD'] = 'Chad';
        $country['CL'] = 'Chile';
        $country['CN'] = 'China';
        $country['HK'] = 'Hong Kong, SAR China';
        $country['MO'] = 'Macao, SAR China';
        $country['CX'] = 'Christmas Island';
        $country['CC'] = 'Cocos (Keeling) Islands';
        $country['CO'] = 'Colombia';
        $country['KM'] = 'Comoros';
        $country['CG'] = 'Congo (Brazzaville)';
        $country['CD'] = 'Congo, (Kinshasa)';
        $country['CK'] = 'Cook Islands';
        $country['CR'] = 'Costa Rica';
        $country['CI'] = "Côte d\'Ivoire";
        $country['HR'] = 'Croatia';
        $country['CU'] = 'Cuba';
        $country['CY'] = 'Cyprus';
        $country['CZ'] = 'Czech Republic';
        $country['DK'] = 'Denmark';
        $country['DJ'] = 'Djibouti';
        $country['DM'] = 'Dominica';
        $country['DO'] = 'Dominican Republic';
        $country['EC'] = 'Ecuador';
        $country['EG'] = 'Egypt';
        $country['SV'] = 'El Salvador';
        $country['GQ'] = 'Equatorial Guinea';
        $country['ER'] = 'Eritrea';
        $country['EE'] = 'Estonia';
        $country['ET'] = 'Ethiopia';
        $country['FK'] = 'Falkland Islands (Malvinas)';
        $country['FO'] = 'Faroe Islands';
        $country['FJ'] = 'Fiji';
        $country['FI'] = 'Finland';
        $country['FR'] = 'France';
        $country['GF'] = 'French Guiana';
        $country['PF'] = 'French Polynesia';
        $country['TF'] = 'French Southern Territories';
        $country['GA'] = 'Gabon';
        $country['GM'] = 'Gambia';
        $country['GE'] = 'Georgia';
        $country['DE'] = 'Germany';
        $country['GH'] = 'Ghana';
        $country['GI'] = 'Gibraltar';
        $country['GR'] = 'Greece';
        $country['GL'] = 'Greenland';
        $country['GD'] = 'Grenada';
        $country['GP'] = 'Guadeloupe';
        $country['GU'] = 'Guam';
        $country['GT'] = 'Guatemala';
        $country['GG'] = 'Guernsey';
        $country['GN'] = 'Guinea';
        $country['GW'] = 'Guinea-Bissau';
        $country['GY'] = 'Guyana';
        $country['HT'] = 'Haiti';
        $country['HM'] = 'Heard and Mcdonald Islands';
        $country['VA'] = 'Holy See (Vatican City State)';
        $country['HN'] = 'Honduras';
        $country['HU'] = 'Hungary';
        $country['IS'] = 'Iceland';
        $country['IN'] = 'India';
        $country['ID'] = 'Indonesia';
        $country['IR'] = 'Iran, Islamic Republic of';
        $country['IQ'] = 'Iraq';
        $country['IE'] = 'Ireland';
        $country['IM'] = 'Isle of Man';
        $country['IL'] = 'Israel';
        $country['IT'] = 'Italy';
        $country['JM'] = 'Jamaica';
        $country['JP'] = 'Japan';
        $country['JE'] = 'Jersey';
        $country['JO'] = 'Jordan';
        $country['KZ'] = 'Kazakhstan';
        $country['KE'] = 'Kenya';
        $country['KI'] = 'Kiribati';
        $country['KP'] = 'Korea (North)';
        $country['KR'] = 'Korea (South)';
        $country['KW'] = 'Kuwait';
        $country['KG'] = 'Kyrgyzstan';
        $country['LA'] = 'Lao PDR';
        $country['LV'] = 'Latvia';
        $country['LB'] = 'Lebanon';
        $country['LS'] = 'Lesotho';
        $country['LR'] = 'Liberia';
        $country['LY'] = 'Libya';
        $country['LI'] = 'Liechtenstein';
        $country['LT'] = 'Lithuania';
        $country['LU'] = 'Luxembourg';
        $country['MK'] = 'Macedonia, Republic of';
        $country['MG'] = 'Madagascar';
        $country['MW'] = 'Malawi';
        $country['MY'] = 'Malaysia';
        $country['MV'] = 'Maldives';
        $country['ML'] = 'Mali';
        $country['MT'] = 'Malta';
        $country['MH'] = 'Marshall Islands';
        $country['MQ'] = 'Martinique';
        $country['MR'] = 'Mauritania';
        $country['MU'] = 'Mauritius';
        $country['YT'] = 'Mayotte';
        $country['MX'] = 'Mexico';
        $country['FM'] = 'Micronesia, Federated States of';
        $country['MD'] = 'Moldova';
        $country['MC'] = 'Monaco';
        $country['MN'] = 'Mongolia';
        $country['ME'] = 'Montenegro';
        $country['MS'] = 'Montserrat';
        $country['MA'] = 'Morocco';
        $country['MZ'] = 'Mozambique';
        $country['MM'] = 'Myanmar';
        $country['NA'] = 'Namibia';
        $country['NR'] = 'Nauru';
        $country['NP'] = 'Nepal';
        $country['NL'] = 'Netherlands';
        $country['AN'] = 'Netherlands Antilles';
        $country['NC'] = 'New Caledonia';
        $country['NZ'] = 'New Zealand';
        $country['NI'] = 'Nicaragua';
        $country['NE'] = 'Niger';
        $country['NG'] = 'Nigeria';
        $country['NU'] = 'Niue';
        $country['NF'] = 'Norfolk Island';
        $country['MP'] = 'Northern Mariana Islands';
        $country['NO'] = 'Norway';
        $country['OM'] = 'Oman';
        $country['PK'] = 'Pakistan';
        $country['PW'] = 'Palau';
        $country['PS'] = 'Palestinian Territory';
        $country['PA'] = 'Panama';
        $country['PG'] = 'Papua New Guinea';
        $country['PY'] = 'Paraguay';
        $country['PE'] = 'Peru';
        $country['PH'] = 'Philippines';
        $country['PN'] = 'Pitcairn';
        $country['PL'] = 'Poland';
        $country['PT'] = 'Portugal';
        $country['PR'] = 'Puerto Rico';
        $country['QA'] = 'Qatar';
        $country['RE'] = 'Réunion';
        $country['RO'] = 'Romania';
        $country['RU'] = 'Russian Federation';
        $country['RW'] = 'Rwanda';
        $country['BL'] = 'Saint-Barthélemy';
        $country['SH'] = 'Saint Helena';
        $country['KN'] = 'Saint Kitts and Nevis';
        $country['LC'] = 'Saint Lucia';
        $country['MF'] = 'Saint-Martin (French part)';
        $country['PM'] = 'Saint Pierre and Miquelon';
        $country['VC'] = 'Saint Vincent and Grenadines';
        $country['WS'] = 'Samoa';
        $country['SM'] = 'San Marino';
        $country['ST'] = 'Sao Tome and Principe';
        $country['SA'] = 'Saudi Arabia';
        $country['SN'] = 'Senegal';
        $country['RS'] = 'Serbia';
        $country['SC'] = 'Seychelles';
        $country['SL'] = 'Sierra Leone';
        $country['SG'] = 'Singapore';
        $country['SK'] = 'Slovakia';
        $country['SI'] = 'Slovenia';
        $country['SB'] = 'Solomon Islands';
        $country['SO'] = 'Somalia';
        $country['ZA'] = 'South Africa';
        $country['GS'] = 'South Georgia and the South Sandwich Islands';
        $country['SS'] = 'South Sudan';
        $country['ES'] = 'Spain';
        $country['LK'] = 'Sri Lanka';
        $country['SD'] = 'Sudan';
        $country['SR'] = 'Suriname';
        $country['SJ'] = 'Svalbard and Jan Mayen Islands';
        $country['SZ'] = 'Swaziland';
        $country['SE'] = 'Sweden';
        $country['CH'] = 'Switzerland';
        $country['SY'] = 'Syrian Arab Republic (Syria)';
        $country['TW'] = 'Taiwan, Republic of China';
        $country['TJ'] = 'Tajikistan';
        $country['TZ'] = 'Tanzania, United Republic of';
        $country['TH'] = 'Thailand';
        $country['TL'] = 'Timor-Leste';
        $country['TG'] = 'Togo';
        $country['TK'] = 'Tokelau';
        $country['TO'] = 'Tonga';
        $country['TT'] = 'Trinidad and Tobago';
        $country['TN'] = 'Tunisia';
        $country['TR'] = 'Turkey';
        $country['TM'] = 'Turkmenistan';
        $country['TC'] = 'Turks and Caicos Islands';
        $country['TV'] = 'Tuvalu';
        $country['UG'] = 'Uganda';
        $country['UA'] = 'Ukraine';
        $country['AE'] = 'United Arab Emirates';
        $country['GB'] = 'United Kingdom';
        $country['US'] = 'United States of America';
        $country['UM'] = 'US Minor Outlying Islands';
        $country['UY'] = 'Uruguay';
        $country['UZ'] = 'Uzbekistan';
        $country['VU'] = 'Vanuatu';
        $country['VE'] = 'Venezuela (Bolivarian Republic)';
        $country['VN'] = 'Viet Nam';
        $country['VI'] = 'Virgin Islands, US';
        $country['WF'] = 'Wallis and Futuna Islands';
        $country['EH'] = 'Western Sahara';
        $country['YE'] = 'Yemen';
        $country['ZM'] = 'Zambia';
        $country['ZW'] = 'Zimbabwe';

        return $country;
    }
}
