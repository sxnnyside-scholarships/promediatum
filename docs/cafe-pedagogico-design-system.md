# Café Pedagógico Design System v1
Product: Promediatum
Revision: February 2026
Classification: Foundational Design System

---

## 1. System Definition

Café Pedagógico is a work-surface design system built for pedagogical software.

It is designed for focused environments where educators manage real academic responsibility.  
The system favors structure, tonal warmth, density control, and calm authority.

It is not decorative.
It is not ornamental.
It is not spectacle-driven.

It is structural.

Café Pedagógico defines how space behaves, how hierarchy is enforced, and how work is prioritized.

---

## 2. Core Philosophy

### 2.1 Work Surface Model

Interfaces must feel like continuous surfaces of work.

Pages are not composed of floating cards.
They are composed of structural sections on a shared surface.

Surfaces define containment.
Sections define rhythm.
Typography defines hierarchy.

### 2.2 Structured Calm

Calm is achieved through:

- Alignment consistency
- Predictable spacing rhythm
- Limited tonal layers
- Stable visual density

Calm is not achieved through excessive whitespace.

### 2.3 Human-Centered Authority

Promediatum serves educators.

The interface must feel:

- Serious
- Warm
- Clear
- Intentional

Never playful.
Never gamified.
Never metric-obsessed.

### 2.4 Density Discipline

Density is controlled through defined modes.

There are only two content densities allowed:

READ MODE
Used for:
- Detail views
- Forms
- Narrative content
- Observational screens

Rules:
- Font size: 14–15px body
- Line height: 1.6
- Vertical padding: 16–24px
- Section spacing: 32px

SCAN MODE
Used for:
- Tables
- Lists
- Attendance grids
- Compact overviews

Rules:
- Font size: 13px body
- Line height: 1.3
- Vertical padding: 8–10px
- Minimal row separation

A page may not mix more than two density levels.

---

## 3. Spatial Architecture

### 3.1 Structural Layout

The application is divided into:

- Navigation Column
- Work Surface

Navigation Column:
- Expanded width: 240px
- Collapsed width: 64px
- Fixed position
- No transparency overlays
- No opacity layering tricks

Work Surface:
- Continuous background layer
- No floating central container
- No nested max-width containers
- Anchored alignment

### 3.2 Content Width Rules

Read Views:
- Max width: 720px

Scan Views:
- Max width: 960px

Content is left-anchored within the Work Surface.
It is not timidly centered inside the viewport.

Maximum empty margin per side must not exceed 15% of viewport width.

---

## 4. Surface System

Café Pedagógico uses tonal layering instead of elevation.

Layer hierarchy:

1. Base Layer (Application Background)
2. Work Surface
3. Section Surface (optional separation)
4. Accent Layer (actions only)
5. State Layer (warning, danger, success)

Rules:

- No heavy shadows.
- Minimal radius (subtle rounding only).
- No decorative borders.
- Section separation via thin horizontal rules or tonal shift.
- Maximum nesting depth: 2 structural levels.

---

## 5. Page Composition Model

Every page follows this structure:

1. Page Header Strip
2. Primary Section
3. Secondary Sections
4. Conditional Alerts (if necessary)

Page Header Strip:
- Contains title and contextual actions.
- Appears once.
- Page must not duplicate its own title below.

Primary Section:
- Dominant content.
- Clear top separation.

Secondary Sections:
- Separated by 32px vertical rhythm.
- Clearly structured, not card-stacked.

---

## 6. Sidebar System

Expanded:
- 240px width
- Icon + label
- Active item has distinct tonal background
- Text opacity 100%

Collapsed:
- 64px width
- Icon only
- Tooltip on hover
- Content resizes in real time

Contrast Requirements:
- Minimum 4.5:1 ratio
- Inactive items: 70% opacity
- Hover and active: 100%

No muddy tone-on-tone layering.
No blending text with background.

---

## 7. Typography System

Hierarchy is defined by:

- Size
- Weight
- Spacing
- Position

Never by colored blocks.

Rules:

- One page title per screen
- No duplicate headers
- No subtitle repeating header
- Section titles smaller than page title
- Body text stable and legible

Typography must feel editorial, not corporate.

---

## 8. Tables (Work Surface Tables)

Tables must:

- Use Scan Mode density
- Avoid vertical separators
- Avoid decorative borders
- Use subtle row hover tint
- No hidden hover controls
- No floating inline action clusters

Numeric columns:
- Right-aligned
- Subtle tonal differentiation
- No colored backgrounds

---

## 9. Action Model

Primary actions:
- Solid accent background
- Left-aligned within section
- Never centered

Secondary actions:
- Tonal button
- Lower contrast

Loading behavior:
- Disable button
- No spinners

Reversible actions:
- Inline undo pattern

Animation:
- 150ms fade only
- No scaling transitions
- No dramatic motion

---

## 10. Structural Integrity Rules

The following are not allowed:

- Floating narrow cards in wide empty space
- Nested max-width inside max-width
- Multi-column KPI dashboards
- Percentage stat grids
- Decorative icons
- Emoji
- Glass or transparency overlays
- Heavy shadows
- Over-nesting containers

If a screen resembles a generic admin dashboard,
it violates Café Pedagógico.

---

## 11. Dark Mode Standard

Dark mode must:

- Increase contrast
- Maintain tonal separation
- Avoid mid-tone-on-mid-tone layering

Sidebar:
- Text at 100% opacity
- Inactive at 70%
- Clear active background shift

If text appears faded, contrast is incorrect.

---

## 12. Recognition Criteria

A correct Café Pedagógico interface must feel:

- Dense but breathable
- Warm but serious
- Structured but calm
- Authoritative but human
- Focused on work, not metrics

It must not resemble:

- Finance dashboards
- Generic admin templates
- Analytics software
- CRM panels

---

## 13. Implementation Mandate

Applying this system requires:

- Layout restructuring
- Container flattening
- Removal of nested scroll
- Enforced width discipline
- Unified header model

This is architectural implementation.
Not cosmetic adjustment.

---

## 14. Closing Definition

Café Pedagógico v1 is defined by:

- Discipline
- Density
- Structure
- Tonal layering
- Calm authority

It is not defined by color alone.
It is defined by how space behaves.

End of Café Pedagógico Design System v1
