/* global aAPP */
function *uid(){ for (let i=1;i<10000;i++) { yield Date.now()+i.toString().padStart(4, '0').substr(-15); } }
const _uid = uid();

function libGetUniqueNumberStr(){
    return _uid.next().value.slice(-15);
}

function libSerialize(obj){ return window.btoa(encodeURIComponent(JSON.stringify(obj))); }

function libDeserialize(str,parse=true){
    return parse
        ? JSON.parse(decodeURIComponent(window.atob(str)))
        : decodeURIComponent(window.atob(str));
}

function libToCamelCase(str) {
    return str.replace(/^([A-Z])|[\s-_](\w)/g,
        function(match, p1, p2, offset) {
            if (p2) {
                return p2.toUpperCase();
            }
            return p1.toLowerCase();
        });
}

function libFirstLetterUp(str,pos=0) { return str.charAt(pos).toUpperCase() + str.slice(pos+1); }
function libFirstLetterLo(str,pos=0) { return str.charAt(pos).toLowerCase() + str.slice(pos+1); }
function libStripTags(input, allowed) {
    allowed = (((allowed || '') + '').toLowerCase().match(/<[a-z][a-z0-9]*>/g) || []).join('');
    let tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi;
    let commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi;
    return input
        ? input
            .toString()
            .replace(commentsAndPhpTags, '')
            .replace(tags, function ($0, $1) {
                return allowed.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : '';
            })
        : '';
}

function libRegExpEscape(s) {
    return s.replace(/[-[\]{}()*+!<=:?.\/\\^$|#\s,]/g, '\\$&');
}

function libReplaceAll(str, find, replace) {
    return str.replace(new RegExp(libRegExpEscape(find), 'g'), replace);
}

function libEllipsis(str, len, pos = 0.5, glue = '...') {
    if(pos == null || isNaN(pos) || pos >= 1 || pos <= 0) { pos = .85; }
    if(!glue){ glue = "..."; }
    let rest = len - glue.length;
    return str.length > rest
        ? str.substr(0, rest * pos)
        + glue
        + str.substr(str.length-rest * (1-pos))
        : str;
}

function libRandomStr() { return Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);}
function libRandomStr1() { return [...Array(32)].map(i=>(~~(Math.random()*36)).toString(36)).join('');}
function libUuid(mask = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx') {
    return mask.replace(/[xy]/g, (c) => {
        let r = (Date.now() + Math.random()*16)%16 | 0;
        return (c==='x' ? r : (r&0x3|0x8)).toString(16);
    });
}

function libGetHtmlTranslationTable(table, quoteStyle) {
    let ent = {};
    let hashMap = {};
    let decimal;
    let constMapTable = {};
    let constMapQuoteStyle = {};

    // Translate arguments
    constMapTable[0] = 'HTML_SPECIALCHARS';
    constMapTable[1] = 'HTML_ENTITIES';
    constMapQuoteStyle[0] = 'ENT_NOQUOTES';
    constMapQuoteStyle[2] = 'ENT_COMPAT';
    constMapQuoteStyle[3] = 'ENT_QUOTES';

    let useTable = !isNaN(table)
        ? constMapTable[table]
        : (table
            ? table.toUpperCase()
            : 'HTML_SPECIALCHARS');

    let useQuoteStyle = !isNaN(quoteStyle)
        ? constMapQuoteStyle[quoteStyle]
        : (quoteStyle
            ? quoteStyle.toUpperCase()
            : 'ENT_COMPAT');

    if (useTable !== 'HTML_SPECIALCHARS' && useTable !== 'HTML_ENTITIES') {
        throw new Error('Table: ' + useTable + ' not supported');
    }

    ent['38'] = '&amp;';
    if (useTable === 'HTML_ENTITIES') {
        ent['160'] = '&nbsp;';
        ent['161'] = '&iexcl;';
        ent['162'] = '&cent;';
        ent['163'] = '&pound;';
        ent['164'] = '&curren;';
        ent['165'] = '&yen;';
        ent['166'] = '&brvbar;';
        ent['167'] = '&sect;';
        ent['168'] = '&uml;';
        ent['169'] = '&copy;';
        ent['170'] = '&ordf;';
        ent['171'] = '&laquo;';
        ent['172'] = '&not;';
        ent['173'] = '&shy;';
        ent['174'] = '&reg;';
        ent['175'] = '&macr;';
        ent['176'] = '&deg;';
        ent['177'] = '&plusmn;';
        ent['178'] = '&sup2;';
        ent['179'] = '&sup3;';
        ent['180'] = '&acute;';
        ent['181'] = '&micro;';
        ent['182'] = '&para;';
        ent['183'] = '&middot;';
        ent['184'] = '&cedil;';
        ent['185'] = '&sup1;';
        ent['186'] = '&ordm;';
        ent['187'] = '&raquo;';
        ent['188'] = '&frac14;';
        ent['189'] = '&frac12;';
        ent['190'] = '&frac34;';
        ent['191'] = '&iquest;';
        ent['192'] = '&Agrave;';
        ent['193'] = '&Aacute;';
        ent['194'] = '&Acirc;';
        ent['195'] = '&Atilde;';
        ent['196'] = '&Auml;';
        ent['197'] = '&Aring;';
        ent['198'] = '&AElig;';
        ent['199'] = '&Ccedil;';
        ent['200'] = '&Egrave;';
        ent['201'] = '&Eacute;';
        ent['202'] = '&Ecirc;';
        ent['203'] = '&Euml;';
        ent['204'] = '&Igrave;';
        ent['205'] = '&Iacute;';
        ent['206'] = '&Icirc;';
        ent['207'] = '&Iuml;';
        ent['208'] = '&ETH;';
        ent['209'] = '&Ntilde;';
        ent['210'] = '&Ograve;';
        ent['211'] = '&Oacute;';
        ent['212'] = '&Ocirc;';
        ent['213'] = '&Otilde;';
        ent['214'] = '&Ouml;';
        ent['215'] = '&times;';
        ent['216'] = '&Oslash;';
        ent['217'] = '&Ugrave;';
        ent['218'] = '&Uacute;';
        ent['219'] = '&Ucirc;';
        ent['220'] = '&Uuml;';
        ent['221'] = '&Yacute;';
        ent['222'] = '&THORN;';
        ent['223'] = '&szlig;';
        ent['224'] = '&agrave;';
        ent['225'] = '&aacute;';
        ent['226'] = '&acirc;';
        ent['227'] = '&atilde;';
        ent['228'] = '&auml;';
        ent['229'] = '&aring;';
        ent['230'] = '&aelig;';
        ent['231'] = '&ccedil;';
        ent['232'] = '&egrave;';
        ent['233'] = '&eacute;';
        ent['234'] = '&ecirc;';
        ent['235'] = '&euml;';
        ent['236'] = '&igrave;';
        ent['237'] = '&iacute;';
        ent['238'] = '&icirc;';
        ent['239'] = '&iuml;';
        ent['240'] = '&eth;';
        ent['241'] = '&ntilde;';
        ent['242'] = '&ograve;';
        ent['243'] = '&oacute;';
        ent['244'] = '&ocirc;';
        ent['245'] = '&otilde;';
        ent['246'] = '&ouml;';
        ent['247'] = '&divide;';
        ent['248'] = '&oslash;';
        ent['249'] = '&ugrave;';
        ent['250'] = '&uacute;';
        ent['251'] = '&ucirc;';
        ent['252'] = '&uuml;';
        ent['253'] = '&yacute;';
        ent['254'] = '&thorn;';
        ent['255'] = '&yuml;';
    }

    if (useQuoteStyle !== 'ENT_NOQUOTES') { ent['34'] = '&quot;'; }
    if (useQuoteStyle === 'ENT_QUOTES')   { ent['39'] = '&#39;';  }
    ent['60'] = '&lt;';
    ent['62'] = '&gt;';

    // ascii decimals to real symbols
    for (decimal in ent) {
        if (ent.hasOwnProperty(decimal)) {
            hashMap[String.fromCharCode(decimal)] = ent[decimal];
        }
    }

    return hashMap;
}

function libHtmlEntityDecode(str, quoteStyle) {
    let entity;
    let symbol;
    let tmpStr = str.toString();
    let hashMap = libGetHtmlTranslationTable('HTML_ENTITIES', quoteStyle);
    if (hashMap === false) { return false; }
    delete (hashMap['&']);
    hashMap['&'] = '&amp;';
    for (symbol in hashMap) {
        entity = hashMap[symbol];
        tmpStr = tmpStr.split(entity).join(symbol);
    }
    tmpStr = tmpStr.split('&#039;').join("'");
    return tmpStr;
}

function libEscapeHtml(str) {
    if(!str) { return ''; }
    const entityMap = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
        '/': '&#x2F;',
        '`': '&#x60;',
        '=': '&#x3D;'
    };
    return String(str).replace(/[&<>"'`=\/]/g,  (s)=>{
        return entityMap[s];
    });
}

