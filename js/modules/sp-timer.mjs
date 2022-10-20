import {libSecondsToTimeProgressive} from '../helpers/libStrUtils.mjs';

export class SpTimer {
    constructor(options={}) {
        this._id = null;
        this._step = +options.step || 1; //интервал в секундах, отрицательное значение - обратный отсчет)
        this._min  = +options.min || 0;  //нижняя граница диапазона
        this._max  = +options.max || 0;  //верхняя граица диапазона

        this._onLimit = options.onLimit || null; //если создаем класс-наследник, то определяем в нем а не передаем сюда

        this._tick = Math.abs(this._step) * 1000; //интервал в милисекундах

        this._isLimitReached = false;
        this._isActive = false;
        this._isWellFormed = true;

        this.reset();

        if(options.canvasId) {
            this._canvas = document.getElementById(options.canvasId);
        }
    }

    reset() {
        this.stop();
        this._isLimitReached = false;
        this._current = this._step < 0 ? this._max : this._min;
        this._publish();
    }

    start(reset = false){
        if(!this._isLimitReached && this._isWellFormed) {
            if (reset) {
                this.reset();
            }
            this._isActive = true;
            this._publish();

            this._id = setTimeout(this._doStep,this._tick);
        }
    }

    stop(){
        if(this._id) {
            clearTimeout(this._id);
            this._id = null;
        }
        this._isActive = false;
    }

    restart(){
        this.reset();
        this.start();
    }

    /**
     * Только для рестарта по событиям активности пользователя
     */
    userEventProcess(){
        if(this._isActive) {
            this.restart();
        }
    }

    //** PROTECTED *************************************************
    //**************************************************************

    _doStep = ()=>{
        if(this._canContinueSteps()) {
            this._current += this._step;
            if(this._canContinueSteps()) {
                this._id = setTimeout(this._doStep,this._tick);
            }
            this._publish();
        }
    }

    _canContinueSteps(){
        if(this._step === 0) {
            this.stop();
            return false;
        }
        if(this._step < 0 && this._current <= this._min || this._step > 0 && this._current >= this._max) {
            this.stop();
            this._current = this._step < 0 ? this._min : this._max;
            this._isLimitReached = true;
            if(this._onLimit) {
                this._onLimit();
            }
            return false;
        }
        return true;
    }

    _publish() {
        if(this._canvas) {
            this._canvas.innerHTML = libSecondsToTimeProgressive(this._current);
        }
    }
}