/**
 * Assistant widget: browser-native speech recognition (input) and speech
 * synthesis (output). No audio ever leaves the device for transcription, and
 * there is no dependency to load.
 */

const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition

/** Last assistant message rendered in the transcript. */
function lastAssistantText() {
    const bubbles = document.querySelectorAll('#assistant-panel [data-assistant-message]')
    return bubbles.length ? bubbles[bubbles.length - 1].dataset.assistantMessage : ''
}

export function assistantWidget(config) {
    return {
        open: Boolean(config.open),
        listening: false,
        supported: assistantWidget.supportsRecognition(),
        voiceReply: false,
        speech: '',
        interim: '',
        recognition: null,
        synth: window.speechSynthesis || null,

        init() {
            this.$watch('open', (isOpen) => {
                if (isOpen) {
                    this.$nextTick(() => this.scrollToBottom())
                } else {
                    this.stopListening()
                    this.stopSpeaking()
                }
            })
        },

        scrollToBottom() {
            const scroller = this.$refs.scroller
            if (scroller) scroller.scrollTop = scroller.scrollHeight
        },

        autoGrow(element) {
            element.style.height = 'auto'
            element.style.height = `${Math.min(element.scrollHeight, 128)}px`
        },

        toggleListening() {
            this.listening ? this.stopListening() : this.startListening()
        },

        startListening() {
            if (!this.supported || this.listening) return

            const recognition = new Recognition()
            recognition.lang = 'fr-FR'
            recognition.interimResults = true
            recognition.continuous = false
            recognition.maxAlternatives = 1

            recognition.onstart = () => {
                this.listening = true
                this.interim = ''
            }

            recognition.onresult = (event) => {
                let final = ''
                let interim = ''

                for (let i = event.resultIndex; i < event.results.length; i++) {
                    const transcript = event.results[i][0].transcript
                    if (event.results[i].isFinal) {
                        final += transcript
                    } else {
                        interim += transcript
                    }
                }

                if (interim) this.interim = interim
                if (final) {
                    this.speech = (this.speech ? `${this.speech} ` : '') + final.trim()
                    this.$refs.form.querySelector('textarea').value = this.speech
                    this.$refs.form.querySelector('textarea').dispatchEvent(new Event('input', { bubbles: true }))
                }
            }

            recognition.onerror = (event) => {
                this.listening = false
                this.interim = ''
                if (event.error === 'not-allowed') {
                    window.dispatchEvent(new CustomEvent('assistant-error', {
                        detail: "Acces au micro refuse par le navigateur.",
                    }))
                }
            }

            recognition.onend = () => {
                this.listening = false
                this.interim = ''

                // Auto-send once the visitor stops talking: the whole point of
                // voice is not having to touch the keyboard.
                if (this.voiceReply && this.speech.trim() !== '') {
                    this.$refs.form.requestSubmit()
                }
            }

            this.recognition = recognition

            try {
                recognition.start()
            } catch {
                this.listening = false
            }
        },

        stopListening() {
            if (this.recognition) {
                try {
                    this.recognition.stop()
                } catch {
                    /* already stopped */
                }
            }
            this.listening = false
            this.interim = ''
        },

        /** Reads the last answer aloud, and keeps reading new ones. */
        speakLastReply() {
            if (!this.synth) return

            if (this.synth.speaking) {
                this.stopSpeaking()
                this.voiceReply = false
                return
            }

            const text = lastAssistantText()
            if (!text) return

            this.voiceReply = true
            this.speak(text)
        },

        speak(text) {
            if (!this.synth || !this.voiceReply || !text) return

            const clean = text.replace(/[•*_`#]/g, ' ').replace(/\s+/g, ' ').trim()
            const utterance = new SpeechSynthesisUtterance(clean)
            utterance.lang = 'fr-FR'
            utterance.rate = 1
            this.synth.speak(utterance)
        },

        stopSpeaking() {
            if (this.synth) this.synth.cancel()
        },
    }
}

assistantWidget.supportsRecognition = () => Boolean(Recognition) && Boolean(window.SpeechSynthesisUtterance)