
gGeoLocation='';

function gGPS(){
	var gps = navigator.geolocation;
	if (gps){
			gps.getCurrentPosition(
			function(position){
				var lat = position.coords.latitude;
				var long = position.coords.longitude;
				gGeoLocation=lat+" "+long;
				},
			function(error){
				gGeoLocation="error";
			},{timeout:20000});
	} else {
		gGeoLocation="no gps";
	}
}
function autoJump(o,n)
{
	alert(n.length());

}
function gShowHide(el)
{
	if (document.getElementById(el).style.display=='inline')
	 	document.getElementById(el).style.display='none';
	else
		document.getElementById(el).style.display='inline';
}

function imprimir()
{
	var tagElements = document.getElementsByTagName('img');

	for (i = 0; i < tagElements.length; i ++)
	{
		currentElement = tagElements[i];

		currentElement.style.display='none';
	}
	document.getElementById('gPrintButton').style.display='inline';

	var tagElements = document.getElementsByTagName('input');

	for (i = 0; i < tagElements.length; i ++)
	{
		currentElement = tagElements[i];
		currentElement.style.display='none';
	}
	if (document.getElementById('gFoto')!=null)
	{
		document.getElementById('gFoto').style.display='inline';
	}
	window.print();
}

function gPutGPS(el)
{
	Ext.getCmp(el).setValue=gGeoLocation;
}

function resizeText(fontIncrease) {

	var currentElement, currentFontSize, newFontSize;

	if (document.getElementsByTagName)
	{
		tags = new Array ('div', 'p', 'a', 'td', 'th', 'span','tbody','table');

		for (j = 0; j < tags.length; j ++)
		{
			var tagElements = document.getElementsByTagName(tags[j]);

			for (i = 0; i < tagElements.length; i ++)
			{
				currentElement = tagElements[i];
				classe=currentElement.className;
				currentElement.className = currentElement.className.replace(" g-big-font",'');
				currentElement.className = currentElement.className.replace(" g-tiny-font",'');

				if (fontIncrease>0)
					currentElement.className += " g-big-font";
				else
					currentElement.className += " g-tiny-font";
				if (fontIncrease>0)
				{
					if (classe.indexOf('g-msg-title')>-1)
					{
						currentElement.style.fontSize='22px';
					}
					if (classe.indexOf('g-msg-subtitle')>-1)
					{
						currentElement.style.fontSize='20px';
					}
					if (classe.indexOf('g-msg-minititle')>-1)
					{
						currentElement.style.fontSize='16px';
					}
					if (classe.indexOf('g-msg-filter')>-1)
					{
						currentElement.style.fontSize='14px';
					}
					if (classe.indexOf('g-msg-normal')>-1)
					{
						currentElement.style.fontSize='13px';
					}
					if (classe.indexOf('g-detail')>-1)
					{
						currentElement.style.fontSize='13px';
					}
					if (classe.indexOf('g-header')>-1)
					{
						currentElement.style.fontSize='13px';
					}
					if (classe.indexOf('g-title')>-1)
					{
						currentElement.style.fontSize='13px';
					}
					if (classe.indexOf('g-summary')>-1)
					{
						currentElement.style.fontSize='13px';
					}
					if (classe.indexOf('g-total')>-1)
					{
						currentElement.style.fontSize='13px';
					}
				} else
				{
					if (classe.indexOf('g-msg-title')>-1)
					{
						currentElement.style.fontSize='15px';
					}
					if (classe.indexOf('g-msg-subtitle')>-1)
					{
						currentElement.style.fontSize='14px';
					}
					if (classe.indexOf('g-msg-minititle')>-1)
					{
						currentElement.style.fontSize='12px';
					}
					if (classe.indexOf('g-msg-filter')>-1)
					{
						currentElement.style.fontSize='9px';
					}
					if (classe.indexOf('g-msg-normal')>-1)
					{
						currentElement.style.fontSize='9px';
					}
					if (classe.indexOf('g-detail')>-1)
					{
						currentElement.style.fontSize='9px';
					}
					if (classe.indexOf('g-header')>-1)
					{
						currentElement.style.fontSize='9px';
					}
					if (classe.indexOf('g-title')>-1)
					{
						currentElement.style.fontSize='9px';
					}
					if (classe.indexOf('g-summary')>-1)
					{
						currentElement.style.fontSize='9px';
					}
					if (classe.indexOf('g-total')>-1)
					{
						currentElement.style.fontSize='9px';
					}
				}

			}
		}
	}
}

