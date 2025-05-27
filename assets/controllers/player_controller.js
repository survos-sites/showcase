import { Controller } from '@hotwired/stimulus';
import * as AsciinemaPlayer from 'asciinema-player';
/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://symfony.com/bundles/StimulusBundle/current/index.html#lazy-stimulus-controllers
*/

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        code: String,
        url: String,
    }
    static targets = ['player', 'marker'];

    initialize() {
        // Called once when the controller is first instantiated (per element)

        // Here you can initialize variables, create scoped callables for event
        // listeners, instantiate external libraries, etc.
        // this._fooBar = this.fooBar.bind(this)
    }

    connect() {
        console.log(this.codeValue + " from " + this.identifier);
        var msg = new SpeechSynthesisUtterance();
        this.player = null;

        // let player = AsciinemaPlayer.create(this.urlValue, this.playerTarget, {
        //     autoPlay: true,
        //     controls: true
        // });
        // return;


        // Called every time the controller is connected to the DOM
        // (on page load, when it's added to the DOM, moved in the DOM, etc.)

        // Here you can add event listeners on the element or target elements,
        // add or remove classes, attributes, dispatch custom events, etc.
        // this.fooTarget.addEventListener('click', this._fooBar)

        // AsciinemaPlayer.create('https://asciinema.org/a/WIFw6ZhT0yFCNgUaVY876Ios8.cast',
        //     document.getElementById('demo'));

        console.log(this.urlValue);
        this.player = AsciinemaPlayer.create(this.urlValue, this.playerTarget, {
            // autoPlay: true,
            controls: true,
            idleTimeLimit: 0.5,
            preload: true,
            // markers: data.markers
        });


        if (0)
        fetch('/cine/' + this.codeValue )
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log(data);
                const castData = [data.header];

                data.lines.forEach((l) => {
                    //multicode analyse
                    // console.log(l);
                    // l[2] = JSON.parse(l[2]);
                    castData.push(l); // [l.interval, l.type, l.text]);
                });

                // console.log(castData);
                // if (0)
                {
                    console.log(data.markers);
                    this.player = AsciinemaPlayer.create({ data: castData }, this.playerTarget, {
                        // autoPlay: true,
                        controls: true,
                        markers: data.markers
                    });
                    this.player.addEventListener('marker', _marker => {
                        // console.log(_marker);
                        this.markerTarget.innerHTML = _marker.label;
                        msg.text = _marker.label;
                        // window.speechSynthesis.speak(msg);
                        // @todo: set this as a control switch
                        this.player.pause();
                        this.player.getCurrentTime().then(( (d) => console.log(d)));
                    });
                    this.player.addEventListener('pause', () => {
                        console.log("paused!");
                    });
                    this.player.addEventListener('ended', () => {
                        console.log("ended!");
                    })

                }
                // Process your JSON data here
            })
            .catch(error => {
                console.error('Fetch error:', error);
            });

        // let player = AsciinemaPlayer.create('/demo.cast', this.playerTarget);
        let data = [
            {version: 2, width: 80, height: 24},
            [1.0, "o", "hello "],
            [2.0, "o", "world!"]
        ];

    }

    seek(e) {
        this.player.pause();
        console.log(e.params);
        this.player.seek(e.params.timestamp).then(() => {
            this.player.getCurrentTime().then ( d => console.log(d));
        });
    }

    // Add custom controller actions here
    // fooBar() { this.fooTarget.classList.toggle(this.bazClass) }

    disconnect() {
        // Called anytime its element is disconnected from the DOM
        // (on page change, when it's removed from or moved in the DOM, etc.)

        // Here you should remove all event listeners added in "connect()"
        // this.fooTarget.removeEventListener('click', this._fooBar)
    }
}
