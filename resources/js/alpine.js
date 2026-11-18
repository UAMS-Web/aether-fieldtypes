// Public Alpine entry. A host application imports this module before it calls `Alpine.start()`,
// so every engine registers while Alpine is still booting:
//
//     import { registerAlpineEngine } from '../../vendor/uams-web/aether-fieldtypes/resources/js/alpine.js'
//
// The fieldtype families add their engines here as they move into the add-on.
export { registerAlpineEngine } from './lib/alpine-engine.js'
