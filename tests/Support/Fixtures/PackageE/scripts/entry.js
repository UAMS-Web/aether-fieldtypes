import './components/registers.js'
import nested from './nested'
export * from './reexport.mjs'
import './components/Thing.vue'
import './cycle-a.js'
import './plain'
import './modern'
import './style.css'
import vue from 'vue'

Statamic.booting(() => {
    Statamic.$components.register('entry-own', {})
    Statamic.component(`tick-name`, {})
})

const lazy = () => import('./lazy.js')

// Statamic.$components.register('commented-out', {})
/* Statamic.component('block-commented', {}) */
