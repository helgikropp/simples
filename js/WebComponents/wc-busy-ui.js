class WcBusyUI extends HTMLElement {
    constructor() {
        super();
        this.hide();
        this.shadow = this.attachShadow({mode: 'open'});
        this.wrapper = document.createElement('section');
        this.wrapper.setAttribute('class', 'busy-ui-wrapper');
        this.overlay = document.createElement('div');
        this.overlay.setAttribute('class', 'busy-ui-overlay');
        this.box = document.createElement('div');
        this.box.setAttribute('class', 'busy-ui-box');
        const style = document.createElement('style');
        style.textContent = `
                    @keyframes busy-ui-spin {
                        0% { transform:translateZ(0) rotate(0deg); }
                        100% { transform:translateZ(0) rotate(360deg); }
                    }
                    @keyframes busy-ui-opacity-pulse {
                        0% { opacity: 1.0; }
                        50% { opacity: 0.2; }
                        100% { opacity: 1.0; }
                    }
                    .busy-ui-opacity-pulse-slow { animation: busy-ui-opacity-pulse 4s linear infinite; }
                    .busy-ui-wrapper { position:fixed;top:0;left:0;width:100%;height:100%;z-index:999999997; }
                    .busy-ui-overlay {
                        position:absolute;
                        top:0;
                        left:0;
                        width:100%;
                        height:100%;
                        z-index:999999998;
                        /*background-color:rgba(5,32,76,0.0);*/
                        background-color:rgba(255,255,255,0.2);
                        transition:background-color .5s linear;
                    }
                    .busy-ui-box {
                        z-index:999999999;
                        border: none;
                        color: #000;
                        background-color:rgba(5,32,76,0.5);
                        width: 300px;
                        height: 200px;
                        position:absolute;
                        top:calc(50% - 100px);
                        left:calc(50% - 150px);
                    }
                    .busy-ui-box:before {
                        content:"";
                        display:block;
                        width:130px;
                        height:130px;
                        border-radius:50%;
                        border-width:5px;
                        border-style:solid;
                        border-color:#fff transparent #fff transparent;
                        position:absolute;
                        top:calc(50% - 70px);
                        left:calc(50% - 70px);
                        will-change:transform;
                        animation:busy-ui-spin .65s infinite ease-in-out;
                    }
                `;
        this.img = document.createElement('img');
        this.img.setAttribute('src', 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB3aWR0aD0iNDIiIGhlaWdodD0iNDciPjxnIGZpbGw9IiNmZmYiPjxwYXRoIGQ9Ik00NjUxIDE1ODJoNDJ2NDdoLTQyeiIvPjxnIHRyYW5zZm9ybT0idHJhbnNsYXRlKDQ2NTEgMTU4MikiPjx1c2UgeGxpbms6aHJlZj0iI0EiIHg9Ii00NjUxIiB5PSItMTUzOC42NiIvPjx1c2UgeGxpbms6aHJlZj0iI0IiIHg9Ii00NjQyLjM0IiB5PSItMTUzOC42MiIvPjx1c2UgeGxpbms6aHJlZj0iI0MiIHg9Ii00NjM0LjU4IiB5PSItMTUzOC42NiIvPjx1c2UgeGxpbms6aHJlZj0iI0QiIHg9Ii00NjI1LjM5IiB5PSItMTUzOC42NiIvPjx1c2UgeGxpbms6aHJlZj0iI0UiIHg9Ii00NjE2LjYxIiB5PSItMTUzOC42MiIvPjx1c2UgeGxpbms6aHJlZj0iI0YiIHg9Ii00NjUwLjgxIiB5PSItMTU1MC41OCIvPjx1c2UgeGxpbms6aHJlZj0iI0ciIHg9Ii00NjQ1LjQ2IiB5PSItMTU1MC42OSIvPjx1c2UgeGxpbms6aHJlZj0iI0giIHg9Ii00NjM2LjQyIiB5PSItMTU1MC41OCIvPjx1c2UgeGxpbms6aHJlZj0iI0kiIHg9Ii00NjI3Ljg3IiB5PSItMTU1MC41OCIvPjx1c2UgeGxpbms6aHJlZj0iI0oiIHg9Ii00NjE5LjciIHk9Ii0xNTUwLjU4Ii8+PHVzZSB4bGluazpocmVmPSIjSyIgeD0iLTQ2MTEuNzkiIHk9Ii0xNTgyIi8+PHVzZSB4bGluazpocmVmPSIjTCIgeD0iLTQ2NTAuODEiIHk9Ii0xNTc5LjIxIi8+PC9nPjwvZz48ZGVmcz48cGF0aCBpZD0iQSIgZD0iTTMuMjQgMy4zNTdhMi45NSAyLjk1IDAgMCAxLTEuMzU2LjMwMkMuNzkgMy42NiAwIDIuOTA0IDAgMS44NDhTLjgzIDAgMS45MiAwYy40MTQgMCAuNzE2LjA3NSAxLjI4LjMwMlYuODNDMi43ODcuNjA0IDIuMzM1LjUgMS45Mi41IDEuMTMuNS41MjcgMS4wOTQuNTI3IDEuODQ4YzAgLjc5Mi42MDMgMS4zNTggMS40MyAxLjM1OC4yNjQgMCAuNTI3LS4wNzUuNzktLjE1VjIuMzRoLS42Nzh2LS40NTNIMy4yNHYxLjQ3eiIvPjxwYXRoIGlkPSJCIiBkPSJNMCAwaC45MDRjLjcxNiAwIDEuMTY4LjM3NyAxLjE2OCAxLjAxOCAwIC40MTUtLjIyNi43MTctLjYwMy44NjguMzQuMjI2LjUyNy41MjguNzkuOThhNC45IDQuOSAwIDAgMCAuNDkuNzE3aC0uNjAzbC0uNDUyLS43MTdjLS41LS43MTctLjY3OC0uODMtLjk0Mi0uODNILjUyN3YxLjU0NkgwVjB6bS41MjcgMS41NDdoLjM0Yy41NjUgMCAuNzE2LS4zMDIuNzE2LS41NjYgMC0uMzQtLjE4OC0uNTI4LS43MTYtLjUyOGgtLjM0djEuMDk0eiIvPjxwYXRoIGlkPSJDIiBkPSJNMS44ODMgMGMxLjE2OCAwIDEuOTIuODMgMS45MiAxLjgxIDAgLjk0My0uNzE2IDEuODEtMS45MiAxLjgxQy43MTYgMy42MiAwIDIuNzU0IDAgMS44MSAwIC44My43NTMgMCAxLjg4MyAwem0wIDMuMTY4Yy43OSAwIDEuMzk0LS41NjYgMS4zOTQtMS4zNThTMi42NzQuNDUzIDEuODgzLjQ1My41IDEuMDE4LjUgMS44MXMuNjAzIDEuMzU4IDEuMzk0IDEuMzU4eiIvPjxwYXRoIGlkPSJEIiBkPSJNLjUuMDM4VjIuMTVjMCAuNzE3LjM3NyAxLjAxOC45OCAxLjAxOC43OSAwIDEuMDU1LS4zNzcgMS4wNTUtMS4wNTZWLjAzOGguNTI3djIuMTVjMCAuODY4LS41MjcgMS40MzMtMS41ODIgMS40MzNDLjcxNiAzLjYyIDAgMy4yMDYgMCAyLjIyNVYwaC41di4wMzh6Ii8+PHBhdGggaWQ9IkUiIGQ9Ik0wIDBoMS4xM2MuNzkgMCAxLjEzLjQ1MyAxLjEzIDEuMDE4UzEuODgzIDIgMS4wNTUgMkguNTI3djEuNTQ3SDBWMHptLjUyNyAxLjU4NGguNTY1Yy4zNzcgMCAuNjQtLjE5LjY0LS41NjZDMS43MzMuNzU0IDEuNTgyLjUgMS4xMy41SC41Mjd2MS4wOTR6Ii8+PHBhdGggaWQ9IkYiIGQ9Ik01LjA0OCAxLjA1NkgxLjIwNXYyLjM0SDQuMThWNC40NUgxLjIwNXYzLjg4NUgwVjBoNS4wNDh2MS4wNTZ6Ii8+PHBhdGggaWQ9IkciIGQ9Ik00LjQ4MyAwYzIuNzUgMCA0LjQ4MyAxLjk2IDQuNDgzIDQuMjYyIDAgMi4yNjMtMS42OTUgNC4yNjItNC41MiA0LjI2Mi0yLjcxMiAwLTQuNDQ1LTItNC40NDUtNC4yNjJDLjAzOCAxLjk2IDEuODA4IDAgNC40ODMgMHptMCA3LjQ2OGMxLjg4MyAwIDMuMjQtMS4zNTggMy4yNC0zLjE2OCAwLTEuODQ4LTEuMzU2LTMuMTY4LTMuMjQtMy4xNjgtMS44NDYgMC0zLjI0IDEuMzU4LTMuMjQgMy4xNjhzMS4zOTQgMy4xNjggMy4yNCAzLjE2OHoiLz48cGF0aCBpZD0iSCIgZD0iTTcuNDk2IDBsLTUuMzEgNy4yOGg1LjMxdjEuMDU2SDBsNS4yNzQtNy4yOEguM1YwaDcuMTk1eiIvPjxwYXRoIGlkPSJJIiBkPSJNNy41MzQgMGwtNS4zMSA3LjI4aDUuMzF2MS4wNTZIMGw1LjMxLTcuMjhILjM0VjBoNy4xOTV6Ii8+PHBhdGggaWQ9IkoiIGQ9Ik0xLjQzIDBsMi4yNiAyLjg2N0w1Ljk1MiAwaDEuNDNsLTMuMDkgMy45MjN2NC40MTNIMy4wOVYzLjkyM0wwIDBoMS40M3oiLz48cGF0aCBpZD0iSyIgZD0iTTIuNzg3IDEuMzk3YTEuMzkgMS4zOSAwIDAgMS0xLjM5NCAxLjM5NkExLjQyIDEuNDIgMCAwIDEgMCAxLjM5NyAxLjM5IDEuMzkgMCAwIDEgMS4zOTQuMDAyYy43NTMtLjAzOCAxLjM5NC42MDQgMS4zOTQgMS4zOTZ6bS0yLjUyNCAwYzAgLjYwNC41IDEuMTMyIDEuMTMgMS4xMzIuNjAzIDAgMS4xMy0uNSAxLjEzLTEuMTMyIDAtLjYwNC0uNS0xLjEzMi0xLjEzLTEuMTMyYTEuMTEgMS4xMSAwIDAgMC0xLjEzIDEuMTMyem0uNS0uNzU0aC43MTZjLjMgMCAuNDUyLjE5LjQ1Mi40MTVzLS4xNS4zNzctLjQxNC40MTVjLjExMy4wMzguMTUuMDc1LjMuMzRsLjE4OC4zMDJIMS42MmwtLjExMy0uMjI2QzEuMzk0IDEuNjYgMS4yOCAxLjUgMS4xMyAxLjVoLS4xMTN2LjYwNGgtLjNWLjY0M2guMDM4em0uMy42NGguM2MuMTg4IDAgLjI2NC0uMTEzLjI2NC0uMTkgMC0uMTUtLjExMy0uMTktLjMtLjE5aC0uMjY0di4zNzd6Ii8+PHBhdGggaWQ9IkwiIGQ9Ik0yMS41ODQgMHY5LjIwNGMtLjc1My4wNzUtMS41NDQuMTEzLTIuMzM1LjExM2EyNC4yIDI0LjIgMCAwIDEtMi4zMzUtLjExM1YwSDB2MS42OTdjMy43MyA0LjY3NyA5LjA3OCA3LjkyIDE1LjIxOCA4Ljk0djE0LjI5NmgxLjY1N3YtMTQuMDdhMjMuNjQgMjMuNjQgMCAwIDAgMi4zMzUuMTEzIDI0LjIxIDI0LjIxIDAgMCAwIDIuMzM1LS4xMTN2Ny4zOTNoLTIuNDF2MS42OTdoNC4xMDZ2LTkuMzE3YzYuMTQtMS4wMTggMTEuNDktNC4zIDE1LjIxOC04Ljk0VjBIMjEuNTg0ek0yLjE0NyAxLjYyMmgxMy4xMXY3LjM1NWMtNS4xNi0uOTQzLTkuNzU2LTMuNTgzLTEzLjExLTcuMzU1ek0yMy4yOCA4Ljk3N1YxLjYyMmgxMy4xMUMzMy4wMzUgNS4zOTQgMjguNDQgOC4wMzQgMjMuMjggOC45Nzd6Ii8+PC9kZWZzPjwvc3ZnPg==');
        this.img.setAttribute('class', 'busy-ui-opacity-pulse-slow');
        this.img.setAttribute('style', 'position:relative;top:calc(50% - 39px);display:block;margin:0 auto;');
        this.img.setAttribute('width', '70px');
        this.img.setAttribute('height', '78px');

        this.shadow.appendChild(style);
        this.shadow.appendChild(this.wrapper);
        this.wrapper.appendChild(this.overlay);
        this.wrapper.appendChild(this.box);
        this.box.appendChild(this.img);
    }
    static init(opts = {}) {
        this.constructor._TAG = opts['tagName'] || 'wc-busy-ui';
    }
    static get tag() { return this.constructor._TAG || 'wc-busy-ui'; }

    static get obj() {
        if(!this.constructor._OBJ) {
            this.constructor._OBJ = document.querySelector(this.tag);
            if(!this.constructor._OBJ) {
                this.constructor._OBJ = document.querySelector('body').appendChild(document.createElement(this.tag));
            }
            customElements.define(this.tag, WcBusyUI);
        }
        return this.constructor._OBJ;
    }
    static get links() { return this.constructor._LINKS || 0; }

    show(){
        this.style.display = 'block';
        this.constructor._LINKS = (this.constructor._LINKS || 0)+1;
        this.setAttribute('links',this.constructor._LINKS);
    }
    hide(){
        if(this.constructor._LINKS) { this.constructor._LINKS--; }
        if(!this.constructor._LINKS) { this.style.display = 'none';}
        this.setAttribute('links',this.constructor._LINKS);
    }
}
