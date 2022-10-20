/* ===  to simplify code  =================================================== */
/* ========================================================================== */

HTMLElement.prototype.$$empty = function() {
    while (this.firstChild) { this.removeChild(this.firstChild); }
    return this;
};

HTMLElement.prototype.$$on = function (events, callback, options) {
    events.split(' ').forEach((event)=>{
        this.addEventListener(
            event,
            callback, //function(event){ callback(Object.create(event, {currentTarget:{value: event.target}})); },
            options
        );
    });
    return this;
};

HTMLElement.prototype.$$onChild = function (event, query, callback, options) {
    this.addEventListener(event, function(event){
        const target = event.target.matches(query) ? event.target : event.target.closest(query);
        const elem = [...this.querySelectorAll(query)].find(item => (item === target));
        if(elem) { callback(Object.create(event, {target:{value: elem},currentTarget:{value: elem}})); }
    }, options);
    return this;
};

HTMLElement.prototype.$$off = function (event, callback, options) {
    this.removeEventListener(event, callback, options);
    return this;
};

HTMLElement.prototype.$$trigger = function (event, options = {}) {
    let objEvent = null;
    if(['click'].includes(event)) {
        objEvent = new MouseEvent(event,options);
    } else {
        objEvent = new Event(event,options);
    }
    this.dispatchEvent(objEvent);
    return this;
};

HTMLElement.prototype.$$append = function(element) {
    if (element instanceof Element || element instanceof HTMLDocument) {
        this.appendChild(element);
    } else if(typeof element === 'string') {
        this.insertAdjacentHTML('beforeend', element);
    }
    return this;
};

HTMLElement.prototype.$$replaceWith = function(elementNew) {
    if (elementNew instanceof Element || elementNew instanceof HTMLDocument) {
        this.parentNode.replaceChild(elementNew,this);
    } else if(typeof elementNew === 'string') {
        let tpl = document.createElement('template');
        tpl.innerHTML = elementNew.trim();
        this.parentNode.replaceChild(tpl.content.firstChild,this);
    }
    return this;
};

HTMLElement.prototype.$$data = function() {
    return Object.assign({},this.dataset);
};



