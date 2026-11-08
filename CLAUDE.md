# Amural

Amural (amural.eu) is a platform where European teachers share, improve and remix lessons in every European language. Credit and income flow back to everyone whose work survives in a lesson. Version control for lessons, like Git but for teachers, is the core of the product.

Full brief: `PROMPT.md`. Visual direction: `DESIGN.md`.

## Stack

- Laravel 12 (Livewire starter kit), PHP 8.3+, PostgreSQL (JSONB where noted).
- Livewire + Alpine for pages. Tiptap is the single lesson-editor island (no real-time collaboration).
- Filament for admin. Pest for tests. Pint and Larastan (level 6+).
- Queues and Scout on the database driver.
- No extra packages without asking first.

## Version-control principles

1. History is append-only: `revisions` and `block_versions` are never updated or deleted. Undo is a new revision.
2. Blocks have stable UUIDs across edits, moves, forks and translations.
3. Nothing merges into someone else's lesson without a person accepting it.
4. Credit follows surviving text, not edit counts.

## Conventions

- UUID primary keys for domain models.
- Business logic lives in `app/Actions` and `app/Domain`, not in controllers, Livewire components or models.
- All UI strings go through `__()`, with keys in `lang/en`, `lang/es` and `lang/ro`.
- No AI, translation, payment or ID-verification integrations yet: interfaces only, with fake implementations.
- Brand: Paper `#F6F1E7` background, Marker `#141414` text. Display font is Philibert Italic Rush (`public/fonts`), text is Inter.
