/**
 * Create HTMLTemplateElement <template> from HTML string
 * @param strHtml
 * @returns {HTMLTemplateElement}
 */
 export function libCreateTemplate(strHtml) {
    let tpl = document.createElement('template');
    tpl.innerHTML = (strHtml || '').trim();
    return tpl;
}

/**
 * Get first tag as HTMLElement from HTML string
 * @param strHtml
 * @returns {ChildNode}
 */
export function libHtmlAsDom(strHtml) {
    return libCreateTemplate(strHtml).content.firstChild;
}


/**
 * Getting the first previous element that matches a selector
 * @param elem
 * @param selector
 * @returns {null|Element}
 */
export function libPrevSibling(elem, selector) {
    let sibling = elem.previousElementSibling;

    if (!selector) {
        return sibling;
    }

    while (sibling) {
        if (sibling.matches(selector)) { 
            return sibling; 
        }
        sibling = sibling.previousElementSibling
    }
    return null;
}

/**
 * Getting the first next element that matches a selector
 * @param elem
 * @param selector
 * @returns {null|Element}
 */
export function libNextSibling(elem, selector) {
    let sibling = elem.nextElementSibling;

    if (!selector) { 
        return sibling;
    }

    while (sibling) {
        if (sibling.matches(selector)) {
            return sibling;
        }
        sibling = sibling.nextElementSibling
    }
    return null;
}

/**
 * Remove element from DOM and keep it in memory
 * @param {Node} node
 * @returns {Node}
 */
export function libDetach(node) { return node.parentElement.removeChild(node); }

/**
 * 
 * @param {*} node 
 * @param {*} display 
 */
export function libToggleDisplay(node, display='') {
    if(node instanceof NodeList) {
        node.forEach((el)=>{ el.style.display = el.style.display === 'none' ? display : 'none'; });
    } else {
        node.style.display = node.style.display === 'none' ? display : 'none';
    }
}
