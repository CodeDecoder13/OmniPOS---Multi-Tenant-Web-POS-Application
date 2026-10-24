// Fails when a translation key exists in one language file but not the others.
import { readFileSync } from 'node:fs';

const langs = ['en', 'fil', 'ja'];
const flat = (obj, prefix = '') =>
    Object.entries(obj).flatMap(([k, v]) => (v && typeof v === 'object' ? flat(v, `${prefix}${k}.`) : [`${prefix}${k}`]));

const keys = Object.fromEntries(
    langs.map((l) => [l, new Set(flat(JSON.parse(readFileSync(new URL(`../resources/js/lang/${l}.json`, import.meta.url), 'utf8'))))]),
);

let bad = 0;
for (const l of langs) {
    for (const other of langs.filter((o) => o !== l)) {
        const missing = [...keys[l]].filter((k) => !keys[other].has(k));
        if (missing.length) {
            bad += missing.length;
            console.error(`${other}.json is missing ${missing.length} key(s) present in ${l}.json:\n  ${missing.join('\n  ')}`);
        }
    }
}
if (bad) process.exit(1);
console.log(`Language files in sync (${keys.en.size} keys each).`);
