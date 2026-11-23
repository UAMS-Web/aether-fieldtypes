math.import({ add: () => 1 })
registry?.import(path)
Statamic.booting(() => {
    Statamic.$components.register('after-member-import', {})
})
loader.import('./does-not-exist.js')
