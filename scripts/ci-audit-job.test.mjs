import { readFileSync } from 'node:fs'
import { join } from 'node:path'
import { describe, expect, it } from 'vitest'
import { parse } from 'yaml'

// The `audit` job in ci.yml runs `composer audit` on every pull request and weekly (#25, #69). The
// pieces that would silently stop it are the schedule leaving `on:`, the command losing
// `--locked` (so it audits an install nobody made) or `--abandoned=ignore` (so an abandoned
// package fails it), and the job leaving the `CI` aggregate's `needs:`.
const WORKFLOW = parse(readFileSync(join(import.meta.dirname, '..', '.github', 'workflows', 'ci.yml'), 'utf8'))

describe('the composer audit job', () => {
    it('runs on a pull request and on a weekly schedule', () => {
        expect(Object.keys(WORKFLOW.on)).toEqual(expect.arrayContaining(['pull_request', 'schedule']))
        expect(WORKFLOW.on.schedule).toHaveLength(1)
        // Weekly, Mondays: minute, hour, two wildcards, then day of week 1
        expect(WORKFLOW.on.schedule[0].cron).toMatch(/^\d+ \d+ \* \* 1$/)
    })

    it('audits the lock file and leaves abandoned packages out', () => {
        const commands = WORKFLOW.jobs.audit.steps.map((step) => step.run).filter(Boolean)

        expect(commands).toEqual(['composer audit --locked --abandoned=ignore'])
    })

    it('keeps the name the required-checks list is built from', () => {
        expect(WORKFLOW.jobs.audit.name).toBe('Composer audit (locked packages)')
    })

    it('is one of the jobs the CI aggregate waits for', () => {
        expect(WORKFLOW.jobs.ci.needs).toContain('audit')
    })
})
