import {OptionDetail} from '@/types';

export const TARGET_AUDIENCE_DETAILS: Record<string, OptionDetail> = {
    'small-island-developing-states': {
        description: 'National of a Small Island Developing State and working or studying in that country.',
        htmlContent: `<p><strong>Small Island Developing States</strong></p>
<ul>
<li>Antigua and Barbuda</li>
<li>Haiti* </li>
<li>St. Kitts and Nevis</li>
<li>Bahamas</li>
<li>Jamaica</li>
<li>St. Lucia</li>
<li>Barbados</li>
<li>Kiribati*</li>
<li>St. Vincent and the Grenadines</li>
<li>Belize</li>
<li>Maldives</li>
<li>Seychelles</li>
<li>Cabo Verde</li>
<li>Marshall Islands</li>
<li>Solomon Islands*</li>
<li>Comoros*</li>
<li>Micronesia (Federated States of)</li>
<li>Suriname</li>
<li>Cook Islands</li>
<li>Mauritius</li>
<li>Timor-Leste* </li>
<li>Cuba</li>
<li>Nauru</li>
<li>Tonga</li>
<li>Dominica</li>
<li>Niue</li>
<li>Trinidad and Tobago</li>
<li>Dominican Republic</li>
<li>Palau</li>
<li>Tuvalu*</li>
<li>Fiji</li>
<li>Papua New Guinea</li>
<li>Vanuatu</li>
<li>Grenada</li>
<li>Samoa</li>
<li>Guinea-Bissau*</li>
<li>São Tomé and Príncipe</li>
<li>Guyana\t</li>
<li>Singapore</li>
</ul><br>
<p><strong>Associate Members of United Nations Regional Commissions</strong></p>
<ul>
<li>American Samoa</li><li>Curacao</li><li>Puerto Rico</li><li>Anguilla\t</li><li>French Polynesia\t</li><li>Sint Maarten</li><li>Aruba\t</li><li>Guadeloupe\t</li><li>Turks and Caicos Islands</li><li>Bermuda\t</li><li>Guam\t</li><li>U.S. Virgin Islands</li><li>British Virgin Islands\t</li><li>Martinique\t </li><li>Cayman Islands\t</li><li>Montserrat\t </li><li>Commonwealth of Northern Marianas\t</li><li>New Caledonia</li>
</ul>`,
    },
    'least-developing-countries': {
        description:
            'National of a Least Developed Country and working or studying in that country.',
        htmlContent: `<p><strong>44 countries</strong></p>
<ul>
<li>Afghanistan</li><li>Angola</li><li>Bangladesh</li><li>Benin</li><li>Burkina Faso</li><li>Burundi</li><li>Cambodia</li><li>Central African Republic</li><li>Chad</li><li>Comoros</li><li>Democratic Rep. of the Congo</li><li>Djibouti</li><li>Eritrea</li><li>Ethiopia</li><li>Gambia</li><li>Guinea</li><li>Guinea-Bissau</li><li>Haiti</li><li>Kiribati</li><li>Lao People’s Dem. Republic</li><li>Lesotho</li><li>Liberia</li><li>Madagascar</li><li>Malawi</li><li>Mali</li><li>Mauritania</li><li>Mozambique</li><li>Myanmar</li><li>Nepal</li><li>Niger</li><li>Rwanda</li><li>Senegal</li><li>Sierra Leone</li><li>Solomon Islands</li><li>Somalia</li><li>South Sudan</li><li>Sudan</li><li>Timor-Leste</li><li>Togo</li><li>Tuvalu</li><li>Uganda</li><li>United Republic of Tanzania</li><li>Yemen</li><li>Zambia</li>
</ul>`,
    },
    'early-career-ocean-professionals': {
        description:
            'An ECOP is a person that self-identifies as being early in their career, within 10 years or less of professional experience in any field related to the ocean. This definition includes post-secondary students and any volunteer roles, so not only employed/paid positions.',
    },
    'youth': {
        description:
            'those persons between 15 and 24 years old. Note that participants younger than 18 years old may need to be accompanied by a legal guardian or parent.',
    },
};