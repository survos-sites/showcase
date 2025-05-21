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

        fetch('/cine/' + this.codeValue )
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                const castData = [data.header];

                data.lines.forEach((l) => {
                    //multicode analyse
                    l[2] = JSON.parse(l[2]);
                    castData.push(l)
                });

                console.log(castData);
                let player = AsciinemaPlayer.create({ data: castData }, this.playerTarget, {
                    autoPlay: true,
                    controls: true
                });
                player.addEventListener('marker', _marker => {
                    console.log(_marker);
                    this.markerTarget.innerHTML = _marker.label;
                    msg.text = "Press any key to continue";
                    window.speechSynthesis.speak(msg);

                    player.pause();
                })

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

    // Add custom controller actions here
    // fooBar() { this.fooTarget.classList.toggle(this.bazClass) }

    disconnect() {
        // Called anytime its element is disconnected from the DOM
        // (on page change, when it's removed from or moved in the DOM, etc.)

        // Here you should remove all event listeners added in "connect()"
        // this.fooTarget.removeEventListener('click', this._fooBar)
    }
}