function urlDecode(str){
    str=str.replace(new RegExp('\\+','g'),' ');
    return unescape(str);
}
function urlEncode(str){
    str=escape(str);
    str=str.replace(new RegExp('\\+','g'),'%2B');
    return str.replace(new RegExp('%20','g'),'+');
}

var END_OF_INPUT = -1;

var base64Chars = new Array(
    'A','B','C','D','E','F','G','H',
    'I','J','K','L','M','N','O','P',
    'Q','R','S','T','U','V','W','X',
    'Y','Z','a','b','c','d','e','f',
    'g','h','i','j','k','l','m','n',
    'o','p','q','r','s','t','u','v',
    'w','x','y','z','0','1','2','3',
    '4','5','6','7','8','9','+','/'
);

var reverseBase64Chars = new Array();
for (var i=0; i < base64Chars.length; i++){
    reverseBase64Chars[base64Chars[i]] = i;
}

var base64Str;
var base64Count;
function setBase64Str(str){
    base64Str = str;
    base64Count = 0;
}
function readBase64(){
    if (!base64Str) return END_OF_INPUT;
    if (base64Count >= base64Str.length) return END_OF_INPUT;
    var c = base64Str.charCodeAt(base64Count) & 0xff;
    base64Count++;
    return c;
}

function utf8_decode (str_data) {
    // Converts a UTF-8 encoded string to ISO-8859-1
    //
    // version: 1109.2015
    // discuss at: http://phpjs.org/functions/utf8_decode
    // +   original by: Webtoolkit.info (http://www.webtoolkit.info/)
    // +      input by: Aman Gupta
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Norman "zEh" Fuchs
    // +   bugfixed by: hitwork
    // +   bugfixed by: Onno Marsman
    // +      input by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // *     example 1: utf8_decode('Kevin van Zonneveld');
    // *     returns 1: 'Kevin van Zonneveld'
    var tmp_arr = [],
        i = 0,
        ac = 0,
        c1 = 0,
        c2 = 0,
        c3 = 0;

    str_data += '';

    while (i < str_data.length) {
        c1 = str_data.charCodeAt(i);
        if (c1 < 128) {
            tmp_arr[ac++] = String.fromCharCode(c1);
            i++;
        } else if (c1 > 191 && c1 < 224) {
            c2 = str_data.charCodeAt(i + 1);
            tmp_arr[ac++] = String.fromCharCode(((c1 & 31) << 6) | (c2 & 63));
            i += 2;
        } else {
            c2 = str_data.charCodeAt(i + 1);
            c3 = str_data.charCodeAt(i + 2);
            tmp_arr[ac++] = String.fromCharCode(((c1 & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
            i += 3;
        }
    }

    return tmp_arr.join('');
}

function get_html_translation_table (table, quote_style) {
    // Returns the internal translation table used by htmlspecialchars and htmlentities
    //
    // version: 1109.2015
    // discuss at: http://phpjs.org/functions/get_html_translation_table
    // +   original by: Philip Peterson
    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: noname
    // +   bugfixed by: Alex
    // +   bugfixed by: Marco
    // +   bugfixed by: madipta
    // +   improved by: KELAN
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
    // +      input by: Frank Forte
    // +   bugfixed by: T.Wild
    // +      input by: Ratheous
    // %          note: It has been decided that we're not going to add global
    // %          note: dependencies to php.js, meaning the constants are not
    // %          note: real constants, but strings instead. Integers are also supported if someone
    // %          note: chooses to create the constants themselves.
    // *     example 1: get_html_translation_table('HTML_SPECIALCHARS');
    // *     returns 1: {'"': '&quot;', '&': '&amp;', '<': '&lt;', '>': '&gt;'}
    var entities = {},
        hash_map = {},
        decimal;
    var constMappingTable = {},
        constMappingQuoteStyle = {};
    var useTable = {},
        useQuoteStyle = {};

    // Translate arguments
    constMappingTable[0] = 'HTML_SPECIALCHARS';
    constMappingTable[1] = 'HTML_ENTITIES';
    constMappingQuoteStyle[0] = 'ENT_NOQUOTES';
    constMappingQuoteStyle[2] = 'ENT_COMPAT';
    constMappingQuoteStyle[3] = 'ENT_QUOTES';

    useTable = !isNaN(table) ? constMappingTable[table] : table ? table.toUpperCase() : 'HTML_SPECIALCHARS';
    useQuoteStyle = !isNaN(quote_style) ? constMappingQuoteStyle[quote_style] : quote_style ? quote_style.toUpperCase() : 'ENT_COMPAT';

    if (useTable !== 'HTML_SPECIALCHARS' && useTable !== 'HTML_ENTITIES') {
        throw new Error("Table: " + useTable + ' not supported');
        // return false;
    }

    entities['38'] = '&amp;';
    if (useTable === 'HTML_ENTITIES') {
        entities['160'] = '&nbsp;';
        entities['161'] = '&iexcl;';
        entities['162'] = '&cent;';
        entities['163'] = '&pound;';
        entities['164'] = '&curren;';
        entities['165'] = '&yen;';
        entities['166'] = '&brvbar;';
        entities['167'] = '&sect;';
        entities['168'] = '&uml;';
        entities['169'] = '&copy;';
        entities['170'] = '&ordf;';
        entities['171'] = '&laquo;';
        entities['172'] = '&not;';
        entities['173'] = '&shy;';
        entities['174'] = '&reg;';
        entities['175'] = '&macr;';
        entities['176'] = '&deg;';
        entities['177'] = '&plusmn;';
        entities['178'] = '&sup2;';
        entities['179'] = '&sup3;';
        entities['180'] = '&acute;';
        entities['181'] = '&micro;';
        entities['182'] = '&para;';
        entities['183'] = '&middot;';
        entities['184'] = '&cedil;';
        entities['185'] = '&sup1;';
        entities['186'] = '&ordm;';
        entities['187'] = '&raquo;';
        entities['188'] = '&frac14;';
        entities['189'] = '&frac12;';
        entities['190'] = '&frac34;';
        entities['191'] = '&iquest;';
        entities['192'] = '&Agrave;';
        entities['193'] = '&Aacute;';
        entities['194'] = '&Acirc;';
        entities['195'] = '&Atilde;';
        entities['196'] = '&Auml;';
        entities['197'] = '&Aring;';
        entities['198'] = '&AElig;';
        entities['199'] = '&Ccedil;';
        entities['200'] = '&Egrave;';
        entities['201'] = '&Eacute;';
        entities['202'] = '&Ecirc;';
        entities['203'] = '&Euml;';
        entities['204'] = '&Igrave;';
        entities['205'] = '&Iacute;';
        entities['206'] = '&Icirc;';
        entities['207'] = '&Iuml;';
        entities['208'] = '&ETH;';
        entities['209'] = '&Ntilde;';
        entities['210'] = '&Ograve;';
        entities['211'] = '&Oacute;';
        entities['212'] = '&Ocirc;';
        entities['213'] = '&Otilde;';
        entities['214'] = '&Ouml;';
        entities['215'] = '&times;';
        entities['216'] = '&Oslash;';
        entities['217'] = '&Ugrave;';
        entities['218'] = '&Uacute;';
        entities['219'] = '&Ucirc;';
        entities['220'] = '&Uuml;';
        entities['221'] = '&Yacute;';
        entities['222'] = '&THORN;';
        entities['223'] = '&szlig;';
        entities['224'] = '&agrave;';
        entities['225'] = '&aacute;';
        entities['226'] = '&acirc;';
        entities['227'] = '&atilde;';
        entities['228'] = '&auml;';
        entities['229'] = '&aring;';
        entities['230'] = '&aelig;';
        entities['231'] = '&ccedil;';
        entities['232'] = '&egrave;';
        entities['233'] = '&eacute;';
        entities['234'] = '&ecirc;';
        entities['235'] = '&euml;';
        entities['236'] = '&igrave;';
        entities['237'] = '&iacute;';
        entities['238'] = '&icirc;';
        entities['239'] = '&iuml;';
        entities['240'] = '&eth;';
        entities['241'] = '&ntilde;';
        entities['242'] = '&ograve;';
        entities['243'] = '&oacute;';
        entities['244'] = '&ocirc;';
        entities['245'] = '&otilde;';
        entities['246'] = '&ouml;';
        entities['247'] = '&divide;';
        entities['248'] = '&oslash;';
        entities['249'] = '&ugrave;';
        entities['250'] = '&uacute;';
        entities['251'] = '&ucirc;';
        entities['252'] = '&uuml;';
        entities['253'] = '&yacute;';
        entities['254'] = '&thorn;';
        entities['255'] = '&yuml;';
    }

    if (useQuoteStyle !== 'ENT_NOQUOTES') {
        entities['34'] = '&quot;';
    }
    if (useQuoteStyle === 'ENT_QUOTES') {
        entities['39'] = '&#39;';
    }
    entities['60'] = '&lt;';
    entities['62'] = '&gt;';


    // ascii decimals to real symbols
    for (decimal in entities) {
        if (entities.hasOwnProperty(decimal)) {
            hash_map[String.fromCharCode(decimal)] = entities[decimal];
        }
    }

    return hash_map;
}

function htmlentities (string, quote_style, charset, double_encode) {
    // Convert all applicable characters to HTML entities
    //
    // version: 1109.2015
    // discuss at: http://phpjs.org/functions/htmlentities
    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: nobbler
    // +    tweaked by: Jack
    // +   bugfixed by: Onno Marsman
    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +    bugfixed by: Brett Zamir (http://brett-zamir.me)
    // +      input by: Ratheous
    // +   improved by: Rafał Kukawski (http://blog.kukawski.pl)
    // +   improved by: Dj (http://phpjs.org/functions/htmlentities:425#comment_134018)
    // -    depends on: get_html_translation_table
    // *     example 1: htmlentities('Kevin & van Zonneveld');
    // *     returns 1: 'Kevin &amp; van Zonneveld'
    // *     example 2: htmlentities("foo'bar","ENT_QUOTES");
    // *     returns 2: 'foo&#039;bar'
    var hash_map = this.get_html_translation_table('HTML_ENTITIES', quote_style),
        symbol = '';
    string = string == null ? '' : string + '';

    if (!hash_map) {
        return false;
    }

    if (quote_style && quote_style === 'ENT_QUOTES') {
        hash_map["'"] = '&#039;';
    }

    if (!!double_encode || double_encode == null) {
        for (symbol in hash_map) {
            if (hash_map.hasOwnProperty(symbol)) {
                string = string.split(symbol).join(hash_map[symbol]);
            }
        }
    } else {
        string = string.replace(/([\s\S]*?)(&(?:#\d+|#x[\da-f]+|[a-zA-Z][\da-z]*);|$)/g, function (ignore, text, entity) {
            for (symbol in hash_map) {
                if (hash_map.hasOwnProperty(symbol)) {
                    text = text.split(symbol).join(hash_map[symbol]);
                }
            }

            return text + entity;
        });
    }

    return string;
}

function encodeBase64(str){
	 str=htmlentities(str,"ENT_QUOTES");
    setBase64Str(str);
    var result = '';
    var inBuffer = new Array(3);
    var lineCount = 0;
    var done = false;
    while (!done && (inBuffer[0] = readBase64()) != END_OF_INPUT){
        inBuffer[1] = readBase64();
        inBuffer[2] = readBase64();
        result += (base64Chars[ inBuffer[0] >> 2 ]);
        if (inBuffer[1] != END_OF_INPUT){
            result += (base64Chars [(( inBuffer[0] << 4 ) & 0x30) | (inBuffer[1] >> 4) ]);
            if (inBuffer[2] != END_OF_INPUT){
                result += (base64Chars [((inBuffer[1] << 2) & 0x3c) | (inBuffer[2] >> 6) ]);
                result += (base64Chars [inBuffer[2] & 0x3F]);
            } else {
                result += (base64Chars [((inBuffer[1] << 2) & 0x3c)]);
                result += ('=');
                done = true;
            }
        } else {
            result += (base64Chars [(( inBuffer[0] << 4 ) & 0x30)]);
            result += ('=');
            result += ('=');
            done = true;
        }
        lineCount += 4;
        if (lineCount >= 76){
            result += ('\n');
            lineCount = 0;
        }
    }
    return result;
}
function readReverseBase64(){
    if (!base64Str) return END_OF_INPUT;
    while (true){
        if (base64Count >= base64Str.length) return END_OF_INPUT;
        var nextCharacter = base64Str.charAt(base64Count);
        base64Count++;
        if (reverseBase64Chars[nextCharacter]){
            return reverseBase64Chars[nextCharacter];
        }
        if (nextCharacter == 'A') return 0;
    }
    return END_OF_INPUT;
}

function ntos(n){
    n=n.toString(16);
    if (n.length == 1) n="0"+n;
    n="%"+n;
    return unescape(n);
}

function decodeBase64(str){
    setBase64Str(str);
    var result = "";
    var inBuffer = new Array(4);
    var done = false;
    while (!done && (inBuffer[0] = readReverseBase64()) != END_OF_INPUT
        && (inBuffer[1] = readReverseBase64()) != END_OF_INPUT){
        inBuffer[2] = readReverseBase64();
        inBuffer[3] = readReverseBase64();
        result += ntos((((inBuffer[0] << 2) & 0xff)| inBuffer[1] >> 4));
        if (inBuffer[2] != END_OF_INPUT){
            result +=  ntos((((inBuffer[1] << 4) & 0xff)| inBuffer[2] >> 2));
            if (inBuffer[3] != END_OF_INPUT){
                result +=  ntos((((inBuffer[2] << 6)  & 0xff) | inBuffer[3]));
            } else {
                done = true;
            }
        } else {
            done = true;
        }
    }
    return result;
}

var digitArray = new Array('0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f');
function toHex(n){
    var result = ''
    var start = true;
    for (var i=32; i>0;){
        i-=4;
        var digit = (n>>i) & 0xf;
        if (!start || digit != 0){
            start = false;
            result += digitArray[digit];
        }
    }
    return (result==''?'0':result);
}

function pad(str, len, pad){
    var result = str;
    for (var i=str.length; i<len; i++){
        result = pad + result;
    }
    return result;
}

function encodeHex(str){
    var result = "";
    for (var i=0; i<str.length; i++){
        result += pad(toHex(str.charCodeAt(i)&0xff),2,'0');
    }
    return result;
}

var hexv = {
  "00":0,"01":1,"02":2,"03":3,"04":4,"05":5,"06":6,"07":7,"08":8,"09":9,"0A":10,"0B":11,"0C":12,"0D":13,"0E":14,"0F":15,
  "10":16,"11":17,"12":18,"13":19,"14":20,"15":21,"16":22,"17":23,"18":24,"19":25,"1A":26,"1B":27,"1C":28,"1D":29,"1E":30,"1F":31,
  "20":32,"21":33,"22":34,"23":35,"24":36,"25":37,"26":38,"27":39,"28":40,"29":41,"2A":42,"2B":43,"2C":44,"2D":45,"2E":46,"2F":47,
  "30":48,"31":49,"32":50,"33":51,"34":52,"35":53,"36":54,"37":55,"38":56,"39":57,"3A":58,"3B":59,"3C":60,"3D":61,"3E":62,"3F":63,
  "40":64,"41":65,"42":66,"43":67,"44":68,"45":69,"46":70,"47":71,"48":72,"49":73,"4A":74,"4B":75,"4C":76,"4D":77,"4E":78,"4F":79,
  "50":80,"51":81,"52":82,"53":83,"54":84,"55":85,"56":86,"57":87,"58":88,"59":89,"5A":90,"5B":91,"5C":92,"5D":93,"5E":94,"5F":95,
  "60":96,"61":97,"62":98,"63":99,"64":100,"65":101,"66":102,"67":103,"68":104,"69":105,"6A":106,"6B":107,"6C":108,"6D":109,"6E":110,"6F":111,
  "70":112,"71":113,"72":114,"73":115,"74":116,"75":117,"76":118,"77":119,"78":120,"79":121,"7A":122,"7B":123,"7C":124,"7D":125,"7E":126,"7F":127,
  "80":128,"81":129,"82":130,"83":131,"84":132,"85":133,"86":134,"87":135,"88":136,"89":137,"8A":138,"8B":139,"8C":140,"8D":141,"8E":142,"8F":143,
  "90":144,"91":145,"92":146,"93":147,"94":148,"95":149,"96":150,"97":151,"98":152,"99":153,"9A":154,"9B":155,"9C":156,"9D":157,"9E":158,"9F":159,
  "A0":160,"A1":161,"A2":162,"A3":163,"A4":164,"A5":165,"A6":166,"A7":167,"A8":168,"A9":169,"AA":170,"AB":171,"AC":172,"AD":173,"AE":174,"AF":175,
  "B0":176,"B1":177,"B2":178,"B3":179,"B4":180,"B5":181,"B6":182,"B7":183,"B8":184,"B9":185,"BA":186,"BB":187,"BC":188,"BD":189,"BE":190,"BF":191,
  "C0":192,"C1":193,"C2":194,"C3":195,"C4":196,"C5":197,"C6":198,"C7":199,"C8":200,"C9":201,"CA":202,"CB":203,"CC":204,"CD":205,"CE":206,"CF":207,
  "D0":208,"D1":209,"D2":210,"D3":211,"D4":212,"D5":213,"D6":214,"D7":215,"D8":216,"D9":217,"DA":218,"DB":219,"DC":220,"DD":221,"DE":222,"DF":223,
  "E0":224,"E1":225,"E2":226,"E3":227,"E4":228,"E5":229,"E6":230,"E7":231,"E8":232,"E9":233,"EA":234,"EB":235,"EC":236,"ED":237,"EE":238,"EF":239,
  "F0":240,"F1":241,"F2":242,"F3":243,"F4":244,"F5":245,"F6":246,"F7":247,"F8":248,"F9":249,"FA":250,"FB":251,"FC":252,"FD":253,"FE":254,"FF":255
};

function decodeHex(str){
    str = str.toUpperCase().replace(new RegExp("s/[^0-9A-Z]//g"));
    var result = "";
    var nextchar = "";
    for (var i=0; i<str.length; i++){
        nextchar += str.charAt(i);
        if (nextchar.length == 2){
            result += ntos(hexv[nextchar]);
            nextchar = "";
        }
    }
    return result;

}

function vDate(o,fmt,nulo,msgerro)
{
	var tdia;
	var tmes;
	var tano;
	tano=0;
	tdata=o.value;
	tbarra='';
	if (tdata.indexOf('/')>0) tbarra='/';
	if (tdata.indexOf('-')>0) tbarra='-';
	if ((nulo) && (tdata==''))
	{
		tdata='';
	} else if (!(nulo) && (tdata==''))
	{
		var agora = new Date();
		var mNome = agora.getMonth() + 1;
		var dNome = agora.getDay() + 1;
		var NrDia = agora.getDate();
		var NrAno=agora.getYear();
		if(NrAno > 2000)
			tano = 2000- NrAno;
		else
			tano = NrAno;
		if (tano>100) tano=tano-100;
	 	if (mNome<10) mNome="0"+mNome;
	 	if (NrDia<10) NrDia="0"+NrDia;
	 	if (tano<10) tano="0"+tano;
		o.value=NrDia + "-" + mNome + "-" + tano;
	} else
	{
		if (tbarra!='')
		{
			fmt=new String(fmt);
			fmts = fmt.split(tbarra);
			tflds = tdata.split(tbarra);
			tdia=-1;
			tmes=-1;
			tano=-1;
			thoje=new Date();
			fmtano='yy';
			for (a=0; a<fmts.length; a++)
			{
				lfmt=new String(fmts[a]);
				if (lfmt.indexOf('y')>-1) fmtano=lfmt;
			}
			for (a=0; a<tflds.length; a++)
			{
				lfmt=new String(fmts[a]);
				if (lfmt.indexOf('d')>-1) tdia=tflds[a];
				if (lfmt.indexOf('m')>-1) tmes=tflds[a];
				if (lfmt.indexOf('y')>-1)
				{
					if (tflds[a]=='')
					{
						tano=-1;
						tbarra='';
					}
					else
						tano=parseInt(tflds[a]);
				}
			}
			if (tano==-1)
			{
				tano=thoje.getYear();
				if (tano<999)
					tano=1900+tano;
				if (fmtano=='yy')
				{
					tano=new String(tano-2000);
					if (tano-2000<10)
						tdata=tdata.concat(tbarra,'0',tano);
					else
						tdata=tdata.concat(tbarra,tano);
				} else
					tdata=tdata.concat(tbarra,tano);
			}
			if (tano<999)
			{
				tano=(2000+tano);
			}
			tsituacao = "true";
			// verifica o tdia valido para cada tmes
			if ((tdia < 1)||(tdia < 1 || tdia > 30) && (  tmes == 4 || tmes == 6 || tmes == 9 || tmes == 11 ) || tdia > 31) {
				tsituacao = "falsa";
			}
			// verifica se o tmes e valido
			if (tmes < 01 || tmes > 12 ) {
				tsituacao = "falsa";
			}
			// verifica se e tano bissexto
			if (tmes == 2 && ( tdia < 1 || tdia > 29 || ( tdia > 28 && (parseInt(tano / 4) != tano / 4)))) {
				tsituacao = "falsa";
			}
			if (tdata== "") {
				tsituacao = "falsa";
			}
			if (tsituacao == "falsa") {
//				alert(msgerro+' '+o.value+' ('+fmt+')');
//				o.focus();
			} else
			{
				o.value=tdata;
			}
		} else
		{
			if (fmt.indexOf('/')>0) tbarra='/';
			if (fmt.indexOf('-')>0) tbarra='-';
			fmt=new String(fmt);
			fmts = fmt.split(tbarra);
			i=parseFloat(tdata);
			if(i>=10100)
			{
				s=i.toString();
				if (i>99999)
				{
					d1=s.substr(0,2);
					d2=s.substr(2,2);
					d3=s.substr(4,2);
				} else
				{
					d1=s.substr(0,1);
					d2=s.substr(1,2);
					d3=s.substr(3,2);
				}
				if (fmts[0].indexOf('d')>-1) tdia=parseFloat(d1);
				if (fmts[0].indexOf('m')>-1) tmes=parseFloat(d1);
				if (fmts[1].indexOf('d')>-1) tdia=parseFloat(d2);
				if (fmts[1].indexOf('m')>-1) tmes=parseFloat(d2);
				tano=100+parseFloat(d3);
			}
			else if(i>=101)
			{
				tdata=new Date();
				tano=tdata.getYear();
				s=i.toString();
				if (i>999)
				{
					d1=s.substr(0,2);
					d2=s.substr(2,2);
				} else
				{
					d1=s.substr(0,1);
					d2=s.substr(1,2);
				}
				if (fmts[0].indexOf('d')>-1) tdia=parseFloat(d1);
				if (fmts[0].indexOf('m')>-1) tmes=parseFloat(d1);
				if (fmts[1].indexOf('d')>-1) tdia=parseFloat(d2);
				if (fmts[1].indexOf('m')>-1) tmes=parseFloat(d2);
			}
			else if(i>0)
			{
				tdata=new Date();
				tdia=i;
				tano=tdata.getYear();
				tmes=tdata.getMonth()+1;
				//t=tdia+tbarra+tmes+tbarra+ano;
			}
			if (tano<1900)
				tano=tano-100;
			else
				tano=tano-2000;
			if (tano<10)
				tano='0'+tano;
			if (tmes<10)
				tmes='0'+tmes;
			if (tdia<10)
				tdia='0'+tdia;
			t='';
			//alert('tdia: '+ tdia+' tmes: '+tmes+' Ano: '+ano);
			for (a=0; a<fmts.length; a++)
			{
				lfmt=new String(fmts[a]);
				if (lfmt.indexOf('d')>-1) t=t+tdia+tbarra;
				if (lfmt.indexOf('m')>-1) t=t+tmes+tbarra;
				if (lfmt.indexOf('y')>-1)
				{
					if (lfmt=='yy')
						t=t+tano+tbarra;
					else
						if (tano>60)
							t=t+'19'+tano+tbarra;
						else
							t=t+'20'+tano+tbarra;
				}
			}
			t=t.substr(0,t.length-1);
			o.value=t;
		}
	}
}
// Funcao para s� deixar o usu�rio digitar n�meros
function gDateTimeKeyCheck(campo, event, fmt)
{
	var BACKSPACE=8;
	var TAB=0;
	var key;
	var tecla;
	var strValidos = "0123456789: " ;
	CheckTAB=false;
	if(navigator.appName.indexOf("Netscape")!= -1)
	  tecla= event.which;
	else
	  tecla= event.keyCode;
	key = String.fromCharCode( tecla);
	if ( tecla == 13 )
		return false;
	if ( tecla == BACKSPACE )
		return true;
	if ( tecla == TAB)
		return true;
	//alert( 'key: ' + tecla + '  -> tecla: ' + tecla);
	fmt=new String(fmt);
	tbarra='/';
	if (fmt.indexOf('-')>0) tbarra='-';
	strValidos=strValidos+tbarra;
	return ( gIsValidKey(key,strValidos));
}
// Funcao para testar se o campo do formul�rio � uma Data e aceita nulo
function gDateTimeVerify(ob,fmt,nulo,msgerro)
{
	t=ob.value;
	xcnt=0;
	hora='';
	//ob.value='Teste';
	for (i=0; i<t.length; i++)
	{
		c = t.substring(i,i+1);
		if (c == ' ')
		{
			xcnt=i;
			break;
		}
	}
	if (xcnt>0)
	{
		hora=t.substring(cnt,t.length);
	}
	gDateVerify(ob,fmt,nulo,msgerro);
	t=ob.value;
	if (xcnt>0)
		ob.value=ob.value+hora;
	else
	{
		tdata=new Date();
		hor=tdata.getHours();
		min=tdata.getMinutes();
		seg=tdata.getSeconds();
		if (hor<10) hor='0'+hor;
		if (min<10) min='0'+min;
		if (seg<10) seg='0'+seg;
		tagora=hor+':'+min+':'+seg;
		ob.value=ob.value+' '+tagora;
	}
}

function vTime(campo)
{
	valor = campo.value;
	if (valor.length >= 5)
	{
		for (i = 1 ; i <= valor.length ; i++)
		{
			c = valor.substring(i-1,i);
			if (((c >= 0) == false) && (c != ":"))
			{
				alert("Hora Inválida");
				campo.select();
				break;
			}
		}
	}
	else if (valor.length==1)
	{
		campo.value="0"+valor+":00";
	} else if (valor.length==2)
	{
		campo.value=valor+":00";
	} else if (valor.length==3)
	{
		campo.value="0"+valor.substring(0,1)+":"+valor.substring(1,3);
	} else if (valor.length==4)
	{
		campo.value=valor.substring(0,2)+":"+valor.substring(2,4);
	} else if (valor.length>0)
	{

	 	alert("Hora Inválida");
	 	campo.select();
	 	return;
 	}
}
// Funcao para testar se o campo do formul�rio � um e-mail
function vEmail(campo)
{
	valor = campo.value;
	arr = false;
	ponto=0;
	if (valor.length >= 7)
	{
		for (i = 1 ; i <= valor.length ; i++)
		{
			c = valor.substring(i-1,i);
			if (c == "@")
			{
					arr=true;
			}
			if (c == ".") 	{
				ponto=ponto+1;
			}
		}
	}	else {
	 	alert("E-mail inválido");
	 	campo.select();
	 	return;
 	}
 	if ((arr == false) || (ponto==0))
 	{
 	  alert("E-mail inválido");
	 	campo.select();
	 	return;
 	}
}

function vUFText(o)
{
	t=o.value;
	o.value=t.substr(0,1).toUpperCase()+t.substr(1,200);
}

function vNCM( obj ){
	v = obj.value;
	if(typeof(v) != "undefined"){
		var max = 8;
		v = v.replace(".","");
		v = v.replace(".","");
		v = v.substring (0,max);
		if (v.length>6)
			v=v.substr(0,4)+"."+v.substr(4,2)+"."+v.substr(6,2);
		else if (v.length>4)
			v=v.substr(0,4)+"."+v.substr(4,2);
		obj.value = v;
	}
	return true;
}

function vUFWText(o)
{
	var sai='';
	t=o.value;
	mai=1;
	for (a=0; a<t.length; a++)
	{
		if (mai==1)
		{
			if ((a<t.length-3) && (a>0))
			{
				if ((t.substr(a-1,4).toLowerCase()==' de ') || (t.substr(a-1,4).toLowerCase()==' da ') || (t.substr(a-1,4).toLowerCase()==' do ')|| (t.substr(a-1,5).toLowerCase()==' das ')|| (t.substr(a-1,5).toLowerCase()==' dos ') || (t.substr(a-1,3).toLowerCase()==' a ')  || (t.substr(a-1,3).toLowerCase()==' e ')  || (t.substr(a-1,3).toLowerCase()==' o ') )
					sai=sai+t.substr(a,1).toLowerCase();
				else
					sai=sai+t.substr(a,1).toUpperCase();
			}
			else
				sai=sai+t.substr(a,1).toUpperCase();
		}
		else
			sai=sai+t.substr(a,1).toLowerCase();
		mai=0;
		if ((t.substr(a,1)==' ') || (t.substr(a,1)=='-') || (t.substr(a,1)==';') || (t.substr(a,1)==',') || (t.substr(a,1)=='.') || (t.substr(a,1)=='_') || (t.substr(a,1)=='/'))
			mai=1;
	}
	o.value=sai;
}


