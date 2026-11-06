# Motion foundation (`@shared/motion`)

Subtle, professional animation helpers built on the [`motion`](https://motion.dev)
library (v12, imported from `motion/react`). Use these instead of ad-hoc
`motion.*` + inline variants so timing, easing and accessibility stay consistent.

## Accessibility (read this first)

Every wrapper honours the OS **"reduce motion"** setting. `app.tsx` wraps the app
in `<MotionConfig reducedMotion="user">`, and each component below also guards with
`useReducedMotion()` so that, when the preference is set, elements render
**statically** — no fades, no movement. Do not bypass these wrappers with raw
`motion.*` unless you add the same guard.

## Components

| Component | Use for | Notes |
|-----------|---------|-------|
| `Reveal` | A section/block that fades + rises the first time it scrolls into view | Wrap the block; optional `delay` (seconds). |
| `Stagger` + `StaggerItem` | A grid/list whose children appear in sequence | `Stagger` **is** the grid element — pass layout classes via `className`. Each direct child is a `StaggerItem`. Defaults to reveal-on-scroll; pass `inView={false}` to play on mount (e.g. above-the-fold hero). |
| `PageTransition` | A page-level enter transition | Give it a `key` tied to the Inertia page (`usePage().component`) so it replays on navigation. Mount-only (no exit) by design — Inertia v2 swaps the page in a single root. |

```tsx
import { Reveal, Stagger, StaggerItem } from '@shared/motion';

<Reveal>
  <SomeSection />
</Reveal>

<Stagger className="grid grid-cols-1 gap-6 sm:grid-cols-3">
  {items.map((item) => (
    <StaggerItem key={item.id}>
      <Card {...item} />
    </StaggerItem>
  ))}
</Stagger>
```

## Variants (for native elements)

When wrapping a `<ul>/<li>` (or another element where an extra `<div>` would break
semantics), apply the shared variants directly to `motion.*` and guard with
`useReducedMotion()` yourself:

```tsx
import { motion, useReducedMotion } from 'motion/react';
import { staggerContainer, staggerItem } from '@shared/motion';
```

## Tokens

`durations` (`fast` 0.2s / `base` 0.3s / `slow` 0.4s), `easeInstitutional`
(gentle ease-out), `springGentle` (hover/tap spring), `slideDistance` (12px).
Change motion feel app-wide by editing `tokens.ts`.