function libSecondsToTimeProgressive (seconds) {
    let h = Math.floor(seconds / 3600);
    let m = Math.floor((seconds - (h * 3600)) / 60);
    let s = seconds - (h * 3600) - (m * 60);
    let t = '';

    if (h !== 0) {
        t = h + ':';
    }
    if (m !== 0 || t !== '') {
        m = (m < 10 && t !== '') ? '0' + m : String(m);
        t += m + ':';
    }
    if (t === '') {
        t = s;
    } else {
        t += (s < 10) ? '0'+s : String(s);
    }
    return t;
}

function libLoginToCredential(login){
    return login.indexOf("\\") === -1 ? "officekiev\\" + login.trim() : login.trim();
}

function libFormatXml(xml, tab = '\t', nl = '\n') {
    let formatted = '', indent = '';
    const nodes = xml.slice(1, -1).split(/>\s*</);
    if (nodes[0][0] === '?') formatted += '<' + nodes.shift() + '>' + nl;
    for (let i = 0; i < nodes.length; i++) {
        const node = nodes[i];
        if (node[0] === '/') indent = indent.slice(tab.length); // decrease indent
        formatted += indent + '<' + node + '>' + nl;
        if (node[0] !== '/' && node[node.length - 1] !== '/' && node.indexOf('</') === -1) indent += tab; // increase indent
    }
    return formatted;
}
export {
    libGetUniqueNumberStr,
    libSerialize,
    libDeserialize,
    libToCamelCase,
    libFirstLetterUp,
    libFirstLetterLo,
    libStripTags,
    libReplaceAll,
    libRegExpEscape,
    libEllipsis,
    libRandomStr,
    libUuid,
    libHtmlEntityDecode,
    libEscapeHtml,
    libSecondsToTimeProgressive,
    libLoginToCredential,
    libFormatXml
};