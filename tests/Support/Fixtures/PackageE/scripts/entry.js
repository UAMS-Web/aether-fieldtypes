import './components/registers.js'
import nested from './nested'
export * from './reexport.mjs'
import './components/Thing.vue'
import './cycle-a.js'
import './plain'
import './modern'
import './typed.ts'
import './legacy.cjs'
import './widget.jsx'
import './panel.tsx'
import './tsonly'
import './date.utils'
import './style.css'
import logo from './logo.svg'
import data from './data.json'
import vue from 'vue'

Statamic.booting(() => {
    Statamic.$components.register('entry-own', {})
    Statamic.component(`tick-name`, {})
})

const lazy = () => import('./lazy.js')

// Statamic.$components.register('commented-out', {})
/* Statamic.component('block-commented', {}) */

const spaced = () => import( './spaced.js')
const multi = () => import(
    /* @vite-ignore */
    './multi.js'
)
const withOptions = () => import('./options.js', { with: { type: 'json' } })

const note = 'see /* here'
Statamic.component('after-string-marker', {}) // a trailing comment
/* a real comment */
