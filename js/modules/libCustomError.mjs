export class CustomError extends Error {
    constructor(msg){
        if(typeof msg === 'object' && msg.hasOwnProperty('action')) {
            //new type
            super();
            this.message = msg.message;
            this.name   = msg.name;
            this.code   = msg.code;
            this.action = msg.action;
        } else {
            //old type
            super();
            this.name = 'CustomError';
            this.message = msg;
        }
    }
}

// factory
export function libCreateCustomError(msg,code='') {
    const errRec = {
        name:    'CustomError',
        message: '',
        stack:   '',
        code:    code,
        action:  null
    };
    if(typeof msg === 'function') {
        errRec.message = errRec.action = msg;
    } else if(msg instanceof Error) {
        errRec.message = libValToErrorMsg(msg.message.match(/(RC_\w+)|(ERR_\w+)/g));
        errRec.name    = msg.name;
        errRec.stack   = msg.stack;
    } else {
        errRec.message = libValToErrorMsg(msg);
    }
    return new CustomError(errRec);
}