// Control Panel entry point. Components, fieldtypes and Inertia pages register inside the
// booting callback so they run once Statamic has initialized.
import SubsectionFieldtype from './components/fieldtypes/SubsectionFieldtype.vue'

Statamic.booting(() => {
    Statamic.$components.register('subsection-fieldtype', SubsectionFieldtype)
})
